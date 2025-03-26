<template>
  <div class="profile-page">
    <h1 class="mb-4">My Profile</h1>
    
    <div class="row">
      <!-- Profile Information Card -->
      <div class="col-lg-8">
        <ProfileInfo 
          :profileData="formattedProfileData"
          :profileImage="isAdmin ? profileData.company?.company_image : profileData.image"
          :editMode="editProfileMode"
          :loading="loading"
          @toggle-edit="editProfileMode = !editProfileMode"
          @save="saveProfile"
          @image-change="handleImageChange"
        />
        
        <!-- Company Locations (Admin Only) -->
        <div v-if="isAdmin">
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
import api from '../utils/api';
import modal from '../utils/modal';
import ProfileInfo from '../components/profile/ProfileInfo.vue';
import CompanyLocations from '../components/profile/CompanyLocations.vue';
import SecuritySettings from '../components/profile/SecuritySettings.vue';
import AccountSettings from '../components/profile/AccountSettings.vue';

export default {
  name: 'ProfilePage',
  components: {
    ProfileInfo,
    CompanyLocations,
    SecuritySettings,
    AccountSettings
  },
  setup() {
    // State
    const loading = ref(true);
    const locationsLoading = ref(false);
    const profileData = ref({});
    const editProfileMode = ref(false);
    const editLocationsMode = ref(false);
    const newProfileImage = ref(null);
    const newCompanyImage = ref(null);
    const passwordData = ref({
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    });
    const passwordLoading = ref(false);
    const sendingResetEmail = ref(false);
    
    // Computed properties
    const isAdmin = computed(() => profileData.value.role === 'admin');
    
    // Add the missing passwordMismatch computed property
    const passwordMismatch = computed(() => {
      return passwordData.value.new_password !== passwordData.value.new_password_confirmation;
    });
    
    // Format profile data based on user role
    const formattedProfileData = computed(() => {
      if (isAdmin.value && profileData.value.company) {
        return {
          company_name: profileData.value.company?.company_name || '',
          email: profileData.value.email || '',
          tel_number: profileData.value.tel_number || '',
          status: profileData.value.status || '',
          role: profileData.value.role || ''
        };
      } else {
        return {
          fullname: profileData.value.fullname || '',
          email: profileData.value.email || '',
          tel_number: profileData.value.tel_number || '',
          status: profileData.value.status || '',
          role: profileData.value.role || ''
        };
      }
    });

    // Fetch profile data
    const fetchProfile = async () => {
      loading.value = true;
      try {
        const response = await api.get('/api/profile');
        profileData.value = response;
        console.log('Profile data:', profileData.value);
      } catch (error) {
        console.error('Failed to fetch profile:', error);
        modal.danger('Error', 'Failed to load profile data. Please try again.');
      } finally {
        loading.value = false;
      }
    };

    // Handle image change
    const handleImageChange = (event) => {
      const file = event.target.files[0];
      if (!file) return;
      
      if (isAdmin.value) {
        newCompanyImage.value = file;
      } else {
        newProfileImage.value = file;
      }
    };

    // Save profile changes
    const saveProfile = async () => {
      loading.value = true;
      
      try {
        // Prepare form data
        const formData = new FormData();
        
        if (isAdmin.value && profileData.value.company) {
          // Admin profile data
          formData.append('company_name', formattedProfileData.value.company_name);
          if (newCompanyImage.value) {
            formData.append('company_image', newCompanyImage.value);
          }
        } else {
          // Employee profile data
          formData.append('fullname', formattedProfileData.value.fullname);
          if (newProfileImage.value) {
            formData.append('profile_image', newProfileImage.value);
          }
        }
        
        // Common data
        formData.append('email', formattedProfileData.value.email);
        formData.append('tel_number', formattedProfileData.value.tel_number);
        
        // Send update request
        const response = await api.post('/api/profile/update', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        
        // Refresh profile data
        await fetchProfile();
        
        // Reset image file references
        newProfileImage.value = null;
        newCompanyImage.value = null;
        
        // Exit edit mode
        editProfileMode.value = false;
        
        // Show success message
        modal.success('Success', 'Profile updated successfully');
        
      } catch (error) {
        console.error('Failed to update profile:', error);
        modal.danger('Error', 'Failed to update profile. Please try again.');
      } finally {
        loading.value = false;
      }
    };

    // Update password
    const updatePassword = async () => {
      passwordLoading.value = true;
      try {
        await api.post('/api/password/change', passwordData.value);
        
        // Reset form
        passwordData.value = {
          current_password: '',
          new_password: '',
          new_password_confirmation: ''
        };
        
        // Show success message
        modal.success('Success', 'Password updated successfully. A confirmation email has been sent to your inbox.');
      } catch (error) {
        console.error('Failed to update password:', error);
        
        if (error.response && error.response.data && error.response.data.errors) {
          const errorMessages = Object.values(error.response.data.errors)
            .flat()
            .join('\n');
          modal.danger('Error', errorMessages);
        } else {
          modal.danger('Error', 'Failed to update password. Please try again.');
        }
      } finally {
        passwordLoading.value = false;
      }
    };

    // Language settings
    const openLanguageSettings = () => {
      // Implement language settings functionality
      modal.info('Language Settings', 'Language settings functionality will be implemented soon.');
    };

    // Fetch profile data on component mount
    onMounted(fetchProfile);

    // Send password reset email for authenticated user
    const sendPasswordResetEmail = async () => {
      if (sendingResetEmail.value) return;
      
      try {
        sendingResetEmail.value = true;
        
        // Show loading modal
        const loadingModal = modal.show({
          type: 'info',
          title: 'Sending Reset Link',
          message: '<div class="text-center"><div class="spinner-border text-primary mb-3" role="status"></div><p>Sending password reset link to your email...</p></div>',
          showClose: false,
          buttons: []
        });
        
        // Get user email from profile data
        const email = profileData.value.email;
        
        if (!email) {
          throw new Error('User email not found');
        }
        
        // Send the reset email
        await api.post('/api/password/forgot', { email });
        
        // Hide loading modal
        loadingModal.hide();
        
        // Show success modal
        modal.success(
          'Reset Link Sent',
          `A password reset link has been sent to <strong>${email}</strong>. Please check your inbox and follow the instructions to reset your password.`
        );
        
      } catch (error) {
        console.error('Failed to send password reset email:', error);
        
        // Show error modal
        modal.danger(
          'Error',
          'Failed to send password reset email. Please try again later.'
        );
      } finally {
        sendingResetEmail.value = false;
      }
    };
    
    return {
      profileData,
      formattedProfileData,
      loading,
      locationsLoading,
      profileData,
      formattedProfileData,
      editProfileMode,
      editLocationsMode,
      passwordData,
      passwordLoading,
      passwordMismatch,
      isAdmin,
      saveProfile,
      updatePassword,
      handleImageChange,
      openLanguageSettings,
      sendPasswordResetEmail
    };
  }
};
</script>

<style scoped>
.profile-page {
  padding-bottom: 2rem;
}
</style>
