<template>
  <div class="login-container">
    <div class="login-card">
      <login-left-panel :texts="animatedTexts" />
      
      <div class="right-panel">
        <h2>Reset Your Password</h2>
        <p>Create a new password for your account</p>
        
        <div v-if="loading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Verifying your reset link...</p>
        </div>
        
        <div v-else-if="!isValidToken" class="alert alert-danger" role="alert">
          <h4 class="alert-heading">Invalid or Expired Link</h4>
          <p>The password reset link is invalid or has expired.</p>
          <hr>
          <p class="mb-0">
            <a href="#" @click.prevent="goToForgotPassword">Request a new password reset link</a>
          </p>
        </div>
        
        <form v-else @submit.prevent="handleResetPassword" class="w-100">
          <input type="hidden" v-model="token">
          <input type="hidden" v-model="email">
          
          <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input 
              type="password" 
              class="form-control" 
              id="password" 
              v-model="password"
              placeholder="Enter your new password"
              required
              minlength="8"
            >
            <div class="form-text">Password must be at least 8 characters long</div>
          </div>
          
          <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input 
              type="password" 
              class="form-control" 
              id="password_confirmation" 
              v-model="password_confirmation"
              placeholder="Confirm your new password"
              required
            >
            <div class="form-text text-danger" v-if="passwordMismatch">
              Passwords do not match
            </div>
          </div>
          
          <div v-if="error" class="alert alert-danger" role="alert">
            {{ error }}
          </div>
          
          <div v-if="successMessage" class="alert alert-success" role="alert">
            {{ successMessage }}
          </div>
          
          <button 
            type="submit" 
            class="login-btn" 
            :disabled="submitLoading || passwordMismatch || !password || !password_confirmation"
          >
            <span v-if="submitLoading" class="spinner-border spinner-border-sm me-2" role="status"></span>
            RESET PASSWORD
          </button>
          
          <div class="text-center signup-link">
            <p>Remember your password? <a href="#" @click.prevent="goToLogin">Login</a></p>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import LoginLeftPanel from '../components/LoginLeftPanel.vue';
import api from '../utils/api';

export default {
  name: 'ResetPasswordPage',
  components: {
    LoginLeftPanel
  },
  setup() {
    const router = useRouter();
    const route = useRoute();
    
    const token = ref('');
    const email = ref('');
    const password = ref('');
    const password_confirmation = ref('');
    const loading = ref(true);
    const submitLoading = ref(false);
    const error = ref(null);
    const successMessage = ref(null);
    const isValidToken = ref(false);

    const animatedTexts = [
      "Create a new password",
      "Secure your account",
      "Get back to your Halal network",
      "We're here to help"
    ];
    
    const passwordMismatch = computed(() => {
      return password.value && password_confirmation.value && 
             password.value !== password_confirmation.value;
    });
    
    // Validate token on component mount
    onMounted(async () => {
      // Get token and email from URL query parameters
      token.value = route.query.token || '';
      email.value = route.query.email || '';
      
      if (!token.value || !email.value) {
        loading.value = false;
        isValidToken.value = false;
        return;
      }
      
      try {
        const response = await api.post('/api/password/validate-token', {
          token: token.value,
          email: email.value
        });
        
        isValidToken.value = response.valid;
      } catch (err) {
        console.error('Token validation error:', err);
        isValidToken.value = false;
      } finally {
        loading.value = false;
      }
    });

    const handleResetPassword = async () => {
      if (passwordMismatch.value) {
        return;
      }
      
      submitLoading.value = true;
      error.value = null;
      successMessage.value = null;
      
      try {
        await api.post('/api/password/reset', {
          token: token.value,
          email: email.value,
          password: password.value,
          password_confirmation: password_confirmation.value
        });
        
        successMessage.value = "Your password has been reset successfully. You can now login with your new password.";
        password.value = '';
        password_confirmation.value = '';
        
        // Redirect to login after 3 seconds
        setTimeout(() => {
          router.push({ name: 'Login' });
        }, 3000);
      } catch (err) {
        console.error('Password reset error:', err);
        if (err.response?.data?.errors) {
          const errorMessages = Object.values(err.response.data.errors)
            .flat()
            .join('\n');
          error.value = errorMessages;
        } else {
          error.value = err.response?.data?.message || 'Failed to reset password. Please try again.';
        }
      } finally {
        submitLoading.value = false;
      }
    };

    const goToLogin = () => {
      router.push({ name: 'Login' });
    };
    
    const goToForgotPassword = () => {
      router.push({ name: 'ForgotPassword' });
    };

    return {
      token,
      email,
      password,
      password_confirmation,
      loading,
      submitLoading,
      error,
      successMessage,
      isValidToken,
      animatedTexts,
      passwordMismatch,
      handleResetPassword,
      goToLogin,
      goToForgotPassword
    };
  }
};
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f5f5f5;
  padding: 20px;
}

.login-card {
  display: flex;
  width: 900px;
  height: 500px;
  background: #fff;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
}

.right-panel {
  width: 60%;
  padding: 40px 60px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.right-panel h2 {
  color: #123524;
  margin-bottom: 15px;
  font-size: 24px;
}

.right-panel p {
  color: #3E7B27;
  margin-bottom: 30px;
}

@media (max-width: 768px) {
  .login-card {
    flex-direction: column;
    width: 100%;
    height: auto;
    border-radius: 0;
  }

  .right-panel {
    width: 100%;
    padding: 30px;
  }
}

/* Add login button styles to match LoginForm */
.login-btn {
  width: 100%;
  padding: 12px;
  background-color: #123524;
  color: #EFE3C2;
  border: none;
  border-radius: 5px;
  font-weight: bold;
  cursor: pointer;
  transition: background-color 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.login-btn:hover {
  background-color: #3E7B27;
}

.login-btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.signup-link {
  text-align: center;
  margin-top: 20px;
  font-size: 14px;
  color: #666;
}

.signup-link a {
  color: #3E7B27;
  text-decoration: none;
  font-weight: bold;
}
</style>