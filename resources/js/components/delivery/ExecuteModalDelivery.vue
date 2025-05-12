<template>
  <div class="modal fade" id="executeModal" tabindex="-1" aria-labelledby="executeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
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
                  <div v-if="delivery && delivery.routes && Object.keys(delivery.routes).length > 0">
                    <!-- Timeline style routes display -->
                    <div class="timeline">
                      <div v-for="(route, routeIndex) in Object.entries(delivery.routes)" :key="routeIndex" class="route-group mb-4">
                        <!-- Start Locations - Loop through all start locations -->
                        <div v-for="(startLocation, startLocationID) in route[1].start_locations" :key="startLocationID" class="timeline-item">
                          <div class="timeline-badge">
                            <div class="timeline-circle"></div>
                          </div>
                          <div class="timeline-panel">
                            <div class="timeline-heading">
                              <div class="d-flex flex-wrap align-items-start gap-2 w-100">
                                <div class="location-header-content">
                                  <div class="d-flex flex-wrap align-items-center gap-2">
                                    <h6 class="timeline-title mb-0">Start Location #{{ startLocationID }}</h6>
                                    <span class="badge" :class="getStatusBadgeClass(startLocation.location_status)">
                                      {{ startLocation.location_status || 'Pending' }}
                                    </span>
                                  </div>
                                  <div class="location-actions mt-2 d-md-none">
                                    <div class="d-flex gap-2">
                                      <button class="btn btn-sm btn-link p-1" @click="toggleLocationItems(route[0], `start-${startLocationID}`)">
                                        <i class="fas" :class="isLocationExpanded(route[0], `start-${startLocationID}`) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                      </button>
                                      
                                      <button 
                                        v-if="routeIndex === 0 && startLocationID === Object.keys(route[1].start_locations)[0] && delivery.status === 'pending'" 
                                        class="btn btn-sm btn-success"
                                        @click="startDeliveryAndRefresh(delivery.deliveryID)"
                                      >
                                        <i class="fas fa-play me-1"></i> <span class="d-none d-sm-inline">Start</span>
                                      </button>
                                      
                                      <button 
                                        v-if="canScanLocation(route[1], 'start', startLocationID)" 
                                        class="btn btn-sm btn-primary"
                                        @click="scanQRCode(startLocation.checkpoints)"
                                      >
                                        <i class="fas fa-qrcode me-1"></i> <span class="d-none d-sm-inline">Scan QR</span>
                                      </button>
                                    </div>
                                  </div>
                                </div>
                                <div class="ms-auto location-actions d-none d-md-block">
                                  <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-link p-1" @click="toggleLocationItems(route[0], `start-${startLocationID}`)">
                                      <i class="fas" :class="isLocationExpanded(route[0], `start-${startLocationID}`) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    </button>
                                    
                                    <button 
                                      v-if="routeIndex === 0 && startLocationID === Object.keys(route[1].start_locations)[0] && delivery.status === 'pending'" 
                                      class="btn btn-sm btn-success"
                                      @click="startDeliveryAndRefresh(delivery.deliveryID)"
                                    >
                                      <i class="fas fa-play me-1"></i> <span>Start</span>
                                    </button>
                                    
                                    <button 
                                      v-if="canScanLocation(route[1], 'start', startLocationID)" 
                                      class="btn btn-sm btn-primary"
                                      @click="scanQRCode(startLocation.checkpoints)"
                                    >
                                      <i class="fas fa-qrcode me-1"></i> <span>Scan QR</span>
                                    </button>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="timeline-body">
                              <p>{{ startLocation.company_address }}</p>
                              <p class="location-type">(Location ID: {{ startLocation.locationID }})</p>
                              
                              <!-- Expanded Items Section for Start Location -->
                              <div v-if="isLocationExpanded(route[0], `start-${startLocationID}`)" class="location-items mt-3">
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
                                          <template v-for="(checkpoint, checkpointIndex) in startLocation.checkpoints" :key="checkpointIndex">
                                            <template v-for="(itemData, itemID) in checkpoint.items" :key="`${checkpointIndex}-${itemID}`">
                                              <tr>
                                                <td>{{ itemID }}</td>
                                                <td>{{ itemData.item_name || 'Unknown Item' }}</td>
                                                <td>{{ itemData.quantity || 1 }}</td>
                                                <td>{{ itemData.orderID || 'N/A' }}</td>
                                              </tr>
                                            </template>
                                          </template>
                                        </tbody>
                                      </table>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        
                        <!-- End Locations - Loop through all end locations -->
                        <div v-for="(endLocation, endLocationID) in route[1].end_locations" :key="endLocationID" class="timeline-item">
                          <div class="timeline-badge">
                            <div class="timeline-circle"></div>
                          </div>
                          <div class="timeline-panel">
                            <div class="timeline-heading">
                              <h6 class="timeline-title">End Location #{{ endLocationID }}</h6>
                              <span class="badge" :class="getStatusBadgeClass(endLocation.location_status)">
                                {{ endLocation.location_status || 'Pending' }}
                              </span>
                              <button class="btn btn-sm btn-link" @click="toggleLocationItems(route[0], `end-${endLocationID}`)">
                                <i class="fas" :class="isLocationExpanded(route[0], `end-${endLocationID}`) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                              </button>
                              
                              <!-- QR Code Scanner - Only show if this location can be scanned -->
                              <button 
                                v-if="canScanLocation(route[1], 'end', endLocationID)" 
                                class="btn btn-sm btn-primary"
                                @click="scanQRCode(endLocation.checkpoints)"
                              >
                                <i class="fas fa-qrcode me-1"></i> Scan QR
                              </button>
                            </div>
                            <div class="timeline-body">
                              <p>{{ endLocation.company_address }}</p>
                              <p class="location-type">(Location ID: {{ endLocation.locationID }})</p>
                              
                              <!-- Expanded Items Section for End Location -->
                              <div v-if="isLocationExpanded(route[0], `end-${endLocationID}`)" class="location-items mt-3">
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
                                          <template v-for="(checkpoint, checkpointIndex) in endLocation.checkpoints" :key="checkpointIndex">
                                            <template v-for="(itemData, itemID) in checkpoint.items" :key="`${checkpointIndex}-${itemID}`">
                                              <tr>
                                                <td>{{ itemID }}</td>
                                                <td>{{ itemData.item_name || 'Unknown Item' }}</td>
                                                <td>{{ itemData.quantity || 1 }}</td>
                                                <td>{{ itemData.orderID || 'N/A' }}</td>
                                              </tr>
                                            </template>
                                          </template>
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
        <div class="modal-footer theme-footer">
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
  emits: ['start-delivery', 'scan-qr-code', 'refresh'],
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
    getRoutesCount() {
      if (!this.delivery || !this.delivery.routes) return 0;
      return Object.keys(this.delivery.routes).length;
    },
    startDeliveryAndRefresh(deliveryID) {
      // Emit the start-delivery event to the parent component
      this.$emit('start-delivery', deliveryID);
      
      // Hide the modal after starting the delivery
      this.hideModal();
      
      // Emit a refresh event to the parent component to refresh the table
      this.$emit('refresh');
    },
    getStatusBadgeClass(status) {
      if (!status) return 'bg-secondary';
      
      switch(status.toLowerCase()) {
        case 'complete':
          return 'bg-success';
        case 'in_progress':
          return 'bg-warning';
        case 'pending':
        default:
          return 'bg-secondary';
      }
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      
      const date = new Date(dateString);
      if (isNaN(date.getTime())) return dateString;
      
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    toggleLocationItems(routeID, locationKey) {
      if (!this.expandedLocations[routeID]) {
        this.expandedLocations[routeID] = {};
      }
      
      this.expandedLocations[routeID][locationKey] = !this.expandedLocations[routeID][locationKey];
      
      // Force re-render
      this.$forceUpdate();
    },
    isLocationExpanded(routeID, locationKey) {
      return this.expandedLocations[routeID] && this.expandedLocations[routeID][locationKey];
    },
    /** 
     * Determine if a location can be scanned 
     * A location can be scanned if all previous locations are complete 
     */
    canScanLocation(route, locationType, locationID) {
      // For start locations 
      if (locationType === 'start') { 
        // Get all start location IDs as an array 
        const startLocationIDs = Object.keys(route.start_locations); 
        const currentIndex = startLocationIDs.indexOf(locationID.toString()); 
        
        // If it's the first location, always allow scanning 
        if (currentIndex === 0) return true; 
        
        // Check if all previous locations are complete 
        for (let i = 0; i < currentIndex; i++) { 
          const prevLocationID = startLocationIDs[i]; 
          if (route.start_locations[prevLocationID].location_status !== 'complete') { 
            return false; 
          } 
        } 
        return true; 
      } 
      
      // For end locations, all start locations must be complete 
      if (locationType === 'end') { 
        // Check if all start locations are complete 
        for (const startLocationID in route.start_locations) { 
          if (route.start_locations[startLocationID].location_status !== 'complete') { 
            return false; 
          } 
        } 
        
        // Get all end location IDs as an array 
        const endLocationIDs = Object.keys(route.end_locations); 
        const currentIndex = endLocationIDs.indexOf(locationID.toString()); 
        
        // If it's the first end location, allow scanning 
        if (currentIndex === 0) return true; 
        
        // Check if all previous end locations are complete 
        for (let i = 0; i < currentIndex; i++) { 
          const prevLocationID = endLocationIDs[i]; 
          if (route.end_locations[prevLocationID].location_status !== 'complete') { 
            return false; 
          } 
        } 
        return true; 
      } 
      
      return false; 
    },
    /**
     * Handle QR code scanning
     */
    scanQRCode(checkpoints) { 
      // Extract checkpoint IDs 
      const checkpointIDs = checkpoints.map(cp => cp.checkID); 
      
      // Encode the checkpoint array as JSON
      const checkpointParam = encodeURIComponent(JSON.stringify(checkpointIDs));
      
      // Open QR scanner page with checkpoint IDs 
      const qrScannerUrl = `/qr-scanner?checkpoints=${checkpointParam}&deliveryID=${this.delivery.deliveryID}`; 
      
      // Open in new window/tab or redirect based on your preference
      window.open(qrScannerUrl, '_blank'); 
      
      // Alternatively, use router to navigate to QR scanner page
      // this.$router.push(qrScannerUrl);
    }
  }
};
</script>

<style scoped>
.timeline {
  position: relative;
  padding: 20px 0;
}

.timeline-item {
  position: relative;
  margin-bottom: 30px;
}

.timeline-badge {
  position: absolute;
  top: 0;
  left: 0;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  text-align: center;
  background-color: #f8f9fa;
  border: 1px solid rgba(18, 53, 36, 0.2);
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
/* Modal footer styling */
.theme-footer {
  background-color: rgba(239, 227, 194, 0.1);
  border-top: 1px solid var(--border-color);
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

/* Responsive Design Improvements */
@media (max-width: 767px) {
  .modal-dialog {
    margin: 0.5rem;
  }
  
  .timeline-panel {
    margin-left: 45px;
    padding: 10px;
  }
  
  .timeline-badge {
    width: 30px;
    height: 30px;
  }
  
  .timeline-circle {
    width: 12px;
    height: 12px;
    margin: 9px;
  }
  
  .timeline-title {
    font-size: 0.9rem;
  }
  
  .badge {
    font-size: 0.75rem;
  }
  
  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }
  
  .location-type {
    font-size: 0.8rem;
  }
  
  .table {
    font-size: 0.8rem;
  }
  
  .table td, .table th {
    padding: 0.5rem;
  }
  
  .card-body {
    padding: 0.75rem;
  }
  
  .form-group {
    margin-bottom: 1rem;
  }
}

/* Small screen optimizations */
@media (max-width: 575px) {
  .modal-dialog {
    margin: 0;
    max-height: 100vh;
  }
  
  .modal-content {
    border-radius: 0;
    min-height: 100vh;
  }
  
  .timeline-panel {
    margin-left: 35px;
    padding: 8px;
  }
  
  .timeline-badge {
    width: 24px;
    height: 24px;
  }
  
  .timeline-circle {
    width: 10px;
    height: 10px;
    margin: 7px;
  }
  
  .card {
    border-radius: 0;
  }
  
  .table-responsive {
    margin: 0 -0.75rem;
  }
  
  .table td, .table th {
    padding: 0.4rem;
    font-size: 0.75rem;
  }
  
  /* Improve button spacing on mobile */
  .d-flex.flex-wrap.gap-2 {
    gap: 0.25rem !important;
  }
  
  /* Make buttons more compact on mobile */
  .btn-sm {
    padding: 0.2rem 0.4rem;
    font-size: 0.7rem;
  }
}

/* Medium screen optimizations */
@media (min-width: 576px) and (max-width: 991px) {
  .timeline-panel {
    margin-left: 50px;
  }
  
  .card-body {
    padding: 1rem;
  }
  
  .table td, .table th {
    padding: 0.6rem;
  }
}

/* Improve table responsiveness */
.table-responsive {
  -webkit-overflow-scrolling: touch;
  max-width: 100%;
}

/* Improve button wrapping */
.btn-group-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

/* Improve text truncation */
.text-truncate {
  max-width: 100%;
}

/* Improve modal scrolling */
.modal-dialog-scrollable {
  max-height: calc(100vh - 1rem);
}

/* Improve timeline spacing */
.timeline-item:last-child {
  margin-bottom: 0;
}

/* Improve card margins on mobile */
@media (max-width: 575px) {
  .card {
    margin-bottom: 0.5rem;
  }
  
  .form-group.mb-4 {
    margin-bottom: 0.75rem !important;
  }
}
</style>