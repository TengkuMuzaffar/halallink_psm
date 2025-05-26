<template>
  <div class="order-management">
    <h1 class="mb-4">Order Management</h1>
    
    <!-- Order Stats -->
    <div  v-if="isSMECompany"  class="row mb-4">
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard 
          title="Total Orders" 
          :count="formatLargeNumber(orderStats.total_orders)" 
          icon="fas fa-shopping-cart" 
          bg-color="bg-primary"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard 
          title="Waiting For Delivery" 
          :count="formatLargeNumber(orderStats.waiting_for_delivery_orders)" 
          icon="fas fa-hourglass-half" 
          bg-color="bg-warning"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard 
          title="In Progress" 
          :count="formatLargeNumber(orderStats.in_progress_orders)" 
          icon="fas fa-cogs" 
          bg-color="bg-info"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard 
          title="Complete" 
          :count="formatLargeNumber(orderStats.complete_orders)" 
          icon="fas fa-check-circle" 
          bg-color="bg-success"
        />
      </div>
    </div>
    
    <!-- Locations and Orders -->
    <div class="card theme-card">
      <div class="card-header d-flex justify-content-between align-items-center theme-header">
        <h5 class="mb-0">Orders by Location</h5>
        <button class="btn btn-sm theme-btn-outline" @click="refreshData">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
      </div>
      <div class="card-body theme-body">
        <!-- Error State -->
        <div v-if="error" class="alert alert-danger" role="alert">
          {{ error }}
        </div>
        
        <!-- Filters -->
        <div class="mb-4">
          <div class="row g-2">
            <div class="col-12 col-md-6 col-lg-4">
              <div class="input-group">
                <input 
                  type="text" 
                  class="form-control" 
                  placeholder="Search orders..." 
                  v-model="searchQuery"
                  @input="debounceSearch"
                />
                <button class="btn btn-outline-secondary" type="button">
                  <i class="fas fa-search"></i>
                </button>
              </div>
            </div>
            
            <div class="col-12 col-md-6 col-lg-2">
              <select class="form-select" v-model="statusFilter" @change="applyFilters">
                <option value="">All Statuses</option>
                <option value="waiting_for_delivery">Waiting for Delivery</option>
                <option value="in_progress">In Progress</option>
                <option value="complete">Complete</option>
              </select>
            </div>
            
            <div class="col-12 col-sm-6 col-lg-3">
              <div class="input-group">
                <span class="input-group-text">From</span>
                <input type="date" class="form-control" v-model="dateRangeFilter.from" @change="applyFilters">
              </div>
            </div>
            
            <div class="col-12 col-sm-6 col-lg-3">
              <div class="input-group">
                <span class="input-group-text">To</span>
                <input type="date" class="form-control" v-model="dateRangeFilter.to" @change="applyFilters">
              </div>
            </div>
          </div>
          
          <div class="d-flex justify-content-end mt-2">
            <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">
              <i class="fas fa-times"></i> Clear Filters
            </button>
          </div>
        </div>
        
        <!-- Loading State - Moved below filters -->
        <div v-if="loading" class="text-center py-5">
          <LoadingSpinner />
        </div>
        
        <!-- No Data State -->
        <div v-if="!loading && (!locations || locations.length === 0)" class="text-center py-5">
          <div class="mb-3">
            <i class="fas fa-box-open fa-4x text-muted"></i>
          </div>
          <h5 class="text-muted">No orders found</h5>
          <p class="text-muted">Try adjusting your filters or check back later.</p>
        </div>
        
        <!-- Locations List -->
        <div v-if="!loading && locations && locations.length > 0">
          <div v-for="(location, locationIndex) in locations" :key="location.locationID" class="location-card mb-4">
            <div class="card theme-inner-card">
              <div class="card-header theme-card-header">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                  <div class="mb-2 mb-md-0">
                    <span class="badge theme-badge-location me-2">{{ location.location_type }}</span>
                    <strong>{{ location.company_address }}</strong>
                  </div>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge theme-badge-primary me-3">{{ location.orders.length }} orders</span>
                    <button 
                      class="btn btn-sm btn-primary" 
                      @click="generateLocationQR(location.locationID, location.companyID || 0)"
                      title="Generate QR Code for this location"
                    >
                      <i class="fas fa-qrcode"></i>
                    </button>
                  </div>
                </div>
              </div>
              <div class="card-body p-0">
                <!-- Orders Table -->
                <div class="table-responsive">
                  <table class="table table-hover mb-0 theme-table">
                    <thead class="theme-table-header">
                      <tr>
                        <th scope="col" style="width: 50px"></th>
                        <th scope="col">Order ID</th>
                        <th scope="col">Status</th>
                        <th scope="col">Date</th>
                        <th scope="col">Customer</th>
                      </tr>
                    </thead>
                    <tbody>
                      <template v-for="(order, orderIndex) in location.orders" :key="order.orderID">
                        <!-- Order Row -->
                        <tr class="theme-list-item" :class="{'theme-list-item-active': expandedOrders[order.orderID]}">
                          <td class="text-center">
                            <button 
                              class="btn btn-sm btn-link p-0" 
                              @click="toggleOrder(order.orderID)"
                              :aria-expanded="expandedOrders[order.orderID] ? 'true' : 'false'"
                            >
                              <i 
                                class="fas theme-icon" 
                                :class="expandedOrders[order.orderID] ? 'fa-chevron-down' : 'fa-chevron-right'"
                              ></i>
                            </button>
                          </td>
                          <td><strong>#{{ order.orderID }}</strong></td>
                          <td>
                            <span class="badge" :class="`theme-badge-${getStatusColor(order.calculated_status || order.order_status)}`">
                              {{ formatStatus(order.calculated_status || order.order_status) }}
                            </span>
                          </td>
                          <td>{{ formatDate(order.created_at) }}</td>
                          <td>{{ order.user ? (order.user.fullname || order.user.email) : 'N/A' }}</td>
                        </tr>
                        
                        <!-- Expanded Order Items -->
                        <tr v-if="expandedOrders[order.orderID]">
                          <td colspan="6" class="p-0">
                            <div class="p-3 theme-details">
                              <h6 class="mb-3 theme-subtitle">Order Items</h6>
                              
                              <!-- Consolidated Items Table -->
                              <div class="table-responsive">
                                <table class="table table-sm table-bordered theme-inner-table">
                                  <thead class="theme-table-header">
                                      <tr>
                                        <th style="width: auto; white-space: nowrap;">Cart ID</th>
                                        <th>Item</th>
                                        <th style="width: auto; white-space: nowrap;">Quantity</th>
                                        <th v-if="isBroilerCompany" style="width: auto; white-space: nowrap;" class="text-center">
                                          <span class="d-none d-sm-inline">AWB Download</span>
                                          <span class="d-inline d-sm-none">AWB</span>
                                        </th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr v-for="item in getAllOrderItems(order)" :key="`${order.orderID}-${item.cartID}-${item.itemID}`">
                                        <td>{{ item.cartID }}</td>
                                        <td>
                                          <div class="d-flex align-items-center">
                                            <div>
                                              <div class="fw-bold">{{ item.item_name }}</div>
                                              <small class="text-muted">{{ item.measurement_value }} {{ item.measurement_type }}</small>
                                            </div>
                                          </div>
                                        </td>
                                        <td>{{ item.quantity }}</td>
                                        <td v-if="isBroilerCompany" class="text-center">
                                          <button 
                                            class="btn btn-sm btn-outline-primary" 
                                            @click="generateOrderQR(order.orderID)"
                                            title="Download AWB Waybill"
                                          >
                                            <i class="fas fa-file-download"></i>
                                            <span class="d-inline d-sm-none ms-1">AWB</span>
                                          </button>
                                        </td>
                                      </tr>
                                  </tbody>
                                </table>
                              </div>
                            </div>
                          </td>
                        </tr>
                      </template>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Pagination -->
        <div v-if="!loading && locations && locations.length > 0" class="d-flex justify-content-between align-items-center mt-4 theme-pagination-container">
          <div>
            <span class="theme-pagination-text">Showing {{ pagination.from || 1 }}-{{ pagination.to || locations.length }} of {{ pagination.total || locations.length }}</span>
          </div>
          <nav aria-label="Order pagination">
            <ul class="pagination mb-0">
              <li class="page-item" :class="{ disabled: currentPage <= 1 || loading }">
                <a class="page-link theme-pagination-link" href="#" @click.prevent="!loading && changePage(currentPage - 1)">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
              <li 
                v-for="page in paginationRange" 
                :key="page" 
                class="page-item"
                :class="{ active: page === currentPage, disabled: loading }"
              >
                <a class="page-link theme-pagination-link" href="#" @click.prevent="!loading && changePage(page)">{{ page }}</a>
              </li>
              <li class="page-item" :class="{ disabled: currentPage >= pagination.last_page || loading }">
                <a class="page-link theme-pagination-link" href="#" @click.prevent="!loading && changePage(currentPage + 1)">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
    
    <!-- Order Detail Modal -->
    <OrderDetailModal 
      ref="orderDetailModalRef"
      :order-id="selectedOrderId" 
      :order-data="selectedOrder" 
      modal-id="orderPageDetailModal" 
      :show-main-modal-after-QR="false"
    />
    
    <!-- Remove the QR Code Modal since we'll use the one from OrderDetailModal -->
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import StatsCard from '../components/ui/StatsCard.vue';
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';
import LoadingSpinner from '../components/ui/LoadingSpinner.vue';
import OrderDetailModal from '../components/order/OrderDetailModal.vue';
import { orderService } from '../services/orderService';
import formatter from '../utils/formatter';
import * as bootstrap from 'bootstrap';
import { useStore } from 'vuex'; // Add this import

export default {
  name: 'OrderManagement',
  components: {
    StatsCard,
    ResponsiveTable,
    LoadingSpinner,
    OrderDetailModal
  },
  setup() {
    // Add store
    const store = useStore();
    
    // State variables
    const loading = ref(true);
    const error = ref(null);
    const locations = ref([]);
    const orderStats = ref({
      total_orders: 0,
      pending_orders: 0,
      completed_orders: 0,
      processing_orders: 0,
      waiting_orders: 0
    });
   
    // QR Code state - we can remove these since we'll use OrderDetailModal's QR functionality
    const selectedOrderId = ref(null);
    const selectedOrder = ref(null);
    const orderDetailModalRef = ref(null);
    
    // Expanded state tracking (only for orders)
    const expandedOrders = ref({});
    
    // Filters
    const searchQuery = ref('');
    const statusFilter = ref('');
    const dateRangeFilter = reactive({
      from: '',
      to: ''
    });
    
    // Sorting
    const sortField = ref('created_at');
    const sortDirection = ref('desc');
    
    // Pagination
    const currentPage = ref(1);
    const perPage = ref(10);
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: 1,
      to: 10
    });
    
    // Computed pagination range
    const paginationRange = computed(() => {
      const range = [];
      
      if (pagination.value.last_page <= 7) {
        // If 7 or fewer pages, show all
        for (let i = 1; i <= pagination.value.last_page; i++) {
          range.push(i);
        }
      } else {
        // Always include first and last page
        let startPage = Math.max(1, currentPage.value - 2);
        let endPage = Math.min(pagination.value.last_page, startPage + 4);
        
        // Adjust if we're near the end
        if (endPage - startPage < 4) {
          startPage = Math.max(1, endPage - 4);
        }
        
        for (let i = startPage; i <= endPage; i++) {
          range.push(i);
        }
      }
      
      return range;
    });
    
    // Format helpers
    const formatLargeNumber = (num) => {
      return formatter.formatLargeNumber(num);
    };
    
    const formatCurrency = (amount) => {
      return formatter.formatCurrency(amount);
    };
    
    const formatDate = (dateString) => {
      return formatter.formatDate(dateString);
    };
    
    const formatStatus = (status) => {
      if (!status) return 'Unknown';
      
      // Convert snake_case to Title Case
      return status
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
    };
    
    const getStatusColor = (status) => {
      if (!status) return 'secondary';
      
      const statusColors = {
        'pending': 'warning',
        'processing': 'info',
        'complete': 'success',
        'waiting_verification': 'primary',
        'waiting_delivery': 'primary',
        'paid': 'dark'
      };
      
      return statusColors[status.toLowerCase()] || 'secondary';
    };
    
    // Toggle order expansion
    const toggleOrder = (orderId) => {
      expandedOrders.value = {
        ...expandedOrders.value,
        [orderId]: !expandedOrders.value[orderId]
      };
    };
    
    const generateLocationQR = async (locationID, companyID) => {
      try {
        // If we have a selected order, use the OrderDetailModal to show the QR
        if (orderDetailModalRef.value) {
          selectedOrderId.value = null;
          selectedOrder.value = null;
          
          // Show the QR code in the modal - pass locationID as second parameter
          orderDetailModalRef.value.showQRCode(null, locationID, companyID);
        }
      } catch (error) {
        console.error('Error generating QR code:', error);
      }
    };
    
    // Add the missing generateOrderQR function
    const generateOrderQR = (cartId) => {
      window.open(`/awb/${cartId}`, '_blank');
    };
    
    // Helper function to find an order by ID across all locations
    const findOrderById = (orderID) => {
      if (!locations.value) return null;
      
      for (const location of locations.value) {
        if (!location.orders) continue;
        
        const order = location.orders.find(o => o.orderID === orderID);
        if (order) return order;
      }
      
      return null;
    };
    
    // Debounce search
    let searchTimeout = null;
    const debounceSearch = () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        applyFilters();
      }, 500);
    };
    
    // Fetch orders with filters, sorting, and pagination
    const fetchOrders = async () => {
      try {
        loading.value = true;
        error.value = null;
        
        const params = {
          page: currentPage.value,
          per_page: perPage.value,
          search: searchQuery.value || null,
          status: statusFilter.value || null,
          sort_field: sortField.value,
          sort_direction: sortDirection.value
        };
        
        // Add date range filters if set
        if (dateRangeFilter.from) {
          params.date_from = dateRangeFilter.from;
        }
        if (dateRangeFilter.to) {
          params.date_to = dateRangeFilter.to;
        }
        
        const response = await orderService.fetchOrders(params);
        console.log("Orders response:", JSON.stringify(response, null, 4));
        
        if (response.success && response.data) {
          // Set locations data
          locations.value = response.data;
          
          // Update pagination if available
          if (response.pagination) {
            pagination.value = response.pagination;
          }
        } else {
          locations.value = [];
          console.error('Unexpected response format:', response);
        }
      } catch (err) {
        console.error('Error fetching orders:', err);
        error.value = err.message || 'Failed to load orders. Please try again later.';
        locations.value = [];
      } finally {
        loading.value = false;
      }
    };
    
    // Fetch order stats
    const fetchOrderStats = async () => {
      try {
        const stats = await orderService.fetchOrderStats();
        console.log('Order stats response:', JSON.stringify(stats)); // Add debugging
        
        // Check if stats exist and have the expected format
        if (stats && typeof stats === 'object') {
          // Map the backend stats to our frontend structure
          orderStats.value = {
            total_orders: stats.total_orders || 0,
            waiting_for_delivery_orders: stats.waiting_for_delivery_orders || 0,
            in_progress_orders: stats.in_progress_orders || 0,
            complete_orders: stats.complete_orders || 0
          };
        } else {
          console.error('Invalid stats format received:', stats);
        }
      } catch (err) {
        console.error('Error fetching order stats:', err);
      }
    };
    
    // Add computed property to check if user's company is a broiler
    const isBroilerCompany = computed(() => {
      const user = store.getters.user;
      if (user && user.company) {
        return user.company.company_type === 'broiler';
      }
      return false;
    });
    const isSMECompany = computed(() => {
      const user = store.getters.user;
      if (user && user.company) {
        return user.company.company_type === 'sme';
      }
      return false;
    });

    // Apply filters (status and date range)
    const applyFilters = () => {
      currentPage.value = 1; // Reset to first page when filtering
      fetchOrders();
    };
    
    // Change page
    const changePage = (page) => {
      if (page < 1 || page > pagination.value.last_page) return;
      currentPage.value = page;
      fetchOrders();
    };
    
    // Add the refreshData function
    const refreshData = () => {
      // Reset to first page when refreshing
      currentPage.value = 1;
      // Clear any error messages
      error.value = null;
      // Fetch fresh data
      fetchOrders();
      fetchOrderStats();
    };
    
    // Reset filters
    const resetFilters = () => {
      searchQuery.value = '';
      statusFilter.value = '';
      dateRangeFilter.from = '';
      dateRangeFilter.to = '';
      applyFilters();
    };
    
    // Lifecycle hooks
    onMounted(() => {
      fetchOrders();
      fetchOrderStats();
    });
    
    // View order details
    const viewOrder = (order) => {
      selectedOrderId.value = order.orderID;
      selectedOrder.value = order;
      
      // Open modal
      const modal = new bootstrap.Modal(document.getElementById('orderPageDetailModal'));
      modal.show();
    };
    
    return {
      // State
      loading,
      error,
      locations,
      orderStats,
      expandedOrders,
      searchQuery,
      statusFilter,
      dateRangeFilter,
      currentPage,
      getAllOrderItems,
      pagination,
      paginationRange,
      formatLargeNumber,
      formatCurrency,
      formatDate,
      formatStatus,
      getStatusColor,
      toggleOrder,
      generateLocationQR,
      generateOrderQR,
      debounceSearch,
      applyFilters,
      changePage,
      refreshData,
      resetFilters,  // Add this new function
      selectedOrderId,
      selectedOrder,
      orderDetailModalRef,
      isBroilerCompany,
      isSMECompany
    };
  }
}

// Helper function to get all items from all checkpoints in an order
const getAllOrderItems = (order) => {
  if (!order || !order.checkpoints) return [];
  
  // Flatten all items from all checkpoints into a single array
  const allItems = [];
  
  for (const checkpoint of order.checkpoints) {
    if (checkpoint.items && checkpoint.items.length) {
      allItems.push(...checkpoint.items);
    }
  }
  
  return allItems;
};
</script>

<style scoped>
.order-management {
  padding: 20px 0;
}

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

.theme-body {
  padding: 1.25rem;
}

.theme-inner-card {
  border-color: var(--border-color);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.theme-card-header {
  background-color: var(--light-bg);
  border-bottom: 1px solid var(--border-color);
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

.theme-btn-primary {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
}

.theme-btn-primary:hover {
  background-color: #0a2016;
  border-color: #0a2016;
  color: var(--secondary-color);
}

/* Badge styles */
.theme-badge-primary {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

.theme-badge-secondary {
  background-color: var(--secondary-color);
  color: var(--primary-color);
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

.theme-list-item:hover {
  background-color: var(--lighter-bg);
}

.theme-list-item-active {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
}

.theme-list-item-active .text-muted {
  color: rgba(239, 227, 194, 0.7) !important;
}

/* Table styles */
.theme-table {
  border-color: var(--border-color);
}

.theme-table-header {
  background-color: var(--light-bg);
  color: var(--primary-color);
}

.theme-inner-table {
  border-color: var(--border-color);
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
  background-color: var(--lighter-bg);
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
  padding: 0.75rem 1rem;
}

.theme-pagination-text {
  color: var(--text-color);
}

.theme-pagination-link {
  color: var(--primary-color);
  border-color: var(--border-color);
}

.theme-pagination-link:hover {
  background-color: var(--light-bg);
  color: var(--primary-color);
  border-color: var(--border-color);
}

.page-item.active .theme-pagination-link {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
}

/* Base badge styles */
.theme-badge {
    color: #fff;
    background-color: #123524;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
}
.theme-badge-location {
    color: #fff;
    background-color: #123524;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
  }
/* Badge color variants */
.theme-badge-primary {
  color: #fff;
  background-color: #0d6efd;
}

.theme-badge-secondary {
  color: #fff;
  background-color: #6c757d;
}

.theme-badge-success {
  color: #fff;
  background-color: #198754;
}

.theme-badge-info {
  color: #000;
  background-color: #0dcaf0;
}

.theme-badge-warning {
  color: #000;
  background-color: #ffc107;
}

.theme-badge-danger {
  color: #fff;
  background-color: #dc3545;
}

.theme-badge-dark {
  color: #fff;
  background-color: #212529;
}

/* Other existing styles */
.theme-badge-accent {
  background-color: var(--accent-color);
  color: var(--secondary-color);
}

/* List styles */
.theme-list-item {
  border-color: var(--border-color);
  transition: background-color 0.2s ease;
}

.theme-list-item:hover {
  background-color: var(--lighter-bg);
}

.theme-list-item-active {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
}

.theme-list-item-active .text-muted {
  color: rgba(239, 227, 194, 0.7) !important;
}

/* Table styles */
.theme-table {
  border-color: var(--border-color);
}

.theme-table-header {
  background-color: var(--light-bg);
  color: var(--primary-color);
}

.theme-inner-table {
  border-color: var(--border-color);
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
  background-color: var(--lighter-bg);
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
  padding: 0.75rem 1rem;
}

.theme-pagination-text {
  color: var(--text-color);
}

.theme-pagination-link {
  color: var(--primary-color);
  border-color: var(--border-color);
}

.theme-pagination-link:hover {
  background-color: var(--light-bg);
  color: var(--primary-color);
  border-color: var(--border-color);
}

.page-item.active .theme-pagination-link {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
}

/* Original styles with theme modifications */
.pagination {
  margin-bottom: 0;
}

.badge {
  font-size: 0.85em;
  padding: 0.35em 0.65em;
}

.location-card {
  transition: all 0.3s ease;
}

.location-card:hover {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table > :not(caption) > * > * {
  padding: 0.75rem 1rem;
}

.btn-link {
  color: var(--accent-color);
  text-decoration: none;
}

.btn-link:hover {
  color: #2e5c1d;
}
</style>