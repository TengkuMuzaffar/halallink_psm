<template>
  <div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header theme-header">
          <h5 class="modal-title" id="assignmentModalLabel">Assign Delivery</h5>
          <button type="button" class="btn-close theme-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="loading" class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Processing assignment...</p>
          </div>
          <div v-else>
            <!-- Order Information -->
            <div v-if="orderInfo && orderInfo.items && orderInfo.items.length > 0" class="form-group mb-4">
              <label class="form-label fw-bold">Order Information</label>
              <div class="card">
                <div class="card-header bg-light">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <strong>Order #{{ orderInfo.orderID }}</strong>
                    </div>
                    <div>
                      <span class="badge ms-2" :class="orderInfo.status === 'paid' ? 'bg-warning' : 'bg-secondary'">
                        {{ orderInfo.status === 'paid' ? 'Pending Delivery' : orderInfo.status }}
                      </span>
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
                          <th>Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, itemIndex) in orderInfo.items" :key="itemIndex">
                          <td>{{ item.item_name || 'Unknown Item' }}</td>
                          <td>{{ item.quantity || 1 }}</td>
                          <td>{{ item.measurement_value || 0 }} {{ item.measurement_type || 'kg' }}</td>
                          <td>RM {{ formatPrice(item.price) }}</td>
                          <td>RM {{ formatPrice(item.total_price || (item.price * item.quantity)) }}</td>
                        </tr>
                      </tbody>
                      <tfoot v-if="orderInfo.items.length > 0">
                        <tr>
                          <td colspan="4" class="text-end"><strong>Total:</strong></td>
                          <td><strong>RM {{ calculateTotal(orderInfo.items) }}</strong></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Trip Information -->
            <div class="form-group mb-4">
              <label class="form-label fw-bold">Trip Information</label>
              <div class="card">
                <div class="card-header bg-light">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <span class="badge primary-badge me-2">Delivery #{{ formData.deliveryID || 'New' }}</span>
                    </div>
                    <div>
                      <span class="badge secondary-badge">{{ getTripType() }}</span>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <!-- Trip Route Information -->
                  <div v-if="orderInfo && orderInfo.trips && orderInfo.trips.length > 0">
                    <div class="trip-route-header mb-3">
                      <strong>Trip Route</strong> ({{ orderInfo.trips.length }} {{ orderInfo.trips.length > 1 ? 'trips' : 'trip' }})
                    </div>
                    
                    <!-- Timeline style trip display -->
                    <div class="timeline">
                      <!-- If multiple trips with same end location -->
                      <div v-if="orderInfo.trips.length > 1 && hasSameEndLocation()">
                        <!-- Show all starting locations -->
                        <div v-for="(trip, tripIndex) in orderInfo.trips" :key="tripIndex" class="timeline-item">
                          <div class="timeline-badge">
                            <div class="timeline-circle"></div>
                          </div>
                          <div class="timeline-panel">
                            <div class="timeline-heading">
                              <h6 class="timeline-title">Trip #{{ trip.tripID }}</h6>
                              <span class="badge status-badge">{{ trip.status }}</span>
                            </div>
                            <div class="timeline-body">
                              <p>{{ trip.startLocationName }}</p>
                              <p class="location-type">({{ trip.startLocationType }})</p>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Show final destination -->
                        <div class="timeline-item">
                          <div class="timeline-badge">
                            <div class="timeline-circle"></div>
                          </div>
                          <div class="timeline-panel">
                            <div class="timeline-heading">
                              <h6 class="timeline-title">Destination</h6>
                            </div>
                            <div class="timeline-body">
                              <p>{{ orderInfo.trips[0].endLocationName }}</p>
                              <p class="location-type">({{ orderInfo.trips[0].endLocationType }})</p>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <!-- If single trip or different end locations -->
                      <div v-else>
                        <div v-for="(trip, tripIndex) in orderInfo.trips" :key="tripIndex">
                          <!-- Start location -->
                          <div class="timeline-item">
                            <div class="timeline-badge">
                              <div class="timeline-circle"></div>
                            </div>
                            <div class="timeline-panel">
                              <div class="timeline-heading">
                                <h6 class="timeline-title">Trip #{{ trip.tripID }}</h6>
                                <span class="badge status-badge">{{ trip.status }}</span>
                              </div>
                              <div class="timeline-body">
                                <p>{{ trip.startLocationName }}</p>
                                <p class="location-type">({{ trip.startLocationType }})</p>
                              </div>
                            </div>
                          </div>
                          
                          <!-- End location -->
                          <div class="timeline-item">
                            <div class="timeline-badge">
                              <div class="timeline-circle"></div>
                            </div>
                            <div class="timeline-panel">
                              <div class="timeline-heading">
                                <h6 class="timeline-title">Destination</h6>
                              </div>
                              <div class="timeline-body">
                                <p>{{ trip.endLocationName }}</p>
                                <p class="location-type">({{ trip.endLocationType }})</p>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-center text-muted">
                    <p>No trip information available</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button 
            type="button" 
            class="btn theme-btn-primary" 
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
        locationID: null,
        orderID: null,
        checkIDs: [],
        fromLocation: null,
        toLocation: null,
        deliveryID: null,
        trips: [] // Add trips array to store trip data
      }
    };
  },
  watch: {
    form: {
      handler(newForm) {
        // Copy form data to local formData
        if (newForm) {
          this.formData = {
            ...this.formData,
            ...newForm
          };
          
          // If orderInfo contains trips, store them in formData
          if (this.orderInfo && this.orderInfo.trips && this.orderInfo.trips.length > 0) {
            this.formData.trips = [...this.orderInfo.trips];
          }
        }
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
    formatPrice(price) {
      return parseFloat(price || 0).toFixed(2);
    },
    calculateTotal(items) {
      if (!items || !items.length) return '0.00';
      const total = items.reduce((sum, item) => {
        return sum + parseFloat(item.total_price || (item.price * item.quantity) || 0);
      }, 0);
      return this.formatPrice(total);
    },
    getTripType() {
      if (!this.orderInfo || !this.orderInfo.trips || this.orderInfo.trips.length === 0) {
        return 'Unknown';
      }
      
      // Get the first trip to determine type
      const firstTrip = this.orderInfo.trips[0];
      
      if (firstTrip.startLocationType === 'supplier' && firstTrip.endLocationType === 'slaughterhouse') {
        return 'Supplier to Slaughterhouse';
      } else if (firstTrip.startLocationType === 'slaughterhouse' && firstTrip.endLocationType === 'distributor') {
        return 'Slaughterhouse to Distributor';
      } else if (firstTrip.startLocationType === 'distributor' && firstTrip.endLocationType === 'retailer') {
        return 'Distributor to Retailer';
      } else {
        return `${firstTrip.startLocationType} to ${firstTrip.endLocationType}`;
      }
    },
    hasSameEndLocation() {
      if (!this.orderInfo || !this.orderInfo.trips || this.orderInfo.trips.length <= 1) {
        return false;
      }
      
      const firstEndLocation = this.orderInfo.trips[0].endLocationID;
      return this.orderInfo.trips.every(trip => trip.endLocationID === firstEndLocation);
    },
    validateAndSubmit() {
      // Prepare data for submission - simplified to only include deliveryID and trips
      const submissionData = {
        deliveryID: this.formData.deliveryID,
        trips: this.formData.trips || [] // Include trips array in submission
      };
      
      // Validate required fields
      if (!submissionData.deliveryID) {
        this.$emit('validation-error', 'No delivery selected. Please select a delivery first.');
        return;
      }
      
      if (!submissionData.trips || submissionData.trips.length === 0) {
        this.$emit('validation-error', 'No trips available to assign.');
        return;
      }
      
      // Emit submit event with the prepared data
      this.$emit('submit', submissionData);
    },
    // Add a method to set the deliveryID
    setDeliveryID(deliveryID) {
      if (deliveryID) {
        this.formData.deliveryID = deliveryID;
        console.log('DeliveryID set in modal:', deliveryID);
      }
    },
    
    // Update the submit method to use the deliveryID
    async submitForm() {
      try {
        this.loading = true;
        this.error = null;
        
        // Ensure we have the deliveryID
        if (!this.formData.deliveryID) {
          this.error = 'No delivery selected. Please select a delivery first.';
          return;
        }
        
        // Prepare the data for submission
        const submitData = {
          deliveryID: this.formData.deliveryID,
          trips: this.orderInfo.trips || []
        };
        
        // Call the service to assign the delivery
        const response = await deliveryService.assignDelivery(submitData);
        
        if (response.success) {
          // Close the modal
          this.$emit('assignment-complete', response.data);
          this.closeModal();
        } else {
          this.error = response.message || 'Failed to assign delivery';
        }
      } catch (error) {
        console.error('Error assigning delivery:', error);
        this.error = error.message || 'An unexpected error occurred';
      } finally {
        this.loading = false;
      }
    },
  }
};
</script>

<style scoped>
/* Color theme */
/* It's generally better to define CSS variables on a component's root element or a specific class like .theme-modal 
   rather than :root in scoped styles, but we'll add specific styles for the header below. */
:root {
  --primary-color: #123524;
  --secondary-color: #EFE3C2;
  --accent-color: #3E7B27;
  --text-color: #666;
}

/* Badge styles */
.primary-badge {
  background-color: #123524;
  color: white;
}

.secondary-badge {
  background-color: #3E7B27;
  color: white;
}

.status-badge {
  background-color: #00BCD4;
  color: white;
}

/* Button theme */
.theme-btn-primary {
  background-color: #123524;
  border-color: #123524;
  color: #EFE3C2;
  transition: all 0.3s ease;
}

.theme-btn-primary:hover {
  background-color: #0a1f16;
  border-color: #0a1f16;
  color: #EFE3C2;
}

.theme-btn-primary:focus {
  box-shadow: 0 0 0 0.25rem rgba(18, 53, 36, 0.25);
  color: #EFE3C2;
}

.theme-btn-primary:disabled {
  background-color: rgba(18, 53, 36, 0.6);
  border-color: rgba(18, 53, 36, 0.6);
  color: rgba(239, 227, 194, 0.7);
}

/* NEW STYLES FOR MODAL HEADER */
.theme-header {
  background-color: #123524;
  color: #EFE3C2;
  border-bottom: none; /* To match the style of DeliveryFormModal */
}

.theme-header .modal-title {
  color: #EFE3C2; /* Ensure the title text color is explicitly set */
}

.theme-close {
  /* For Bootstrap 5's SVG close button, filter is used to change color */
  filter: invert(1) brightness(1.5) sepia(1) hue-rotate(180deg) saturate(5); /* Adjusted to make it appear as #EFE3C2 */
  /* A simpler filter if the above is too complex or doesn't render #EFE3C2 well:
     filter: brightness(0) invert(1); then adjust opacity or use background-image with SVG fill.
     Given #EFE3C2 is a light color, making the default dark 'X' light:
  */
   filter: brightness(0) saturate(100%) invert(91%) sepia(15%) saturate(548%) hue-rotate(349deg) brightness(99%) contrast(91%); /* This filter aims for #EFE3C2 */

}

.theme-close:hover {
  opacity: 1;
}
/* END NEW STYLES FOR MODAL HEADER */

/* Timeline styles */
.timeline {
  position: relative;
  padding: 20px 0;
}

.timeline:before {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  left: 15px;
  width: 2px;
  background-color: #3E7B27;
  z-index: 0;
}

.timeline-item {
  position: relative;
  margin-bottom: 30px;
}

.timeline-item:last-child {
  margin-bottom: 0;
}

.timeline-badge {
  position: absolute;
  top: 0;
  left: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.timeline-circle {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background-color: #3E7B27;
  border: 2px solid white;
}

.timeline-panel {
  position: relative;
  margin-left: 45px;
  padding: 15px;
  background-color: #f8f9fa;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.timeline-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.timeline-title {
  margin: 0;
  color: #123524;
  font-weight: 600;
}

.timeline-body p {
  margin-bottom: 5px;
  color: #666;
}

.location-type {
  color: #666;
  font-size: 0.9em;
}
</style>
