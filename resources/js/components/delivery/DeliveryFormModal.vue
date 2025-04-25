<template>
  <div class="modal fade" id="deliveryFormModal" tabindex="-1" aria-labelledby="deliveryFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deliveryFormModalLabel">Create Delivery</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="loading" class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Processing...</p>
          </div>
          <form v-else @submit.prevent="validateAndSubmit">
            <!-- Scheduled Date -->
            <div class="form-group mb-3">
              <label for="scheduledDate" class="form-label">Scheduled Date</label>
              <input 
                type="date" 
                id="scheduledDate" 
                v-model="formData.scheduledDate" 
                class="form-control"
                :min="minDate"
                required
              >
              <div v-if="errors.scheduledDate" class="text-danger mt-1">
                {{ errors.scheduledDate }}
              </div>
            </div>
            
            <!-- From Location -->
            <div class="form-group mb-3">
              <label for="fromLocation" class="form-label">From Location</label>
              <select id="fromLocation" v-model="formData.fromLocation" class="form-select" required>
                <option value="">Select Origin Location</option>
                <option v-for="location in fromLocations" 
                        :key="location.locationID" 
                        :value="location.locationID">
                  {{ location.company_address }}
                </option>
              </select>
              <div v-if="errors.fromLocation" class="text-danger mt-1">
                {{ errors.fromLocation }}
              </div>
            </div>
            
            <!-- To Location -->
            <div class="form-group mb-3">
              <label for="toLocation" class="form-label">To Location</label>
              <select id="toLocation" v-model="formData.toLocation" class="form-select" required>
                <option value="">Select Destination Location</option>
                <option v-for="location in toLocations" 
                        :key="location.locationID" 
                        :value="location.locationID">
                  {{ location.company_address }}
                </option>
              </select>
              <div v-if="errors.toLocation" class="text-danger mt-1">
                {{ errors.toLocation }}
              </div>
            </div>
            
            <!-- Additional Notes (Optional) -->
            <div class="form-group mb-3">
              <label for="notes" class="form-label">Notes (Optional)</label>
              <textarea 
                id="notes" 
                v-model="formData.notes" 
                class="form-control"
                rows="3"
                placeholder="Add any additional information about this delivery"
              ></textarea>
            </div>
            
            <!-- Verification Information -->
            <div class="form-group mb-3">
              <label class="form-label">Verification Information</label>
              <div class="card">
                <div class="card-body">
                  <p class="text-muted mb-2">
                    <i class="fas fa-info-circle me-2"></i>
                    This delivery will be linked to verification records when assigned to checkpoints.
                  </p>
                  <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span>Driver Assignment</span>
                      <span class="badge bg-secondary">Pending</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span>Vehicle Assignment</span>
                      <span class="badge bg-secondary">Pending</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span>Checkpoint Verification</span>
                      <span class="badge bg-secondary">Pending</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="validateAndSubmit"
            :disabled="loading || !isFormValid"
          >
            <i class="fas fa-save me-1"></i> Create Delivery
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import deliveryService from '../../services/deliveryService';
import modalUtil from '../../utils/modal';

export default {
  name: 'DeliveryFormModal',
  
  emits: ['delivery-created'],
  
  setup(props, { emit }) {
    const loading = ref(false);
    const fromLocations = ref([]);
    const toLocations = ref([]);
    const errors = ref({});
    
    const formData = ref({
      scheduledDate: '',
      fromLocation: '',
      toLocation: '',
      notes: ''
    });
    
    const minDate = computed(() => {
      const today = new Date();
      return today.toISOString().split('T')[0];
    });
    
    const isFormValid = computed(() => {
      return (
        formData.value.scheduledDate && 
        formData.value.fromLocation && 
        formData.value.toLocation &&
        formData.value.fromLocation !== formData.value.toLocation
      );
    });
    
    const fetchLocations = async () => {
      try {
        loading.value = true;
        const response = await deliveryService.getLocations();
        
        if (response.success) {
          fromLocations.value = response.data;
          toLocations.value = response.data;
        } else {
          modalUtil.showDanger('Error', response.message || 'Failed to load locations');
        }
      } catch (error) {
        console.error('Error fetching locations:', error);
        modalUtil.showDanger('Error', 'An unexpected error occurred while loading locations');
      } finally {
        loading.value = false;
      }
    };
    
    const validateForm = () => {
      errors.value = {};
      
      if (!formData.value.scheduledDate) {
        errors.value.scheduledDate = 'Scheduled date is required';
      }
      
      if (!formData.value.fromLocation) {
        errors.value.fromLocation = 'Origin location is required';
      }
      
      if (!formData.value.toLocation) {
        errors.value.toLocation = 'Destination location is required';
      }
      
      if (formData.value.fromLocation === formData.value.toLocation) {
        errors.value.toLocation = 'Destination must be different from origin';
      }
      
      return Object.keys(errors.value).length === 0;
    };
    
    const validateAndSubmit = async () => {
      if (!validateForm()) {
        return;
      }
      
      try {
        loading.value = true;
        
        const response = await deliveryService.createDelivery(formData.value);
        
        if (response.success) {
          modalUtil.showSuccess('Success', 'Delivery created successfully');
          emit('delivery-created', response.data);
          resetForm();
          // Close the modal
          const modalElement = document.getElementById('deliveryFormModal');
          if (modalElement) {
            const bsModal = bootstrap.Modal.getInstance(modalElement);
            if (bsModal) {
              bsModal.hide();
            }
          }
        } else {
          modalUtil.showDanger('Error', response.message || 'Failed to create delivery');
        }
      } catch (error) {
        console.error('Error creating delivery:', error);
        modalUtil.showDanger('Error', 'An unexpected error occurred while creating the delivery');
      } finally {
        loading.value = false;
      }
    };
    
    const resetForm = () => {
      formData.value = {
        scheduledDate: '',
        fromLocation: '',
        toLocation: '',
        notes: ''
      };
      errors.value = {};
    };
    
    const showModal = () => {
      resetForm();
      fetchLocations();
      
      const modalElement = document.getElementById('deliveryFormModal');
      if (modalElement) {
        const bsModal = new bootstrap.Modal(modalElement);
        bsModal.show();
      }
    };
    
    onMounted(() => {
      // Initialize the modal when component is mounted
      const modalElement = document.getElementById('deliveryFormModal');
      if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', resetForm);
      }
    });
    
    return {
      loading,
      formData,
      fromLocations,
      toLocations,
      errors,
      minDate,
      isFormValid,
      validateAndSubmit,
      showModal
    };
  }
};
</script>

<style scoped>
.card {
  border-radius: 0.375rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.list-group-item {
  padding: 0.75rem 1.25rem;
}

.badge {
  font-weight: 500;
  padding: 0.5em 0.75em;
}
</style>