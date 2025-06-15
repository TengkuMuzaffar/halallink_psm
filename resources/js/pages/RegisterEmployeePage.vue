<template>
  <div class="login-container">
    <div class="login-card">
      <login-left-panel :texts="animatedTexts" />
      
      <div class="right-panel">
        <div class="company-info mb-4" v-if="companyName">
          <div class="company-image mb-3" v-if="companyImage">
            <img :src="companyImage" alt="Company Logo" class="img-fluid rounded" style="max-height: 80px;">
          </div>
          <h2>Join {{ companyName }}</h2>
          <p>Register as an employee</p>
        </div>
        <div v-else>
          <h2>Join Your Company</h2>
          <p>Register as an employee</p>
        </div>
        
        <!-- Success message -->
        <div v-if="successMessage" class="alert alert-success w-100 mb-4">
          <i class="fas fa-check-circle me-2"></i> {{ successMessage }}
        </div>
        
        <!-- Error message display -->
        <div v-if="error" class="alert alert-danger w-100 mb-4">
          <div class="text-center">
            <i class="fas fa-exclamation-circle me-2"></i>
          </div>          <span v-if="typeof error === 'string'">{{ error }}</span>
          <ul v-else-if="typeof error === 'object'" class="mb-0 ps-3">
            <li v-for="(messages, field) in error" :key="field">
              <strong>{{ field }}:</strong> {{ Array.isArray(messages) ? messages.join(', ') : messages }}
            </li>
          </ul>
        </div>
        
        <register-form-employee
          v-if="!successMessage"
          :loading="loading"
          :error="null"
          :formId="formID"
          @submit="handleRegister"
          @login="goToLogin"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import LoginLeftPanel from '../components/LoginLeftPanel.vue';
import RegisterFormEmployee from '../components/RegisterFormEmployee.vue';
import { registerEmployee } from '../services/authService';
import axios from 'axios';

export default {
  name: 'RegisterEmployeePage',
  components: {
    LoginLeftPanel,
    RegisterFormEmployee
  },
  setup() {
    const router = useRouter();
    const route = useRoute();
    const loading = ref(false);
    const error = ref(null);
    const successMessage = ref(null);
    const formID = ref('');
    const companyName = ref('');
    const companyImage = ref('');

    const animatedTexts = [
      "Join your company team",
      "Connect with your colleagues",
      "Access company resources",
      "Be part of the Halal ecosystem"
    ];

    // Fetch company information
    const fetchCompanyInfo = async (id) => {
      try {
        loading.value = true;
        const response = await axios.get(`/api/companies/form/${id}`);
        
        if (response.data) {
          companyName.value = response.data.company_name;
          
          // Handle company image path
          if (response.data.company_image) {
            companyImage.value = response.data.company_image;
          }
        }
        
        loading.value = false;
      } catch (error) {
        console.error('Error fetching company info:', error);
        error.value = 'Could not retrieve company information. Please check the registration link.';
        loading.value = false;
      }
    };

    onMounted(() => {
      // console.log('Route query:', route.query);
      // Check if formID is provided in the route
      if (!route.query.formID) {
        console.error('No formID in query params');
        error.value = "Company ID is required. Please contact your administrator.";
        // Redirect to login after a delay
        setTimeout(() => {
          router.push({ name: 'Login' });
        }, 3000);
      } else {
        console.log('FormID found:', route.query.formID);
        formID.value = route.query.formID;
        
        // Fetch company information
        fetchCompanyInfo(formID.value);
      }
    });

    const handleRegister = async (formData) => {
      loading.value = true;
      error.value = null;
      successMessage.value = null;
      
      try {
        // Add formID to the form data
        formData.append('formID', formID.value);
        
        // Use the authService to register employee
        const data = await registerEmployee(formData);
        
        // Show success message
        successMessage.value = data.message || "Registration successful! Your account is pending approval. Redirecting to login page...";
        
        // Redirect to Login page after a short delay
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
      formID,
      companyName,
      companyImage,
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

.company-info {
  text-align: center;
}

.company-image {
  display: flex;
  justify-content: center;
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