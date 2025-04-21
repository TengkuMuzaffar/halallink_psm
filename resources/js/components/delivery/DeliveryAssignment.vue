<template>
  <div>
    <!-- Location Filter -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-4">
            <label for="locationFilter" class="form-label">Filter by Location:</label>
            <select 
              id="locationFilter" 
              class="form-select" 
              :value="selectedLocationID"
              @change="$emit('change-location', $event.target.value)"
            >
              <option value="">All Locations</option>
              <option v-for="location in locations" :key="location.locationID" :value="location.locationID">
                {{ location.location_name }}
              </option>
            </select>
          </div>
          <div class="col-md-8 d-flex justify-content-end">
            <button class="btn btn-outline-secondary me-2" @click="$emit('refresh')">
              <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delivery Groups -->
    <div v-if="loading" class="text-center my-5">
      <LoadingSpinner size="lg" message="Loading delivery data..." />
    </div>
    
    <div v-else-if="error" class="alert alert-danger" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i> {{ error }}
    </div>
    
    <div v-else-if="Object.keys(groupedDeliveries).length === 0" class="alert alert-info" role="alert">
      <i class="fas fa-info-circle me-2"></i> No pending deliveries found.
    </div>
    
    <div v-else>
      <!-- Loop through each location -->
      <div v-for="(locationData, locationID) in groupedDeliveries" :key="locationID" class="card mb-4">
        <div class="card-header bg-light">
          <h5 class="mb-0">
            <i class="fas fa-map-marker-alt me-2"></i> 
            {{ locationData.company_address || 'Unknown Location' }}
          </h5>
        </div>
        <div class="card-body">
          <!-- Loop through each order in this location -->
          <div v-for="(items, orderID) in locationData.orders || {}" :key="orderID" class="mb-3 border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-0">
                  <i class="fas fa-shopping-cart me-2"></i> Order #{{ orderID }}
                </h6>
                <div class="text-muted mt-1">
                  Total: {{ calculateTotalMeasurement(items) }} kg
                </div>
              </div>
              <div class="d-flex">
                <button class="btn btn-sm btn-outline-secondary me-2" @click="$emit('toggle-details', locationID, orderID)" aria-expanded="isOrderExpanded(locationID, orderID)">
                  <i :class="isOrderExpanded(locationID, orderID) ? 'fas fa-chevron-up me-1 ms-1' : 'fas fa-chevron-down me-1 ms-1'" aria-hidden="true"></i>
                  <span class="d-none d-md-inline">{{ isOrderExpanded(locationID, orderID) ? 'Hide Details' : 'Show Details' }}</span>
                  <span class="visually-hidden">{{ isOrderExpanded(locationID, orderID) ? 'Hide Order Details' : 'Show Order Details' }}</span>
                </button>
                <button class="btn btn-sm btn-primary" @click="$emit('assign-delivery', locationID, orderID)">
                  <i class="fas fa-truck me-1" aria-hidden="true"></i>
                  <span class="d-none d-md-inline">Assign</span>
                  <span class="visually-hidden">Assign Delivery</span>
                </button>
              </div>
            </div>
            
            <!-- Collapsible order details -->
            <div v-if="isOrderExpanded(locationID, orderID)" class="mt-3 order-details">
              <div class="table-responsive">
                <table class="table table-sm table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>Item</th>
                      <th>Quantity</th>
                      <th>Weight</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in items" :key="index">
                      <td>{{ item.item_name || `Item #${item.itemID}` }}</td>
                      <td>{{ item.quantity }}</td>
                      <td>{{ item.measurement_value }} {{ item.measurement_type }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > 0" class="d-flex justify-content-between align-items-center mt-4">
      <div>
        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
      </div>
      <nav aria-label="Page navigation">
        <ul class="pagination">
          <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
            <a class="page-link" href="#" @click.prevent="$emit('change-page', pagination.current_page - 1)">
              Previous
            </a>
          </li>
          <li 
            v-for="page in getPageNumbers()" 
            :key="page" 
            class="page-item"
            :class="{ active: page === pagination.current_page }"
          >
            <a class="page-link" href="#" @click.prevent="$emit('change-page', page)">{{ page }}</a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
            <a class="page-link" href="#" @click.prevent="$emit('change-page', pagination.current_page + 1)">
              Next
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script>
import LoadingSpinner from '../../components/ui/LoadingSpinner.vue';

export default {
  name: 'DeliveryAssignment',
  components: {
    LoadingSpinner
  },
  props: {
    loading: Boolean,
    error: String,
    locations: Array,
    selectedLocationID: String,
    groupedDeliveries: Object,
    pagination: Object,
    isOrderExpanded: Function,
    calculateTotalMeasurement: Function
  },
  methods: {
    getPageNumbers() {
      const totalPages = this.pagination.last_page;
      const currentPage = this.pagination.current_page;
      
      if (totalPages <= 5) {
        return Array.from({ length: totalPages }, (_, i) => i + 1);
      }
      
      if (currentPage <= 3) {
        return [1, 2, 3, 4, 5];
      }
      
      if (currentPage >= totalPages - 2) {
        return [totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
      }
      
      return [currentPage - 2, currentPage - 1, currentPage, currentPage + 1, currentPage + 2];
    }
  }
};
</script>

<style scoped>
.order-details {
  background-color: #f8f9fa;
  border-radius: 4px;
  padding: 10px;
  transition: all 0.3s ease;
}

/* Add animation for expanding/collapsing */
.order-details-enter-active, .order-details-leave-active {
  transition: max-height 0.3s ease, opacity 0.3s ease;
  max-height: 500px;
  overflow: hidden;
}
.order-details-enter, .order-details-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>