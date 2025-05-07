<template>
  <div class="card">
    <!-- In the Created Deliveries card header -->
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Created Deliveries</h5>
      <div>
        <button class="btn btn-sm btn-success me-2" @click="$emit('create-delivery')">
          <i class="fas fa-plus"></i>
        </button>
        <button class="btn btn-sm btn-outline-primary" @click="$emit('refresh')">
          <i class="fas fa-sync-alt"></i>
        </button>
      </div>
    </div>
    <!-- Add selected delivery info with deselect button -->
    <div v-if="selectedDeliveryID" class="p-2 bg-light border-bottom">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <span class="badge bg-success me-2">Selected</span>
          <span>Delivery #{{ selectedDeliveryID }}</span>
        </div>
        <button class="btn btn-sm btn-outline-danger" @click="$emit('deselect')">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    <div class="card-body p-0">
      <div v-if="loading" class="p-3 text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <div v-else-if="Object.keys(deliveries).length === 0" class="p-3 text-center">
        <p class="text-muted mb-0">No deliveries found</p>
      </div>
      <ul v-else class="list-group list-group-flush">
        <li v-for="(delivery, index) in deliveries" :key="index" 
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
                       @change="$emit('select', delivery.deliveryID)">
                <label class="form-check-label" :for="'delivery-' + delivery.deliveryID">
                  <span class="visually-hidden">Select Delivery #{{ delivery.deliveryID }}</span>
                </label>
              </div>
              <div>
                <div class="fw-bold">Delivery #{{ delivery.deliveryID }}</div>
                <div class="small">
                  <span class="badge bg-primary me-1">{{ delivery.status || 'Pending' }}</span>
                  <span class="text-muted">{{ formatDate(delivery.scheduledDate) }}</span>
                </div>
              </div>
            </div>
            <div>
              <i class="fas expand-icon" 
                 :class="expandedDeliveries[delivery.deliveryID] ? 'fa-chevron-up' : 'fa-chevron-down'"
                 @click.stop="$emit('toggle-details', delivery.deliveryID)"></i>
            </div>
          </div>
          <!-- Expanded Details Section -->
          <div v-if="expandedDeliveries[delivery.deliveryID]" class="mt-3 delivery-details">
            <div class="card">
              <div class="card-body">
                <div class="row">
                  <div class="col-12">
                    <h6>Delivery Information</h6>
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
      <div v-if="Object.keys(deliveries).length > 0" class="d-flex justify-content-center p-2">
        <button class="btn btn-sm btn-outline-secondary me-2" 
                :disabled="currentPage <= 1"
                @click="$emit('change-page', currentPage - 1)">
          <i class="fas fa-chevron-left"></i>
        </button>
        <span class="align-self-center mx-2">
          Page {{ currentPage }} of {{ pagination.last_page }}
          ({{ pagination.total }} total)
        </span>
        <button class="btn btn-sm btn-outline-secondary ms-2" 
                :disabled="!hasMorePages"
                @click="$emit('change-page', currentPage + 1)">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CreatedDeliveriesList',
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

.delivery-details {
  background-color: #f8f9fa;
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

.list-group-item:hover {
  background-color: #f8f9fa;
}

.list-group-item.active {
  background-color: #123524;
  border-color: #123524;
}

.list-group-item.active .text-muted {
  color: rgba(255, 255, 255, 0.7) !important;
}
</style>