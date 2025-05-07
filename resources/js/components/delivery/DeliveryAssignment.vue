<template>
  <div class="delivery-assignment">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Assign Deliveries</h5>
        <div class="d-flex">
          <button class="btn btn-sm btn-outline-primary me-2" @click="$emit('refresh')">
            <i class="fas fa-sync-alt"></i>
          </button>
          <div class="location-filter-container">
            <select 
              class="form-select form-select-sm" 
              v-model="localSelectedLocationID"
              @change="$emit('change-location', localSelectedLocationID)"
            >
              <option value="">All Locations</option>
              <optgroup label="Origin Locations">
                <option v-for="location in fromLocations" :key="'from-'+location.locationID" :value="location.locationID">
                  <i class="fas fa-arrow-right"></i> From: {{ location.company_address }}
                </option>
              </optgroup>
              <optgroup label="Destination Locations">
                <option v-for="location in toLocations" :key="'to-'+location.locationID" :value="location.locationID">
                  <i class="fas fa-arrow-left"></i> To: {{ location.company_address }}
                </option>
              </optgroup>
            </select>
            <div class="location-filter-icon">
              <i class="fas fa-map-marker-alt"></i>
            </div>
          </div>
        </div>
      </div>
      
      <div class="card-body p-0">
        <!-- Loading, error and empty states remain unchanged -->
        <div v-if="loading" class="p-4 text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 text-muted">Loading delivery data...</p>
        </div>
        
        <div v-else-if="error" class="p-4">
          <div class="alert alert-danger mb-0">
            {{ error }}
          </div>
        </div>
        
        <div v-else-if="Object.keys(groupedDeliveries).length === 0" class="p-4 text-center">
          <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i> No deliveries found for the selected criteria.
          </div>
        </div>
        
        <div v-else>
          <!-- Delivery Groups by Location -->
          <div v-for="(locationData, locationID) in groupedDeliveries" :key="locationID" class="location-group">
            <div class="location-header p-3 border-bottom bg-light">
              <h5 class="mb-0">
                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                {{ getLocationName(locationID) || locationData.company_address || 'Unknown Location' }}
              </h5>
            </div>
            
            <!-- Orders for this location -->
            <div v-if="!locationData.orders || Object.keys(locationData.orders).length === 0" class="p-3 text-center text-muted">
              No orders found for this location
            </div>
            
            <div v-else class="order-list">
              <div v-for="(orderData, orderID) in locationData.orders" :key="orderID" class="order-item border-bottom">
                <div class="order-header p-3 d-flex justify-content-between align-items-center" 
                     @click="$emit('toggle-details', locationID, orderID)">
                  <div>
                    <h6 class="mb-1">Order #{{ orderID }}</h6>
                    <div class="small text-muted d-flex align-items-center">
                      <span v-if="orderData.items && orderData.items.length">
                        {{ orderData.items.length }} items | 
                        {{ calculateTotalMeasurement(orderData.items) }} kg
                      </span>
                      <!-- Removed the From/To location display div -->
                      <!-- 
                      <div class="ms-2 d-flex align-items-center" v-if="orderData.from && orderData.to">
                        <span class="badge bg-secondary me-1">From:</span>
                        <span class="me-2">{{ getLocationAddress(orderData.from) }}</span>
                        <i class="fas fa-arrow-right mx-1"></i>
                        <span class="badge bg-secondary me-1">To:</span>
                        <span>{{ getLocationAddress(orderData.to) }}</span>
                      </div> 
                      -->
                    </div>
                  </div>
                  <div class="d-flex align-items-center">
                    <span class="badge bg-primary me-2">
                      {{ orderData.status || 'Pending' }}
                    </span>
                    <span class="badge bg-info me-3" v-if="getTripType(orderData)">
                      {{ getTripType(orderData) }}
                    </span>
                    <button 
                      class="btn btn-sm btn-primary"
                      :disabled="!selectedDeliveryID"
                      @click.stop="$emit('assign-delivery', locationID, orderID)"
                    >
                      <i class="fas fa-truck me-1"></i> Assign
                    </button>
                    <i class="fas" :class="isOrderExpanded(locationID, orderID) ? 'fa-chevron-up ms-3' : 'fa-chevron-down ms-3'"></i>
                  </div>
                </div>
                
                <!-- Order Details (Expanded) - Only showing items -->
                <div v-if="isOrderExpanded(locationID, orderID)" class="order-details p-3 bg-light">
                  <!-- Order Items -->
                  <div class="card mb-0">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                      <div>
                        <i class="fas fa-box me-2"></i> Order Items
                      </div>
                      <div class="small">
                        Total: {{ orderData.items ? orderData.items.length : 0 }} items
                      </div>
                    </div>
                    <div class="card-body p-0">
                      <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                          <thead>
                            <tr>
                              <th>Item</th>
                              <th>Quantity</th>
                              <th>Measurement</th>
                              <th>Price</th>
                              <th>Total</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(item, index) in orderData.items" :key="index">
                              <td>{{ item.item_name || 'Unknown Item' }}</td>
                              <td>{{ item.quantity || 1 }}</td>
                              <td>{{ item.measurement_value || 0 }} {{ item.measurement_type || 'kg' }}</td>
                              <td>RM {{ formatPrice(item.price) }}</td>
                              <td>RM {{ formatPrice(item.total_price || item.price) }}</td>
                            </tr>
                          </tbody>
                          <tfoot>
                            <tr class="fw-bold">
                              <td colspan="4" class="text-end">Total:</td>
                              <td>RM {{ formatPrice(calculateOrderTotal(orderData.items)) }}</td>
                            </tr>
                          </tfoot>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="d-flex justify-content-center p-3">
          <nav aria-label="Page navigation">
            <ul class="pagination mb-0">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="$emit('change-page', pagination.current_page - 1)">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
              <li 
                v-for="page in getPageNumbers()" 
                :key="page" 
                class="page-item"
                :class="{ active: page === pagination.current_page }"
              >
                <a class="page-link" href="#" @click.prevent="$emit('change-page', page)">
                  {{ page }}
                </a>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <a class="page-link" href="#" @click.prevent="$emit('change-page', pagination.current_page + 1)">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DeliveryAssignment',
  props: {
    loading: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: null
    },
    locations: {
      type: Array,
      default: () => []
    },
    selectedLocationID: {
      type: [String, Number],
      default: ''
    },
    groupedDeliveries: {
      type: Object,
      default: () => ({})
    },
    pagination: {
      type: Object,
      default: () => ({})
    },
    isOrderExpanded: {
      type: Function,
      required: true
    },
    calculateTotalMeasurement: {
      type: Function,
      required: true
    },
    selectedDeliveryID: {
      type: [String, Number],
      default: null
    }
  },
  data() {
    return {
      localSelectedLocationID: this.selectedLocationID
    };
  },
  computed: {
    // Filter locations into from/to categories
    fromLocations() {
      return this.locations.filter(location => 
        location.location_type === 'farm' || 
        location.location_type === 'slaughterhouse' || 
        location.location_type === 'processing'
      );
    },
    toLocations() {
      return this.locations.filter(location => 
        location.location_type === 'distribution' || 
        location.location_type === 'retail' || 
        location.location_type === 'customer'
      );
    }
  },
  watch: {
    selectedLocationID(newVal) {
      this.localSelectedLocationID = newVal;
    }
  },
  methods: {
    // Existing methods remain unchanged
    getLocationName(locationID) {
      const location = this.locations.find(loc => loc.locationID == locationID);
      return location ? location.company_address : null;
    },
    
    getLocationAddress(location) {
      if (!location) return 'Unknown Location';
      
      if (location.company_address) {
        return location.company_address;
      }
      
      // Try to find the location in the locations array
      if (location.locationID) {
        const foundLocation = this.locations.find(loc => loc.locationID == location.locationID);
        if (foundLocation && foundLocation.company_address) {
          return foundLocation.company_address;
        }
      }
      
      return 'Unknown Location';
    },
    
    formatPrice(price) {
      return parseFloat(price || 0).toFixed(2);
    },
    
    calculateOrderTotal(items) {
      if (!Array.isArray(items)) return 0;
      
      return items.reduce((total, item) => {
        return total + parseFloat(item.total_price || item.price || 0);
      }, 0);
    },
    
    getPageNumbers() {
      if (!this.pagination) return [1];
      
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
    },
    
    getTripType(orderData) {
      if (!orderData) return '';
      
      // Determine trip type based on from/to locations
      if (orderData.from && orderData.to) {
        const fromType = this.getLocationType(orderData.from.locationID);
        const toType = this.getLocationType(orderData.to.locationID);
        
        if (fromType === 'farm' && toType === 'slaughterhouse') {
          return 'Farm to Slaughterhouse';
        } else if (fromType === 'slaughterhouse' && toType === 'processing') {
          return 'Slaughterhouse to Processing';
        } else if (fromType === 'processing' && toType === 'distribution') {
          return 'Processing to Distribution';
        } else if (fromType === 'distribution' && toType === 'retail') {
          return 'Distribution to Retail';
        } else if (toType === 'customer') {
          return 'Delivery to Customer';
        }
      }
      
      return 'Standard Delivery';
    },
    
    getLocationType(locationID) {
      const location = this.locations.find(loc => loc.locationID == locationID);
      return location ? location.location_type : null;
    },
    
    getTripType(orderData) {
      if (!orderData) return null;
      
      // Check if we have trips with phase information
      if (orderData.trips && orderData.trips.length > 0) {
        const trip = orderData.trips[0]; // Use the first trip to determine type
        
        if (trip.phase === 1) {
          return 'Supplier to Slaughterhouse';
        } else if (trip.phase === 2) {
          return 'Slaughterhouse to Customer';
        }
      }
      
      // If we have from and to locations
      if (orderData.from && orderData.to) {
        const fromLocationID = orderData.from.locationID;
        const toLocationID = orderData.to.locationID;
        
        // Check if this is likely a broiler to slaughterhouse trip
        // by examining the items and their locations
        if (orderData.items && orderData.items.length > 0) {
          const isBroilerToSlaughterhouse = orderData.items.some(item => 
            item.locationID === fromLocationID && item.slaughterhouse_locationID === toLocationID
          );
          
          if (isBroilerToSlaughterhouse) {
            return 'Broiler to Slaughterhouse';
          } else {
            return 'Slaughterhouse to Customer';
          }
        }
      }
      
      // If we have checkpoints, determine based on arrange_number
      if (orderData.checkpoints) {
        const hasArrange1or2 = orderData.checkpoints.some(cp => 
          cp.arrange_number === 1 || cp.arrange_number === 2
        );
        
        const hasArrange3or4 = orderData.checkpoints.some(cp => 
          cp.arrange_number === 3 || cp.arrange_number === 4
        );
        
        if (hasArrange1or2 && !hasArrange3or4) {
          return 'Broiler to Slaughterhouse';
        } else if (hasArrange3or4) {
          return 'Slaughterhouse to Customer';
        }
      }
      
      // If we couldn't determine the trip type, fall back to a generic type based on location types
      if (orderData.from && orderData.to) {
        const fromType = this.getLocationType(orderData.from.locationID);
        const toType = this.getLocationType(orderData.to.locationID);
        
        if (fromType === 'farm' && toType === 'slaughterhouse') {
          return 'Farm to Slaughterhouse';
        } else if (fromType === 'slaughterhouse' && toType === 'processing') {
          return 'Slaughterhouse to Processing';
        } else if (fromType === 'processing' && toType === 'distribution') {
          return 'Processing to Distribution';
        } else if (fromType === 'distribution' && toType === 'retail') {
          return 'Distribution to Retail';
        } else if (toType === 'customer') {
          return 'Delivery to Customer';
        }
      }
      
      return 'Standard Delivery';
    }
  }
};
</script>

<style scoped>
.location-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.order-item:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.order-header {
  cursor: pointer;
}

.order-details {
  background-color: #f8f9fa;
}

/* New styles for the location filter */
.location-filter-container {
  position: relative;
  min-width: 220px;
}

.location-filter-container select {
  padding-left: 30px;
  /* appearance: auto; */ /* Remove or comment out this line */
  width: 100%; /* Keep this for responsiveness */
}

.location-filter-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
  pointer-events: none;
}

/* Style for optgroup */
::v-deep optgroup {
  font-weight: bold;
  color: #495057;
  background-color: #f8f9fa;
}

::v-deep option {
  padding: 8px 12px;
}

::v-deep option:hover {
  background-color: #e9ecef;
}
</style>
