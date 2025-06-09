<template>
  <div class="modal fade" id="deliveryFormModal" tabindex="-1" aria-labelledby="deliveryFormModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content theme-modal">
        <div class="modal-header theme-header">
          <h5 class="modal-title" id="deliveryFormModalLabel">Create Delivery</h5>
          <button type="button" class="btn-close theme-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body theme-body">
          <LoadingSpinner v-if="loading" message="Processing..." />
          <form v-else @submit.prevent="validateAndSubmit">
            <!-- Scheduled Date -->
            <div class="form-group mb-3">
              <label for="scheduled_date" class="form-label theme-label">Scheduled Date</label>
              <input 
                type="date" 
                id="scheduled_date" 
                v-model="formData.scheduled_date" 
                class="form-control theme-input"
                :min="minDate"
                @change="onDateChange"
                required
              >
              <div v-if="errors.scheduled_date" class="text-danger mt-1">
                {{ errors.scheduled_date }}
              </div>
            </div>
            
            <!-- Driver Selection -->
            <div class="form-group mb-3">
              <label for="driver" class="form-label theme-label">Driver</label>
              <select id="driver" v-model="formData.userID" class="form-select theme-select" :disabled="!formData.scheduled_date" required>
                <option value="">Select Driver</option>
                <option v-for="driver in drivers" 
                        :key="driver.userID" 
                        :value="driver.userID">
                  {{ driver.fullname }}
                </option>
              </select>
              <div v-if="!formData.scheduled_date && !errors.userID" class="text-muted mt-1">
                Please select a date first
              </div>
              <div v-if="errors.userID" class="text-danger mt-1">
                {{ errors.userID }}
              </div>
            </div>
            
            <!-- Vehicle Selection -->
            <div class="form-group mb-3">
              <label for="vehicle" class="form-label theme-label">Vehicle</label>
              <select id="vehicle" v-model="formData.vehicleID" class="form-select theme-select" :disabled="!formData.scheduled_date" required>
                <option value="">Select Vehicle</option>
                <option v-for="vehicle in vehicles" 
                        :key="vehicle.vehicleID" 
                        :value="vehicle.vehicleID">
                  {{ vehicle.vehicle_plate }} - {{ vehicle.vehicle_load_weight }}kg
                </option>
              </select>
              <div v-if="!formData.scheduled_date && !errors.vehicleID" class="text-muted mt-1">
                Please select a date first
              </div>
              <div v-if="errors.vehicleID" class="text-danger mt-1">
                {{ errors.vehicleID }}
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer theme-footer">
          <button type="button" class="btn theme-btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button 
            type="button" 
            class="btn theme-btn-primary" 
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
import { ref, computed, onMounted, watch } from 'vue';
import deliveryService from '../../services/deliveryService';
import { showModal, showSuccess, showDanger } from '../../utils/modal';
import * as bootstrap from 'bootstrap';
import LoadingSpinner from '../ui/LoadingSpinner.vue';

export default {
  name: 'DeliveryFormModal',
  
  components: {
    LoadingSpinner
  },
  
  emits: ['delivery-created'],
  
  setup(props, { emit }) {
    const loading = ref(false);
    const drivers = ref([]);
    const vehicles = ref([]);
    const errors = ref({});
    
    const formData = ref({
      scheduled_date: '',
      userID: '',
      vehicleID: ''
    });
    
    const minDate = computed(() => {
      const today = new Date();
      return today.toISOString().split('T')[0];
    });
    
    const isFormValid = computed(() => {
      return (
        formData.value.scheduled_date && 
        formData.value.userID &&
        formData.value.vehicleID
      );
    });
    
    const onDateChange = () => {
      // Reset selections when date changes
      formData.value.userID = '';
      formData.value.vehicleID = '';
      
      if (formData.value.scheduled_date) {
        fetchDrivers();
        fetchVehicles();
      }
    };
    
    const fetchDrivers = async () => {
      if (!formData.value.scheduled_date) return;
      
      try {
        loading.value = true;
        const response = await deliveryService.getDrivers(formData.value.scheduled_date);
        // console.log(response); // Add this line to log the response to the console
        if (response.success) {
          drivers.value = response.data;
        } else {
          showDanger('Error', response.message || 'Failed to load drivers');
        }
      } catch (error) {
        console.error('Error fetching drivers:', error);
        showDanger('Error', 'An unexpected error occurred while loading drivers');
      } finally {
        loading.value = false;
      }
    };
    
    const fetchVehicles = async () => {
      if (!formData.value.scheduled_date) return;
      
      try {
        loading.value = true;
        const response = await deliveryService.getVehicles(formData.value.scheduled_date);
        
        if (response.success) {
          vehicles.value = response.data;
        } else {
          showDanger('Error', response.message || 'Failed to load vehicles');
        }
      } catch (error) {
        console.error('Error fetching vehicles:', error);
        showDanger('Error', 'An unexpected error occurred while loading vehicles');
      } finally {
        loading.value = false;
      }
    };
    
    const validateForm = () => {
      errors.value = {};
      
      if (!formData.value.scheduled_date) {
        errors.value.scheduled_date = 'Scheduled date is required';
      }
      
      if (!formData.value.userID) {
        errors.value.userID = 'Driver is required';
      }
      
      if (!formData.value.vehicleID) {
        errors.value.vehicleID = 'Vehicle is required';
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
          showSuccess('Success', 'Delivery created successfully'); // First success modal
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
          showDanger('Error', response.message || 'Failed to create delivery');
        }
      } catch (error) {
        console.error('Error creating delivery:', error);
        showDanger('Error', 'An unexpected error occurred while creating the delivery');
      } finally {
        loading.value = false;
      }
    };
    
    const resetForm = () => {
      formData.value = {
        scheduled_date: '',
        userID: '',
        vehicleID: ''
      };
      errors.value = {};
      drivers.value = [];
      vehicles.value = [];
    };
    
    const showModal = () => {
      resetForm();
      
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
      drivers,
      vehicles,
      errors,
      minDate,
      isFormValid,
      onDateChange,
      validateAndSubmit,
      showModal
    };
  }
};
</script>

<style scoped>
.modal-dialog {
  max-width: 500px;
}

/* Theme colors */
.theme-modal {
  --primary-color: #123524;
  --secondary-color: #EFE3C2;
  --accent-color: #3E7B27;
  --text-color: #333;
  --light-text: #666;
  --border-color: rgba(18, 53, 36, 0.2);
  --light-bg: rgba(239, 227, 194, 0.2);
  --lighter-bg: rgba(239, 227, 194, 0.1);
  
  border: none;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  background-color: #fff;
}

/* Header */
.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
}

.theme-close {
  color: var(--secondary-color);
  opacity: 0.8;
  filter: invert(1) brightness(1.5);
}

.theme-close:hover {
  opacity: 1;
}

/* Body */
.theme-body {
  background-color: #fff;
  color: var(--text-color);
}

.theme-text {
  color: var(--text-color);
}

.theme-spinner {
  color: var(--accent-color);
}

/* Footer */
.theme-footer {
  background-color: var(--light-bg);
  border-top: 1px solid var(--border-color);
}

/* Form elements */
.theme-label {
  font-weight: 500;
  color: var(--primary-color);
}

.theme-input, .theme-select {
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
  border-color: var(--border-color);
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.theme-input:focus, .theme-select:focus {
  border-color: var(--accent-color);
  box-shadow: 0 0 0 0.25rem rgba(62, 123, 39, 0.25);
}

.theme-input:disabled, .theme-select:disabled {
  background-color: var(--lighter-bg);
  opacity: 0.7;
}

/* Buttons */
.theme-btn-primary {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
  transition: all 0.3s ease;
}

.theme-btn-primary:hover {
  background-color: #0a1f16;
  border-color: #0a1f16;
  color: var(--secondary-color);
}

.theme-btn-primary:focus {
  box-shadow: 0 0 0 0.25rem rgba(18, 53, 36, 0.25);
  color: var(--secondary-color);
}

.theme-btn-primary:disabled {
  background-color: rgba(18, 53, 36, 0.6);
  border-color: rgba(18, 53, 36, 0.6);
  color: rgba(239, 227, 194, 0.7);
}

.theme-btn-secondary {
  background-color: #6c757d;
  border-color: #6c757d;
  color: white;
  transition: all 0.3s ease;
}

.theme-btn-secondary:hover {
  background-color: #5a6268;
  border-color: #5a6268;
  color: white;
}

.theme-btn-secondary:focus {
  box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.25);
}

/* Error states */
.text-danger {
  color: #dc3545 !important;
}

/* Hover effects for interactive elements */
.theme-input:hover:not(:disabled), .theme-select:hover:not(:disabled) {
  border-color: var(--accent-color);
}
</style>