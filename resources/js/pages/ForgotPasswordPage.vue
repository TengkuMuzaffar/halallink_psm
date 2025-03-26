<template>
  <div class="login-container">
    <div class="login-card">
      <login-left-panel :texts="animatedTexts" />
      
      <div class="right-panel">
        <h2>Reset Password</h2>
        <p>Enter your email to receive a password reset link</p>
        
        <form @submit.prevent="handleForgotPassword" class="w-100">
          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input 
              type="email" 
              class="form-control" 
              id="email" 
              v-model="email"
              placeholder="Enter your email"
              required
            >
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
            :disabled="loading"
          >
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
            SEND RESET LINK
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
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import LoginLeftPanel from '../components/LoginLeftPanel.vue';
import api from '../utils/api'; // Add this import statement

export default {
  name: 'ForgotPasswordPage',
  components: {
    LoginLeftPanel
  },
  setup() {
    const router = useRouter();
    const email = ref('');
    const loading = ref(false);
    const error = ref(null);
    const successMessage = ref(null);

    const animatedTexts = [
      "Recover your account",
      "Secure password reset",
      "Get back to your Halal network",
      "We're here to help"
    ];

    const handleForgotPassword = async () => {
      loading.value = true;
      error.value = null;
      successMessage.value = null;
      
      try {
        await api.post('/api/password/forgot', {
          email: email.value
        });
        
        successMessage.value = "Password reset link has been sent to your email. Please check your inbox.";
        email.value = ''; // Clear the form
      } catch (err) {
        console.error('Password reset error:', err);
        if (err.response?.data?.errors) {
          const errorMessages = Object.values(err.response.data.errors)
            .flat()
            .join('\n');
          error.value = errorMessages;
        } else {
          error.value = err.response?.data?.message || 'Failed to send reset link. Please try again.';
        }
      } finally {
        loading.value = false;
      }
    };

    const goToLogin = () => {
      router.push({ name: 'Login' });
    };

    return {
      email,
      loading,
      error,
      successMessage,
      animatedTexts,
      handleForgotPassword,
      goToLogin
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