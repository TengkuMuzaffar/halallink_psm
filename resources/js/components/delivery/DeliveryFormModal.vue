<template>
  <div class="modal fade" id="deliveryFormModal" tabindex="-1" aria-labelledby="deliveryFormModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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
              <label for="scheduled_date" class="form-label">Scheduled Date</label>
              <input 
                type="date" 
                id="scheduled_date" 
                v-model="formData.scheduled_date" 
                class="form-control"
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
              <label for="driver" class="form-label">Driver</label>
              <select id="driver" v-model="formData.userID" class="form-select" :disabled="!formData.scheduled_date" required>
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
              <label for="vehicle" class="form-label">Vehicle</label>
              <select id="vehicle" v-model="formData.vehicleID" class="form-select" :disabled="!formData.scheduled_date" required>
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
import { ref, computed, onMounted, watch } from 'vue';
import deliveryService from '../../services/deliveryService';
import { showModal, showSuccess, showDanger } from '../../utils/modal';
import * as bootstrap from 'bootstrap';

export default {
  name: 'DeliveryFormModal',
  
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
        console.log(response); // Add this line to log the response to the console
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

.form-label {
  font-weight: 500;
}

.form-select, .form-control {
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
}
</style>