<template>
  <div class="delivery-management">
    <h1 class="mb-4">Delivery Management</h1>

    <!-- Delivery Stats - Moved above tabs -->
    <div class="row mb-4">
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard
          title="Pending Assignment"
          :count="deliveryStats.pending"
          icon="fas fa-clock"
          bg-color="bg-warning"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard
          title="In Progress"
          :count="deliveryStats.inProgress"
          icon="fas fa-truck-loading"
          bg-color="bg-info"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard
          title="Completed Today"
          :count="deliveryStats.completedToday"
          icon="fas fa-check-circle"
          bg-color="bg-success"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard
          title="Issues Reported"
          :count="deliveryStats.issues"
          icon="fas fa-exclamation-triangle"
          bg-color="bg-danger"
        />
      </div>
    </div>

    <!-- Navigation Tabs - Moved below stats cards -->
    <div class="delivery-tabs mb-4">
      <div class="row g-2">
        <div class="col-6">
          <div 
            class="delivery-tab-card" 
            :class="{ 'active': activeTab === 'assign' }"
            @click="activeTab = 'assign'"
          >
            <div class="tab-icon">
              <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="tab-content">
              <h5 class="tab-title">Assign Deliveries</h5>
              <p class="tab-desc mb-0">Manage new orders</p>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div 
            class="delivery-tab-card" 
            :class="{ 'active': activeTab === 'execute' }"
            @click="activeTab = 'execute'"
          >
            <div class="tab-icon">
              <i class="fas fa-truck"></i>
            </div>
            <div class="tab-content">
              <h5 class="tab-title">Execute Deliveries</h5>
              <p class="tab-desc mb-0">Track operations</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i> {{ error }}
    </div>
    
    <!-- Assign Deliveries Tab Content -->
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
      @change-location="handleLocationChange"
      @change-page="changePage"
      @toggle-details="toggleOrderDetails"
      @assign-delivery="assignDelivery"
      @refresh="refreshData"
    />
    
    <!-- Execute Deliveries Tab Content -->
    <DeliveryExecution 
      v-else-if="activeTab === 'execute'"
    />

    <!-- Assign Delivery Modal Component -->
    <DeliveryAssignmentModal
      :drivers="drivers"
      :vehicles="vehicles"
      :initialFormData="assignmentForm"
      :locationInfo="locationInfo"
      :loading="assignmentLoading"
      @submit="submitAssignment"
      @validation-error="handleValidationError"
      ref="assignmentModal"
    />
  </div>
</template>

<script>
import StatsCard from '../components/ui/StatsCard.vue';
import Badge from '../components/ui/Badge.vue';
import DeliveryAssignment from '../components/delivery/DeliveryAssignment.vue';
import DeliveryExecution from '../components/delivery/DeliveryExecution.vue';
import DeliveryAssignmentModal from '../components/delivery/DeliveryAssignmentModal.vue';
import deliveryService from '../services/deliveryService';
import { showModal } from '../utils/modal';
import api from '../utils/api';

export default {
  name: 'DeliveryPage',
  components: {
    StatsCard,
    Badge,
    DeliveryAssignment,
    DeliveryExecution,
    DeliveryAssignmentModal
  },
  // In the data() function of DeliveryPage.vue, add the locationInfo property
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
        scheduledDate: new Date().toISOString().split('T')[0]
      },
      locationInfo: {
      from: null,
      to: null
    },
      assignmentLoading: false
    };
  },
  mounted() {
    this.fetchDeliveryData();
    this.fetchLocations();
    this.fetchDrivers();
    this.fetchVehicles();
    this.fetchDeliveryStats();
  },
  methods: {
    async fetchDeliveryData() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await deliveryService.getDeliveries(
          this.pagination.current_page,
          this.pagination.per_page,
          this.selectedLocationID
        );
        console.log('API Response:', response);
        if (response.success) {
          this.groupedDeliveries = response.data;
          this.pagination = response.pagination;
        } else {
          throw new Error(response.message || 'Failed to fetch delivery data');
        }
      } catch (error) {
        console.error('Error fetching delivery data:', error);
        this.error = error.message || 'An error occurred while fetching delivery data';
      } finally {
        this.loading = false;
      }
    },
    
    async fetchLocations() {
      try {
        const response = await deliveryService.getLocations();
        if (response.success) {
          this.locations = response.data;
        }
      } catch (error) {
        console.error('Error fetching locations:', error);
      }
    },
    
    async fetchDrivers() {
      try {
        const response = await deliveryService.getDrivers();
        // console.log('Drivers API response:', response);
        
        if (Array.isArray(response)) {
          // If response is directly an array of drivers
          this.drivers = response;
          console.log('Drivers loaded:', this.drivers.length, this.drivers);
        } else if (response && response.data) {
          // If response has the expected structure with data property
          this.drivers = response.data;
          console.log('Drivers loaded:', this.drivers.length, this.drivers);
        } else {
          console.error('Failed to fetch drivers:', response);
          this.drivers = []; // Ensure drivers is at least an empty array
        }
      } catch (error) {
        console.error('Error fetching drivers:', error);
        this.drivers = []; // Ensure drivers is at least an empty array
      }
    },
    
    async fetchVehicles() {
      try {
        const response = await deliveryService.getVehicles();
        if (response.vehicles) {
          this.vehicles = response.vehicles.data;
        } else {
          console.error('Failed to fetch vehicles');
        }
      } catch (error) {
        console.error('Error fetching vehicles:', error);
      }
    },
    
    async fetchDeliveryStats() {
      try {
        const response = await deliveryService.getDeliveryStats();
        if (response.success) {
          this.deliveryStats = response.data;
        }
      } catch (error) {
        console.error('Error fetching delivery stats:', error);
        this.deliveryStats = {
          pending: 0,
          inProgress: 0,
          completedToday: 0,
          issues: 0
        };
      }
    },
    
    refreshData() {
      this.fetchDeliveryData();
      this.fetchDeliveryStats();
    },
    
    handleLocationChange(locationID) {
      this.selectedLocationID = locationID;
      this.fetchDeliveryData();
    },
    
    changePage(page) {
      if (page < 1 || page > this.pagination.last_page) {
        return;
      }
      
      this.pagination.current_page = page;
      this.fetchDeliveryData();
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
      return location ? location.location_name : null;
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
    
    calculateOrderTotal(items) {
      // Check if items is an array before using reduce
      if (!Array.isArray(items)) {
        console.warn('Items is not an array:', items);
        return 0;
      }
      return items.reduce((total, item) => total + parseFloat(item.total_price || 0), 0);
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
    
    // Remove the duplicate assignDelivery method and use openAssignmentModal instead
    assignDelivery(locationID, orderID) {
      // Get the order data for this order
      const orderData = this.groupedDeliveries[locationID]?.orders[orderID];
      
      if (!orderData) {
        console.error('Order data not found for', locationID, orderID);
        return;
      }
      
      console.log('Order data:', orderData); // Log the order data to check its structure
      
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
    
    // Keep only one handleValidationError method
    handleValidationError(message) {
      // Use showModal for validation errors
      showModal({
        type: 'warning',
        title: 'Validation Error',
        message: message || 'Please check your input and try again'
      });
    },
    
    // Keep only one submitAssignment method
    async submitAssignment(formData) {
      try {
        this.assignmentLoading = true;
        
        // Include from and to location information in the request
        const response = await deliveryService.assignDelivery(formData);
        
        if (response.success) {
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
                  this.fetchDeliveryData(); // Changed from fetchDeliveries to fetchDeliveryData
                  this.fetchDeliveryStats(); // Also refresh the stats
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
    
    handleValidationError(message) {
      // Use showModal for validation errors
      showModal({
        type: 'warning',
        title: 'Validation Error',
        message: message || 'Please check your input and try again'
      });
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
          toLocation: toLocation
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
    },
    
    
    handleValidationError(message) {
      // Use showModal for validation errors
      showModal({
        type: 'warning',
        title: 'Validation Error',
        message: message || 'Please check your input and try again'
      });
    },
    
    // ... other methods ...
  }
}
</script>

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
  
  .tab-title {
    font-size: 1.2rem;
  }
}

/* Keep existing styles below */
.nav-tabs .nav-link.active {
  color: #fff;
  background-color: #123524;
  border-color: #123524;
}

.nav-tabs .nav-link:not(.active):hover {
  border-color: #e9ecef #e9ecef #dee2e6;
  color: #123524;
}

.nav-tabs .nav-link {
  color: #495057;
}

.card {
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  border: none;
  margin-bottom: 20px;
}

.card-header {
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  padding: 15px 20px;
}

.card-body {
  padding: 20px;
}

.table th {
  font-weight: 600;
  color: #495057;
}

.pagination {
  margin-bottom: 0;
}

.page-link {
  color: #007bff;
  border-radius: 4px;
  margin: 0 2px;
}

.page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}

@media (max-width: 768px) {
  .card-header, .card-body {
    padding: 15px;
  }
  
  .tab-icon {
    width: 45px;
    height: 45px;
    font-size: 1.5rem;
    margin-right: 1rem;
  }
  
  .tab-content h4 {
    font-size: 1.1rem;
  }
  
  .tab-content p {
    font-size: 0.8rem;
  }
}

.order-details {
  background-color: #f8f9fa;
  border-radius: 4px;
  padding: 10px;
  transition: all 0.3s ease;
}

/* Add animation for expanding/collapsing */
.order-details-enter-active, .order-details-leave-active {
  transition: max-height 0.3s ease, opacity 0.3s ease;
  max-height: 500px;
  overflow: hidden;
}
.order-details-enter, .order-details-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>