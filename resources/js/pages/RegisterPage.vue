<template>
  <div class="login-container">
    <div class="login-card">
      <login-left-panel :texts="animatedTexts" />
      
      <div class="right-panel">
        <h2>Create Account</h2>
        <p>Register to join our community</p>
        
        <register-form
          :loading="loading"
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
import LoginLeftPanel from '../components/LoginLeftPanel.vue';
import RegisterForm from '../components/RegisterForm.vue';
import axios from 'axios';

export default {
  name: 'RegisterPage',
  components: {
    LoginLeftPanel,
    RegisterForm
  },
  setup() {
    const router = useRouter();
    const loading = ref(false);
    const error = ref(null);

    const animatedTexts = [
      "Join our Halal business community",
      "Connect with verified Halal businesses",
      "Grow your Halal business network",
      "Access exclusive Halal resources"
    ];

    const handleRegister = async (formData) => {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await axios.post('/api/register', formData);
        localStorage.setItem('token', response.data.access_token);
        router.push({ name: 'dashboard' });
      } catch (err) {
        console.error('Registration error:', err);
        error.value = err.response?.data?.errors || err.response?.data?.message || 'Registration failed. Please try again.';
      } finally {
        loading.value = false;
      }
    };

    const goToLogin = () => {
      router.push({ name: 'login' });
    };

    return {
      loading,
      error,
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