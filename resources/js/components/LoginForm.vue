<template>
  <form @submit.prevent="handleSubmit" class="login-form">
    <div class="form-group">
      <input 
        type="email" 
        v-model="email" 
        placeholder="Email"
        required
      >
    </div>
    
    <div class="form-group">
      <input 
        type="password" 
        v-model="password" 
        placeholder="Password"
        required
      >
    </div>

    <div class="forgot-password">
      <a href="#">Forgot your password?</a>
    </div>

    <button type="submit" class="login-btn" :disabled="loading">
      <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
      LOG IN
    </button>

    <div class="signup-link">
      Don't have an account? <a href="#" @click.prevent="$emit('signup')">sign up</a>
    </div>
  </form>
</template>

<script>
import { ref } from 'vue';

export default {
  name: 'LoginFormComponent',
  props: {
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['submit', 'signup'],
  setup(props, { emit }) {
    const email = ref('');
    const password = ref('');

    const handleSubmit = () => {
      emit('submit', { email: email.value, password: password.value });
    };

    return {
      email,
      password,
      handleSubmit
    };
  }
}
</script>

<style scoped>
.login-form {
  width: 100%;
  max-width: 400px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group input {
  width: 100%;
  padding: 12px;
  border: none;
  background-color: #f5f5f5;
  border-radius: 5px;
  font-size: 14px;
}

.forgot-password {
  text-align: right;
  margin-bottom: 20px;
}

.forgot-password a {
  color: #3E7B27;
  text-decoration: none;
  font-size: 14px;
}

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