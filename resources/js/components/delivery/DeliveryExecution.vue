<template>
  <div class="delivery-execution">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Execute Deliveries</h5>
        <button class="btn btn-sm btn-outline-primary" @click="$emit('refresh')">
          <i class="fas fa-sync-alt"></i>
        </button>
      </div>
      
      <div class="card-body">
        <div v-if="loading" class="text-center p-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 text-muted">Loading delivery data...</p>
        </div>
        
        <div v-else-if="error" class="alert alert-danger">
          {{ error }}
        </div>
        
        <div v-else>
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="form-group">
                <label for="statusFilter" class="form-label">Filter by Status</label>
                <select id="statusFilter" v-model="statusFilter" class="form-select">
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
                <label for="dateFilter" class="form-label">Filter by Date</label>
                <input type="date" id="dateFilter" v-model="dateFilter" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="driverFilter" class="form-label">Filter by Driver</label>
                <select id="driverFilter" v-model="driverFilter" class="form-select">
                  <option value="">All Drivers</option>
                  <option v-for="driver in drivers" :key="driver.userID" :value="driver.userID">
                    {{ driver.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Driver</th>
                  <th>Vehicle</th>
                  <th>Scheduled Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="delivery in filteredDeliveries" :key="delivery.deliveryID">
                  <td>{{ delivery.deliveryID }}</td>
                  <td>{{ delivery.from?.company_address || 'N/A' }}</td>
                  <td>{{ delivery.to?.company_address || 'N/A' }}</td>
                  <td>{{ delivery.driver?.name || 'N/A' }}</td>
                  <td>{{ delivery.vehicle?.vehicle_plate || 'N/A' }}</td>
                  <td>{{ formatDate(delivery.scheduledDate) }}</td>
                  <td>
                    <span class="badge" :class="getStatusClass(delivery.status)">
                      {{ delivery.status || 'Pending' }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <button class="btn btn-sm btn-info" @click="viewDelivery(delivery)">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button 
                        class="btn btn-sm btn-success" 
                        @click="generateQRCode(delivery)"
                        :disabled="delivery.status === 'completed'"
                      >
                        <i class="fas fa-qrcode"></i>
                      </button>
                      <button 
                        class="btn btn-sm btn-warning" 
                        @click="updateStatus(delivery)"
                        :disabled="delivery.status === 'completed'"
                      >
                        <i class="fas fa-edit"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div v-if="filteredDeliveries.length === 0" class="text-center p-4">
            <p class="text-muted">No deliveries found matching your criteria</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { fetchData } from '../../utils/api';
import * as modalUtil from '../../utils/modal';

export default {
  name: 'DeliveryExecution',
  props: {
    loading: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: null
    },
    deliveries: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      statusFilter: '',
      dateFilter: '',
      driverFilter: '',
      drivers: [],
      selectedDelivery: null,
      qrCodeUrl: ''
    };
  },
  computed: {
    filteredDeliveries() {
      let result = Object.values(this.deliveries);
      
      if (this.statusFilter) {
        result = result.filter(d => d.status === this.statusFilter);
      }
      
      if (this.dateFilter) {
        result = result.filter(d => {
          const deliveryDate = new Date(d.scheduledDate).toISOString().split('T')[0];
          return deliveryDate === this.dateFilter;
        });
      }
      
      if (this.driverFilter) {
        result = result.filter(d => d.driver?.userID === this.driverFilter);
      }
      
      return result;
    }
  },
  mounted() {
    this.fetchDrivers();
  },
  methods: {
    async fetchDrivers() {
      try {
        const response = await fetchData('/api/users/drivers');
        if (response.success) {
          this.drivers = response.data;
        }
      } catch (error) {
        console.error('Error fetching drivers:', error);
      }
    },
    
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      
      const date = new Date(dateString);
      return date.toLocaleDateString();
    },
    
    getStatusClass(status) {
      switch (status?.toLowerCase()) {
        case 'completed':
          return 'bg-success';
        case 'in_progress':
          return 'bg-info';
        case 'pending':
          return 'bg-warning';
        case 'failed':
          return 'bg-danger';
        default:
          return 'bg-secondary';
      }
    },
    
    viewDelivery(delivery) {
      this.selectedDelivery = delivery;
      
      modalUtil.showModal({
        type: 'info',
        title: `Delivery Details - #${delivery.deliveryID}`,
        message: `
          <div>
            <p><strong>From:</strong> ${delivery.from?.company_address || 'N/A'}</p>
            <p><strong>To:</strong> ${delivery.to?.company_address || 'N/A'}</p>
            <p><strong>Driver:</strong> ${delivery.driver?.name || 'N/A'}</p>
            <p><strong>Vehicle:</strong> ${delivery.vehicle?.vehicle_plate || 'N/A'}</p>
            <p><strong>Scheduled Date:</strong> ${this.formatDate(delivery.scheduledDate)}</p>
            <p><strong>Status:</strong> ${delivery.status || 'Pending'}</p>
            <p><strong>Notes:</strong> ${delivery.notes || 'No notes'}</p>
          </div>
        `,
        buttons: [
          { label: 'Close', type: 'secondary', dismiss: true }
        ]
      });
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
  }
}
</script>

<style scoped>
.btn-group .btn {
  margin-right: 5px;
}

.btn-group .btn:last-child {
  margin-right: 0;
}
</style>