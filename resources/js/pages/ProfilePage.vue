<template>
  <div class="profile-page">
    <h1 class="mb-4">My Profile</h1>
    
    <!-- Email Verification Alert -->
    <div v-if="!emailVerified" class="alert alert-warning mb-4">
      <div class="d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div>
          <strong>Your email is not verified.</strong> 
          <p class="mb-0">Please verify your email to access all features of the application.</p>
        </div>
        <button 
          class="btn btn-sm btn-primary ms-auto" 
          @click="sendVerificationEmail"
          :disabled="sendingVerification"
        >
          <span v-if="sendingVerification">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Sending...
          </span>
          <span v-else>Send Verification Email</span>
        </button>
      </div>
    </div>
    
    <div class="row">
      <!-- Profile Information Card -->
      <div class="col-lg-8">
        <div class="mb-4">  <!-- Added wrapper div with margin -->
          <ProfileInfo 
            :profileData="formattedProfileData"
            :profileImage="isAdmin ? profileData.company?.company_image : profileData.image"
            :editMode="editProfileMode"
            :loading="loading"
            @toggle-edit="editProfileMode = !editProfileMode"
            @save="saveProfile"
            @image-change="handleImageChange"
          />
        </div>
        
        <!-- Company Locations (Admin Only) -->
        <div v-if="isAdmin" class="mb-4">  <!-- Added margin bottom -->
          <CompanyLocations
            v-model:locations="profileData.locations"
            :loading="locationsLoading"
            :editMode="editLocationsMode"
            @loading="locationsLoading = $event"
            @toggle-edit="editLocationsMode = !editLocationsMode"
          />
        </div>
      </div>
      
      <!-- Security Card -->
      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0">Security</h5>
          </div>
          <div class="card-body">
            <!-- Email Verification Status -->
            <div class="mb-4">
              <h6>Email Verification</h6>
              <div class="d-flex align-items-center">
                <span v-if="emailVerified" class="text-success">
                  <i class="bi bi-check-circle-fill me-2"></i> Verified
                </span>
                <span v-else class="text-warning">
                  <i class="bi bi-exclamation-circle-fill me-2"></i> Not Verified
                </span>
              </div>
            </div>
            
            <form @submit.prevent="updatePassword">
              <div class="mb-3">
                <label for="current-password" class="form-label">Current Password</label>
                <input 
                  type="password" 
                  class="form-control" 
                  id="current-password"
                  v-model="passwordData.current_password"
                  required
                >
                <div class="form-text">
                  <a href="#" @click.prevent="sendPasswordResetEmail" class="text-primary">
                    Forgot your password?
                  </a>
                </div>
              </div>
              <div class="mb-3">
                <label for="new-password" class="form-label">New Password</label>
                <input 
                  type="password" 
                  class="form-control" 
                  id="new-password"
                  v-model="passwordData.new_password"
                  required
                  minlength="8"
                >
              </div>
              <div class="mb-3">
                <label for="confirm-password" class="form-label">Confirm Password</label>
                <input 
                  type="password" 
                  class="form-control" 
                  id="confirm-password"
                  v-model="passwordData.new_password_confirmation"
                  required
                >
                <div class="form-text text-danger" v-if="passwordMismatch">
                  Passwords do not match
                </div>
              </div>
              <button 
                type="submit" 
                class="btn btn-primary w-100"
                :disabled="passwordLoading || passwordMismatch"
              >
                <span v-if="passwordLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Update Password
              </button>
            </form>
          </div>
        </div>
        
        <!-- Language Button (Admin Only) -->
        <div v-if="isAdmin" class="mb-4">
          <button class="btn btn-outline-primary w-100" @click="openLanguageSettings">
            <i class="fas fa-language me-2"></i> Language Settings
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStore } from 'vuex';
import api from '../utils/api';
import modal from '../utils/modal';  // This is correct - importing the modal utility
import ProfileInfo from '../components/profile/ProfileInfo.vue';
import CompanyLocations from '../components/profile/CompanyLocations.vue';

export default {
  name: 'ProfilePage',
  components: {
    ProfileInfo,
    CompanyLocations
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    const store = useStore();
    // Remove this line - modal is already imported correctly above
    // const modal = useModal();
    
    // State variables
    const loading = ref(true);
    const profileData = ref({});
    const editProfileMode = ref(false);
    const editLocationsMode = ref(false);
    const locationsLoading = ref(false);
    const newProfileImage = ref(null);
    const newCompanyImage = ref(null);
    const emailVerified = ref(true);
    const sendingVerification = ref(false);
    
    // Password related state
    const passwordData = ref({
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    });
    const passwordLoading = ref(false);
    const passwordMismatch = computed(() => {
      return passwordData.value.new_password !== passwordData.value.new_password_confirmation;
    });
    
    // Check if user is admin
    const isAdmin = computed(() => {
      return profileData.value.role === 'admin';
    });
    
    // Format profile data for the form
    const formattedProfileData = computed(() => {
      if (profileData.value.role === 'admin') {
        return {
          ...profileData.value,
          company_name: profileData.value.company?.company_name
        };
      }
      return {
        ...profileData.value
      };
    });
    
    // In the setup function, update the fetchProfileData function:
    
    // Fetch profile data
    const fetchProfileData = async () => {
      try {
        loading.value = true;
        const response = await api.get('/api/profile');
        profileData.value = response;
        console.log('Fetched profile data:', response);
        // Check email verification status - make sure we're checking the right property
        emailVerified.value = !!response.email_verified_at;
        
        // Also update the store with the latest verification status
        store.commit('SET_EMAIL_VERIFIED', !!response.email_verified_at);
        
        console.log('Email verification status:', {
          email_verified_at: response.email_verified_at,
          emailVerified: emailVerified.value
        });
        
        loading.value = false;
      } catch (error) {
        console.error('Error fetching profile data:', error);
        loading.value = false;
        modal.danger('Error', 'Failed to load profile data');
      }
    };
    
    // Also add a watcher to sync with store state
    watch(() => store.getters.emailVerified, (newValue) => {
      emailVerified.value = newValue;
    });
    
    
    
    // Send verification email
    const sendVerificationEmail = async () => {
      try {
        sendingVerification.value = true;
        const response = await store.dispatch('sendVerificationEmail');
        // Replace showToast with success modal
        modal.success('Email Sent', response.message || 'Verification link sent to your email address');
        sendingVerification.value = false;
      } catch (error) {
        console.error('Error sending verification email:', error);
        sendingVerification.value = false;
        // Replace showToast with danger modal
        modal.danger('Error', 'Failed to send verification email');
      }
    };
    
    // Handle image change event from ProfileInfo component
    const handleImageChange = (event) => {
      const file = event.target.files[0];
      if (!file) return;
      
      // Check if file is an image
      if (!file.type.match('image.*')) {
        // Replace showToast with danger modal
        modal.danger('Invalid File', 'Please select an image file');
        return;
      }
      
      // Check file size (max 2MB)
      if (file.size > 2 * 1024 * 1024) {
        // Replace showToast with danger modal
        modal.danger('File Too Large', 'Image size should not exceed 2MB');
        return;
      }
      
      // Store the file in the appropriate variable based on user role
      if (isAdmin.value) {
        newCompanyImage.value = file;
      } else {
        newProfileImage.value = file;
      }
      
      // Preview the image
      const reader = new FileReader();
      reader.onload = (e) => {
        if (isAdmin.value) {
          profileData.value.company.company_image = e.target.result;
        } else {
          profileData.value.image = e.target.result;
        }
      };
      reader.readAsDataURL(file);
    };
    
    // Save profile changes
    const saveProfile = async () => {
      try {
        const formData = new FormData();
        
        // Add common fields
        formData.append('email', profileData.value.email);
        formData.append('tel_number', profileData.value.tel_number || '');
        
        // Add role-specific fields and image
        if (isAdmin.value) {
          formData.append('company_name', profileData.value.company?.company_name || '');
          if (newCompanyImage.value) {
            formData.append('company_image', newCompanyImage.value);
          }
        } else {
          formData.append('fullname', profileData.value.fullname || '');
          if (newProfileImage.value) {
            formData.append('profile_image', newProfileImage.value);
          }
        }
        
        // Send the request
        const response = await api.post('/api/profile/update', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        
        // Update local data and Vuex store with complete user data
        if (response.user) {
          // Update the complete user object including company data
          const updatedUser = {
            ...response.user,
            company: response.company || user.value?.company
          };
          
          // Update local state
          profileData.value = updatedUser;
          
          // Update Vuex store with complete user data
          store.commit('SET_USER', updatedUser);
        }
        
        // Reset image variables
        newProfileImage.value = null;
        newCompanyImage.value = null;
        
        // Exit edit mode
        editProfileMode.value = false;
        
        // Show success message
        modal.success('Success', 'Profile updated successfully');
      } catch (error) {
        console.error('Error updating profile:', error);
        modal.danger('Error', 'Failed to update profile');
      }
    };
    
    // Update password
    const updatePassword = async () => {
      if (passwordMismatch.value) {
        return;
      }
      
      try {
        passwordLoading.value = true;
        
        await api.post('/api/profile/password', passwordData.value);
        
        // Reset form
        passwordData.value = {
          current_password: '',
          new_password: '',
          new_password_confirmation: ''
        };
        
        // Show success message
        modal.success('Success', 'Password updated successfully');
        
        passwordLoading.value = false;
      } catch (error) {
        console.error('Error updating password:', error);
        
        // Show error message
        if (error.response?.status === 422) {
          // Validation error
          const errorMessage = error.response.data.message || 'Invalid password data';
          modal.danger('Validation Error', errorMessage);
        } else {
          modal.danger('Error', 'Failed to update password');
        }
        
        passwordLoading.value = false;
      }
    };
    
    // Send password reset email
    const sendPasswordResetEmail = async () => {
      try {
        if (!profileData.value.email) {
          modal.warning('Warning', 'Email address is required');
          return;
        }
        
        await api.post('/api/password/forgot', { email: profileData.value.email });
        
        modal.success('Email Sent', 'Password reset link has been sent to your email');
      } catch (error) {
        console.error('Error sending password reset email:', error);
        modal.danger('Error', 'Failed to send password reset email');
      }
    };
    
    // Open language settings
    const openLanguageSettings = () => {
      // Implement language settings functionality
      modal.info('Language Settings', 'Language settings functionality will be implemented soon.');
    };
    
    // Check for verifyEmail query parameter
    onMounted(() => {
      fetchProfileData();
      
      if (route.query.verifyEmail === 'true') {
        // Show modal about email verification
        modal.warning('Email Verification Required', 'Please verify your email to access all features');
      }
    });
    
    return {
      loading,
      profileData,
      formattedProfileData,
      editProfileMode,
      editLocationsMode,
      locationsLoading,
      isAdmin,
      handleImageChange,
      saveProfile,
      emailVerified,
      sendVerificationEmail,
      sendingVerification,
      passwordData,
      passwordLoading,
      passwordMismatch,
      updatePassword,
      sendPasswordResetEmail,
      openLanguageSettings
    };
  }
};
</script>

<style scoped>
.profile-page {
  padding-bottom: 2rem;
}
</style>
