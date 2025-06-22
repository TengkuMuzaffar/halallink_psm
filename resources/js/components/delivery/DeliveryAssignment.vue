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
      <!-- Add this right after the card-header div -->
      <div class="help-banner" v-if="!selectedDeliveryID">
        <div class="help-icon"><i class="fas fa-lightbulb"></i></div>
        <div class="help-text">
          <strong>Quick Tip:</strong> First create a delivery, then assign trips to it.
        </div>
      </div>
      <!-- Add Phase Tabs -->
      <!-- <div class="phase-tabs">
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
      </div> -->
      <!-- Replace the current phase tabs with this more intuitive version -->
      <div class="phase-selector mb-2">
        <div class="btn-group w-100 phase-btn-group">
          <button 
            class="btn phase-btn" 
            :class="activePhase === 1 ? 'btn-active' : 'btn-inactive'" 
            @click="changePhase(1)"
          >
            <div class="phase-icon"><i class="fas fa-truck-loading"></i></div>
            <div class="phase-text">
              <span class="phase-title">Pickup</span>
              <span class="phase-subtitle">Supplier → Slaughterhouse</span>
            </div>
          </button>
          <button 
            class="btn phase-btn" 
            :class="activePhase === 2 ? 'btn-active' : 'btn-inactive'" 
            @click="changePhase(2)"
          >
            <div class="phase-icon"><i class="fas fa-truck"></i></div>
            <div class="phase-text">
              <span class="phase-title">Delivery</span>
              <span class="phase-subtitle">Slaughterhouse → Customer</span>
            </div>
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
          <div class="table-responsive d-none d-md-block">
            <table class="table table-hover theme-table">
              <thead class="theme-table-header">
                <tr>
                  <th style="width: 8vw;">Reference</th>
                  <th>Start Location</th>
                  <th>End Location</th>
                  <th style="width: 10vw;">Action</th>
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
                  <td class="reference-cell">
                    <div class="id-container">
                      <div class="trip-id" data-bs-toggle="tooltip" :title="`Trip ID: ${trip.tripID}`">Trip #{{ trip.tripID }}</div>
                      <div class="order-id" data-bs-toggle="tooltip" :title="`Order ID: ${trip.orderID}`">Order #{{ trip.orderID }}</div>
                    </div>
                  </td>
                  <td class="location-cell">
                    <div class="location-display">
                      <div class="location-icon pickup-icon">
                        <i class="fas fa-map-marker-alt"></i>
                      </div>
                      <div class="location-details">
                        <div class="location-type">{{ trip.start_location.type }}</div>
                        <div class="location-address">{{ trip.start_location.address }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="location-cell">
                    <div class="location-display">
                      <div class="location-icon dropoff-icon">
                        <i class="fas fa-flag-checkered"></i>
                      </div>
                      <div class="location-details">
                        <div class="location-type">{{ trip.end_location.type }}</div>
                        <div class="location-address">{{ trip.end_location.address }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="action-cell">
                    <div class="btn-group">
                      <button 
                        class="btn btn-sm btn-icon" 
                        data-bs-toggle="modal"
                        :data-bs-target="`#itemModal-${trip.tripID}`"
                        title="View Items"
                      >
                        <i class="fas fa-box"></i>
                      </button>
                      <button 
                      class="btn btn-sm btn-icon-primary" 
                      @click="showAssignmentConfirmation(trip)"
                      :disabled="!selectedDeliveryID"
                      title="Assign to Delivery"
                    >
                      <i class="fas fa-plus-circle"></i>
                    </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div class="trip-cards d-md-none"> <!-- Show only on mobile -->
            <!-- Loading State -->
            <div v-if="loading" class="text-center py-4">
              <LoadingSpinner size="md" message="Loading delivery data..." />
            </div>
            <!-- No Data State -->
            <div v-else-if="!trips || trips.length === 0" class="empty-state p-4 text-center">
                <i class="fas fa-info-circle me-2 text-muted"></i>
                No trips found for the selected criteria.
              </div>
              
              <!-- Trip Cards for Mobile -->
              <div v-else class="trip-card-container">
                <div v-for="trip in trips" :key="trip.tripID" class="trip-card">
                  <div class="trip-card-header">
                    <div class="trip-ids">
                      <span class="trip-id">Trip #{{ trip.tripID }}</span> ||
                      <span class="order-id"> Order #{{ trip.orderID }}</span>
                    </div>
                    <div class="trip-actions">
                      <button 
                        class="btn btn-sm btn-icon"
                        data-bs-toggle="modal"
                        :data-bs-target="`#itemModal-${trip.tripID}`"
                        title="View Items"
                      >
                        <i class="fas fa-box"></i>
                      </button>
                    </div>
                  </div>
                  <div class="trip-card-body">
                    <div class="location-section">
                      <div class="location-icon pickup-icon">
                        <i class="fas fa-map-marker-alt"></i>
                      </div>
                      <div class="location-details">
                        <div class="location-label">Pickup</div>
                        <div class="location-type">{{ trip.start_location.type }}</div>
                        <div class="location-address">{{ trip.start_location.address }}</div>
                      </div>
                    </div>
                    <div class="location-connector"></div>
        
                  <div class="location-section">
                    <div class="location-icon dropoff-icon">
                      <i class="fas fa-flag-checkered"></i>
                    </div>
                    <div class="location-details">
                      <div class="location-label">Dropoff</div>
                      <div class="location-type">{{ trip.end_location.type }}</div>
                      <div class="location-address">{{ trip.end_location.address }}</div>
                    </div>
                  </div>
                </div>
                
                <div class="trip-card-footer">
                  <button 
                    class="btn btn-assign w-100"
                    @click="showAssignmentConfirmation(trip)"
                    :disabled="!selectedDeliveryID"
                  >
                    <i class="fas fa-plus-circle me-2"></i>
                    Assign to Delivery
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Add modals for each trip -->
          <ItemModal
            v-for="trip in trips"
            :key="`modal-${trip.tripID}`"
            :modal-id="`itemModal-${trip.tripID}`"
            :items="trip.items"
          />
          
          <!-- Add Pagination Component -->
          <div class="d-flex justify-content-center p-3" v-if="pagination && pagination.last_page > 1">
            <Pagination 
              :pagination="pagination" 
              :maxVisiblePages="5"
              @page-changed="$emit('change-page', $event)" 
            />
          </div>
        </div>
      </div>
    </div>
    <!-- Add confirmation modal at the end of the template -->
    <div class="modal fade" id="confirmAssignmentModal" tabindex="-1" aria-labelledby="confirmAssignmentModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmAssignmentModalLabel">Confirm Assignment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <strong>Important:</strong> Once a trip is assigned to a delivery, it cannot be edited, only deleted.
            </div>
            <p>Are you sure you want to assign this trip to the selected delivery?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="confirmAssignment">Confirm Assignment</button>
          </div>
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
    return {
      tripToAssign: null, // Store the trip that will be assigned after confirmation
      confirmModal: null  // Store the modal instance
    }; // Remove expandedTrip since we're using modal now
  },
  emits: ['refresh', 'assign-trip', 'change-phase', 'change-page'],
  methods: {
    changePhase(phase) {
      this.$emit('change-phase', phase);
    },
    // Add method to show confirmation modal
    showAssignmentConfirmation(trip) {
      this.tripToAssign = trip;
      this.confirmModal.show();
    },
    // Add method to confirm assignment
    confirmAssignment() {
      if (this.tripToAssign) {
        this.$emit('assign-trip', this.tripToAssign);
        this.confirmModal.hide();
        this.tripToAssign = null;
      }
    }
  },
    // Remove toggleItems method since we're using modal now
    mounted() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Initialize the confirmation modal
    this.confirmModal = new bootstrap.Modal(document.getElementById('confirmAssignmentModal'));
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

/* .theme-spinner {
  color: var(--accent-color);
} */

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
.location-display {
  display: flex;
  align-items: flex-start;
}

.location-cell .location-icon {
  width: 1.5rem;
  height: 1.5rem;
  font-size: 0.8rem;
  margin-right: 0.5rem;
  margin-top: 0.25rem;
}

/* Adjust the existing location-cell style */
.location-cell {
  min-width: 180px;
  max-width: 250px;
}
.action-cell {
  width: 6vw;
  text-align: center;
}

.btn-icon-primary {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background-color: var(--accent-color);
  color: white;
  border: none;
  margin-left: 0.25rem;
}

.btn-icon-primary:hover {
  background-color: #2e5c1d;
}

.btn-icon-primary:disabled {
  background-color: #6c757d;
  opacity: 0.65;
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
:deep(.pagination .page-link) {
  color: var(--primary-color);
}

:deep(.pagination .page-item.active .page-link) {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
}

:deep(.pagination .page-item.disabled .page-link) {
  color: var(--light-text);
}
/* Add these styles to your existing <style> section */

/* Phase Selector Styling */
.phase-selector {
  background-color: var(--lighter-bg);
  border-radius: 0.5rem;
  margin: 0.5rem 1rem;
  overflow: hidden;
}

.phase-btn-group {
  display: flex;
  width: 100%;
}

.phase-btn {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  border: none;
  background: transparent;
  transition: all 0.2s ease;
  flex: 1;
}

.btn-active {
  background-color: var(--accent-color);
  color: white;
}

.btn-inactive {
  background-color: transparent;
  color: var(--text-color);
}

.phase-icon {
  font-size: 1.25rem;
  margin-right: 0.75rem;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.2);
}

.phase-text {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.phase-title {
  font-weight: bold;
  font-size: 0.9rem;
}

.phase-subtitle {
  font-size: 0.75rem;
  opacity: 0.8;
}

/* Help Banner */
.help-banner {
  display: flex;
  align-items: center;
  background-color: var(--light-bg);
  padding: 0.75rem 1rem;
  margin: 0.5rem 1rem;
  border-radius: 0.5rem;
  border-left: 4px solid var(--accent-color);
}

.help-icon {
  color: var(--accent-color);
  font-size: 1.25rem;
  margin-right: 0.75rem;
}

.help-text {
  font-size: 0.9rem;
  color: var(--text-color);
}

/* Mobile Card View */
.trip-card-container {
  padding: 0.5rem;
}

.trip-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  margin-bottom: 1rem;
  overflow: hidden;
  border: 1px solid var(--border-color);
}

.trip-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  background-color: var(--lighter-bg);
  border-bottom: 1px solid var(--border-color);
}

.reference-cell {
  padding: 0.75rem 0.5rem;
}

.id-container {
  display: flex;
  flex-direction: column;
}

.trip-id {
  font-weight: bold;
  font-size: 0.9rem;
  color: var(--primary-color);
}

.order-id {
  font-size: 0.8rem;
  color: var(--light-text);
}

.btn-icon {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background-color: var(--lighter-bg);
  color: var(--accent-color);
  border: 1px solid var(--border-color);
}

.trip-card-body {
  padding: 1rem;
}

.location-section {
  display: flex;
  margin-bottom: 0.5rem;
}

.location-icon {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  margin-right: 0.75rem;
  flex-shrink: 0;
}

.pickup-icon {
  background-color: rgba(62, 123, 39, 0.1);
  color: var(--accent-color);
}

.dropoff-icon {
  background-color: rgba(18, 53, 36, 0.1);
  color: var(--primary-color);
}

.location-connector {
  width: 2px;
  height: 1.5rem;
  background-color: var(--border-color);
  margin-left: 1rem;
  margin-bottom: 0.5rem;
}

.location-details {
  flex-grow: 1;
}

.location-label {
  font-size: 0.75rem;
  color: var(--light-text);
  text-transform: uppercase;
}

.location-type {
  font-weight: bold;
  color: var(--text-color);
  font-size: 0.9rem;
}

.location-address {
  font-size: 0.85rem;
  color: var(--light-text);
  word-break: break-word;
}

.trip-card-footer {
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--border-color);
}

.btn-assign {
  background-color: var(--accent-color);
  color: white;
  border: none;
  padding: 0.5rem;
  border-radius: 0.25rem;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-assign:hover {
  background-color: #2e5c1d;
}

.btn-assign:disabled {
  background-color: #6c757d;
  opacity: 0.65;
}

/* Responsive Adjustments */
@media (max-width: 576px) {
  .phase-icon {
    margin-right: 0.5rem;
    width: 1.5rem;
    height: 1.5rem;
    font-size: 0.9rem;
  }
  
  .phase-title {
    font-size: 0.8rem;
  }
  
  .phase-subtitle {
    font-size: 0.7rem;
  }
  
  .trip-card-header {
    padding: 0.5rem 0.75rem;
  }
  
  .trip-card-body {
    padding: 0.75rem;
  }
  
  .location-icon {
    width: 1.5rem;
    height: 1.5rem;
    margin-right: 0.5rem;
  }
}

/* Fix the pagination styling with modern :deep() syntax */
:deep(.pagination .page-link) {
  color: var(--primary-color);
}

:deep(.pagination .page-item.active .page-link) {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
}

:deep(.pagination .page-item.disabled .page-link) {
  color: var(--light-text);
}

/* Modal Theme Styling */
.modal-content {
  border-color: var(--border-color);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.modal-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
}

.modal-header .btn-close {
  color: var(--secondary-color);
  opacity: 0.8;
  filter: invert(1) brightness(1.8);
}

.modal-header .btn-close:hover {
  opacity: 1;
}

.modal-body {
  color: var(--text-color);
}

.modal-body .alert-warning {
  background-color: var(--light-bg);
  border-color: var(--accent-color);
  color: var(--text-color);
}

.modal-footer {
  border-top-color: var(--border-color);
}

.modal-footer .btn-outline-secondary {
  color: var(--text-color);
  border-color: var(--border-color);
}

.modal-footer .btn-outline-secondary:hover {
  background-color: var(--lighter-bg);
  color: var(--primary-color);
}

.modal-footer .btn-primary {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
  color: var(--secondary-color);
}

.modal-footer .btn-primary:hover {
  background-color: #2e5c1d;
  border-color: #2e5c1d;
}
</style>
