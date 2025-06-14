<template>
  <div class="card theme-card">
    <div class="card-header d-flex justify-content-between align-items-center theme-header">
      <h5 class="mb-0">Profile Information</h5>
      <button 
        class="btn btn-primary theme-btn-primary" 
        @click="$emit('toggle-edit')"
        v-if="!editMode"
      >
        <i class="bi bi-pencil-fill me-1"></i> 
        <span class="d-none d-md-inline">Edit Profile</span>
      </button>
      <div v-else>
        <button class="btn btn-success theme-btn-primary me-2" @click="$emit('save')">
          <i class="bi bi-check-lg me-1"></i> 
          <span class="d-none d-md-inline">Save</span>
        </button>
        <button class="btn btn-secondary theme-btn-secondary" @click="$emit('toggle-edit')">
          <i class="bi bi-x-lg me-1"></i> 
          <span class="d-none d-md-inline">Cancel</span>
        </button>
      </div>
    </div>
    <div class="card-body theme-body">
      <div v-if="loading" class="text-center py-4">
        <LoadingSpinner message="Loading profile..." />
      </div>
      <div v-else>
        <!-- View Mode -->
        <form @submit.prevent="$emit('save')">
          <div class="row mb-4">
            <div class="col-md-3 text-center">
              <div class="avatar-container mb-3">
                <img 
                  :src="profileImage || '/images/blank.jpg'" 
                  alt="Profile Picture" 
                  class="rounded-circle img-thumbnail" 
                  style="width: 150px; height: 150px; object-fit: cover;"
                >
                <div class="mt-2" v-if="editMode">
                  <label for="profile-image" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-camera-fill me-1"></i> Change Photo
                  </label>
                  <input 
                    type="file" 
                    id="profile-image" 
                    class="d-none" 
                    accept="image/*"
                    @change="$emit('image-change', $event)"
                  >
                </div>
              </div>
            </div>
            <div class="col-md-9">
              <!-- Different fields based on role -->
              <template v-if="isAdmin">
                <!-- Admin Fields -->
                <div class="mb-3">
                  <label for="company_name" class="form-label">Company Name</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    id="company_name" 
                    v-model="profileData.company_name" 
                    :disabled="!editMode"
                    required
                  >
                </div>
              </template>
              <template v-else>
                <!-- Employee Fields -->
                <div class="mb-3">
                  <label for="fullname" class="form-label">Full Name</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    id="fullname" 
                    v-model="profileData.fullname" 
                    :disabled="!editMode"
                    required
                  >
                </div>
              </template>
              
              <!-- Common Fields -->
              <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input 
                  type="email" 
                  class="form-control" 
                  id="email" 
                  v-model="profileData.email" 
                  :disabled="!editMode"
                  required
                >
              </div>
              
              <div class="mb-3">
                <label for="tel_number" class="form-label">Phone Number</label>
                <input 
                  type="tel" 
                  class="form-control" 
                  id="tel_number" 
                  v-model="profileData.tel_number" 
                  :disabled="!editMode"
                >
              </div>
              
              <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="status" 
                  v-model="profileData.status"
                  disabled
                >
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';
import LoadingSpinner from '../ui/LoadingSpinner.vue';

export default {
  name: 'ProfileInfo',
  components: {
    LoadingSpinner
  },
  props: {
    profileData: {
      type: Object,
      required: true
    },
    profileImage: {
      type: String,
      default: null
    },
    editMode: {
      type: Boolean,
      default: false
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['toggle-edit', 'save', 'cancel', 'image-change'],
  setup(props) {
    const isAdmin = computed(() => props.profileData.role === 'admin');
    
    return {
      isAdmin
    };
  }
}
</script>

<style scoped>
.theme-card {
  --primary-color: #123524;
  --secondary-color: #f5f5f5;
  --accent-color: #3E7B27;
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: box-shadow 0.3s ease;
}

.theme-card:hover {
  box-shadow: 0 4px 12px rgba(18, 53, 36, 0.15);
}

.theme-header {
  background-color: var(--primary-color);
  color: white;
  border-bottom: none;
}

.theme-body {
  background-color: var(--secondary-color);
}

.theme-btn-primary {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
  color: var(--secondary-color);
}

.theme-btn-primary:hover {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(18, 53, 36, 0.3);
}

.theme-btn-secondary {
  transition: all 0.3s ease;
}

.theme-btn-secondary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.form-control:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(18, 53, 36, 0.25);
}

.form-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(18, 53, 36, 0.25);
}

.btn-outline-primary {
  color: var(--primary-color);
  border-color: var(--primary-color);
}

.btn-outline-primary:hover {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(18, 53, 36, 0.3);
}

.avatar-container {
  position: relative;
  display: inline-block;
}
</style>