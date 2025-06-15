<template>
  <div class="card mb-4 theme-card">
    <div class="card-header d-flex justify-content-between align-items-center theme-header">
      <h5 class="mb-0">Company Locations</h5>
      <button 
        class="btn btn-primary theme-btn-primary" 
        @click="$emit('toggle-edit')"
        v-if="!editMode"
      >
        <i class="bi bi-pencil-fill me-1"></i> 
        <span class="d-none d-md-inline">Edit Locations</span>
      </button>
      <div v-else>
        <button class="btn btn-success theme-btn-primary me-2" @click="saveLocations">
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
        <LoadingSpinner message="Loading locations..." />
      </div>
      <div v-else>
        <!-- View Mode -->
        <div v-if="!editMode">
          <div v-if="locations.length === 0" class="text-center py-3">
            <i class="fas fa-map-marker-alt text-muted mb-2" style="font-size: 2rem;"></i>
            <p class="text-muted">No locations added yet</p>
          </div>
          <div v-else class="location-list">
            <div v-for="(location, index) in locations" :key="location.locationID || index" class="location-item mb-3 p-3 border rounded">
              <div class="d-flex justify-content-between">
                <div>
                  <div class="fw-bold">{{ location.company_address }}</div>
                  <div class="text-muted small">{{ location.location_type }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Edit Mode -->
        <div v-else>
          <form @submit.prevent="saveLocations">
            <div v-for="(location, index) in editableLocations" :key="index" class="location-item mb-3 p-3 border rounded">
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label">Address</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="location.company_address" 
                    required
                  >
                </div>
                <div class="col-md-4">
                  <label class="form-label">Type</label>
                  <select class="form-select" v-model="location.location_type" required>
                    <option value="headquarters">Headquarters</option>
                    <option v-if="companyType === 'broiler'" value="supplier">Supplier</option>
                    <option v-if="companyType === 'slaughterhouse'" value="slaughterhouse">Slaughterhouse</option>
                    <option v-if="companyType === 'sme'" value="kitchen">Kitchen</option>
                  </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button 
                    type="button" 
                    class="btn btn-danger btn-sm w-100" 
                    @click="removeLocation(index)"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
            
            <div class="mb-3">
              <button 
                type="button" 
                class="btn btn-outline-primary btn-sm" 
                @click="addNewLocation"
              >
                <i class="fas fa-plus me-1"></i> Add Location
              </button>
            </div>
            
            <div class="d-flex justify-content-end">
              <button 
                type="submit" 
                class="btn btn-success" 
                :disabled="saving"
              >
                <i class="fas fa-save me-1"></i> Save Locations
                <span v-if="saving" class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch, computed } from 'vue';
import api from '../../utils/api';
import modal from '../../utils/modal';
import { useStore } from 'vuex';
import LoadingSpinner from '../ui/LoadingSpinner.vue';
// import marketplaceService from '../../services/marketplaceService';

export default {
  name: 'CompanyLocations',
  components: {
    LoadingSpinner
  },
  props: {
    locations: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    editMode: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:locations', 'loading', 'toggle-edit'],
  setup(props, { emit }) {
    const store = useStore();
    const saving = ref(false);
    const editableLocations = ref([]);
    const locationsToDelete = ref([]);
    
    // Get company type from store
    const companyType = computed(() => {
      const user = store.getters.user;
      return user && user.company ? user.company.company_type : 'admin';
    });
    
    // Watch for changes in locations or edit mode
    watch(() => props.locations, (newLocations) => {
      if (props.editMode) {
        editableLocations.value = Array.isArray(newLocations) ? JSON.parse(JSON.stringify(newLocations || [])) : [];
      }
    }, { immediate: true });
    
    watch(() => props.editMode, (newValue) => {
      if (newValue) {
        // Create a deep copy of locations for editing
        editableLocations.value = Array.isArray(props.locations) ? JSON.parse(JSON.stringify(props.locations || [])) : [];
        locationsToDelete.value = [];
      }
    });
    
    // Add new location
    // Update addNewLocation to set default location type based on company type
    const addNewLocation = () => {
      editableLocations.value.push({
        company_address: '',
        location_type: 'headquarters'
      });
    };
    
    // Remove location
    const removeLocation = (index) => {
      const location = editableLocations.value[index];
      
      // If it has an ID, mark for deletion on the server
      if (location.locationID) {
        locationsToDelete.value.push(location.locationID);
      }
      
      // Remove from editable array
      editableLocations.value.splice(index, 1);
    };
    
    // Save locations
    const saveLocations = async () => {
      saving.value = true;
      emit('loading', true);
      
      try {
        const payload = {
          locations: editableLocations.value || [], // Ensure it's always an array
          delete_ids: locationsToDelete.value || []
        };
        
        const response = await api.post('/api/profile/locations', payload);
        
        // Update parent component with new locations
        emit('update:locations', response.locations || []);
        
        // Show success message
        modal.success('Success', 'Company locations updated successfully');
        
        // Exit edit mode
        emit('toggle-edit');
      } catch (error) {
        console.error('Error saving locations:', error);
        modal.danger('Error', 'Failed to update company locations. Please try again.');
      } finally {
        saving.value = false;
        emit('loading', false);
      }
    };
    
    return {
      saving,
      editableLocations,
      companyType,
      addNewLocation,
      removeLocation,
      saveLocations
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

.btn-outline-danger {
  transition: all 0.3s ease;
}

.btn-outline-danger:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
}

.btn-outline-primary {
  color: var(--primary-color);
  border-color: var(--primary-color);
  transition: all 0.3s ease;
}

.btn-outline-primary:hover {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(18, 53, 36, 0.3);
}

.location-item {
  transition: all 0.3s ease;
  border: 1px solid #e9ecef;
  background-color: white;
}

.location-item:hover {
  border-color: var(--primary-color);
  box-shadow: 0 2px 8px rgba(18, 53, 36, 0.1);
  transform: translateY(-1px);
}

.form-control:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(18, 53, 36, 0.25);
}

.form-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(18, 53, 36, 0.25);
}

.badge.bg-success {
  background-color: var(--primary-color) !important;
}
</style>