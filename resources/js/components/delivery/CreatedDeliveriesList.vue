<template>
  <div class="card theme-card">
    <!-- Card header with simplified controls -->
    <div class="card-header d-flex justify-content-between align-items-center theme-header">
      <h5 class="mb-0 fs-4">Created Deliveries</h5>
      <div>
        <button class="btn btn-primary me-2 easy-touch" @click="$emit('create-delivery')" aria-label="Create new delivery">
          <i class="fas fa-plus"></i>
          <span class="d-none d-md-inline ms-1">New</span>
        </button>
        <button class="btn theme-btn-outline easy-touch" @click="$emit('refresh')" aria-label="Refresh list">
          <i class="fas fa-sync-alt"></i>
          <span class="d-none d-md-inline ms-1">Refresh</span>
        </button>
      </div>
    </div>
    
    <!-- Selected delivery info with larger text -->
    <div v-if="selectedDeliveryID" class="p-3 theme-selected-delivery border-bottom">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <span class="badge theme-badge-accent me-2 fs-6">Selected</span>
          <span class="fs-5">Delivery #{{ selectedDeliveryID }}</span>
        </div>
        <button class="btn theme-btn-outline-danger easy-touch" @click="$emit('deselect')" aria-label="Deselect delivery">
          <i class="fas fa-times"></i>
          <span class="d-none d-md-inline ms-1">Deselect</span>
        </button>
      </div>
    </div>
    
    <!-- Help text for new users -->
    <div class="p-2 bg-light border-bottom">
      <p class="mb-0 text-center"><i class="fas fa-info-circle me-1"></i> Tap on a delivery to select it. Use the large buttons for actions.</p>
    </div>
    
    <div class="card-body p-0">
      <LoadingSpinner 
        v-if="loading" 
        size="lg" 
        message="Loading deliveries..." 
      />
      <div v-else-if="Object.keys(deliveries).length === 0" class="p-4 text-center">
        <p class="text-muted mb-0 fs-5">No deliveries found</p>
      </div>
      <ul v-else class="list-group list-group-flush">
        <li v-for="(delivery, index) in deliveries" :key="index" 
            class="list-group-item theme-list-item p-3"
            :class="{'theme-list-item-active': selectedDeliveryID === delivery.deliveryID}"
            @click="$emit('select', delivery.deliveryID)">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center flex-grow-1">
              <!-- Larger radio button for easier selection -->
              <div class="form-check me-3">
                <input class="form-check-input theme-checkbox large-checkbox" 
                       type="radio" 
                       :id="'delivery-' + delivery.deliveryID" 
                       name="selectedDelivery" 
                       :value="delivery.deliveryID"
                       :checked="selectedDeliveryID === delivery.deliveryID"
                       @change="$emit('select', delivery.deliveryID)">
                <label class="form-check-label" :for="'delivery-' + delivery.deliveryID">
                  <span class="visually-hidden">Select Delivery #{{ delivery.deliveryID }}</span>
                </label>
              </div>
              <div class="flex-grow-1">
                <div class="fw-bold fs-5">Delivery #{{ delivery.deliveryID }}</div>
                <div class="fs-6">
                  <span class="badge theme-badge-primary me-1 p-2">{{ delivery.status || 'Pending' }}</span>
                  <span class="text-muted">{{ formatDate(delivery.scheduledDate) }}</span>
                </div>
              </div>
            </div>
            <!-- Larger touch target for expand/collapse -->
            <div class="easy-touch-circle" @click.stop="$emit('toggle-details', delivery.deliveryID)">
              <i class="fas theme-icon expand-icon" 
                 :class="expandedDeliveries[delivery.deliveryID] ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </div>
          </div>
          
          <!-- Expanded Details Section with larger text -->
          <div v-if="expandedDeliveries[delivery.deliveryID]" class="mt-3 delivery-details theme-details">
            <div class="card theme-inner-card">
              <div class="card-body p-3">
                <div class="row">
                  <div class="col-12">
                    <h6 class="theme-subtitle fs-5">Delivery Information</h6>
                    <p class="mb-2 w-100 fs-6"><strong>Status:</strong> {{ delivery.status || 'Pending' }}</p>
                    <p class="mb-2 w-100 fs-6"><strong>Scheduled Date:</strong> {{ formatDate(delivery.scheduledDate) }}</p>
                    <p class="mb-2 w-100 fs-6"><strong>Driver:</strong> {{ delivery.driver?.fullname || 'Not assigned' }}</p>
                    <p class="mb-2 w-100 fs-6"><strong>Vehicle:</strong> {{ delivery.vehicle?.vehicle_plate || 'Not assigned' }}</p>
                    
                    <!-- Action buttons with larger touch targets -->
                    <div class="mt-4 d-flex justify-content-center">
                      <button 
                        class="btn theme-btn-outline-danger easy-touch-lg" 
                        @click.stop="confirmDeleteDelivery(delivery.deliveryID)"
                        :disabled="delivery.status === 'In Progress' || delivery.status === 'Completed'"
                      >
                        <i class="fas fa-trash-alt me-2"></i> Delete Delivery
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </li>
      </ul>
      
      <!-- Replace the current pagination section with this: -->
      <div v-if="Object.keys(deliveries).length > 0" class="p-3 theme-pagination-container">
        <Pagination 
          :pagination="pagination" 
          :maxVisiblePages="3" 
          @page-changed="$emit('change-page', $event)" 
          class="large-pagination"
        />
      </div>
    </div>
  </div>
  
  <!-- Simplified confirmation modal with larger text -->
  <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fs-4" id="confirmDeleteModalLabel">Confirm Deletion</h5>
          <button type="button" class="btn-close btn-close-lg" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Warning:</strong> This action cannot be undone. The delivery will be permanently deleted.
          </div>
          <p class="fs-5">Are you sure you want to delete this delivery?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary easy-touch-lg" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i> Cancel
          </button>
          <button type="button" class="btn btn-danger easy-touch-lg" @click="deleteDelivery">
            <i class="fas fa-trash-alt me-1"></i> Delete Permanently
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import LoadingSpinner from '../ui/LoadingSpinner.vue';
import Pagination from '../ui/Pagination.vue';
import deliveryService from '../../services/deliveryService';

export default {
  name: 'CreatedDeliveriesList',
  components: {
    LoadingSpinner,
    Pagination
  },
  props: {
    deliveries: {
      type: Object,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    },
    selectedDeliveryID: {
      type: [Number, String],
      default: null
    },
    expandedDeliveries: {
      type: Object,
      default: () => ({})
    },
    currentPage: {
      type: Number,
      default: 1
    },
    pagination: {
      type: Object,
      default: () => ({
        current_page: 1,
        last_page: 1,
        total: 0
      })
    },
    hasMorePages: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      deliveryToDelete: null,
      confirmModal: null
    };
  },
  emits: ['refresh', 'select', 'deselect', 'toggle-details', 'change-page', 'create-delivery'],
  methods: {
    formatDate(date) {
      if (!date) return 'Not scheduled';
      
      const dateObj = new Date(date);
      return dateObj.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },
    confirmDeleteDelivery(deliveryID) {
      this.deliveryToDelete = deliveryID;
      this.confirmModal.show();
    },
    async deleteDelivery() {
      if (!this.deliveryToDelete) return;
      
      try {
        const response = await deliveryService.deleteDelivery(this.deliveryToDelete);
        
        if (response.success) {
          // If the deleted delivery was selected, deselect it
          if (this.selectedDeliveryID === this.deliveryToDelete) {
            this.$emit('deselect');
          }
          
          // Refresh the deliveries list
          this.$emit('refresh');
          this.confirmModal.hide();
          this.deliveryToDelete = null;
        } else {
          console.error('Failed to delete delivery:', response.message);
          // You could add error handling here
        }
      } catch (error) {
        console.error('Error deleting delivery:', error);
        // You could add error handling here
      }
    }
  },
  mounted() {
    // Initialize the confirmation modal
    this.confirmModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
  }
}
</script>

<style scoped>
/* Theme colors */
.theme-card {
  --primary-color: #123524;
  --secondary-color: #EFE3C2;
  --accent-color: #3E7B27;
  --text-color: #333;
  --light-text: #666;
  --border-color: rgba(18, 53, 36, 0.2);
  --light-bg: rgba(239, 227, 194, 0.2);
  --lighter-bg: rgba(239, 227, 194, 0.1);
  --selected-bg: #2A6B3A; /* Brighter green for better visibility */
  --selected-text: #FFFFFF; /* White text for maximum contrast */
  --selected-border: #4CAF50; /* Bright green border for emphasis */
  --selected-highlight: rgba(76, 175, 80, 0.3); /* Highlight color for selected elements */
  
  border-color: var(--border-color);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
}

.theme-selected-delivery {
  background-color: var(--light-bg);
  color: var(--primary-color);
}

/* Button styles */
.theme-btn-accent {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
  color: var(--secondary-color);
}

.theme-btn-accent:hover {
  background-color: #2e5c1d;
  border-color: #2e5c1d;
  color: var(--secondary-color);
}

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

.theme-btn-outline:disabled {
  color: rgba(239, 227, 194, 0.5);
  border-color: rgba(239, 227, 194, 0.5);
}

.theme-btn-outline-danger {
  color: #dc3545;
  border-color: #dc3545;
  background-color: transparent;
}

.theme-btn-outline-danger:hover {
  color: white;
  background-color: #dc3545;
  border-color: #dc3545;
}

/* Badge styles */
.theme-badge-primary {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

.theme-badge-accent {
  background-color: var(--accent-color);
  color: var(--secondary-color);
}

/* List styles */
.theme-list-item {
  border-color: var(--border-color);
  transition: all 0.3s ease; /* Smoother transition for all properties */
}

.theme-list-item-active {
  background-color: var(--selected-bg);
  border-color: var(--selected-border);
  color: var(--selected-text);
  box-shadow: 0 0 0 2px var(--selected-border); /* Outline effect */
  transform: translateY(-2px); /* Slight lift effect */
}

.theme-list-item-active .text-muted {
  color: rgba(255, 255, 255, 0.8) !important; /* Brighter muted text */
}

/* Selected delivery banner */
.theme-selected-delivery {
  background-color: var(--selected-highlight);
  color: var(--primary-color);
  border-left: 4px solid var(--selected-border); /* Left accent border */
}

/* Checkbox styling */
.theme-checkbox {
  border-color: var(--accent-color);
}

.theme-checkbox:checked {
  background-color: var(--selected-border); /* Brighter green for checked state */
  border-color: var(--selected-border);
  box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.3); /* Glow effect around checkbox */
}

/* Icon styles */
.theme-icon {
  color: var(--accent-color);
}

.theme-list-item-active .theme-icon {
  color: var(--selected-text);
}

.theme-list-item-active .easy-touch-circle {
  background-color: rgba(255, 255, 255, 0.2); /* Brighter background for icons */
  border: 1px solid rgba(255, 255, 255, 0.4); /* Subtle border */
}

.theme-list-item-active .theme-icon {
  color: var(--secondary-color);
}

/* Spinner */
.theme-spinner {
  color: var(--accent-color);
}

/* Details section */
.theme-details {
  background-color: transparent;
}

.theme-inner-card {
  background-color: rgba(255, 255, 255, 0.7);
  border-color: var(--border-color);
}

.theme-list-item-active .theme-inner-card {
  background-color: rgba(255, 255, 255, 0.9);
  color: var(--text-color);
}

.theme-subtitle {
  color: var(--primary-color);
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 0.5rem;
  margin-bottom: 1rem;
}

/* Pagination */
.theme-pagination-container {
  background-color: var(--lighter-bg);
  border-top: 1px solid var(--border-color);
}

.theme-pagination-text {
  color: var(--text-color);
}

/* Checkbox styling */
.theme-checkbox {
  border-color: var(--accent-color);
}

.theme-checkbox:checked {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
}

/* Original styles with theme modifications */
.expand-icon {
  cursor: pointer;
  padding: 8px;
  border-radius: 50%;
  transition: background-color 0.2s;
}

.expand-icon:hover {
  background-color: rgba(62, 123, 39, 0.1);
}

.theme-list-item-active .expand-icon:hover {
  background-color: rgba(239, 227, 194, 0.2);
}

.delivery-details {
  border-radius: 0.25rem;
  transition: all 0.3s ease;
  width: 100%;
}

.delivery-details .card {
  width: 100%;
}

.delivery-details .card-body {
  padding: 1rem;
}

.delivery-details p {
  word-break: break-word;
}

.list-group-item {
  cursor: pointer;
}

/* New styles for elderly users and small screens */
.easy-touch {
  min-height: 44px;
  min-width: 44px;
  padding: 0.5rem 1rem;
  font-size: 1rem;
}

/* Thumb zone optimization for mobile */
@media (max-width: 767.98px) {
  .card-header {
    padding: 1rem;
  }
  
  .easy-touch, .easy-touch-lg {
    padding: 0.75rem;
  }
  
  /* Add these new styles for better responsiveness on small screens */
  .easy-touch {
    min-height: 38px;
    min-width: 38px;
    padding: 0.4rem 0.6rem;
    font-size: 0.9rem;
  }
  
  /* Make icons slightly smaller on mobile */
  .easy-touch i.fas {
    font-size: 0.9rem;
  }
  
  /* Ensure the header doesn't get too crowded */
  .card-header h5.fs-4 {
    font-size: 1.1rem !important;
  }
}

/* Add an extra breakpoint for very small screens */
@media (max-width: 375px) {
  .easy-touch {
    min-height: 34px;
    min-width: 34px;
    padding: 0.3rem 0.5rem;
    font-size: 0.85rem;
  }
  
  .card-header {
    padding: 0.75rem;
  }
  
  .card-header h5.fs-4 {
    font-size: 1rem !important;
  }
}
</style>

