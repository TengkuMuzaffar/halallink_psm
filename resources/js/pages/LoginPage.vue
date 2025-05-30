<template>
  <div class="login-container">
    <div class="login-card">
      <login-left-panel :texts="animatedTexts" />
      
      <div class="right-panel">
        <h2>Welcome</h2>
        <p>Log-in to your account to continue</p>
        <login-form
          :loading="loading"
          :error="error"
          @submit="handleLogin"
          @signup="goToRegister"
          @forgot-password="goToForgotPassword"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useStore } from 'vuex';
import LoginForm from '../components/LoginForm.vue';
import LoginLeftPanel from '../components/LoginLeftPanel.vue';
import { login } from '../services/authService';

export default {
  name: 'LoginPage',
  components: {
    LoginForm,
    LoginLeftPanel
  },
  setup() {
    const router = useRouter();
    const store = useStore();
    const loading = ref(false);
    const error = ref(null);

    const animatedTexts = [
      "Welcome to HalalLink",
      "Connect with Halal businesses",
      "Grow your Halal network",
      "Access exclusive resources"
    ];

    const handleLogin = async (credentials) => {
      loading.value = true;
      error.value = null;
      
      try {
        // Use the authService instead of direct store dispatch
        const data = await login(credentials);
        
        // Update store with user and token
        store.commit('SET_USER', data.user);
        store.commit('SET_TOKEN', data.access_token);
        
        // Redirect based on company type
        const companyType = data.user.company?.company_type;
        if (companyType) {
          switch (companyType) {
            case 'admin':
              router.push({ name: 'CompanyManagement' });
              break;
            case 'logistic':
              router.push({ name: 'DeliveryManagement' });
              break;
            case 'broiler':
              router.push({ name: 'OrderManagement' });
              break;
            case 'sme':
              router.push({ name: 'Marketplace' });
              break;
            case 'slaughterhouse':
              router.push({ name: 'OrderManagement' });
              break;
            default:
              router.push({ name: 'Dashboard' }); // Fallback
          }
        } else {
          router.push({ name: 'Dashboard' }); // Fallback if no company type
        }
      } catch (err) {
        console.error('Login error:', err);
        if (err.response?.status === 500) {
          error.value = 'Server error. Please try again later or contact support.';
        } else {
          error.value = err.response?.data?.message || 'Login failed. Please check your credentials.';
        }
      } finally {
        loading.value = false;
      }
    };

    const goToRegister = () => {
      router.push({ name: 'Register' });
    };
    
    const goToForgotPassword = () => {
      router.push({ name: 'ForgotPassword' });
    };

    return {
      loading,
      error,
      animatedTexts,
      handleLogin,
      goToRegister,
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
</style>
