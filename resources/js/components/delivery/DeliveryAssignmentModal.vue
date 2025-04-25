<template>
  <div class="modal fade" id="assignDeliveryModal" tabindex="-1" aria-labelledby="assignDeliveryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="assignDeliveryModalLabel">Assign Delivery</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <!-- Location Information (Hidden) -->
            <input type="hidden" v-model="form.fromLocation">
            <input type="hidden" v-model="form.toLocation">
            
            <!-- Driver Selection -->
            <div class="mb-3">
              <label for="driverSelect" class="form-label">Select Driver</label>
              <select id="driverSelect" class="form-select" v-model="form.userID" required>
                <option value="">-- Select Driver --</option>
                <option v-for="driver in drivers" :key="driver.userID" :value="driver.userID">
                  {{ driver.fullname || driver.name }} ({{ driver.tel_number || driver.email }})
                </option>
              </select>
            </div>
            
            <!-- Vehicle Selection -->
            <div class="mb-3">
              <label for="vehicleSelect" class="form-label">Select Vehicle</label>
              <select id="vehicleSelect" class="form-select" v-model="form.vehicleID" required>
                <option value="">-- Select Vehicle --</option>
                <option v-for="vehicle in vehicles" :key="vehicle.vehicleID" :value="vehicle.vehicleID">
                  {{ vehicle.vehicle_plate }} ({{ vehicle.vehicle_load_weight }} kg)
                </option>
              </select>
            </div>
            
            <!-- Scheduled Date -->
            <div class="mb-3">
              <label for="scheduledDate" class="form-label">Scheduled Date</label>
              <input 
                type="date" 
                id="scheduledDate" 
                class="form-control" 
                v-model="form.scheduledDate" 
                :min="getTodayDate()"
                required
              >
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" @click="submitForm" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            Assign
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';

export default {
  name: 'DeliveryAssignmentModal',
  props: {
    drivers: {
      type: Array,
      required: true
    },
    vehicles: {
      type: Array,
      required: true
    },
    initialFormData: {
      type: Object,
      default: () => ({
        locationID: null,
        orderID: null,
        userID: '',
        vehicleID: '',
        scheduledDate: new Date().toISOString().split('T')[0],
        fromLocation: null,
        toLocation: null,
        checkIDs: []
      })
    },
    locationInfo: {
      type: Object,
      default: () => ({
        from: null,
        to: null
      })
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      modal: null,
      form: { ...this.initialFormData }
    };
  },
  watch: {
    initialFormData: {
      handler(newVal) {
        this.form = { ...newVal };
      },
      deep: true
    }
  },
  methods: {
    show() {
      if (!this.modal) {
        const modalEl = document.getElementById('assignDeliveryModal');
        this.modal = new Modal(modalEl);
      }
      this.modal.show();
    },
    hide() {
      if (this.modal) {
        this.modal.hide();
      }
    },
    submitForm() {
      // Validate form
      if (!this.form.userID || !this.form.vehicleID || !this.form.scheduledDate) {
        this.$emit('validation-error', 'Please fill in all required fields');
        return;
      }
      
      // Make sure fromLocation and toLocation are included in the form data
      const formData = {
        ...this.form,
        fromLocation: this.form.fromLocation || this.form.locationID,
        toLocation: this.form.toLocation
      };
      
      console.log('Submitting form data:', formData);
      this.$emit('submit', formData);
    },
    getTodayDate() {
      return new Date().toISOString().split('T')[0];
    }
  }
};
</script>