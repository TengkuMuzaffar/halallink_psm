<template>
  <form @submit.prevent="handleSubmit" class="login-form">
    <!-- Step 1: Company Information -->
    <div v-if="currentStep === 1">
      <h3 class="step-title">Company Information</h3>
      
      <div class="form-group">
        <input 
          type="text" 
          v-model="formData.company_name" 
          placeholder="Company Name"
          required
        >
      </div>

      <div class="form-group">
        <select 
          v-model="formData.company_type" 
          required
          class="form-select"
        >
          <option value="" disabled selected>Select Company Type</option>
          <option value="broiler">Broiler</option>
          <option value="slaughterhouse">Slaughterhouse</option>
          <option value="SME">SME</option>
          <option value="logistic">Logistic</option>
        </select>
      </div>

      <div class="form-group">
        <input 
          type="text" 
          v-model="formData.company_image" 
          placeholder="Company Logo URL (Optional)"
        >
      </div>

      <button type="button" class="login-btn" @click="nextStep">
        NEXT
      </button>
    </div>

    <!-- Step 2: Admin User Information -->
    <div v-if="currentStep === 2">
      <h3 class="step-title">Admin Account</h3>
      
      <div class="form-group">
        <input 
          type="email" 
          v-model="formData.email" 
          placeholder="Email"
          required
        >
      </div>
      
      <div class="form-group">
        <input 
          type="tel" 
          v-model="formData.tel_number" 
          placeholder="Phone Number"
          required
        >
      </div>
      
      <div class="form-group">
        <input 
          type="password" 
          v-model="formData.password" 
          placeholder="Password"
          required
        >
      </div>

      <div class="form-group">
        <input 
          type="password" 
          v-model="formData.password_confirmation" 
          placeholder="Confirm Password"
          required
        >
      </div>

      <div class="button-group">
        <button type="button" class="back-btn" @click="prevStep">
          BACK
        </button>
        <button type="submit" class="login-btn" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
          REGISTER
        </button>
      </div>
    </div>

    <div class="signup-link">
      Already have an account? <a href="#" @click.prevent="$emit('login')">sign in</a>
    </div>
  </form>
</template>

<script>
import { ref } from 'vue';

export default {
  name: 'RegisterForm',
  props: {
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['submit', 'login'],
  setup(props, { emit }) {
    const currentStep = ref(1);
    const formData = ref({
      // Company information
      company_name: '',
      company_type: '',
      company_image: '',
      
      // Admin user information
      email: '',
      password: '',
      password_confirmation: '',
      tel_number: ''
    });

    const nextStep = () => {
      // Basic validation for step 1
      if (!formData.value.company_name || !formData.value.company_type) {
        alert('Please fill in all required fields');
        return;
      }
      currentStep.value = 2;
    };

    const prevStep = () => {
      currentStep.value = 1;
    };

    const handleSubmit = () => {
      emit('submit', formData.value);
    };

    return {
      currentStep,
      formData,
      nextStep,
      prevStep,
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

.step-title {
  text-align: center;
  margin-bottom: 20px;
  color: #123524;
}

.form-group {
  margin-bottom: 20px;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 12px;
  border: none;
  background-color: #f5f5f5;
  border-radius: 5px;
  font-size: 14px;
}

.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 1em;
}

.button-group {
  display: flex;
  gap: 10px;
}

.login-btn, .back-btn {
  padding: 12px;
  border: none;
  border-radius: 5px;
  font-weight: bold;
  cursor: pointer;
  transition: background-color 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-btn {
  width: 100%;
  background-color: #123524;
  color: #EFE3C2;
}

.back-btn {
  width: 30%;
  background-color: #f5f5f5;
  color: #123524;
}

.login-btn:hover {
  background-color: #3E7B27;
}

.back-btn:hover {
  background-color: #e0e0e0;
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