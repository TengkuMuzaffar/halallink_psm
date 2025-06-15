<template>
  <form @submit.prevent="handleSubmit" class="login-form" enctype="multipart/form-data">
    <div class="form-group">
      <input 
        type="text" 
        v-model="formData.fullname" 
        placeholder="Full Name"
        required
      >
    </div>
    
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
        <label for="tel_number">Phone Number</label>
        <div class="phone-input-container">
          <div class="phone-prefix">+60</div>
          <input 
            type="tel" 
            id="tel_number"
            v-model="phoneNumber"
            placeholder="xxxxx xxxx"
            required
            class="phone-input"
          >
        </div>
        <small class="form-text text-muted">Malaysian format: +60 xxxxx xxxx (enter only the digits after +60)</small>
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

    <div class="form-group file-upload">
      <label for="employee_image" class="file-label">
        <span>{{ imageFileName || 'Upload Profile Picture (Optional)' }}</span>
        <input 
          type="file" 
          id="employee_image" 
          @change="handleFileUpload"
          accept="image/*"
          class="file-input"
        >
      </label>
      <div v-if="imagePreview" class="logo-preview">
        <img :src="imagePreview" alt="Profile Picture Preview">
      </div>
    </div>

    <!-- Update the button styling to match RegisterForm.vue -->
    <button 
      type="submit" 
      class="btn btn-primary w-100" 
      :disabled="loading"
    >
      <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
      {{ loading ? 'Registering...' : 'Register' }}
    </button>
    
    <div class="text-center mt-3">
      Already have an account? <a href="#" @click.prevent="$emit('login')">Login</a>
    </div>
  </form>
</template>

<script>
import { ref, reactive, computed, watch } from 'vue';

export default {
  name: 'RegisterFormEmployee',
  props: {
    loading: {
      type: Boolean,
      default: false
    },
    error: {
      type: [String, Object],
      default: null
    },
    formId: {
      type: String,
      required: true
    }
  },
  emits: ['submit', 'login'],
  setup(props, { emit }) {
    const formData = reactive({
      fullname: '',
      email: '',
      tel_number: '+60 ', // Initialize with prefix
      password: '',
      password_confirmation: ''
    });
    
    // Separate variable for phone number without prefix
    const phoneNumber = ref('');
    
    // Watch for changes in phoneNumber and update formData.tel_number
    watch(phoneNumber, (newValue) => {
      formData.tel_number = `+60${newValue}`;
    });
    
    const imageFile = ref(null);
    const imageFileName = ref('');
    const imagePreview = ref('');
    
    const handleFileUpload = (event) => {
      const file = event.target.files[0];
      if (file) {
        imageFile.value = file;
        imageFileName.value = file.name;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
          imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    };
    
    const handleSubmit = () => {
      // Create FormData object to handle file upload
      const submitData = new FormData();
      
      // Add form fields to FormData
      Object.keys(formData).forEach(key => {
        submitData.append(key, formData[key]);
      });
      
      // Add image file if selected
      if (imageFile.value) {
        submitData.append('image', imageFile.value);
      }
      
      // Emit submit event with form data
      emit('submit', submitData);
    };
    
    return {
      formData,
      phoneNumber,
      imageFile,
      imageFileName,
      imagePreview,
      handleFileUpload,
      handleSubmit
    };
  }
};
</script>

<style scoped>
.login-form {
  width: 100%;
}

.form-group {
  margin-bottom: 20px;
}

input, select {
  width: 100%;
  padding: 12px 15px;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-size: 14px;
  transition: border-color 0.3s;
}

input:focus, select:focus {
  border-color: #4a6cf7;
  outline: none;
}

.login-btn {
  width: 100%;
  padding: 12px;
  background: #4a6cf7;
  color: white;
  border: none;
  border-radius: 5px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.login-btn:hover {
  background: #3a5bd9;
}

.login-btn:disabled {
  background: #a0aec0;
  cursor: not-allowed;
}

.signup-link {
  text-align: center;
  margin-top: 20px;
  font-size: 14px;
  color: #666;
}

.signup-link a {
  color: #4a6cf7;
  text-decoration: none;
  font-weight: 600;
}

.file-upload {
  position: relative;
  margin-bottom: 20px;
}

.file-label {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 12px 15px;
  border: 1px dashed #ddd;
  border-radius: 5px;
  font-size: 14px;
  color: #666;
  cursor: pointer;
  transition: border-color 0.3s;
}

.file-label:hover {
  border-color: #4a6cf7;
}

.file-input {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.logo-preview {
  margin-top: 10px;
  text-align: center;
}

.logo-preview img {
  max-width: 100%;
  max-height: 150px;
  border-radius: 5px;
}

.button-group {
  display: flex;
  gap: 10px;
}

.back-btn {
  flex: 1;
  padding: 12px;
  background: #f1f1f1;
  color: #333;
  border: none;
  border-radius: 5px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.back-btn:hover {
  background: #e1e1e1;
}

.step-title {
  font-size: 18px;
  margin-bottom: 20px;
  color: #333;
}

/* Add these styles to match RegisterForm.vue */
.btn-primary {
  background-color: #3E7B27;
  border-color: #3E7B27;
  padding: 10px 15px;
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.btn-primary:hover, .btn-primary:focus {
  background-color: #123524;
  border-color: #123524;
  box-shadow: 0 0 0 0.25rem rgba(62, 123, 39, 0.25);
}

.btn-primary:disabled {
  background-color: #3E7B27;
  border-color: #3E7B27;
  opacity: 0.65;
}

a {
  color: #3E7B27;
  text-decoration: none;
  font-weight: 500;
}

a:hover {
  color: #123524;
  text-decoration: underline;
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
