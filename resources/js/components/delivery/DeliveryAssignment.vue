<template>
  <div class="delivery-assignment">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center theme-header">
        <h5 class="mb-0">Assign Deliveries</h5>
        <div class="d-flex">
          <button class="btn btn-sm btn-outline-primary theme-btn-outline" @click="$emit('refresh')">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
      </div>
      
      <!-- Add Phase Tabs -->
      <div class="phase-tabs">
        <div class="nav nav-tabs" role="tablist">
          <button 
            class="nav-link" 
            :class="{ active: activePhase === 1 }" 
            @click="changePhase(1)"
            id="phase1-tab"
            type="button"
            role="tab"
            aria-controls="phase1"
            aria-selected="activePhase === 1"
          >
            <i class="fas fa-truck-loading me-2"></i>Phase 1 (Supplier to Slaughterhouse)
          </button>
          <button 
            class="nav-link" 
            :class="{ active: activePhase === 2 }" 
            @click="changePhase(2)"
            id="phase2-tab"
            type="button"
            role="tab"
            aria-controls="phase2"
            aria-selected="activePhase === 2"
          >
            <i class="fas fa-truck me-2"></i>Phase 2 (Slaughterhouse to Customer)
          </button>
        </div>
      </div>
      
      <div class="card-body p-0">
        <div v-if="error" class="p-4">
          <div class="alert alert-danger mb-0">
            {{ error }}
          </div>
        </div>
        
        <div v-else>
          <div class="table-responsive">
            <table class="table table-hover theme-table">
              <thead class="theme-table-header">
                <tr>
                  <th style="width: 5vw;">Trip ID</th>
                  <th style="width: 5vw;">Order ID</th>
                  <th>Start Location</th>
                  <th>End Location</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <!-- Loading State Inside Table -->
                <tr v-if="loading" class="table-loading-row">
                  <td colspan="5" class="text-center py-4">
                    <LoadingSpinner 
                      size="md" 
                      message="Loading delivery data..." 
                    />
                  </td>
                </tr>
                
                <!-- No Data State -->
                <tr v-else-if="!trips || trips.length === 0" class="table-empty-row">
                  <td colspan="5" class="text-center py-4">
                    <div class="empty-state">
                      <i class="fas fa-info-circle me-2 text-muted"></i>
                      No trips found for the selected criteria.
                    </div>
                  </td>
                </tr>
                
                <!-- Data Rows -->
                <tr v-else v-for="trip in trips" :key="trip.tripID">
                  <td class="text-nowrap">{{ trip.tripID }}</td>
                  <td class="text-nowrap">{{ trip.orderID }}</td>
                  <td class="location-cell">
                    <div class="location-type">{{ trip.start_location.type }}</div>
                    <div class="location-address">{{ trip.start_location.address }}</div>
                  </td>
                  <td class="location-cell">
                    <div class="location-type">{{ trip.end_location.type }}</div>
                    <div class="location-address">{{ trip.end_location.address }}</div>
                  </td>
                  <td>
                    <div class="d-flex gap-2 flex-wrap justify-content-start">
                      <button 
                        class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="modal"
                        :data-bs-target="`#itemModal-${trip.tripID}`"
                      >
                        <i class="fas fa-box me-1"></i>
                        <span class="ms-1 d-none d-sm-inline">Items</span>
                      </button>
                      <button 
                        class="btn btn-sm btn-primary theme-btn-primary"
                        @click="$emit('assign-trip', trip)"
                        :disabled="!selectedDeliveryID"
                      >
                        <i class="fas fa-plus-circle me-1"></i>
                        <span class="ms-1 d-none d-sm-inline">Assign</span>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Add modals for each trip -->
          <ItemModal
            v-for="trip in trips"
            :key="`modal-${trip.tripID}`"
            :modal-id="`itemModal-${trip.tripID}`"
            :items="trip.items"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Pagination from '../ui/Pagination.vue';
import ItemModal from './ItemModal.vue';
import LoadingSpinner from '../ui/LoadingSpinner.vue';

export default {
  name: 'DeliveryAssignment',
  components: {
    Pagination,
    ItemModal,
    LoadingSpinner
  },
  props: {
    loading: Boolean,
    error: String,
    trips: {
      type: Array,
      default: () => []
    },
    pagination: Object,
    selectedDeliveryID: [Number, String],
    activePhase: {
      type: Number,
      required: true
    }
  },
  data() {
    return {}; // Remove expandedTrip since we're using modal now
  },
  emits: ['refresh', 'assign-trip', 'change-phase', 'change-page'],
  methods: {
    changePhase(phase) {
      this.$emit('change-phase', phase);
    }
    // Remove toggleItems method since we're using modal now
  }
};
</script>

<style scoped>
/* Updated theme colors from CreatedDeliveriesList.vue */
.delivery-assignment .card {
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

.phase-tabs .nav-tabs {
  background-color: var(--lighter-bg);
}

.phase-tabs .nav-link {
  color: var(--text-color);
}

.phase-tabs .nav-link.active {
  color: var(--accent-color);
  border-bottom-color: var(--accent-color);
}

.theme-spinner {
  color: var(--accent-color);
}

.theme-alert-info {
  background-color: var(--light-bg);
  color: var(--primary-color);
  border-color: var(--border-color);
}

.theme-table {
  --table-border-color: var(--border-color);
}

.theme-table-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

.theme-btn-primary {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
  color: white;
}

.theme-btn-primary:hover {
  background-color: #2e5c1d;
  border-color: #2e5c1d;
}
.theme-btn-primary:disabled {
  background-color: #6c757d; /* Disabled state color */
  border-color: #6c757d;
}

.list-unstyled {
  padding-left: 0;
  list-style: none;
}

.pagination .page-link {
  color: #007bff;
}

.pagination .page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
  color: #fff;
}

.pagination .page-item.disabled .page-link {
  color: #6c757d;
}

/* Ensure no extra padding if card-body has p-0 */
.card-body.p-0 .table-responsive {
    border-top: 1px solid #e0e0e0; /* Add a top border if needed */
}

.items-carousel {
  overflow-x: auto;
  padding: 1rem 0;
}

.item-card {
  min-width: 200px;
  max-width: 250px;
}

.item-card .card {
  background-color: white;
  border: 1px solid var(--border-color);
  transition: transform 0.2s;
}

.item-card .card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Responsive styles for mobile */
@media (max-width: 768px) {
  .table > :not(caption) > * > * {
    padding: 0.5rem;
  }

  .location-cell {
    min-width: 200px;
  }

  .location-type {
    font-weight: bold;
    color: var(--accent-color);
    text-transform: capitalize;
    font-size: 0.875rem;
  }

  .location-address {
    font-size: 0.875rem;
    word-break: break-word;
  }

  .item-card {
    min-width: 160px;
    max-width: 200px;
  }

  .items-carousel {
    margin: 0 -0.5rem;
    padding: 0.5rem;
  }

  .card-body {
    padding: 0.75rem;
  }

  .card-title {
    font-size: 1rem;
  }

  .card-text {
    font-size: 0.875rem;
  }
}
</style>
