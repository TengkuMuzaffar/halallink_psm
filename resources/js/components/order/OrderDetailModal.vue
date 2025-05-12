<template>
  <div class="modal fade" :id="modalId" tabindex="-1" :aria-labelledby="`${modalId}Label`" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header theme-header">
          <h5 class="modal-title" :id="`${modalId}Label`">Order Details: #{{ order ? order.orderID : '' }}</h5>
          <button type="button" class="btn-close theme-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <LoadingSpinner v-if="loading" message="Loading order details..." />
          <div v-if="!loading && order">
            <!-- Order Information -->
            <p><strong>Status:</strong> <span :class="`badge bg-${getStatusColor(order.calculated_status || order.order_status)}`">{{ formatStatus(order.calculated_status || order.order_status) }}</span></p>
            <p><strong>Total Amount:</strong> {{ formatCurrency(order.payment ? order.payment.payment_amount : order.total_amount) }}</p>
            <p><strong>Delivery Address:</strong> {{ order.delivery_address }}</p>
            <p><strong>Ordered At:</strong> {{ formatDateTime(order.created_at) }}</p>
            <p><strong>Customer:</strong> {{ order.user ? order.user.fullname : 'N/A' }} ({{ order.user ? order.user.email : 'N/A' }})</p>

            <hr>
            
            <!-- Locations and Checkpoints -->
            <h6>Supplier Locations:</h6>
            <div v-if="order.locations && order.locations.length > 0">
              <div v-for="location in order.locations" :key="location.locationID" class="mb-4 p-3 border rounded">
                <div class="location-header">
                  <h6 class="location-title mb-2">
                    Location: {{ location.location_name || 'Unknown Location' }}
                  </h6>
                  <div class="location-actions">
                    <button 
                      class="btn btn-sm theme-btn-secondary me-2" 
                      @click="showQRCode(order.orderID, location.locationID)"
                      title="Generate QR Code"
                    >
                      <i class="fas fa-qrcode"></i> <span class="d-none d-sm-inline">QR Code</span>
                    </button>
                    <span :class="`badge bg-${getStatusColor(location.location_status)}`">
                      {{ formatStatus(location.location_status) }}
                    </span>
                  </div>
                </div>
                
                <!-- Checkpoints for this location -->
                <div v-if="location.checkpoints && location.checkpoints.length > 0">
                  <div v-for="checkpoint in location.checkpoints" :key="checkpoint.checkID" class="mt-3 p-2 border-top pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span><strong>Checkpoint #{{ checkpoint.checkID }}</strong></span>
                      <span :class="`badge bg-${getStatusColor(checkpoint.checkpoint_status)}`">{{ formatStatus(checkpoint.checkpoint_status) }}</span>
                    </div>
                    <p v-if="checkpoint.timestamp"><strong>Timestamp:</strong> {{ formatDateTime(checkpoint.timestamp) }}</p>
                    <p v-if="checkpoint.notes"><strong>Notes:</strong> {{ checkpoint.notes }}</p>
                    
                    <!-- Items in this checkpoint -->
                    <div v-if="checkpoint.items && Object.keys(checkpoint.items).length > 0" class="mt-2">
                      <h6>Items:</h6>
                      <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                          <thead class="table-light">
                            <tr>
                              <th>Item</th>
                              <th>Quantity</th>
                              <th>Measurement</th>
                              <th>Price</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(item, itemID) in checkpoint.items" :key="itemID">
                              <td>{{ item.item_name }}</td>
                              <td>{{ item.quantity }}</td>
                              <td>{{ item.item_info.measurement_value }} {{ item.item_info.measurement_type }}</td>
                              <td>{{ formatCurrency(item.item_info.price) }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <p v-else class="text-muted">No items associated with this checkpoint.</p>
                  </div>
                </div>
                <p v-else class="text-muted">No checkpoints available for this location.</p>
              </div>
            </div>
            <p v-else>No supplier locations found for this order.</p>
            
            <!-- Original Items Section (if needed) -->
            <hr v-if="order.items && order.items.length > 0">
            <h6 v-if="order.items && order.items.length > 0">All Order Items:</h6>
            <ul class="list-group" v-if="order.items && order.items.length > 0">
              <li v-for="item in order.items" :key="item.itemID" class="list-group-item">
                {{ item.item_name || (item.poultry ? item.poultry.poultry_name : 'Unknown') }} 
                ({{ item.poultry ? item.poultry.poultry_type : 'N/A' }}) - 
                Quantity: {{ item.pivot ? item.pivot.quantity : item.quantity }}
                <br>
                <small>Provider: {{ item.company ? item.company.company_name : 'N/A' }}</small>
              </li>
            </ul>
          </div>
          <div v-if="!loading && !order && error">
            <p class="text-danger">{{ error }}</p>
          </div>
        </div>
        <div class="modal-footer theme-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- QR Code Modal -->
  <div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header theme-header">
          <h5 class="modal-title" id="qrCodeModalLabel">QR Code for Order #{{ selectedOrderID }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <div v-if="qrCodeLoading" class="my-3">
            <LoadingSpinner message="Generating QR code..." />
          </div>
          <div v-else>
            <div class="mb-3">
              <img v-if="qrCodeUrl" :src="qrCodeUrl" alt="QR Code" class="img-fluid" style="max-width: 250px;" />
              <p v-else class="text-danger">Failed to generate QR code.</p>
            </div>
            <div class="mb-3">
              <p class="mb-1"><strong>Order ID:</strong> {{ selectedOrderID }}</p>
              <p class="mb-1"><strong>Location ID:</strong> {{ selectedLocationID }}</p>            
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeQRModal">Back</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import LoadingSpinner from '../ui/LoadingSpinner.vue';
import { orderService } from '../../services/orderService';
import formatter from '../../utils/formatter';

export default {
  name: 'OrderDetailModal',
  components: { LoadingSpinner },
  props: {
    modalId: {
      type: String,
      default: 'orderDetailModal'
    },
    orderId: {
      type: [Number, String],
      default: null
    },
    orderData: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      loading: false,
      order: null,
      error: null,
      qrCodeLoading: false,
      qrCodeUrl: null,
      qrCodeLink: '',
      selectedOrderID: null,
      selectedLocationID: null,
      qrModal: null,
      mainModal: null,
    };
  },
  computed: {
    // We don't need sortedCheckpoints anymore as we're using the locations structure
  },
  watch: {
    orderId(newId) {
      if (newId) {
        if (this.orderData) {
          // Use the provided order data if available
          this.order = this.orderData;
          this.loading = false;
        } else {
          // Fallback to fetching from API if no data provided
          this.fetchOrderDetails(newId);
        }
      } else {
        this.order = null;
        this.error = null;
      }
    },
    orderData(newData) {
      if (newData) {
        this.order = newData;
        this.loading = false;
        this.error = null;
      }
    }
  },
  methods: {
    formatCurrency(amount) {
      return formatter.formatCurrency(amount);
    },
    formatDateTime(dateString) {
      return formatter.formatDate(dateString);
    },
    capitalizeFirstLetter(string) {
      if (!string) return '';
      return string.charAt(0).toUpperCase() + string.slice(1);
    },
    formatStatus(status) {
      if (!status) return 'Unknown';
      
      // Convert snake_case to Title Case
      return status
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
    },
    async fetchOrderDetails(id) {
      this.loading = true;
      this.error = null;
      this.order = null;
      try {
        const response = await orderService.getOrderById(id);
        this.order = response.data;
      } catch (err) {
        console.error('Error fetching order details:', err);
        this.error = 'Failed to load order details. ' + (err.response?.data?.message || err.message);
      } finally {
        this.loading = false;
      }
    },
    getStatusColor(status) {
      const statusMap = {
        pending: 'warning',
        processing: 'info',
        shipped: 'primary',
        delivered: 'success',
        completed: 'success',
        complete: 'success',
        cancelled: 'danger',
        waiting_delivery: 'primary',
        paid: 'success'
      };
      return statusMap[status?.toLowerCase()] || 'secondary';
    },
    showQRCode(orderID, locationID) {
      this.selectedOrderID = orderID;
      this.selectedLocationID = locationID;
      this.qrCodeLoading = true;
      this.qrCodeUrl = null;
      
      // Get modal elements
      const mainModalEl = document.getElementById(this.modalId);
      const qrModalEl = document.getElementById('qrCodeModal');
      
      // Hide the main modal
      if (this.mainModal) {
        if (mainModalEl) {
          // Set inert attribute to prevent focus
          mainModalEl.setAttribute('inert', '');
          // Remove aria-hidden to prevent accessibility issues
          mainModalEl.removeAttribute('aria-hidden');
        }
        this.mainModal.hide();
      }
      
      // Generate QR code URL
      const apiUrl = `/api/qrcode/process/${orderID}/${locationID}`;
      this.qrCodeLink = `${window.location.origin}${apiUrl}`;
      
      // Generate QR code image
      this.generateQRCode(this.qrCodeLink);
      
      // Show QR code modal
      if (this.qrModal) {
        if (qrModalEl) {
          // Remove inert attribute to allow focus
          qrModalEl.removeAttribute('inert');
          // Remove aria-hidden to prevent accessibility issues
          qrModalEl.removeAttribute('aria-hidden');
        }
        this.qrModal.show();
      }
    },
    generateQRCode(text) {
      this.qrCodeLoading = false;
      
      // Create a canvas element
      const canvas = document.createElement('canvas');
      
      // Use QRCode.js to generate QR code
      QRCode.toCanvas(canvas, text, {
        width: 250,
        margin: 2,
        color: {
          dark: '#000000',
          light: '#ffffff'
        }
      }, (error) => {
        if (error) {
          console.error('Error generating QR code:', error);
          this.qrCodeUrl = null;
        } else {
          // Convert canvas to data URL
          this.qrCodeUrl = canvas.toDataURL();
        }
      });
    },
    copyToClipboard() {
      const copyText = this.$refs.qrLinkInput;
      copyText.select();
      document.execCommand('copy');
      this.copySuccess = true;
      
      // Reset copy success message after 3 seconds
      setTimeout(() => {
        this.copySuccess = false;
      }, 3000);
    },
    closeQRModal() {
      // Get modal elements
      const mainModalEl = document.getElementById(this.modalId);
      const qrModalEl = document.getElementById('qrCodeModal');
      
      // Hide QR code modal
      if (this.qrModal) {
        if (qrModalEl) {
          // Set inert attribute to prevent focus
          qrModalEl.setAttribute('inert', '');
          // Remove aria-hidden to prevent accessibility issues
          qrModalEl.removeAttribute('aria-hidden');
        }
        this.qrModal.hide();
        
        // Remove backdrop manually
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
          backdrop.classList.remove('show');
          setTimeout(() => {
            backdrop.remove();
          }, 150);
        });
      }
      
      // Show main modal
      if (this.mainModal) {
        if (mainModalEl) {
          // Remove inert attribute to allow focus
          mainModalEl.removeAttribute('inert');
          // Remove aria-hidden to prevent accessibility issues
          mainModalEl.removeAttribute('aria-hidden');
        }
        this.mainModal.show();
      }
    }
  },
  mounted() {
    // If an orderId is passed initially, load it.
    if (this.orderId) {
      if (this.orderData) {
        // Use the provided order data if available
        this.order = this.orderData;
      } else {
        // Fallback to fetching from API
        this.fetchOrderDetails(this.orderId);
      }
    }
    
    // Initialize Bootstrap modals with custom options
    this.mainModal = new bootstrap.Modal(document.getElementById(this.modalId), {
      backdrop: true,
      keyboard: true,
      focus: true
    });
    
    this.qrModal = new bootstrap.Modal(document.getElementById('qrCodeModal'), {
      backdrop: true,
      keyboard: true,
      focus: true
    });
    
    // Remove aria-hidden when added by Bootstrap
    const mainModalEl = document.getElementById(this.modalId);
    const qrModalEl = document.getElementById('qrCodeModal');
    
    // Use MutationObserver to detect when Bootstrap adds aria-hidden
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === 'attributes' && mutation.attributeName === 'aria-hidden') {
          const element = mutation.target;
          // If element has focusable children, remove aria-hidden
          if (element.querySelector('button, a, input, select, textarea, [tabindex]:not([tabindex="-1"])')) {
            element.removeAttribute('aria-hidden');
          }
        }
      });
    });
    
    // Observe both modals
    if (mainModalEl) {
      observer.observe(mainModalEl, { attributes: true });
    }
    
    if (qrModalEl) {
      observer.observe(qrModalEl, { attributes: true });
      qrModalEl.addEventListener('hidden.bs.modal', () => {
        // Show the main modal again when QR modal is closed
        if (this.mainModal) {
          // Remove inert attribute to allow focus
          if (mainModalEl) {
            mainModalEl.removeAttribute('inert');
          }
          this.mainModal.show();
        }
      });
    }
    
    // Add event listeners for modal hidden events to clean up backdrops
    if (mainModalEl) {
      mainModalEl.addEventListener('hidden.bs.modal', () => {
        // Clean up any leftover backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
          backdrop.classList.remove('show');
          setTimeout(() => {
            backdrop.remove();
          }, 150);
        });
      });
    }
    
    if (qrModalEl) {
      qrModalEl.addEventListener('hidden.bs.modal', (event) => {
        // Clean up any leftover backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
          backdrop.classList.remove('show');
          setTimeout(() => {
            backdrop.remove();
          }, 150);
        });
        
        // Show the main modal again when QR modal is closed
        if (this.mainModal) {
          // Remove inert attribute to allow focus
          if (mainModalEl) {
            mainModalEl.removeAttribute('inert');
          }
          this.mainModal.show();
        }
      });
    }
  }
};
</script>

<style scoped>
.modal-lg {
  max-width: 800px;
}

.table-sm td, .table-sm th {
  padding: 0.3rem;
  font-size: 0.9rem;
}

/* Theme colors using CSS variables on component level */
.modal {
  --primary-color: #123524;
  --secondary-color: #EFE3C2;
  --accent-color: #3E7B27;
  --text-color: #666;
  --border-color: rgba(18, 53, 36, 0.2);
}

/* Modal header styling */
.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
}

.theme-close {
  filter: invert(1) brightness(1.5);
}

/* Modal footer styling */
.theme-footer {
  background-color: rgba(239, 227, 194, 0.1);
  border-top: 1px solid var(--border-color);
}

/* Button styling */
.theme-btn-secondary {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
  color: white;
}

.theme-btn-secondary:hover {
  background-color: #346b21;
  border-color: #346b21;
  color: white;
}

/* Badge styling */
.badge {
  padding: 0.5em 0.8em;
}

.primary-badge {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

.secondary-badge {
  background-color: var(--accent-color);
  color: white;
}

/* Card styling */
.card {
  border-color: var(--border-color);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid var(--border-color);
}

/* Table styling */
.table-responsive {
  margin-bottom: 0;
}

.table {
  margin-bottom: 0;
}

.table th {
  background-color: rgba(18, 53, 36, 0.05);
}

/* Responsive Design Improvements */
.location-header {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

@media (min-width: 576px) {
  .location-header {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
}

.location-title {
  margin: 0;
  font-size: 1rem;
  flex: 1;
}

.location-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Table Responsive Improvements */
@media (max-width: 767px) {
  .table-sm {
    font-size: 0.8rem;
  }
  
  .table-sm td, .table-sm th {
    padding: 0.25rem;
  }
  
  /* Stack table on mobile */
  .table-responsive {
    margin: 0 -0.75rem;
  }
  
  .modal-body {
    padding: 1rem;
  }
  
  /* Improve badge readability on mobile */
  .badge {
    padding: 0.35em 0.65em;
    font-size: 0.75em;
  }
}

/* QR Code Modal Improvements */
@media (max-width: 575px) {
  .modal-dialog-centered {
    margin: 0.5rem;
  }
  
  .modal-body {
    padding: 0.75rem;
  }
  
  .img-fluid {
    max-width: 200px; /* Slightly smaller QR code on mobile */
  }
}

/* General Modal Improvements */
.modal-lg {
  margin: 0.5rem;
  max-width: calc(100% - 1rem);
}

@media (min-width: 992px) {
  .modal-lg {
    max-width: 800px;
    margin: 1.75rem auto;
  }
}

/* Improve text wrapping for long content */
.modal-body p {
  word-break: break-word;
}

/* Improve list items on mobile */
.list-group-item {
  padding: 0.5rem;
}

@media (max-width: 575px) {
  .list-group-item {
    font-size: 0.9rem;
  }
}

/* Theme button improvements */
.theme-btn-secondary {
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

/* Improve checkpoint spacing on mobile */
@media (max-width: 575px) {
  .border-top.pt-3 {
    padding-top: 0.75rem !important;
  }
  
  .mb-4 {
    margin-bottom: 1rem !important;
  }
}
</style>