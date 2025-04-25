<template>
  <div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="assignmentModalLabel">Assign Delivery</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="loading" class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Processing assignment...</p>
          </div>
          <form v-else @submit.prevent="validateAndSubmit">
            <div class="row mb-3">
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label for="driver" class="form-label">Driver</label>
                  <select id="driver" v-model="formData.userID" class="form-select" required>
                    <option value="">Select Driver</option>
                    <option v-for="driver in drivers" :key="driver.userID" :value="driver.userID">
                      {{ driver.name }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label for="vehicle" class="form-label">Vehicle</label>
                  <select id="vehicle" v-model="formData.vehicleID" class="form-select" required>
                    <option value="">Select Vehicle</option>
                    <option v-for="vehicle in vehicles" :key="vehicle.vehicleID" :value="vehicle.vehicleID">
                      {{ vehicle.vehicle_plate }} ({{ vehicle.vehicle_type }})
                    </option>
                  </select>
                </div>
              </div>
            </div>
            
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
            </div>
            
            <div class="row mb-3">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">From Location</label>
                  <div class="form-control bg-light">
                    {{ getLocationName(formData.fromLocation) }}
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">To Location</label>
                  <div class="form-control bg-light">
                    {{ getLocationName(formData.toLocation) }}
                  </div>
                </div>
              </div>
            </div>
            
            <div class="form-group mb-3">
              <label class="form-label">Checkpoints</label>
              <div class="card">
                <div class="card-body p-0">
                  <div v-if="formData.checkIDs && formData.checkIDs.length > 0" class="list-group list-group-flush">
                    <div v-for="(checkID, index) in formData.checkIDs" :key="index" class="list-group-item">
                      <div class="d-flex justify-content-between align-items-center">
                        <div>
                          <i class="fas fa-check-circle me-2 text-success"></i>
                          Checkpoint #{{ checkID }}
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="p-3 text-center text-muted">
                    No checkpoints selected
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Order and Item Information -->
            <div v-if="orderInfo && orderInfo.items && orderInfo.items.length > 0" class="form-group mb-3">
              <label class="form-label">Order Information</label>
              <div class="card">
                <div class="card-header bg-light">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <strong>Order #{{ orderInfo.orderID }}</strong>
                    </div>
                    <div>
                      <span class="badge bg-primary">{{ getTripType() }}</span>
                    </div>
                  </div>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                      <thead>
                        <tr>
                          <th>Item</th>
                          <th>Quantity</th>
                          <th>Measurement</th>
                          <th>Price</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, itemIndex) in orderInfo.items" :key="itemIndex">
                          <td>{{ item.item_name || 'Unknown Item' }}</td>
                          <td>{{ item.quantity || 1 }}</td>
                          <td>{{ item.measurement_value || 0 }} {{ item.measurement_type || 'kg' }}</td>
                          <td>RM {{ formatPrice(item.price) }}</td>
                        </tr>
                      </tbody>
                      <tfoot v-if="orderInfo.items.length > 0">
                        <tr>
                          <td colspan="3" class="text-end"><strong>Total:</strong></td>
                          <td><strong>RM {{ calculateTotal(orderInfo.items) }}</strong></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="form-group mb-3">
              <label for="notes" class="form-label">Notes (Optional)</label>
              <textarea id="notes" v-model="formData.notes" class="form-control" rows="3"></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="validateAndSubmit"
            :disabled="loading"
          >
            <i class="fas fa-save me-1"></i> Assign Delivery
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
    form: {
      type: Object,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    },
    drivers: {
      type: Array,
      default: () => []
    },
    vehicles: {
      type: Array,
      default: () => []
    },
    locationInfo: {
      type: Object,
      default: () => ({
        from: null,
        to: null
      })
    },
    orderInfo: {
      type: Object,
      default: () => null
    }
  },
  data() {
    return {
      modal: null,
      formData: {
        userID: '',
        vehicleID: '',
        scheduledDate: new Date().toISOString().split('T')[0],
        notes: '',
        locationID: null,
        orderID: null,
        checkIDs: [],
        fromLocation: null,
        toLocation: null,
        deliveryID: null
      }
    };
  },
  computed: {
    minDate() {
      return new Date().toISOString().split('T')[0];
    }
  },
  watch: {
    form: {
      handler(newVal) {
        this.formData = { ...this.formData, ...newVal };
      },
      deep: true
    }
  },
  mounted() {
    this.modal = new Modal(document.getElementById('assignmentModal'));
  },
  methods: {
    show() {
      this.modal.show();
    },
    
    hide() {
      this.modal.hide();
    },
    
    getLocationName(locationID) {
      // This is a simplified version - you might want to fetch location names from a store or service
      return locationID ? `Location #${locationID}` : 'Not specified';
    },
    
    formatPrice(price) {
      return parseFloat(price || 0).toFixed(2);
    },
    
    calculateTotal(items) {
      if (!items || !Array.isArray(items)) return '0.00';
      
      const total = items.reduce((sum, item) => {
        const itemPrice = parseFloat(item.price || 0);
        const quantity = parseInt(item.quantity || 1);
        return sum + (itemPrice * quantity);
      }, 0);
      
      return total.toFixed(2);
    },
    
    getTripType() {
      if (!this.formData.checkIDs || this.formData.checkIDs.length === 0) {
        return 'Unknown Trip';
      }
      
      // Check if we have arrange_number information in the orderInfo
      if (this.orderInfo && this.orderInfo.checkpoints) {
        const checkpoints = this.orderInfo.checkpoints;
        const hasArrange1or2 = checkpoints.some(cp => cp.arrange_number === 1 || cp.arrange_number === 2);
        const hasArrange3or4 = checkpoints.some(cp => cp.arrange_number === 3 || cp.arrange_number === 4);
        
        if (hasArrange1or2 && !hasArrange3or4) {
          return 'Broiler to Slaughterhouse';
        } else if (hasArrange3or4) {
          return 'Slaughterhouse to Customer';
        }
      }
      
      // Fallback logic based on checkpoint IDs
      // This is a simplified version - in a real app, you would fetch checkpoint details
      return 'Delivery Trip';
    },
    
    validateAndSubmit() {
      // Basic validation
      if (!this.formData.userID) {
        this.$emit('validation-error', 'Please select a driver');
        return;
      }
      
      if (!this.formData.vehicleID) {
        this.$emit('validation-error', 'Please select a vehicle');
        return;
      }
      
      if (!this.formData.scheduledDate) {
        this.$emit('validation-error', 'Please select a scheduled date');
        return;
      }
      
      if (!this.formData.fromLocation) {
        this.$emit('validation-error', 'From location is required');
        return;
      }
      
      if (!this.formData.toLocation) {
        this.$emit('validation-error', 'To location is required');
        return;
      }
      
      if (!this.formData.checkIDs || this.formData.checkIDs.length === 0) {
        this.$emit('validation-error', 'No checkpoints selected');
        return;
      }
      
      // Submit the form
      this.$emit('submit', this.formData);
    }
  }
}
</script>