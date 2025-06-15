<template>
  <div class="login-container">
    <div class="login-card">
      <login-left-panel :texts="animatedTexts" />
      
      <div class="right-panel">
        <h2>Create Account</h2>
        <p>Register to join our community</p>
        
        <!-- Success message -->
        <div v-if="successMessage" class="alert alert-success w-100 mb-4">
          <i class="fas fa-check-circle me-2"></i> {{ successMessage }}
        </div>
        
        <!-- Error message display -->
        <div v-if="error" class="alert alert-danger w-100 mb-4">
          <div class="text-center">
            <i class="fas fa-exclamation-circle me-2"></i>
          </div>
          <span v-if="typeof error === 'string'">{{ error }}</span>
          <ul v-else-if="typeof error === 'object'" class="mb-0 ps-3">
            <li v-for="(messages, field) in error" :key="field">
              <strong>{{ field }}:</strong> {{ Array.isArray(messages) ? messages.join(', ') : messages }}
            </li>
          </ul>
        </div>
        
        <register-form
          v-if="!successMessage"
          :loading="loading"
          :error="null"
          @submit="handleRegister"
          @login="goToLogin"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useStore } from 'vuex';
import LoginLeftPanel from '../components/LoginLeftPanel.vue';
import RegisterForm from '../components/RegisterForm.vue';
import { register } from '../services/authService';

export default {
  name: 'RegisterPage',
  components: {
    LoginLeftPanel,
    RegisterForm
  },
  setup() {
    const router = useRouter();
    const store = useStore();
    const loading = ref(false);
    const error = ref(null);
    const successMessage = ref(null);

    const animatedTexts = [
      "Join our Halal business community",
      "Connect with verified Halal businesses",
      "Grow your Halal business network",
      "Access exclusive Halal resources"
    ];

    const handleRegister = async (formData) => {
      loading.value = true;
      error.value = null;
      successMessage.value = null;
      
      try {
        // Use the authService instead of direct axios call
        const data = await register(formData);
        
        // Show success message - updated to reflect pending approval
        successMessage.value = data.message || "Registration successful! Your account is pending approval. Redirecting to login page...";
        
        // Redirect to Login page after a short delay instead of Dashboard
        setTimeout(() => {
          router.push({ name: 'Login' });
        }, 3000);
      } catch (err) {
        console.error('Registration error:', err);
        if (err.response?.data?.errors) {
          error.value = err.response.data.errors;
        } else {
          error.value = err.response?.data?.message || 'Registration failed. Please try again.';
        }
      } finally {
        loading.value = false;
      }
    };

    const goToLogin = () => {
      router.push({ name: 'Login' });
    };

    return {
      loading,
      error,
      successMessage,
      animatedTexts,
      handleRegister,
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
  height: auto;
  min-height: 600px;
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

.alert {
  border-radius: 8px;
}

.alert-success {
  background-color: #d4edda;
  border-color: #c3e6cb;
  color: #155724;
}

.alert-danger {
  background-color: #f8d7da;
  border-color: #f5c6cb;
  color: #721c24;
}

@media (max-width: 768px) {
  .login-card {
    flex-direction: column;
    width: 100%;
    height: auto;
  }

  .right-panel {
    width: 100%;
    padding: 30px;
  }
}
</style>