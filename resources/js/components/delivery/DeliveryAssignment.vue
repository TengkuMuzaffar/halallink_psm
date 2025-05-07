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
      
      <div class="card-body p-0">
        <!-- Loading, error and empty states remain unchanged -->
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
        
        <div v-else-if="Object.keys(groupedDeliveries).length === 0" class="p-4 text-center">
          <div class="alert theme-alert-info mb-0">
            <i class="fas fa-info-circle me-2"></i> No deliveries found for the selected criteria.
          </div>
        </div>
        
        <div v-else>
          <!-- Delivery Groups by Location -->
          <div v-for="(locationData, locationID) in groupedDeliveries" :key="locationID" class="location-group">
            <div class="location-header p-3 border-bottom theme-location-header">
              <h5 class="mb-0">
                <i class="fas fa-map-marker-alt me-2 theme-icon"></i>
                {{ getLocationName(locationID) || locationData.company_address || 'Unknown Location' }}
              </h5>
            </div>
            
            <!-- Orders for this location -->
            <div v-if="!locationData.orders || Object.keys(locationData.orders).length === 0" class="p-3 text-center text-muted">
              No orders found for this location
            </div>
            
            <div v-else class="order-list">
              <div v-for="(orderData, orderID) in locationData.orders" :key="orderID" class="order-item border-bottom">
                <div class="order-header p-3 d-flex justify-content-between align-items-center flex-wrap" 
                     @click="$emit('toggle-details', locationID, orderID)">
                  <div>
                    <h6 class="mb-1">Order #{{ orderID }}</h6>
                    <div class="small text-muted d-flex align-items-center">
                      <span v-if="orderData.items && orderData.items.length">
                        {{ orderData.items.length }} items | 
                        {{ calculateTotalMeasurement(orderData.items) }} kg
                      </span>
                    </div>
                  </div>
                  <div class="d-flex align-items-center order-actions mt-2 mt-sm-0">
                    <span class="badge theme-badge-primary me-2">
                      {{ orderData.status || 'Pending' }}
                    </span>
                    <span class="badge theme-badge-secondary me-2" v-if="getTripType(orderData)">
                      {{ getTripType(orderData) }}
                    </span>
                    <button 
                      class="btn btn-sm theme-btn-primary"
                      :disabled="!selectedDeliveryID"
                      @click.stop="$emit('assign-delivery', locationID, orderID)"
                    >
                      <i class="fas fa-truck me-1 d-none d-sm-inline"></i> Assign
                    </button>
                    <i class="fas ms-2 theme-icon" :class="isOrderExpanded(locationID, orderID) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                  </div>
                </div>
                
                <!-- Order Details (Expanded) - Only showing items -->
                <div v-if="isOrderExpanded(locationID, orderID)" class="order-details p-3 theme-details-bg">
                  <!-- Order Items -->
                  <div class="card mb-0">
                    <div class="card-header theme-card-header d-flex justify-content-between align-items-center">
                      <div>
                        <i class="fas fa-box me-2"></i> Order Items
                      </div>
                      <div class="small">
                        Total: {{ orderData.items ? orderData.items.length : 0 }} items
                      </div>
                    </div>
                    <div class="card-body p-0">
                      <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 theme-table">
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
                            <tr class="fw-bold theme-table-footer">
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
            <ul class="pagination mb-0 theme-pagination">
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
/* Base styles */
.location-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.order-item:hover {
  background-color: rgba(62, 123, 39, 0.05);
}

.order-header {
  cursor: pointer;
}

.order-details {
  background-color: #f8f9fa;
}

/* Theme colors */
.theme-header {
  background-color: #123524;
  color: #EFE3C2;
  border-bottom: none;
}

.theme-btn-outline {
  color: #EFE3C2;
  border-color: #EFE3C2;
  background-color: transparent;
}

.theme-btn-outline:hover {
  color: #123524;
  background-color: #EFE3C2;
  border-color: #EFE3C2;
}

.theme-btn-primary {
  background-color: #123524;
  border-color: #123524;
  color: #EFE3C2;
}

.theme-btn-primary:hover {
  background-color: #0a1f16;
  border-color: #0a1f16;
  color: #EFE3C2;
}

.theme-btn-primary:disabled {
  background-color: rgba(18, 53, 36, 0.65);
  border-color: rgba(18, 53, 36, 0.65);
}

.theme-badge-primary {
  background-color: #123524;
  color: #EFE3C2;
}

.theme-badge-secondary {
  background-color: #3E7B27;
  color: #EFE3C2;
}

.theme-icon {
  color: #3E7B27;
}

.theme-location-header {
  background-color: rgba(18, 53, 36, 0.1);
  border-bottom: 1px solid rgba(18, 53, 36, 0.2);
  color: #123524;
}

.theme-details-bg {
  background-color: rgba(239, 227, 194, 0.3);
}

.theme-card-header {
  background-color: #3E7B27;
  color: #EFE3C2;
  border-bottom: none;
}

.theme-alert-info {
  background-color: rgba(239, 227, 194, 0.5);
  color: #123524;
  border-color: rgba(18, 53, 36, 0.2);
}

.theme-spinner {
  color: #3E7B27;
}

/* Table styles */
.theme-table thead {
  background-color: rgba(18, 53, 36, 0.1);
}

.theme-table thead th {
  color: #123524;
  border-bottom: 2px solid #3E7B27;
}

.theme-table tbody tr:nth-child(odd) {
  background-color: rgba(239, 227, 194, 0.2);
}

.theme-table tbody tr:nth-child(even) {
  background-color: rgba(239, 227, 194, 0.1);
}

.theme-table-footer {
  background-color: rgba(18, 53, 36, 0.1);
  color: #123524;
}

/* Pagination styles */
.theme-pagination .page-link {
  color: #123524;
  border-color: rgba(18, 53, 36, 0.2);
}

.theme-pagination .page-item.active .page-link {
  background-color: #123524;
  border-color: #123524;
  color: #EFE3C2;
}

.theme-pagination .page-item.disabled .page-link {
  color: rgba(18, 53, 36, 0.5);
}

.theme-pagination .page-link:hover {
  background-color: rgba(18, 53, 36, 0.1);
  color: #123524;
}

/* Responsive styles for order actions */
.order-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
}

@media (max-width: 576px) {
  .order-actions {
    margin-top: 0.5rem;
    width: 100%;
    justify-content: flex-end;
  }
  
  .order-actions .badge {
    font-size: 0.7rem;
  }
  
  .order-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }
}

/* Table responsive styles */
@media (max-width: 768px) {
  .table th, .table td {
    padding: 0.5rem;
    font-size: 0.85rem;
  }
}

/* Location filter styles - keeping for reference but not using */
.location-filter-container {
  position: relative;
  min-width: 220px;
}

.location-filter-container select {
  padding-left: 30px;
  width: 100%;
}

.location-filter-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
  pointer-events: none;
}

/* Style for optgroup - keeping for reference but not using */
:deep(optgroup) {
  font-weight: bold;
  color: #123524;
  background-color: rgba(239, 227, 194, 0.5);
}

:deep(option) {
  padding: 8px 12px;
}

:deep(option:hover) {
  background-color: rgba(18, 53, 36, 0.1);
}
</style>
