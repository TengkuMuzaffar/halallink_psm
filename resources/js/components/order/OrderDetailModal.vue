<template>
  
  <!-- QR Code Modal -->
  <div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header theme-header">
          <h5 class="modal-title" id="qrCodeModalLabel">{{ qrCodeTitle || 'QR Code' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <div v-if="qrCodeLoading" class="my-3">
            <LoadingSpinner message="Generating QR code..." />
          </div>
          <div v-else>
            <div class="mb-3">
              <img v-if="qrCodeUrl" :src="qrCodeUrl" alt="QR Code" class="img-fluid secure-qr" style="max-width: 250px;" @contextmenu.prevent />
              <p v-else class="text-danger">Failed to generate QR code.</p>
            </div>
            <div class="mb-3">
              <!-- Remove the Order ID display -->
              <p v-if="selectedLocationID" class="mb-1"><strong>Location ID:</strong> {{ selectedLocationID }}</p>
              <p v-if="qrCodeDescription" class="mt-2">{{ qrCodeDescription }}</p>
            </div>
            <!-- Removed the URL input and copy button -->
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeQRModal">Back</button>
          <!-- Removed the download button -->
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
    },
    showMainModalAfterQR: {
      type: Boolean,
      default: true
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
      qrCodeLink: '',
      qrCodeTitle: '',
      qrCodeDescription: '',
      selectedOrderID: null,
      selectedLocationID: null,
      qrModal: null,
      mainModal: null,
      copySuccess: false,
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
    showQRCode(orderID, locationID, companyID = null) {
      // Don't set selectedOrderID to hide it from display
      this.selectedOrderID = null; // Changed from orderID to null
      this.selectedLocationID = locationID;
      this.qrCodeLoading = true;
      this.qrCodeUrl = null;
      
      // Get modal element
      const qrModalEl = document.getElementById('qrCodeModal');
      
      // Find the location to get its companyID if not provided
      if (companyID === null && this.order && this.order.locations) {
        const location = this.order.locations.find(loc => loc.locationID === locationID);
        if (location) {
          companyID = location.companyID;
        }
      }
      
      // Generate QR code URL for process route
      const apiUrl = `/api/qrcode/process/${locationID}/${companyID || 0}`;
      this.qrCodeLink = `${window.location.origin}${apiUrl}`;
      this.qrCodeTitle = 'Location QR Code';
      this.selectedLocationID = `Location ${locationID}`; 
      this.qrCodeDescription = 'Scan this QR code to process items at this location.';
      
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
    
    // New method to generate QR code for order verification
    generateOrderVerificationQR(orderID) {
      this.selectedOrderID = orderID;
      this.qrCodeLoading = true;
      this.qrCodeUrl = null;
      
      // Get modal element
      const qrModalEl = document.getElementById('qrCodeModal');
      
      // Generate QR code URL for verification route
      const apiUrl = `/api/qrcode/verifies/${orderID}`;
      this.qrCodeLink = `${window.location.origin}${apiUrl}`;
      this.qrCodeTitle = 'Order Verification QR Code';
      this.qrCodeDescription = 'Scan this QR code to check the verification status of this order.';
      
      // Generate QR code image
      this.generateQRCode(this.qrCodeLink);
      
      // Show QR code modal
      if (this.qrModal) {
        if (qrModalEl) {
          qrModalEl.removeAttribute('inert');
          qrModalEl.removeAttribute('aria-hidden');
        }
        this.qrModal.show();
      }
    },
    closeQRModal() {
      // Get modal element
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
          
          // Clear the link from memory for security
          this.qrCodeLink = '';
        }
      });
    },
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
    
    // Initialize only the QR code modal
    this.qrModal = new bootstrap.Modal(document.getElementById('qrCodeModal'), {
      backdrop: true,
      keyboard: true,
      focus: true
    });
    
    // Remove aria-hidden when added by Bootstrap
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
    
    // Observe QR modal
    if (qrModalEl) {
      observer.observe(qrModalEl, { attributes: true });
      qrModalEl.addEventListener('hidden.bs.modal', (event) => {
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

/* Secure QR code styling */
.secure-qr {
  user-select: none;
  pointer-events: none;
  -webkit-user-drag: none;
  -khtml-user-drag: none;
  -moz-user-drag: none;
  -o-user-drag: none;
}
</style>