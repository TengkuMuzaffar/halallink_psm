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
        <div v-if="loading" class="p-4 text-center">
          <div class="spinner-border theme-spinner" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 text-muted">Loading delivery data...</p>
        </div>
        
        <div v-else-if="error" class="p-4">
          <div class="alert alert-danger mb-0">
            {{ error }}
          </div>
        </div>
        
        <div v-else-if="!trips || trips.length === 0" class="p-4 text-center">
          <div class="alert theme-alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i> No trips found for the selected criteria.
          </div>
        </div>
        
        <div v-else>
          <!-- Display individual trips in a table -->
          <div class="table-responsive">
            <table class="table table-hover theme-table">
              <thead class="theme-table-header">
                <tr>
                  <th>Trip ID</th>
                  <th>Order ID</th>
                  <th>Start Location</th>
                  <th>End Location</th>
                  <th>Items</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="trip in trips" :key="trip.tripID">
                  <td>{{ trip.tripID }}</td>
                  <td>{{ trip.orderID }}</td>
                  <td>{{ trip.start_location.address }} ({{ trip.start_location.type }})</td>
                  <td>{{ trip.end_location.address }} ({{ trip.end_location.type }})</td>
                  <td>
                    <ul class="list-unstyled mb-0">
                      <li v-for="item in trip.items" :key="item.itemID">
                        {{ item.name }} ({{ item.measurement_value }} {{ item.measurement_type }})
                      </li>
                    </ul>
                  </td>
                  <td>
                    <button 
                      class="btn btn-sm btn-primary theme-btn-primary"
                      @click="$emit('assign-trip', trip)"
                      :disabled="!selectedDeliveryID"
                    >
                      <i class="fas fa-plus-circle me-1"></i> Assign
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <!-- Replace the existing pagination div with: -->
          <!-- Replace this condition -->
          <!-- Show pagination whenever we have pagination data -->
          <div v-if="pagination" class="p-3 border-top">
            <Pagination 
              :pagination="pagination"
              @page-changed="$emit('change-page', $event)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Pagination from '../ui/Pagination.vue';

export default {
  name: 'DeliveryAssignment',
  components: {
    Pagination
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
  emits: ['refresh', 'assign-trip', 'change-phase', 'change-page'], // Added 'assign-trip'
  methods: {
    changePhase(phase) {
      this.$emit('change-phase', phase);
    },
    // Removed methods related to groupedDeliveries like getLocationName, getFilteredDeliveries, etc.
    // as the data structure is now a flat list of trips.
  }
};
</script>

<style scoped>
.delivery-assignment .card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  overflow: hidden; /* Ensures child elements conform to border radius */
}

.theme-header {
  background-color: #f8f9fa; /* Light grey background */
  color: #333; /* Darker text color */
  padding: 0.75rem 1.25rem;
  border-bottom: 1px solid #e0e0e0;
}

.theme-btn-outline {
  color: #007bff; /* Primary color for outline button */
  border-color: #007bff;
}

.theme-btn-outline:hover {
  background-color: #007bff;
  color: #fff;
}

.phase-tabs .nav-tabs {
  border-bottom: 1px solid #dee2e6;
  background-color: #fff; /* White background for tabs */
}

.phase-tabs .nav-link {
  color: #495057; /* Standard tab text color */
  border: 1px solid transparent;
  border-bottom: none;
  padding: 0.75rem 1.25rem;
  font-weight: 500;
}

.phase-tabs .nav-link.active {
  color: #007bff; /* Primary color for active tab */
  background-color: #fff;
  border-color: #dee2e6 #dee2e6 #fff;
  border-bottom: 2px solid #007bff; /* Highlight active tab */
}

.phase-tabs .nav-link i {
  margin-right: 0.5rem;
}

.theme-spinner {
  color: #007bff; /* Primary color for spinner */
}

.theme-alert-info {
  background-color: #e6f7ff; /* Light blue for info alerts */
  color: #005c99; /* Darker blue text for info alerts */
  border: 1px solid #b3e0ff; /* Border for info alerts */
}

.table-responsive {
  margin-top: 0; /* Remove any top margin if card-body has p-0 */
}

.theme-table {
  margin-bottom: 0; /* Remove bottom margin if it's the last element in card-body */
}

.theme-table-header th {
  background-color: #f1f3f5; /* Slightly different shade for table header */
  color: #333;
  font-weight: 600;
  border-bottom: 2px solid #dee2e6;
  text-align: left;
  padding: 0.75rem;
}

.theme-table td {
  vertical-align: middle;
  padding: 0.75rem;
  border-top: 1px solid #e9ecef; /* Lighter border for table rows */
}

.theme-table tbody tr:hover {
  background-color: #f8f9fa; /* Hover effect for table rows */
}

.theme-btn-primary {
  background-color: #007bff;
  border-color: #007bff;
  color: #fff;
}

.theme-btn-primary:hover {
  background-color: #0056b3;
  border-color: #0056b3;
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

</style>
