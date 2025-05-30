<template>
  <div class="card theme-card">
    <!-- In the Created Deliveries card header -->
    <div class="card-header d-flex justify-content-between align-items-center theme-header">
      <h5 class="mb-0">Created Deliveries</h5>
      <div>
        <button class="btn btn-sm btn-primary me-2" @click="$emit('create-delivery')">
          <i class="fas fa-plus"></i>
        </button>
        <button class="btn btn-sm theme-btn-outline" @click="$emit('refresh')">
          <i class="fas fa-sync-alt"></i>
        </button>
      </div>
    </div>
    <!-- Add selected delivery info with deselect button -->
    <div v-if="selectedDeliveryID" class="p-2 theme-selected-delivery border-bottom">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <span class="badge theme-badge-accent me-2">Selected</span>
          <span>Delivery #{{ selectedDeliveryID }}</span>
        </div>
        <button class="btn btn-sm theme-btn-outline-danger" @click="$emit('deselect')">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    <div class="card-body p-0">
      <LoadingSpinner 
        v-if="loading" 
        size="md" 
        message="Loading deliveries..." 
      />
      <div v-else-if="Object.keys(deliveries).length === 0" class="p-3 text-center">
        <p class="text-muted mb-0">No deliveries found</p>
      </div>
      <ul v-else class="list-group list-group-flush">
        <li v-for="(delivery, index) in deliveries" :key="index" 
            class="list-group-item theme-list-item"
            :class="{'theme-list-item-active': selectedDeliveryID === delivery.deliveryID}">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
              <div class="form-check me-2">
                <input class="form-check-input theme-checkbox" 
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
              <div>
                <div class="fw-bold">Delivery #{{ delivery.deliveryID }}</div>
                <div class="small">
                  <span class="badge theme-badge-primary me-1">{{ delivery.status || 'Pending' }}</span>
                  <span class="text-muted">{{ formatDate(delivery.scheduledDate) }}</span>
                </div>
              </div>
            </div>
            <div>
              <i class="fas theme-icon expand-icon" 
                 :class="expandedDeliveries[delivery.deliveryID] ? 'fa-chevron-up' : 'fa-chevron-down'"
                 @click.stop="$emit('toggle-details', delivery.deliveryID)"></i>
            </div>
          </div>
          <!-- Expanded Details Section -->
          <div v-if="expandedDeliveries[delivery.deliveryID]" class="mt-3 delivery-details theme-details">
            <div class="card theme-inner-card">
              <div class="card-body">
                <div class="row">
                  <div class="col-12">
                    <h6 class="theme-subtitle">Delivery Information</h6>
                    <p class="mb-1 w-100"><strong>Status:</strong> {{ delivery.status || 'Pending' }}</p>
                    <p class="mb-1 w-100"><strong>Scheduled Date:</strong> {{ formatDate(delivery.scheduledDate) }}</p>
                    <p class="mb-1 w-100"><strong>Driver:</strong> {{ delivery.driver?.fullname || 'Not assigned' }}</p>
                    <p class="mb-1 w-100"><strong>Vehicle:</strong> {{ delivery.vehicle?.vehicle_plate || 'Not assigned' }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </li>
      </ul>
      <!-- Simple pagination for created deliveries -->
      <div v-if="Object.keys(deliveries).length > 0" class="d-flex justify-content-center p-2 theme-pagination-container">
        <button class="btn btn-sm theme-btn-outline me-2" 
                :disabled="currentPage <= 1"
                @click="$emit('change-page', currentPage - 1)">
          <i class="fas fa-chevron-left"></i>
        </button>
        <span class="align-self-center mx-2 theme-pagination-text">
          Page {{ currentPage }} of {{ pagination.last_page }}
          ({{ pagination.total }} total)
        </span>
        <button class="btn btn-sm theme-btn-outline ms-2" 
                :disabled="!hasMorePages"
                @click="$emit('change-page', currentPage + 1)">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import LoadingSpinner from '../ui/LoadingSpinner.vue';

export default {
  name: 'CreatedDeliveriesList',
  components: {
    LoadingSpinner
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
  methods: {
    formatDate(date) {
      if (!date) return 'Not scheduled';
      
      // You can use the same date formatting logic as in your parent component
      // or implement a simple one here
      const dateObj = new Date(date);
      return dateObj.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    }
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
  transition: background-color 0.2s ease;
}

.theme-list-item-active {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
}

.theme-list-item-active .text-muted {
  color: rgba(239, 227, 194, 0.7) !important;
}

/* Icon styles */
.theme-icon {
  color: var(--accent-color);
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
</style>