<template>
  <div class="modal fade" id="executeModal" tabindex="-1" aria-labelledby="executeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header theme-header">
          <h5 class="modal-title" id="executeModalLabel">Delivery Details</h5>
          <button type="button" class="btn-close theme-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="loading" class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading delivery details...</p>
          </div>
          <div v-else>
            <!-- Delivery Information -->
            <div class="form-group mb-4">
              <label class="form-label fw-bold">Delivery Information</label>
              <div class="card">
                <div class="card-header bg-light">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <span class="badge primary-badge me-2">Delivery #{{ delivery?.deliveryID || 'N/A' }}</span>
                    </div>
                    <div>
                      <span class="badge" :class="getStatusBadgeClass(delivery?.status)">
                        {{ delivery?.status || 'Pending' }}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                      <p class="mb-2"><strong>Scheduled Date:</strong></p>
                      <p class="text-truncate">{{ formatDate(delivery?.scheduled_date) }}</p>
                    </div>
                    <div class="col-md-6 col-lg-4">
                      <p class="mb-2"><strong>Driver:</strong></p>
                      <p class="text-truncate">{{ delivery?.fullname || delivery?.driver?.fullname || 'N/A' }}</p>
                    </div>
                    <div class="col-md-6 col-lg-4">
                      <p class="mb-2"><strong>Vehicle:</strong></p>
                      <p class="text-truncate">{{ delivery?.vehicle_plate || delivery?.vehicle?.vehicle_plate || 'N/A' }}</p>
                    </div>
                    <!-- Removed redundant status display -->
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Routes Information -->
            <div class="form-group mb-4">
              <label class="form-label fw-bold">Routes Information</label>
              <div class="card">
                <div class="card-header bg-light">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <span class="badge primary-badge me-2">Delivery Routes</span>
                    </div>
                    <div>
                      <span class="badge secondary-badge">{{ getRoutesCount() }}</span>
                    </div>
                  </div>
                </div>
                <div class="card-body">
                  <!-- Routes Information -->
                  <div v-if="delivery && delivery.routes && delivery.routes.length > 0">
                    <!-- Timeline style routes display -->
                    <div class="timeline">
                      <div v-for="(route, routeIndex) in delivery.routes" :key="routeIndex" class="route-group mb-4">
                        <!-- Start Location -->
                        <div class="timeline-item">
                          <div class="timeline-badge">
                            <div class="timeline-circle"></div>
                          </div>
                          <div class="timeline-panel">
                            <div class="timeline-heading">
                              <h6 class="timeline-title">Start Location</h6>
                              <span class="badge status-badge">{{ route.start_location.status || 'Pending' }}</span>
                              <button class="btn btn-sm btn-link" @click="toggleLocationItems(route.routeID, 'start')">
                                <i class="fas" :class="isLocationExpanded(route.routeID, 'start') ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                              </button>
                              
                              <!-- Start Button - Only show for first route with pending status -->
                              <button 
                                v-if="routeIndex === 0 && delivery.status === 'pending'" 
                                class="btn btn-sm btn-success ms-2"
                                @click="startDelivery(delivery.deliveryID)"
                              >
                                <i class="fas fa-play me-1"></i> Start
                              </button>
                              
                              <!-- QR Code Scanner - Only show for in-progress deliveries -->
                              <button 
                                v-if="delivery.status === 'in_progress'" 
                                class="btn btn-sm btn-primary ms-2"
                                @click="scanQRCode(route.routeID, 'start')"
                              >
                                <i class="fas fa-qrcode me-1"></i> Scan QR
                              </button>
                            </div>
                            <div class="timeline-body">
                              <p>{{ route.start_location.address }}</p>
                              <p class="location-type">({{ route.start_location.type || 'N/A' }})</p>
                              
                              <!-- Expanded Items Section for Start Location -->
                              <div v-if="isLocationExpanded(route.routeID, 'start')" class="location-items mt-3">
                                <div class="card">
                                  <div class="card-header bg-light">
                                    <strong>Items</strong>
                                  </div>
                                  <div class="card-body p-0">
                                    <div class="table-responsive">
                                      <table class="table table-striped table-hover mb-0">
                                        <thead>
                                          <tr>
                                            <th>Item ID</th>
                                            <th>Name</th>
                                            <th>Quantity</th>
                                            <th>Order ID</th>
                                          </tr>
                                        </thead>
                                        <tbody>
                                          <tr v-for="(item, itemIndex) in getLocationItems(route, 'start')" :key="itemIndex">
                                            <td>{{ item.itemID }}</td>
                                            <td>{{ item.item_name || 'Unknown Item' }}</td>
                                            <td>{{ item.quantity || 1 }}</td>
                                            <td>{{ item.orderID || 'N/A' }}</td>
                                          </tr>
                                        </tbody>
                                      </table>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        
                        <!-- End Location -->
                        <div class="timeline-item">
                          <div class="timeline-badge">
                            <div class="timeline-circle"></div>
                          </div>
                          <div class="timeline-panel">
                            <div class="timeline-heading">
                              <h6 class="timeline-title">End Location</h6>
                              <span class="badge status-badge">{{ route.end_location.status || 'Pending' }}</span>
                              <button class="btn btn-sm btn-link" @click="toggleLocationItems(route.routeID, 'end')">
                                <i class="fas" :class="isLocationExpanded(route.routeID, 'end') ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                              </button>
                              
                              <!-- QR Code Scanner - Only show for in-progress deliveries -->
                              <button 
                                v-if="delivery.status === 'in_progress'" 
                                class="btn btn-sm btn-primary ms-2"
                                @click="scanQRCode(route.routeID, 'end')"
                              >
                                <i class="fas fa-qrcode me-1"></i> Scan QR
                              </button>
                            </div>
                            <div class="timeline-body">
                              <p>{{ route.end_location.address }}</p>
                              <p class="location-type">({{ route.end_location.type || 'N/A' }})</p>
                              
                              <!-- Expanded Items Section for End Location -->
                              <div v-if="isLocationExpanded(route.routeID, 'end')" class="location-items mt-3">
                                <div class="card">
                                  <div class="card-header bg-light">
                                    <strong>Items</strong>
                                  </div>
                                  <div class="card-body p-0">
                                    <div class="table-responsive">
                                      <table class="table table-striped table-hover mb-0">
                                        <thead>
                                          <tr>
                                            <th>Item ID</th>
                                            <th>Name</th>
                                            <th>Quantity</th>
                                            <th>Order ID</th>
                                          </tr>
                                        </thead>
                                        <tbody>
                                          <tr v-for="(item, itemIndex) in getLocationItems(route, 'end')" :key="itemIndex">
                                            <td>{{ item.itemID }}</td>
                                            <td>{{ item.item_name || 'Unknown Item' }}</td>
                                            <td>{{ item.quantity || 1 }}</td>
                                            <td>{{ item.orderID || 'N/A' }}</td>
                                          </tr>
                                        </tbody>
                                      </table>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-center text-muted">
                    <p>No routes information available</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';

export default {
  name: 'ExecuteModalDelivery',
  props: {
    loading: {
      type: Boolean,
      default: false
    },
    delivery: {
      type: Object,
      default: () => null
    }
  },
  emits: ['start-delivery', 'scan-qr-code'],
  data() {
    return {
      modal: null,
      expandedLocations: {}
    };
  },
  methods: {
    showModal() {
      if (!this.modal) {
        this.modal = new Modal(document.getElementById('executeModal'));
      }
      this.modal.show();
    },
    
    hideModal() {
      if (this.modal) {
        this.modal.hide();
      }
    },
    
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    
    getStatusBadgeClass(status) {
      switch (status) {
        case 'pending':
          return 'bg-warning text-dark';
        case 'in_progress':
          return 'bg-info text-white';
        case 'completed':
          return 'bg-success text-white';
        case 'failed':
          return 'bg-danger text-white';
        default:
          return 'bg-secondary text-white';
      }
    },
    
    getRoutesCount() {
      if (!this.delivery || !this.delivery.routes) return 0;
      
      // Handle both array and object formats
      if (Array.isArray(this.delivery.routes)) {
        return this.delivery.routes.length;
      } else if (typeof this.delivery.routes === 'object') {
        return Object.keys(this.delivery.routes).length;
      }
      
      return 0;
    },
    
    // Add a method to safely access items
    getLocationItems(route, locationType) {
      const location = route[`${locationType}_location`];
      if (!location) return [];
      
      // If items is already an array, return it
      if (location.items && Array.isArray(location.items)) {
        return location.items;
      }
      
      // If items is an object, convert to array
      if (location.items && typeof location.items === 'object') {
        return Object.entries(location.items).map(([itemID, item]) => ({
          itemID,
          ...item
        }));
      }
      
      // If no items but checkpoints exist, extract items from checkpoints
      if (location.checkpoints && Array.isArray(location.checkpoints)) {
        const items = [];
        location.checkpoints.forEach(checkpoint => {
          if (checkpoint.items) {
            Object.entries(checkpoint.items).forEach(([itemID, item]) => {
              items.push({
                itemID,
                ...item  // This should already include orderID if it exists
              });
            });
          }
        });
        return items;
      }
      
      return [];
    },
    toggleLocationItems(routeID, locationType) {
      const key = `${routeID}-${locationType}`;
      this.expandedLocations[key] = !this.expandedLocations[key];
      this.$forceUpdate();
    },
    
    isLocationExpanded(routeID, locationType) {
      const key = `${routeID}-${locationType}`;
      return !!this.expandedLocations[key];
    },
    
    startDelivery(deliveryID) {
      this.$emit('start-delivery', deliveryID);
    },
    
    scanQRCode(routeID, locationType) {
      // Implement QR code scanning functionality
      console.log(`Scanning QR code for route ${routeID}, location type: ${locationType}`);
      
      // You can implement a modal with QR scanner here
      // For example:
      this.$emit('scan-qr-code', {
        routeID: routeID,
        locationType: locationType,
        deliveryID: this.delivery.deliveryID
      });
    },
  }
};
</script>

<style scoped>
/* Timeline styling */
.timeline {
  position: relative;
  padding: 20px 0;
  margin-bottom: 20px;
}

.timeline:before {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  left: 20px;
  width: 3px;
  background-color: #3E7B27;
  margin-left: -1.5px;
}

.timeline-item {
  margin-bottom: 20px;
  position: relative;
}

.timeline-badge {
  width: 40px;
  height: 40px;
  position: absolute;
  top: 0;
  left: 0;
  border-radius: 50%;
  text-align: center;
  z-index: 1;
}

.timeline-circle {
  width: 16px;
  height: 16px;
  margin: 12px;
  background-color: #3E7B27;
  border-radius: 50%;
}

.timeline-panel {
  position: relative;
  margin-left: 60px;
  background-color: #fff;
  border-radius: 4px;
  padding: 15px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.timeline-heading {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.timeline-title {
  margin: 0;
  font-weight: 600;
  margin-right: 10px;
}

.status-badge {
  background-color: #3E7B27;
  color: white;
  margin-right: 10px;
}

.location-type {
  color: #666;
  font-size: 0.9rem;
}

/* Card styling */
.card {
  border-color: rgba(18, 53, 36, 0.2);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid rgba(18, 53, 36, 0.2);
}

.primary-badge {
  background-color: #123524;
  color: #EFE3C2;
}

.secondary-badge {
  background-color: #3E7B27;
  color: white;
}

/* Button styling */
.btn-link {
  color: #3E7B27;
  padding: 0 5px;
}

.btn-link:hover {
  color: #123524;
}

/* Table styling */
.table-responsive {
  margin-bottom: 0;
}

.table {
  margin-bottom: 0;
}

/* Theme header */
.theme-header {
  background-color: #123524;
  color: #EFE3C2;
  border-bottom: none;
}

.theme-close {
  filter: invert(1) brightness(1.5);
}

/* Responsive fixes */
.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 100%;
}

.row.g-3 {
  --bs-gutter-y: 1rem;
}

@media (max-width: 767.98px) {
  .timeline-panel {
    padding: 10px;
  }
  
  .timeline-heading {
    flex-wrap: wrap;
  }
  
  .timeline-title {
    width: 100%;
    margin-bottom: 5px;
  }
}

@media (min-width: 768px) and (max-width: 991.98px) {
  .card-body .row > div {
    margin-bottom: 10px;
  }
}
</style>