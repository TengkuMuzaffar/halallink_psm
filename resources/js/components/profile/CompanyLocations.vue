<template>
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Company Locations</h5>
      <button 
        class="btn btn-primary" 
        @click="$emit('toggle-edit')"
        v-if="!editMode"
      >
        <i class="bi bi-pencil-fill me-1"></i> 
        <span class="d-none d-md-inline">Edit Locations</span>
      </button>
      <div v-else>
        <button class="btn btn-success me-2" @click="saveLocations">
          <i class="bi bi-check-lg me-1"></i> 
          <span class="d-none d-md-inline">Save</span>
        </button>
        <button class="btn btn-secondary" @click="$emit('toggle-edit')">
          <i class="bi bi-x-lg me-1"></i> 
          <span class="d-none d-md-inline">Cancel</span>
        </button>
      </div>
    </div>
    <div class="card-body">
      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
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
                    <option value="slaughterhouse">Slaughterhouse</option>
                    <option value="supplier">Supplier</option>
                    <option value="kitchen">Kitchen</option>
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
import { ref, watch } from 'vue';
import api from '../../utils/api';
import modal from '../../utils/modal';

export default {
  name: 'CompanyLocations',
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
    const saving = ref(false);
    const editableLocations = ref([]);
    const locationsToDelete = ref([]);
    
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
      addNewLocation,
      removeLocation,
      saveLocations
    };
  }
}
</script>

<style scoped>
.location-item {
  background-color: #f8f9fa;
  transition: all 0.2s;
}

.location-item:hover {
  background-color: #f1f3f5;
}
</style>