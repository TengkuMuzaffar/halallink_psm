<template>
  <div class="delivery-execution">
    <div class="card theme-card">
      <div class="card-header d-flex justify-content-between align-items-center theme-header">
        <h5 class="mb-0">Execute Deliveries</h5>
        <button class="btn btn-sm theme-btn-outline" @click="$emit('refresh')">
          <i class="fas fa-sync-alt"></i>
        </button>
      </div>
      
      <div class="card-body theme-body">
        <div v-if="loading" class="text-center p-4">
          <div class="spinner-border theme-spinner" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 theme-text">Loading delivery data...</p>
        </div>
        
        <div v-else-if="error" class="alert alert-danger">
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
                <input type="date" id="dateFilter" v-model="dateFilter" class="form-control theme-input">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="driverFilter" class="form-label theme-label">Filter by Driver</label>
                <select id="driverFilter" v-model="driverFilter" class="form-select theme-select">
                  <option value="">All Drivers</option>
                  <option v-for="driver in drivers" :key="driver.userID" :value="driver.userID">
                    {{ driver.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>
          
          <ResponsiveTable
            :columns="columns"
            :items="filteredDeliveries"
            :loading="loading"
            :has-actions="false"
            item-key="deliveryID"
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
                <button class="btn btn-sm theme-btn-info" @click="viewDelivery(item)">
                  <i class="fas fa-eye"></i>
                </button>
                <button 
                  class="btn btn-sm theme-btn-success" 
                  @click="generateQRCode(item)"
                  :disabled="item.status === 'completed'"
                >
                  <i class="fas fa-qrcode"></i>
                </button>
                <button 
                  class="btn btn-sm theme-btn-warning" 
                  @click="updateStatus(item)"
                  :disabled="item.status === 'completed'"
                >
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
    />
  </div>
</template>

<script>
import { fetchData } from '../../utils/api';
import * as modalUtil from '../../utils/modal';
import ResponsiveTable from '../ui/ResponsiveTable.vue';
import ExecuteModalDelivery from './ExecuteModalDelivery.vue';
import deliveryService from '../../services/deliveryService';

export default {
  name: 'DeliveryExecution',
  components: {
    ResponsiveTable,
    ExecuteModalDelivery
  },
  props: {
    deliveries: {
      type: [Array, Object],
      default: () => []
    },
    drivers: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      loading: false,
      modalLoading: false,
      error: null,
      selectedDelivery: null,
      statusFilter: '',
      dateFilter: '',
      driverFilter: '',
      columns: [
        { key: 'deliveryID', label: 'ID', sortable: true },
        { key: 'driver.fullname', label: 'Driver', sortable: true },
        { key: 'vehicle.vehicle_plate', label: 'Vehicle', sortable: true },
        { key: 'status', label: 'Status', sortable: true },
        { key: 'actions', label: 'Actions', sortable: false }
      ]
    };
  },
  computed: {
    filteredDeliveries() {
      
      if (!this.deliveries) {
        console.log('Deliveries is null or undefined');
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
        console.log('Converted object to array:', deliveriesArray);
        return this.applyFilters(deliveriesArray);
      }
      
      // Handle array case
      if (Array.isArray(this.deliveries)) {
        return this.applyFilters(this.deliveries);
      }
      
      // Fallback for any other case
      console.log('Deliveries is in an unexpected format');
      return [];
    }
  },
  methods: {
    viewDelivery(delivery) {
      // Transform the delivery object to match the expected format in the modal
      this.modalLoading = true;
      
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
     // Add this new method to apply filters
    applyFilters(deliveriesArray) {
      return deliveriesArray.filter(delivery => {
        // Status filter
        if (this.statusFilter && delivery.status !== this.statusFilter) {
          return false;
        }
        
        // Date filter
        if (this.dateFilter) {
          const deliveryDate = new Date(delivery.scheduled_date).toISOString().split('T')[0];
          if (deliveryDate !== this.dateFilter) {
            return false;
          }
        }
        
        // Driver filter
        if (this.driverFilter && delivery.userID !== this.driverFilter) {
          return false;
        }
        
        return true;
      });
    },
    // Add the missing startDelivery method
    startDelivery(deliveryID) {
      // Implement your logic to start the delivery
      console.log('Starting delivery:', deliveryID);
      
      // Example implementation:
      this.loading = true;
      deliveryService.startDelivery(deliveryID)
        .then(response => {
          // Handle success
          this.$emit('refresh'); // Refresh the list
        })
        .catch(error => {
          // Handle error
          console.error('Error starting delivery:', error);
        })
        .finally(() => {
          this.loading = false;
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
    
    generateQRCode(delivery) {
      this.selectedDelivery = delivery;
      
      // Generate QR code URL - in a real app, you would use a QR code library or API
      this.qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=delivery:${delivery.deliveryID}`;
      
      modalUtil.showModal({
        type: 'info',
        title: 'Delivery QR Code',
        message: `
          <div class="text-center">
            <p>Scan this QR code to update delivery status:</p>
            <div class="mb-3">
              <img src="${this.qrCodeUrl}" alt="QR Code" class="img-fluid" style="max-width: 200px;">
            </div>
            <p class="mb-0">
              <strong>Delivery ID:</strong> ${delivery.deliveryID}<br>
              <strong>From:</strong> ${delivery.from?.company_address || 'N/A'}<br>
              <strong>To:</strong> ${delivery.to?.company_address || 'N/A'}
            </p>
          </div>
        `,
        buttons: [
          { label: 'Close', type: 'secondary', dismiss: true },
          { 
            label: 'Print', 
            type: 'primary',
            onClick: () => this.printQRCode(delivery)
          }
        ]
      });
    },
    
    printQRCode(delivery) {
      const printWindow = window.open('', '_blank');
      printWindow.document.write(`
        <html>
          <head>
            <title>Delivery QR Code - #${delivery.deliveryID}</title>
            <style>
              body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
              .container { max-width: 400px; margin: 0 auto; }
              img { max-width: 100%; height: auto; }
              .info { margin-top: 20px; text-align: left; }
            </style>
          </head>
          <body>
            <div class="container">
              <h2>Delivery QR Code</h2>
              <img src="${this.qrCodeUrl}" alt="QR Code">
              <div class="info">
                <p><strong>Delivery ID:</strong> ${delivery.deliveryID}</p>
                <p><strong>From:</strong> ${delivery.from?.company_address || 'N/A'}</p>
                <p><strong>To:</strong> ${delivery.to?.company_address || 'N/A'}</p>
                <p><strong>Driver:</strong> ${delivery.driver?.name || 'N/A'}</p>
                <p><strong>Vehicle:</strong> ${delivery.vehicle?.vehicle_plate || 'N/A'}</p>
              </div>
            </div>
          </body>
        </html>
      `);
      
      printWindow.document.close();
      printWindow.focus();
      
      // Print after a short delay to ensure content is loaded
      setTimeout(() => {
        printWindow.print();
        printWindow.close();
      }, 250);
    },
    
    updateStatus(delivery) {
      this.selectedDelivery = delivery;
      
      modalUtil.showModal({
        type: 'warning',
        title: 'Update Delivery Status',
        customModalBody: (modalType, message) => `
          <div class="modal-body">
            <div class="form-group mb-3">
              <label for="newStatus" class="form-label">Status</label>
              <select id="newStatus" class="form-select">
                <option value="pending" ${delivery.status === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="in_progress" ${delivery.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                <option value="completed" ${delivery.status === 'completed' ? 'selected' : ''}>Completed</option>
                <option value="failed" ${delivery.status === 'failed' ? 'selected' : ''}>Failed</option>
              </select>
            </div>
            <div class="form-group mb-3">
              <label for="statusNotes" class="form-label">Notes</label>
              <textarea id="statusNotes" class="form-control" rows="3"></textarea>
            </div>
          </div>
        `,
        buttons: [
          { label: 'Cancel', type: 'secondary', dismiss: true },
          { 
            label: 'Update', 
            type: 'primary',
            onClick: (event) => {
              const modal = event.target.closest('.modal');
              const newStatus = modal.querySelector('#newStatus').value;
              const statusNotes = modal.querySelector('#statusNotes').value;
              
              this.submitStatusUpdate(delivery, newStatus, statusNotes);
            }
          }
        ]
      });
    },
    
    async submitStatusUpdate(delivery, newStatus, statusNotes) {
      try {
        modalUtil.showLoading('Updating', 'Updating delivery status...');
        
        const response = await fetchData(`/api/deliveries/${delivery.deliveryID}/status`, {
          method: 'put',
          data: {
            status: newStatus,
            notes: statusNotes
          }
        });
        
        modalUtil.closeLoading();
        
        if (response.success) {
          modalUtil.showSuccess('Success', 'Delivery status updated successfully', {
            onHidden: () => {
              this.$emit('refresh');
            }
          });
        } else {
          modalUtil.showDanger('Error', response.message || 'Failed to update delivery status');
        }
      } catch (error) {
        modalUtil.closeLoading();
        console.error('Error updating delivery status:', error);
        modalUtil.showDanger('Error', error.message || 'An unexpected error occurred');
      }
    }
  },
  watch: {
    statusFilter() {
      this.$emit('filter-changed', {
        status: this.statusFilter,
        date: this.dateFilter,
        driver: this.driverFilter
      });
    },
    dateFilter() {
      this.$emit('filter-changed', {
        status: this.statusFilter,
        date: this.dateFilter,
        driver: this.driverFilter
      });
    },
    driverFilter() {
      this.$emit('filter-changed', {
        status: this.statusFilter,
        date: this.dateFilter,
        driver: this.driverFilter
      });
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
</style>
