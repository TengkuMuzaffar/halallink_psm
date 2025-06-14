<template>
  <div class="delivery-execution">
    <!-- Add the loading overlay component at the top level -->
    <LoadingSpinner 
      v-if="modalLoading" 
      :overlay="true" 
      size="lg" 
      message="Please wait while the delivery is loading..."
    />
    
    <div class="card theme-card">
      <div class="card-header d-flex justify-content-between align-items-center theme-header">
        <h5 class="mb-0">Execute Deliveries</h5>
        <button class="btn btn-sm theme-btn-outline" @click="handleRefresh">
          <i class="fas fa-sync-alt"></i>
        </button>
      </div>
      
      <div class="card-body theme-body">
        <div v-if="error" class="alert alert-danger">
          {{ error }}
        </div>
        
        <div v-else>
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="form-group">
                <label for="statusFilter" class="form-label theme-label">Filter by Status</label>
                <select id="statusFilter" v-model="statusFilter" class="form-select theme-select">
                  <option value="">All Statuses</option>
                  <option value="pending">Pending</option>
                  <option value="in_progress">In Progress</option>
                  <option value="completed">Completed</option>
                  <option value="failed">Failed</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="dateFilter" class="form-label theme-label">Filter by Date</label>
                <input 
                  type="date" 
                  id="dateFilter" 
                  v-model="dateFilter" 
                  class="form-control theme-input"
                  :min="minDate"
                  required
                >
              </div>
            </div>
            <!-- Add Search Input -->
            <div class="col-md-4">
              <div class="form-group">
                <label for="searchInput" class="form-label theme-label">Search</label>
                <input 
                  type="text" 
                  id="searchInput" 
                  v-model="searchTerm" 
                  class="form-control theme-input"
                  placeholder="Search by ID, Driver, Vehicle..."
                >
              </div>
            </div>
           
          </div>
          
          <ResponsiveTable
            :columns="columns"
            :items="filteredDeliveries"
            :loading="loading"
            :has-actions="true"
            :show-filters="false"
            item-key="deliveryID"
            class="delivery-table"
          >
            <!-- Status column slot -->
            <template #status="{ item }">
              <span class="badge" :class="getThemeStatusClass(item.status)">
                {{ item.status || 'Pending' }}
              </span>
            </template>

            <!-- Actions column slot -->
            <template #actions="{ item }">
              <div class="btn-group">
                <button class="btn btn-sm btn-primary" @click="viewDelivery(item)">
                  <i class="fas fa-edit"></i>                
                </button>
               
               
              </div>
            </template>
          </ResponsiveTable>
        </div>
      </div>
    </div>
    
    <!-- Add the ExecuteModalDelivery component -->
    <ExecuteModalDelivery 
      :loading="modalLoading" 
      :delivery="selectedDelivery"
      ref="executeModal"
      @start-delivery="startDelivery"
      @refresh="$emit('refresh')"
      @reopen-delivery-modal="reopenDeliveryModal"
    />
  </div>
</template>

<script>
import { fetchData } from '../../utils/api';
import * as modalUtil from '../../utils/modal';
import ResponsiveTable from '../ui/ResponsiveTable.vue';
import ExecuteModalDelivery from './ExecuteModalDelivery.vue';
import LoadingSpinner from '../ui/LoadingSpinner.vue';
import deliveryService from '../../services/deliveryService';

export default {
  name: 'DeliveryExecution',
  components: {
    ResponsiveTable,
    ExecuteModalDelivery,
    LoadingSpinner
  },
  props: {
    deliveries: {
      type: [Array, Object],
      default: () => []
    },
    drivers: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      modalLoading: false,
      error: null,
      selectedDelivery: null,
      statusFilter: '',
      dateFilter: '',
      searchTerm: '',
      columns: [
        { key: 'deliveryID', label: 'ID', sortable: true },
        { key: 'driver.fullname', label: 'Driver', sortable: true },
        { key: 'vehicle.vehicle_plate', label: 'Vehicle', sortable: true },
        { key: 'status', label: 'Status', sortable: true },
      ]
    };
  },
  computed: {
    minDate() {
      const today = new Date();
      return today.toISOString().split('T')[0];
    },
    filteredDeliveries() {
      // console.log('Filtering deliveries:', this.deliveries);
      if (!this.deliveries) {
        // console.log('Deliveries is null or undefined');
        return [];
      }
      
      // Check if it's an object but not an array
      if (typeof this.deliveries === 'object' && !Array.isArray(this.deliveries)) {
        // Handle empty object case
        if (Object.keys(this.deliveries).length === 0) {
          return [];
        }
        
        // Convert object to array
        const deliveriesArray = Object.values(this.deliveries);
        // console.log('Converted object to array:', deliveriesArray);
        return this.applyFilters(deliveriesArray);
      }
      
      // Handle array case
      if (Array.isArray(this.deliveries)) {
        return this.applyFilters(this.deliveries);
      }
      
      // Fallback for any other case
      // console.log('Deliveries is in an unexpected format');
      return [];
    }
  },
  methods: {
    viewDelivery(delivery) {
      // Transform the delivery object to match the expected format in the modal
      this.modalLoading = true;
      // console.log("selected deliveries: "+ JSON.stringify(delivery, null, 3));
    
      // Create a copy of the delivery to avoid modifying the original
      const transformedDelivery = { ...delivery };
    
      // Transform routes from object to array if it's an object
      if (transformedDelivery.routes && typeof transformedDelivery.routes === 'object' && !Array.isArray(transformedDelivery.routes)) {
        transformedDelivery.routes = Object.values(transformedDelivery.routes).map(route => {
          // Add routeID if not present
          if (!route.routeID && route.start_location && route.end_location) {
            route.routeID = `${route.start_location.locationID}-${route.end_location.locationID}`;
          }
    
          // Transform start_location
          if (route.start_location) {
            route.start_location.address = route.start_location.company_address;
    
            // Transform checkpoints to items if needed
            if (route.start_location.checkpoints && Array.isArray(route.start_location.checkpoints)) {
              route.start_location.items = {};
              route.start_location.checkpoints.forEach(checkpoint => {
                if (checkpoint.items) {
                  Object.assign(route.start_location.items, checkpoint.items);
                }
              });
            }
          }
    
          // Transform end_location
          if (route.end_location) {
            route.end_location.address = route.end_location.company_address;
    
            // Transform checkpoints to items if needed
            if (route.end_location.checkpoints && Array.isArray(route.end_location.checkpoints)) {
              route.end_location.items = {};
              route.end_location.checkpoints.forEach(checkpoint => {
                if (checkpoint.items) {
                  Object.assign(route.end_location.items, checkpoint.items);
                }
              });
            }
          }
    
          return route;
        });
      }
    
      // Set the transformed delivery
      this.selectedDelivery = transformedDelivery;
      // Show the modal
      this.$nextTick(() => {
        this.modalLoading = false;
        this.$refs.executeModal.showModal();
      });
    },
    handleRefresh() {
      // Pass current filters when refreshing
      this.$emit('refresh', {
        status: this.statusFilter,
        date: this.dateFilter,
        searchTerm: this.searchTerm // Include searchTerm
      });
    },
     applyFilters(deliveriesArray) {
       const lowerSearchTerm = this.searchTerm.toLowerCase(); // Convert search term to lowercase once
       return deliveriesArray.filter(delivery => {
         // Status filter
         if (this.statusFilter && delivery.status !== this.statusFilter) {
           return false;
         }

         // Date filter
         if (this.dateFilter) {
           const utcDate = new Date(delivery.scheduled_date);
           const localDate = new Date(utcDate.getTime() - (utcDate.getTimezoneOffset() * 60000));
           const deliveryDate = localDate.toISOString().split('T')[0];
           if (deliveryDate !== this.dateFilter) {
             return false;
           }
         }

         // Search filter
         if (lowerSearchTerm) {
           const deliveryID = delivery.deliveryID ? String(delivery.deliveryID).toLowerCase() : '';
           const driverName = delivery.driver?.fullname ? delivery.driver.fullname.toLowerCase() : '';
           const vehiclePlate = delivery.vehicle?.vehicle_plate ? delivery.vehicle.vehicle_plate.toLowerCase() : '';

           if (
             !deliveryID.includes(lowerSearchTerm) &&
             !driverName.includes(lowerSearchTerm) &&
             !vehiclePlate.includes(lowerSearchTerm)
           ) {
             return false;
           }
         }

         return true;
       });
     },
    startDelivery(deliveryID) {
      // console.log('Starting delivery:', deliveryID);
      
      // Show loading overlay
      this.modalLoading = true;
      
      // Hide the modal if it's open
      if (this.$refs.executeModal) {
        this.$refs.executeModal.hideModal();
      }
      
      deliveryService.startDelivery(deliveryID)
        .then(response => {
          this.$emit('refresh');
        })
        .catch(error => {
          console.error('Error starting delivery:', error);
        })
        .finally(() => {
          // Hide loading overlay after a short delay to ensure data is refreshed
          setTimeout(() => {
            this.modalLoading = false;
          }, 1000);
        });
    },
    
    getThemeStatusClass(status) {
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
    handleFiltersChanged() {
      this.$emit('filter-changed', {
        statusFilter: this.statusFilter,
        dateFilter: this.dateFilter,
      });
    },
    reopenDeliveryModal(deliveryID) {
      // Emit refresh and wait for parent to update data
      this.$emit('refresh');
      
      // Set a timeout to allow the data to refresh before reopening the modal
      setTimeout(() => {
        // Find the updated delivery in the filtered deliveries
        const updatedDelivery = this.filteredDeliveries.find(d => d.deliveryID === deliveryID);
        if (updatedDelivery) {
          // Reopen the modal with the updated delivery
          this.viewDelivery(updatedDelivery);
        }
      }, 1000); // Wait for 1 second to ensure data is refreshed
    },
  },
  mounted() {
    // Vue 3 doesn't support $on, so we don't need this code
    // Instead, we'll handle the event through props and emits
  },
    
  watch: {
    statusFilter() {
      this.$emit('filter-changed', {
        status: this.statusFilter,
        date: this.dateFilter,
      });
    },
    dateFilter() {
      this.$emit('filter-changed', {
        status: this.statusFilter,
        date: this.dateFilter,
      });
    },
    searchTerm() { // Add watch for searchTerm
       // No need to emit filter-changed here, as filteredDeliveries computed property will react
       // and update the table automatically.
    }
    
  }
}

</script>

<style scoped>
/* Theme colors */
.delivery-execution {
  --primary-color: #123524;
  --secondary-color: #EFE3C2;
  --accent-color: #3E7B27;
  --text-color: #333;
  --light-text: #666;
  --border-color: rgba(18, 53, 36, 0.2);
  --light-bg: rgba(239, 227, 194, 0.2);
  --lighter-bg: rgba(239, 227, 194, 0.1);
}

/* Card theme */
.theme-card {
  border-color: var(--border-color);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
}

.theme-body {
  background-color: #fff;
  color: var(--text-color);
}

.theme-text {
  color: var(--text-color);
}

/* Form elements */
.theme-label {
  color: var(--primary-color);
  font-weight: 500;
}

.theme-input, .theme-select {
  border-color: var(--border-color);
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.theme-input:focus, .theme-select:focus {
  border-color: var(--accent-color);
  box-shadow: 0 0 0 0.25rem rgba(62, 123, 39, 0.25);
}

/* Table styles */
.theme-table {
  border-color: var(--border-color);
}

.theme-table-header {
  background-color: var(--primary-color);
}

.theme-table-header th {
  border-color: var(--primary-color);
  color: var(--secondary-color) !important;
  font-weight: 600;
  padding: 12px 8px;
  white-space: nowrap;
}

.theme-table tbody tr:nth-child(odd) {
  background-color: var(--light-bg);
}

.theme-table tbody tr:hover {
  background-color: var(--lighter-bg);
}

/* Button styles */
.theme-btn-outline {
  color: var(--secondary-color);
  border-color: var(--secondary-color);
  background-color: transparent;
}

.theme-btn-outline:hover {
  color: var(--primary-color);
  background-color: var(--secondary-color);
  border-color: var(--secondary-color);
}

.theme-btn-info {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
}

.theme-btn-info:hover {
  background-color: #0a1f16;
  border-color: #0a1f16;
}

.theme-btn-success {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
  color: var(--secondary-color);
}

.theme-btn-success:hover {
  background-color: #2e5c1d;
  border-color: #2e5c1d;
}

.theme-btn-warning {
  background-color: #e0a800;
  border-color: #e0a800;
  color: #212529;
}

.theme-btn-warning:hover {
  background-color: #c69500;
  border-color: #c69500;
}

.theme-btn-info:disabled,
.theme-btn-success:disabled,
.theme-btn-warning:disabled {
  opacity: 0.65;
}

/* Badge styles */
.theme-badge-success {
  background-color: var(--accent-color);
  color: var(--secondary-color);
}

.theme-badge-info {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

.theme-badge-warning {
  background-color: #e0a800;
  color: #212529;
}

.theme-badge-danger {
  background-color: #dc3545;
  color: white;
}

.theme-badge-secondary {
  background-color: #6c757d;
  color: white;
}

/* Spinner */
.theme-spinner {
  color: var(--accent-color);
}

/* Original styles with theme modifications */
.btn-group .btn {
  margin-right: 5px;
}

.btn-group .btn:last-child {
  margin-right: 0;
}

/* Add responsive table styles */
.delivery-table {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

@media (max-width: 768px) {
  .delivery-table :deep(table) {
    min-width: 600px; /* Ensure minimum width for content */
  }

  .delivery-table :deep(th),
  .delivery-table :deep(td) {
    white-space: nowrap;
    padding: 8px;
  }

  .delivery-table :deep(.btn-group) {
    display: flex;
    flex-direction: row;
    gap: 4px;
  }

  .delivery-table :deep(.btn-group .btn) {
    padding: 4px 8px;
    font-size: 0.875rem;
  }

  /* Improve badge readability on mobile */
  .delivery-table :deep(.badge) {
    font-size: 0.75rem;
    padding: 4px 8px;
    white-space: nowrap;
  }

  /* Adjust filter section for mobile */
  .row.mb-4 > div {
    margin-bottom: 1rem;
  }

  /* Make form controls more touch-friendly */
  .theme-input,
  .theme-select {
    height: 42px;
    padding: 8px 12px;
  }

  /* Improve button touch targets */
  .btn-sm {
    min-height: 32px;
    min-width: 32px;
  }
}

/* Add smooth scrolling for better mobile experience */
.delivery-table {
  scroll-behavior: smooth;
}

/* Optimize table header for mobile */
.theme-table-header th {
  position: sticky;
  top: 0;
  z-index: 1;
  background-color: var(--primary-color);
}
</style>
