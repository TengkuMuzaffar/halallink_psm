<template>
  <div class="delivery-management">
    <h1 class="mb-4">Delivery Management</h1>

    <!-- Delivery Stats -->
    <StatsCard :stats="deliveryStats" />

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

    <!-- Display error message for assign tab -->
    <!-- <div v-if="activeTab === 'assign' && assignError" class="alert alert-danger mb-4" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i> {{ assignError }}
    </div> -->

    <!-- Display error message for execute tab -->
    <!-- <div v-if="activeTab === 'execute' && executeError" class="alert alert-danger mb-4" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i> {{ executeError }}
    </div> -->
    
    <!-- Two-column layout for Assign tab -->
    <div v-if="activeTab === 'assign'" class="row">
      <!-- Left column: List of created deliveries -->
      <div class="col-lg-4 col-md-12 mb-4">
        <CreatedDeliveriesList
          :deliveries="createdDeliveries"
          :loading="createdDeliveriesLoading"
          :selectedDeliveryID="selectedDeliveryID"
          :expandedDeliveries="expandedDeliveries"
          :currentPage="createdDeliveriesPage"
          :pagination="createdDeliveriesPagination"
          :hasMorePages="hasMoreCreatedDeliveries"
          @create-delivery="openCreateDeliveryModal"
          @refresh="refreshCreatedDeliveries"
          @select="selectDelivery"
          @deselect="deselectDelivery"
          @toggle-details="toggleDeliveryDetails"
          @change-page="changeCreatedDeliveriesPage"
        />
      </div>
      
      <!-- Right column: DeliveryAssignment component -->
      <div class="col-lg-8 col-md-12">
        <DeliveryAssignment 
          v-if="activeTab === 'assign'"
          :loading="assignDeliveriesLoading"
          :error="assignError"
          :trips="trips"
          :pagination="tripsPagination"
          :selectedDeliveryID="selectedDeliveryID"
          :activePhase="activePhase"
          @refresh="fetchTrips"
          @assign-trip="assignTrip"
          @change-phase="handlePhaseChange"
          @change-page="handleTripsPageChange"
        />
      </div>
    </div>

    <!-- Add this section for Execute tab -->
    <div v-if="activeTab === 'execute'">
      <DeliveryExecution 
        ref="deliveryExecution"
        :deliveries="executionDeliveries"
        :loading="executeDeliveriesLoading"
        :error="executeError"
        @refresh="refreshExecutionDeliveries"
        @filter-changed="handleExecutionFilterChange"
      />
    </div>
 
  
     <!-- Create Delivery Modal Component -->
    <DeliveryFormModal ref="deliveryFormModal" @delivery-created="onDeliveryCreated" />
  </div>
</template>

<script>
import { showModal, closeLoading } from '../utils/modal';
import * as bootstrap from 'bootstrap';
import deliveryService from '../services/deliveryService';
import StatsCard from '../components/delivery/StatsCard.vue'; // Updated import
import DeliveryAssignment from '../components/delivery/DeliveryAssignment.vue';
import DeliveryExecution from '../components/delivery/DeliveryExecution.vue';
import DeliveryFormModal from '../components/delivery/DeliveryFormModal.vue';
import CreatedDeliveriesList from '../components/delivery/CreatedDeliveriesList.vue';

export default {
  name: 'DeliveryPage',
  components: {
    StatsCard,
    DeliveryAssignment,
    DeliveryExecution,
    DeliveryFormModal,
    CreatedDeliveriesList
  },
  data() {
    return {
      activeTab: 'assign',
      loading: true,
      createdDeliveriesLoading: false,
      assignDeliveriesLoading: false,
      executeDeliveriesLoading: false,
      // Separate error states for each tab
      pendingSelectionID: null, // Store the ID 
      assignError: null,
      executeError: null,
      trips: [],
      tripsPagination: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
        from: 0,
        to: 0
      },
      executionDeliveries: {},
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
        from: 0,
        to: 0
      },
      createdDeliveriesPagination: {
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
        unassignedTrips: 0
      },
      selectedLocationID: '',
      locations: [],
      drivers: [],
      vehicles: [],
      expandedOrders: {},
      assignmentForm: {
        deliveryID: null,
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
      createdDeliveriesPerPage: 3, // Change from 10 to 3
      hasMoreCreatedDeliveries: false,
      selectedDeliveryID: null,
      selectedOrderInfo: null,
      expandedDeliveries: {}, // Add this line to fix the error
      deliveryFormModal: null,
      // Add active phase for filtering
      activePhase: 1
    };
  },
  mounted() {
    this.fetchTrips();
    this.fetchDeliveryStats();
    this.fetchCreatedDeliveries();
  },
  
  created() {
    // Get selectionID from URL query parameters
    const selectionID = this.$route.query.selectionID;
    // console.log('Selection ID From Parameter:', selectionID);
    if (selectionID) {
      this.activeTab = 'execute';
      this.pendingSelectionID = selectionID;
    }
  },

  watch: {
      activeTab: {
        immediate: true,
        handler(newTab) {
          if (newTab === 'execute') {
            // Pass filters when switching to execute tab
            this.refreshExecutionDeliveries({}).then(() => {
              // If we have a pending selection, trigger the modal
              if (this.pendingSelectionID) {
                // Convert pendingSelectionID to number for comparison
                const selectionIDNum = Number(this.pendingSelectionID);
                const delivery = this.executionDeliveries.find(
                  d => d.deliveryID === selectionIDNum
                );
                // console.log('Delivery Found:', delivery);
                if (delivery) {
                  this.$refs.deliveryExecution.viewDelivery(delivery);
                  this.pendingSelectionID = null; // Clear the pending ID
                }
              }
            });
          }
        }
      }
    },
  methods: {
    async fetchTrips() {
      try {
        this.assignDeliveriesLoading = true;
        this.assignError = null;
        
        const response = await deliveryService.getTrips({
          locationID: this.selectedLocationID,
          page: this.tripsPagination.current_page,
          per_page: this.tripsPagination.per_page,
          phase: this.activePhase
        });
        
        if (response.success) {
          this.trips = response.data;
          // console.log('Fetched Trips:', this.trips);
          // Update pagination but preserve current_page
          const currentPage = this.tripsPagination.current_page;
          this.tripsPagination = {
            ...response.pagination,
            current_page: currentPage
          };
        } else {
          this.assignError = 'Failed to fetch trips';
        }
      } catch (error) {
        console.error('Error fetching trips:', error);
        this.assignError = error.message || 'An error occurred while fetching trips';
      } finally {
        this.assignDeliveriesLoading = false;
      }
    },
    handlePhaseChange(phase) {
      this.activePhase = phase;
      this.fetchTrips();
    },

    handleTripsPageChange(page) {
      // console.log('Changing page to:', page);
      this.tripsPagination.current_page = page;
      this.fetchTrips();
    },

    async assignTrip(trip) {
      try {
        if (!this.selectedDeliveryID) {
          this.assignError = 'Please select a delivery first';
          return;
        }

        const response = await deliveryService.assignSingleTrip({
          deliveryID: this.selectedDeliveryID,
          tripID: trip.tripID
        });

        if (response && response.success) {
          await this.fetchTrips();
          await this.refreshCreatedDeliveries();
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
                  this.fetchTrips();
                  this.fetchDeliveryStats();
                  this.fetchCreatedDeliveries();
                }
              }
            ]
          });
          // this.$toast.success('Trip assigned successfully');
        } else {
          this.assignError = (response && response.message) || 'Failed to assign trip';
        }
      } catch (error) {
        console.error('Error assigning trip:', error);
        this.assignError = error.message || 'An error occurred while assigning trip';
      }
    },
    // Add this new method
    deselectDelivery() {
      // console.log('Deselecting delivery, current selectedDeliveryID:', this.selectedDeliveryID);
      this.selectedDeliveryID = null;
      // console.log('After deselection, selectedDeliveryID:', this.selectedDeliveryID);
      // Use the correct method name
      this.fetchTrips();
    },
    
    onDeliveryCreated() {
      // Refresh both created deliveries and assignable deliveries
      this.refreshCreatedDeliveries();
      this.refreshAssignDeliveries();
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
    
    async fetchCreatedDeliveries() {
      try {
        this.createdDeliveriesLoading = true;
        this.assignError = null; // Reset assign error
        
        const response = await deliveryService.getCreatedDeliveries(
          this.createdDeliveriesPage,
          3
        );
        
        if (response.success) {
          this.createdDeliveries = response.data;
          this.hasMoreCreatedDeliveries = response.pagination.current_page < response.pagination.last_page;
          this.createdDeliveriesPagination = response.pagination;
        } else {
          this.assignError = response.message || 'Failed to fetch created deliveries';
        }
      } catch (error) {
        console.error('Error fetching created deliveries:', error);
        this.assignError = 'An error occurred while fetching deliveries';
      } finally {
        this.createdDeliveriesLoading = false;
      }
      
      this.fetchDeliveryStats();
    },
    
    
    
    selectDelivery(deliveryID) {
      this.selectedDeliveryID = deliveryID;
      // Refresh the delivery assignment data based on selected delivery
      this.fetchTrips();
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
      this.fetchTrips();
    },
    
    refreshData() {
      // Only refresh delivery stats since it's common to all views
      this.fetchDeliveryStats();
      // Reset the selected order info when refreshing data
      this.selectedOrderInfo = null;
      
      // Add this condition to refresh execution deliveries when on that tab
      if (this.activeTab === 'execute') {
        this.refreshExecutionDeliveries();
      }
    },
    
    // Method to refresh only created deliveries
    async refreshCreatedDeliveries() {
      try {
        this.createdDeliveriesLoading = true;
        this.assignError = null; // Reset assign error
        
        const response = await deliveryService.getCreatedDeliveries(
          this.createdDeliveriesPage,
          3
        );
        
        if (response.success) {
          this.createdDeliveries = response.data;
          this.hasMoreCreatedDeliveries = response.pagination.current_page < response.pagination.last_page;
          this.createdDeliveriesPagination = response.pagination;
        } else {
          this.assignError = response.message || 'Failed to fetch created deliveries';
        }
      } catch (error) {
        console.error('Error fetching created deliveries:', error);
        this.assignError = 'An error occurred while fetching deliveries';
      } finally {
        this.createdDeliveriesLoading = false;
      }
      
      this.fetchDeliveryStats();
    },

    // Method to refresh only assign deliveries
    async refreshAssignDeliveries() {
      try {
        this.assignDeliveriesLoading = true;
        this.assignError = null; // Reset assign error
        
        const response = await deliveryService.getTrips({
          locationID: this.selectedLocationID,
          page: this.tripsPagination.current_page,
          per_page: this.tripsPagination.per_page,
          phase: this.activePhase // Add phase parameter to API request
        });
        // console.log('API Response Assign Deliveries:', JSON.stringify(response, null, 4));
        if (response.success) {
          this.groupedDeliveries = response.data;
          this.tripsPagination = response.pagination;
        } else {
          this.assignError = response.message || 'Failed to load delivery data';
        }
      } catch (error) {
        console.error('Error fetching delivery data:', error);
        this.assignError = 'An unexpected error occurred while loading delivery data';
      } finally {
        this.assignDeliveriesLoading = false;
      }
      
      this.fetchDeliveryStats();
    },
    
    changePage(page) {
      if (page < 1 || page > this.pagination.last_page) {
        return;
      }
      
      this.pagination.current_page = page;
      this.fetchTrips();
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
    getOrderInfo(locationID, orderID) {
      if (!this.groupedDeliveries[locationID] || !this.groupedDeliveries[locationID].orders[orderID]) {
        return null;
      }
      return this.groupedDeliveries[locationID].orders[orderID];
    },
    
    // Add this new method
    getLocationInfo(locationID, orderID) {
      const orderInfo = this.getOrderInfo(locationID, orderID);
      if (!orderInfo) {
        return {
          from: null,
          to: null
        };
      }
      
      return {
        from: orderInfo.from || null,
        to: orderInfo.to || null
      };
    },
    async assignDelivery(locationID, orderID) {
      // console.log('assignDelivery called with:', { locationID, orderID });
      // console.log('groupedDeliveries structure:', JSON.stringify(this.groupedDeliveries, null, 2));
      // console.log('Selected location data:', this.groupedDeliveries[locationID]);
      
      try {
        // Reset the assignment form
        this.assignmentForm = {
          locationID: locationID,
          orderID: orderID,
          deliveryID: this.selectedDeliveryID,
          scheduledDate: new Date().toISOString().split('T')[0],
          checkIDs: [],
          fromLocation: null,
          toLocation: null
        };
        
        // console.log('Assignment form initialized:', this.assignmentForm);
        
        // Instead of using getLocationInfo which doesn't exist, directly set location info
        this.locationInfo = {
          from: null,
          to: null
        };
        
        // Try to get location info from the grouped deliveries if available
        if (this.groupedDeliveries[locationID]) {
          const locationData = this.groupedDeliveries[locationID];
          // console.log('Location data found:', locationData);
          
          // Find the order in the location's orders
          if (locationData.orders && locationData.orders[orderID]) {
            const orderData = locationData.orders[orderID];
            // console.log('Order data found:', orderData);
            
            // Set the location info
            this.locationInfo = {
              from: orderData.from || locationData.from || null,
              to: orderData.to || locationData.to || null
            };
            
            // Set the order info
            this.selectedOrderInfo = orderData;
          } else {
            // console.log('Order not found in location data');
          }
        } else {
          // console.log('Location not found in groupedDeliveries');
        }
        
        // console.log('Final locationInfo:', this.locationInfo);
        // console.log('Final selectedOrderInfo:', this.selectedOrderInfo);
        
        // Show the modal
        this.$refs.assignmentModal.show();
      } catch (error) {
        console.error('Error in assignDelivery:', error);
        
        // Show error modal
        showModal({
          type: 'error',
          title: 'Error',
          message: error?.message || 'An error occurred while preparing the assignment',
          buttons: [{ label: 'OK', type: 'primary', dismiss: true }]
        });
      }
    },
    
    // Add this new method
    getLocationInfo(locationID, orderID) {
      const orderInfo = this.getOrderInfo(locationID, orderID);
      if (!orderInfo) {
        return {
          from: null,
          to: null
        };
      }
      
      return {
        from: orderInfo.from || null,
        to: orderInfo.to || null
      };
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
    async fetchDeliveryStats() {
      try {
        const response = await deliveryService.getDeliveryStats();
        
        if (response.success) {
          this.deliveryStats = response.data;
        } else {
          console.error('Failed to fetch delivery stats:', response.message);
        }
      } catch (error) {
        console.error('Error fetching delivery stats:', error);
      }
    },
    
    async refreshExecutionDeliveries(filters = {}) {
      try {
        this.executeDeliveriesLoading = true;
        this.executeError = null; // Reset error before fetching
        // console.log('Fetching execution deliveries with filters:', filters);
        const response = await deliveryService.getExecutionDeliveries(filters);
        // console.log('execution deliveries structure:', JSON.stringify(response, null, 2));

        if (response.success) {
          this.executionDeliveries =  Object.values(response.data);
          // console.log('execution deliveries structure:', JSON.stringify(this.executionDeliveries, null, 2));

        } else {
          this.executeError = response.message || 'Failed to load execution deliveries';
        }
      } catch (error) {
        console.error('Error fetching execution deliveries:', error);
        this.executeError = 'An error occurred while loading execution deliveries. Please try again later.';
      } finally {
        this.executeDeliveriesLoading = false;
      }
    },
    
    handleExecutionFilterChange(filters) {
      this.refreshExecutionDeliveries(filters);
    },
    
    openCreateDeliveryModal() {
      const modalElement = document.getElementById('deliveryFormModal');
      if (modalElement) {
        const bsModal = new bootstrap.Modal(modalElement);
        bsModal.show();
      } else {
        console.error('Modal element not found with ID: deliveryFormModal');
      }
    },
    openAssignmentModal(orderInfo) {
      // Store the selected order info
      this.selectedOrderInfo = orderInfo;
      
      // Get the modal component and set the deliveryID
      const assignmentModal = this.$refs.assignmentModal;
      if (assignmentModal) {
        assignmentModal.setDeliveryID(this.selectedDeliveryID);
      }
      
      // Open the modal
      const modalElement = document.getElementById('assignmentModal');
      if (modalElement) {
        const bsModal = new bootstrap.Modal(modalElement);
        bsModal.show();
      }
    },
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


