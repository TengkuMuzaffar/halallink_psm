<template>
  <div class="delivery-management">
    <h1 class="mb-4">Delivery Management</h1>

    <!-- Delivery Stats -->
    <div class="row mb-4">
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard
          title="Pending Assignment"
          :count="deliveryStats.pending"
          icon="fas fa-clock"
          bgColor="bg-warning"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard
          title="In Progress"
          :count="deliveryStats.inProgress"
          icon="fas fa-truck"
          bgColor="bg-info"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard
          title="Completed Today"
          :count="deliveryStats.completedToday"
          icon="fas fa-check-circle"
          bgColor="bg-success"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard
          title="Issues"
          :count="deliveryStats.issues"
          icon="fas fa-exclamation-triangle"
          bgColor="bg-danger"
        />
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="row delivery-tabs mb-4">
      <div class="col-md-6 mb-3">
        <div 
          class="delivery-tab-card" 
          :class="{ active: activeTab === 'assign' }"
          @click="activeTab = 'assign'"
        >
          <div class="tab-icon">
            <i class="fas fa-clipboard-list"></i>
          </div>
          <div class="tab-content">
            <div class="tab-title">Assign Deliveries</div>
            <div class="tab-desc">Create and assign delivery trips to drivers</div>
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-3">
        <div 
          class="delivery-tab-card" 
          :class="{ active: activeTab === 'execute' }"
          @click="activeTab = 'execute'"
        >
          <div class="tab-icon">
            <i class="fas fa-truck"></i>
          </div>
          <div class="tab-content">
            <div class="tab-title">Execute Deliveries</div>
            <div class="tab-desc">Track and manage ongoing deliveries</div>
          </div>
        </div>
      </div>
    </div>

    <!-- <div v-if="error" class="alert alert-danger" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i> {{ error }}
    </div> -->
    
    <!-- Two-column layout for Assign tab -->
    <div v-if="activeTab === 'assign'" class="row">
      <!-- Left column: List of created deliveries -->
      <div class="col-md-4">
        <div class="card">
          <!-- In the Created Deliveries card header -->
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Created Deliveries</h5>
            <div>
              <button class="btn btn-sm btn-success me-2" @click="openCreateDeliveryModal">
                <i class="fas fa-plus"></i>
              </button>
              <button class="btn btn-sm btn-outline-primary" @click="refreshCreatedDeliveries">
                <i class="fas fa-sync-alt"></i>
              </button>
            </div>
          </div>
          <!-- Add selected delivery info -->
          <div v-if="selectedDeliveryID" class="p-2 bg-light border-bottom">
            <div class="d-flex align-items-center">
              <span class="badge bg-success me-2">Selected</span>
              <span>Delivery #{{ selectedDeliveryID }}</span>
            </div>
          </div>
          <div class="card-body p-0">
            <div v-if="loading" class="p-3 text-center">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            <div v-else-if="Object.keys(createdDeliveries).length === 0" class="p-3 text-center">
              <p class="text-muted mb-0">No deliveries found</p>
            </div>
            <ul v-else class="list-group list-group-flush">
              <li v-for="(delivery, index) in createdDeliveries" :key="index" 
                  class="list-group-item"
                  :class="{'active': selectedDeliveryID === delivery.deliveryID}">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center">
                    <div class="form-check me-2">
                      <input class="form-check-input" 
                             type="radio" 
                             :id="'delivery-' + delivery.deliveryID" 
                             name="selectedDelivery" 
                             :value="delivery.deliveryID"
                             :checked="selectedDeliveryID === delivery.deliveryID"
                             @change="selectDelivery(delivery.deliveryID)">
                      <label class="form-check-label" :for="'delivery-' + delivery.deliveryID">
                        <span class="visually-hidden">Select Delivery #{{ delivery.deliveryID }}</span>
                      </label>
                    </div>
                    <div>
                      <div class="fw-bold">Delivery #{{ delivery.deliveryID }}</div>
                      <div class="small text-muted">
                        <span v-if="delivery.from">From: {{ getLocationName(delivery.from.locationID) }}</span>
                        <span v-if="delivery.to"> → To: {{ getLocationName(delivery.to.locationID) }}</span>
                      </div>
                      <div class="small">
                        <span class="badge bg-primary me-1">{{ delivery.status || 'Pending' }} {{ formatDate(delivery.scheduledDate) }}</span>
                      </div>
                    </div>
                  </div>
                  <div>
                    <i class="fas expand-icon" 
                       :class="expandedDeliveries[delivery.deliveryID] ? 'fa-chevron-up' : 'fa-chevron-down'"
                       @click.stop="toggleDeliveryDetails(delivery.deliveryID)"></i>
                  </div>
                </div>
                <!-- Expanded Details Section -->
                <div v-if="expandedDeliveries[delivery.deliveryID]" class="mt-3 delivery-details">
                  <div class="card">
                    <div class="card-body">
                      <div class="row">
                        <div class="col-md-6">
                          <h6>Delivery Information</h6>
                          <p class="mb-1"><strong>Status:</strong> {{ delivery.status || 'Pending' }}</p>
                          <p class="mb-1"><strong>Scheduled Date:</strong> {{ formatDate(delivery.scheduledDate) }}</p>
                          <p class="mb-1"><strong>Driver:</strong> {{ delivery.driver?.fullname || 'Not assigned' }}</p>
                          <p class="mb-1"><strong>Vehicle:</strong> {{ delivery.vehicle?.vehicle_plate || 'Not assigned' }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
            <!-- Simple pagination for created deliveries -->
            <div v-if="Object.keys(createdDeliveries).length > 0" class="d-flex justify-content-center p-2">
              <button class="btn btn-sm btn-outline-secondary me-2" 
                      :disabled="createdDeliveriesPage <= 1"
                      @click="changeCreatedDeliveriesPage(createdDeliveriesPage - 1)">
                <i class="fas fa-chevron-left"></i>
              </button>
              <span class="align-self-center mx-2">Page {{ createdDeliveriesPage }}</span>
              <button class="btn btn-sm btn-outline-secondary ms-2" 
                      :disabled="!hasMoreCreatedDeliveries"
                      @click="changeCreatedDeliveriesPage(createdDeliveriesPage + 1)">
                <i class="fas fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Right column: DeliveryAssignment component -->
      <div class="col-md-8">
        <!-- Update the DeliveryAssignment component -->
        <DeliveryAssignment 
          v-if="activeTab === 'assign'"
          :loading="loading"
          :error="error"
          :locations="locations"
          :selectedLocationID="selectedLocationID"
          :groupedDeliveries="groupedDeliveries"
          :pagination="pagination"
          :isOrderExpanded="isOrderExpanded"
          :calculateTotalMeasurement="calculateTotalMeasurement"
          :selectedDeliveryID="selectedDeliveryID"
          @change-location="handleLocationChange"
          @change-page="changePage"
          @toggle-details="toggleOrderDetails"
          @assign-delivery="assignDelivery"
          @refresh="refreshAssignDeliveries"
        />
      </div>
    </div>
    <!-- Assign Delivery Modal Component -->
    <DeliveryAssignmentModal
      ref="assignmentModal"
      :form="assignmentForm"
      :loading="assignmentLoading"
      :drivers="drivers"
      :vehicles="vehicles"
      :locationInfo="locationInfo"
      :orderInfo="selectedOrderInfo"
      @submit="submitAssignment"
      @validation-error="handleValidationError"
    />
     <!-- Create Delivery Modal Component -->
    <DeliveryFormModal ref="deliveryFormModal" @delivery-created="onDeliveryCreated" />
  </div>
</template>

<script>
import { showModal, closeLoading } from '../utils/modal';
import * as bootstrap from 'bootstrap';
import deliveryService from '../services/deliveryService';
import StatsCard from '../components/ui/StatsCard.vue';
import DeliveryAssignment from '../components/delivery/DeliveryAssignment.vue';
import DeliveryExecution from '../components/delivery/DeliveryExecution.vue';
import DeliveryAssignmentModal from '../components/delivery/DeliveryAssignmentModal.vue';
import DeliveryFormModal from '../components/delivery/DeliveryFormModal.vue';

export default {
  name: 'DeliveryPage',
  components: {
    StatsCard,
    DeliveryAssignment,
    DeliveryExecution,
    DeliveryAssignmentModal,
    DeliveryFormModal
  },
  data() {
    return {
      activeTab: 'assign',
      loading: true,
      error: null,
      groupedDeliveries: {},
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
        from: 0,
        to: 0
      },
      deliveryStats: {
        pending: 0,
        inProgress: 0,
        completedToday: 0,
        issues: 0
      },
      selectedLocationID: '',
      locations: [],
      drivers: [],
      vehicles: [],
      expandedOrders: {},
      assignmentForm: {
        locationID: null,
        orderID: null,
        userID: '',
        vehicleID: '',
        scheduledDate: new Date().toISOString().split('T')[0],
        checkIDs: [],
        fromLocation: null,
        toLocation: null
      },
      locationInfo: {
        from: null,
        to: null
      },
      assignmentLoading: false,
      // Properties for created deliveries
      createdDeliveries: {},
      createdDeliveriesPage: 1,
      createdDeliveriesPerPage: 10,
      hasMoreCreatedDeliveries: false,
      selectedDeliveryID: null,
      selectedOrderInfo: null,
      expandedDeliveries: {} // Add this line to fix the error
    };
  },
  mounted() {
    this.fetchDeliveryAssignmentTrips();
    this.fetchLocationsForAssignment();

    this.fetchDeliveryStats();
    this.fetchCreatedDeliveries();
  },
  methods: {
    async fetchDeliveryAssignmentTrips() {
      try {
        this.loading = true;
        this.error = null;
        
        const response = await deliveryService.getTrips({
          locationID: this.selectedLocationID,
          page: this.pagination.current_page,
          per_page: this.pagination.per_page
        });
        console.log(response);
        if (response.success) {
          this.groupedDeliveries = response.data;
          this.pagination = response.pagination;
        } else {
          this.error = response.message || 'Failed to load delivery data';
        }
      } catch (error) {
        console.error('Error fetching delivery data:', error);
        this.error = 'An unexpected error occurred while loading delivery data';
      } finally {
        this.loading = false;
      }
    },
    openCreateDeliveryModal() {
      // Use the DeliveryFormModal component's method to show the modal
      this.$refs.deliveryFormModal.showModal();
    },
    
    onDeliveryCreated(deliveryData) {
      // Remove the showModal call and just refresh the data
      this.fetchDeliveryAssignmentTrips();
      this.fetchDeliveryStats();
      this.fetchCreatedDeliveries();
    },
    

    async fetchLocationsForAssignment() {
      try {
        const response = await deliveryService.getLocations();
        if (response.success) {
          this.locations = response.data;
          
          // Set default location if none selected
          if (!this.selectedLocationID && this.locations.length > 0) {
            this.selectedLocationID = this.locations[0].locationID;
          }
        } else {
          console.error('Failed to load locations:', response.message);
        }
      } catch (error) {
        console.error('Error fetching locations:', error);
      }
    },
    
    formatDate(dateString) {
      if (!dateString) return '';
      
      // Parse the date string
      const date = new Date(dateString);
      
      // Check if the date is valid
      if (isNaN(date.getTime())) return '';
      
      // Format the date as DD/MM/YYYY
      return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    },
    
    refreshCreatedDeliveries() {
      this.fetchCreatedDeliveries();
    },

    // Add new method for refreshing assign deliveries only
    refreshAssignDeliveries() {
      this.fetchDeliveryAssignmentTrips();
    },
    
    async fetchDeliveryStats() {
      try {
        const response = await deliveryService.getDeliveryStats();
        if (response.success) {
          this.deliveryStats = response.data;
        } else {
          console.error('Failed to load delivery stats:', response.message);
        }
      } catch (error) {
        console.error('Error fetching delivery stats:', error);
      }
    },
    
    async fetchCreatedDeliveries() {
      try {
        const response = await deliveryService.getCreatedDeliveries(
          this.createdDeliveriesPage,
          this.createdDeliveriesPerPage
        );
    
        // Log each property of the response object
        console.log('Response Success:', response.success);
        console.log('Response Data:', response.data);
        console.log('Response Pagination:', response.pagination);
    
        if (response.success) {
          this.createdDeliveries = response.data;
          this.hasMoreCreatedDeliveries = response.pagination && 
                                         response.pagination.current_page < response.pagination.last_page;
        } else {
          console.error('Failed to fetch created deliveries:', response.message);
        }
      } catch (error) {
        console.error('Error fetching created deliveries:', error);
      }
    },
    
    selectDelivery(deliveryID) {
      this.selectedDeliveryID = deliveryID;
      // Refresh the delivery assignment data based on selected delivery
      this.fetchDeliveryAssignmentTrips();
    },
    
    changeCreatedDeliveriesPage(page) {
      if (page < 1 || (page > 1 && !this.hasMoreCreatedDeliveries)) {
        return;
      }
      
      this.createdDeliveriesPage = page;
      this.fetchCreatedDeliveries();
    },
    
    viewDeliveryDetails(delivery) {
      showModal({
        type: 'info',
        title: `Delivery Details - #${delivery.deliveryID}`,
        message: `
          <div>
            <p><strong>From:</strong> ${this.getLocationName(delivery.from?.locationID)}</p>
            <p><strong>To:</strong> ${this.getLocationName(delivery.to?.locationID)}</p>
            <p><strong>Status:</strong> ${delivery.status || 'Pending'}</p>
            <p><strong>Scheduled Date:</strong> ${delivery.scheduledDate}</p>
            <p><strong>Driver:</strong> ${delivery.driver?.name || 'Not assigned'}</p>
            <p><strong>Vehicle:</strong> ${delivery.vehicle?.vehicle_plate || 'Not assigned'}</p>
          </div>
        `,
        buttons: [
          { label: 'Close', type: 'secondary', dismiss: true }
        ]
      });
    },
    
    handleLocationChange(locationID) {
      this.selectedLocationID = locationID;
      this.pagination.current_page = 1;
      this.fetchDeliveryAssignmentTrips();
    },
    
    refreshData() {
      // Remove the fetchDeliveryAssignmentTrips() and fetchCreatedDeliveries() calls
      // Only refresh delivery stats since it's common
      this.fetchDeliveryStats();
      // Reset the selected order info when refreshing data
      this.selectedOrderInfo = null;
    },
    
    
    changePage(page) {
      if (page < 1 || page > this.pagination.last_page) {
        return;
      }
      
      this.pagination.current_page = page;
      this.fetchDeliveryAssignmentTrips();
    },
    
    getPageNumbers() {
      const totalPages = this.pagination.last_page;
      const currentPage = this.pagination.current_page;
      
      if (totalPages <= 5) {
        return Array.from({ length: totalPages }, (_, i) => i + 1);
      }
      
      if (currentPage <= 3) {
        return [1, 2, 3, 4, 5];
      }
      
      if (currentPage >= totalPages - 2) {
        return [totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
      }
      
      return [currentPage - 2, currentPage - 1, currentPage, currentPage + 1, currentPage + 2];
    },
    
    getLocationName(locationID) {
      const location = this.locations.find(loc => loc.locationID == locationID);
      return location ? location.company_address : null;
    },
    
    formatPrice(price) {
      return parseFloat(price).toFixed(2);
    },
    
    calculateTotalMeasurement(items) {
      // Check if items is an array before using reduce
      if (!Array.isArray(items)) {
        console.warn('Items is not an array:', items);
        return 0;
      }
      
      const total = items.reduce((sum, item) => {
        // Only add if measurement type is kg or similar
        if (item.measurement_type && item.measurement_type.toLowerCase().includes('kg')) {
          return sum + (parseFloat(item.measurement_value || 0) * parseFloat(item.quantity || 1));
        }
        return sum;
      }, 0);
      
      return total.toFixed(2);
    },
    
    /**
     * Toggle the expanded state of an order
     */
    toggleOrderDetails(locationID, orderID) {
      const key = `${locationID}-${orderID}`;
      if (!this.expandedOrders) {
        this.expandedOrders = {};
      }
      this.expandedOrders[key] = !this.isOrderExpanded(locationID, orderID);
    },
    
    /**
     * Check if an order is expanded
     */
    isOrderExpanded(locationID, orderID) {
      if (!this.expandedOrders) {
        return false;
      }
      const key = `${locationID}-${orderID}`;
      return !!this.expandedOrders[key];
    },
    
    assignDelivery(locationID, orderID) {
      // Get the order data for this order
      const orderData = this.groupedDeliveries[locationID]?.orders[orderID];
      
      if (!orderData) {
        console.error('Order data not found for', locationID, orderID);
        return;
      }
      
      console.log('Order data:', orderData);
      
      // Set the selected order info for the modal
      this.selectedOrderInfo = {
        orderID: orderID,
        items: orderData.items || [],
        checkpoints: []
      };
      
      // If we have checkpoints in the order data, add them to the selectedOrderInfo
      if (orderData.checkpoints) {
        this.selectedOrderInfo.checkpoints = orderData.checkpoints;
      } else {
        // Try to determine the trip type based on the items and their locations
        const fromLocation = orderData.from?.locationID;
        const toLocation = orderData.to?.locationID;
        
        // Create synthetic checkpoints based on from/to locations
        if (fromLocation && toLocation) {
          // Check if this is likely a broiler to slaughterhouse trip (arrange_number 1-2)
          // or a slaughterhouse to customer trip (arrange_number 3-4)
          const isBroilerToSlaughterhouse = orderData.items?.some(item => 
            item.locationID === fromLocation && item.slaughterhouse_locationID === toLocation
          );
          
          if (isBroilerToSlaughterhouse) {
            this.selectedOrderInfo.checkpoints = [
              { arrange_number: 1, locationID: fromLocation },
              { arrange_number: 2, locationID: toLocation }
            ];
          } else {
            this.selectedOrderInfo.checkpoints = [
              { arrange_number: 3, locationID: fromLocation },
              { arrange_number: 4, locationID: toLocation }
            ];
          }
        }
      }
      
      // Extract checkIDs from items - handle both data structures
      let checkIDs = [];
      
      if (orderData.items && Array.isArray(orderData.items)) {
        // New structure: orderData has an items array property
        checkIDs = orderData.items
          .filter(item => item.checkID)
          .map(item => item.checkID);
      } else if (Array.isArray(orderData)) {
        // Old structure: orderData is directly an array of items
        checkIDs = orderData
          .filter(item => item.checkID)
          .map(item => item.checkID);
      } else {
        console.warn('Unable to extract checkIDs from order data:', orderData);
      }
      
      // Get the fromLocationID and toLocationID directly from the orderData
      let fromLocationID = null;
      let toLocationID = null;
      
      // Check if orderData has from and to properties with locationID
      if (orderData.from && orderData.from.locationID) {
        fromLocationID = orderData.from.locationID;
      }
      
      if (orderData.to && orderData.to.locationID) {
        toLocationID = orderData.to.locationID;
      }
      
      // Create the item object with all necessary data
      const item = {
        locationID,
        orderID,
        checkIDs,
        fromLocationID,
        toLocationID
      };
      
      // Call openAssignmentModal with the item
      this.openAssignmentModal(item);
    },
    
    handleValidationError(message) {
      // Use showModal for validation errors
      showModal({
        type: 'warning',
        title: 'Validation Error',
        message: message || 'Please check your input and try again'
      });
    },
    /**
     * Toggle the expanded state of a delivery
     */
    toggleDeliveryDetails(deliveryID) {
      // Initialize if not already
      if (!this.expandedDeliveries) {
        this.expandedDeliveries = {};
      }
      
      // Toggle the expanded state for this delivery
      this.expandedDeliveries[deliveryID] = !this.expandedDeliveries[deliveryID];
      
      // If we're expanding this delivery, collapse all others
      if (this.expandedDeliveries[deliveryID]) {
        const deliveryIDStr = deliveryID.toString();
        Object.keys(this.expandedDeliveries).forEach(key => {
          if (key !== deliveryIDStr) {
            this.expandedDeliveries[key] = false;
          }
        });
      }
    },

      


    async submitAssignment(formData) {
      try {
        this.assignmentLoading = true;
        
        // Include from and to location information in the request
        const response = await deliveryService.assignDelivery(formData);
        
        if (response.success) {
          // Reset the selected order info
          this.selectedOrderInfo = null;
          
          // Use showModal instead of modalUtil.showSuccess
          showModal({
            type: 'success',
            title: 'Success',
            message: response.message || 'Delivery assigned successfully',
            buttons: [
              { 
                label: 'OK', 
                type: 'success', 
                dismiss: true,
                onClick: () => {
                  this.$refs.assignmentModal.hide();
                  this.fetchDeliveryAssignmentTrips();
                  this.fetchDeliveryStats();
                  this.fetchCreatedDeliveries();
                }
              }
            ]
          });
        } else {
          // Use showModal for error messages too
          showModal({
            type: 'danger',
            title: 'Error',
            message: response.message || 'Failed to assign delivery'
          });
        }
      } catch (error) {
        console.error('Error assigning delivery:', error);
        // Use showModal for caught errors
        showModal({
          type: 'danger',
          title: 'Error',
          message: error.message || 'An unexpected error occurred'
        });
      } finally {
        this.assignmentLoading = false;
      }
    },
    
    async openAssignmentModal(item) {
      try {
        console.log('Opening assignment modal for item:', item);
        
        // Get the location data directly from the item
        const fromLocation = item.fromLocationID || item.locationID;
        const toLocation = item.toLocationID;
        
        // Set the initial form data
        this.assignmentForm = {
          locationID: item.locationID,
          orderID: item.orderID,
          userID: '',
          vehicleID: '',
          scheduledDate: new Date().toISOString().split('T')[0],
          checkIDs: item.checkIDs || [],
          // Add from and to location directly from the item
          fromLocation: fromLocation,
          toLocation: toLocation,
          deliveryID: this.selectedDeliveryID
        };
        
        // We still set locationInfo for backward compatibility
        this.locationInfo = {
          from: { locationID: fromLocation },
          to: { locationID: toLocation }
        };
        
        // Show the modal
        this.$refs.assignmentModal.show();
      } catch (error) {
        console.error('Error preparing assignment modal:', error);
        showModal({
          type: 'danger',
          title: 'Error',
          message: 'Failed to prepare assignment modal'
        });
      }
    }
  }
}
</script>

<style scoped>
.list-group-item.active {
  background-color: #123524;
  border-color: #123524;
  color: white;
}

.list-group-item.active .text-muted {
  color: rgba(255, 255, 255, 0.7) !important;
}

.list-group-item:hover:not(.active) {
  background-color: rgba(0, 0, 0, 0.03);
}

.expand-icon {
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  transition: background-color 0.2s;
}

.expand-icon:hover {
  background-color: rgba(0, 0, 0, 0.1);
}

.list-group-item.active .expand-icon:hover {
  background-color: rgba(255, 255, 255, 0.2);
}

/* Remove the default hover effect on active items */
.list-group-item.active:hover {
  background-color: #123524;
  border-color: #123524;
}

/* Style the selected delivery indicator */
.p-2.bg-light.border-bottom {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.badge.bg-success {
  background-color: #123524 !important;
}
</style>

<style scoped>
/* New tab styling */
.delivery-tabs {
  margin-top: 1rem;
}

.delivery-tab-card {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  border-radius: 8px;
  background-color: #f8f9fa;
  border: 2px solid #e9ecef;
  transition: all 0.3s ease;
  cursor: pointer;
  height: 100%;
}

.delivery-tab-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  border-color: #123524;
}

.delivery-tab-card.active {
  background-color: #123524;
  color: white;
  border-color: #123524;
  box-shadow: 0 4px 10px rgba(18, 53, 36, 0.3);
}

.tab-icon {
  font-size: 1.25rem;
  margin-right: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 40px;
  height: 40px;
  background-color: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  flex-shrink: 0;
}

.delivery-tab-card.active .tab-icon {
  background-color: rgba(255, 255, 255, 0.3);
}

.tab-content {
  flex: 1;
  overflow: hidden;
}

.tab-title {
  margin-bottom: 0.25rem;
  font-weight: 600;
  font-size: 1rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tab-desc {
  opacity: 0.8;
  font-size: 0.8rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (min-width: 576px) {
  .delivery-tab-card {
    padding: 1rem;
  }
  
  .tab-icon {
    font-size: 1.5rem;
    min-width: 50px;
    height: 50px;
  }
  
  .tab-title {
    font-size: 1.1rem;
  }
  
  .tab-desc {
    font-size: 0.85rem;
  }
}

@media (min-width: 992px) {
  .delivery-tab-card {
    padding: 1.25rem;
  }
  
  .tab-icon {
    font-size: 1.75rem;
    min-width: 55px;
    height: 55px;
  }
}
.delivery-details {
  background-color: #f8f9fa;
  border-radius: 0.25rem;
  transition: all 0.3s ease;
}

.list-group-item {
  cursor: pointer;
}

.list-group-item:hover {
  background-color: #f8f9fa;
}

.fa-chevron-up, .fa-chevron-down {
  transition: transform 0.3s ease;
}

</style>


