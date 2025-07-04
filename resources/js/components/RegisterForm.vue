<template>
  <form @submit.prevent="handleSubmit" class="login-form" enctype="multipart/form-data">
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
          <option value="sme">SME</option>
          <option value="logistic">Logistic</option>
        </select>
      </div>

      <div class="form-group file-upload">
        <label for="company_logo" class="file-label">
          <span>{{ logoFileName || 'Upload Company Logo (Optional)' }}</span>
          <input 
            type="file" 
            id="company_logo" 
            @change="handleFileUpload"
            accept="image/*"
            class="file-input"
          >
        </label>
        <div v-if="logoPreview" class="logo-preview">
          <img :src="logoPreview" alt="Company Logo Preview">
        </div>
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
        <div class="form-group">
          <div class="phone-input-container">
            <div class="phone-prefix">+60</div>
            <input 
              type="tel" 
              v-model="phoneNumber"
              placeholder="xxxxx xxxx"
              required
              class="phone-input"
            >
          </div>
          <small class="form-text text-muted">Malaysian format: +60 xxxxx xxxx</small>
        </div>
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
import { ref, reactive, watch } from 'vue';

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
    const logoPreview = ref(null);
    const logoFileName = ref('');
    const phoneNumber = ref(''); // New ref for phone number without prefix
    
    const formData = ref({
      // Company information
      company_name: '',
      company_type: '',
      company_logo: null,
      
      // Admin user information
      email: '',
      password: '',
      password_confirmation: '',
      tel_number: '+60' // Initialize with prefix
    });
    
    // Watch for changes in phoneNumber and update formData.tel_number
    watch(phoneNumber, (newValue) => {
      // Format the phone number to ensure it matches the required pattern
      // Remove any spaces first
      const cleaned = newValue.replace(/\s+/g, '');
      
      // Check if we have at least 9 digits
      if (cleaned.length >= 9) {
        // Format as xxxxx xxxx
        const firstPart = cleaned.substring(0, 5);
        const secondPart = cleaned.substring(5, 9);
        formData.value.tel_number = `+60 ${firstPart} ${secondPart}`;
      } else {
        // Not enough digits yet, just store with the prefix
        formData.value.tel_number = `+60 ${cleaned}`;
      }
    });
    
    const handleFileUpload = (event) => {
      const file = event.target.files[0];
      if (!file) return;
      
      formData.value.company_logo = file;
      logoFileName.value = file.name;
      
      // Create preview
      const reader = new FileReader();
      reader.onload = (e) => {
        logoPreview.value = e.target.result;
      };
      reader.readAsDataURL(file);
    };

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
      // Create FormData object to handle file upload
      const submitData = new FormData();
      
      // Append all form fields
      Object.keys(formData.value).forEach(key => {
        if (key === 'company_logo' && formData.value[key] !== null) {
          // Make sure to append the file with the correct field name
          submitData.append('company_logo', formData.value[key], formData.value[key].name);
        } else if (formData.value[key] !== null && formData.value[key] !== undefined) {
          submitData.append(key, formData.value[key]);
        }
      });
      
      // Log FormData entries for debugging
      // console.log('FormData contents:');
      // for (let pair of submitData.entries()) {
      //   console.log(pair[0] + ': ' + (pair[1] instanceof File ? `File: ${pair[1].name} (${pair[1].type})` : pair[1]));
      // }
      
      emit('submit', submitData);
    };

    return {
      currentStep,
      formData,
      phoneNumber,
      logoPreview,
      logoFileName,
      nextStep,
      prevStep,
      handleFileUpload,
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

.file-upload {
  position: relative;
}

.file-label {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 12px;
  background-color: #f5f5f5;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
  color: #666;
  text-align: center;
}

.file-input {
  position: absolute;
  left: 0;
  top: 0;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
}

.logo-preview {
  margin-top: 10px;
  text-align: center;
}

.logo-preview img {
  max-width: 100%;
  max-height: 100px;
  border-radius: 5px;
  border: 1px solid #ddd;
}
.phone-input-container {
  display: flex;
  width: 100%;
  border: 1px solid #ddd;
  border-radius: 5px;
  overflow: hidden;
}

.phone-prefix {
  background-color: #f0f0f0;
  padding: 12px 15px;
  border-right: 1px solid #ddd;
  color: #333;
  font-weight: 500;
  display: flex;
  align-items: center;
}

.phone-input {
  flex: 1;
  border: none;
  padding: 12px 15px;
  outline: none;
}

.phone-input-container:focus-within {
  border-color: #4a6cf7;
}
</style>