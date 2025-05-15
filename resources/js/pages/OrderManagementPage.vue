<template>
  <div class="order-management">
    <h1 class="mb-4">Order Management</h1>
    
    <!-- Order Stats -->
    <div class="row mb-4">
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
          title="Pending Orders" 
          :count="formatLargeNumber(orderStats.pending_orders)" 
          icon="fas fa-hourglass-half" 
          bg-color="bg-warning"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard 
          title="Processing Orders" 
          :count="formatLargeNumber(orderStats.processing_orders)" 
          icon="fas fa-cogs" 
          bg-color="bg-info"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard 
          title="Completed Orders" 
          :count="formatLargeNumber(orderStats.completed_orders)" 
          icon="fas fa-check-circle" 
          bg-color="bg-success"
        />
      </div>
    </div>
    
    <!-- Locations and Orders -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Orders by Location</h5>
      </div>
      <div class="card-body">
        <!-- Error State -->
        <div v-if="error" class="alert alert-danger" role="alert">
          {{ error }}
        </div>
        
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <LoadingSpinner />
        </div>
        
        <!-- Filters -->
        <div class="mb-4 d-flex gap-2">
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
          
          <select class="form-select" v-model="statusFilter" @change="applyFilters">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="complete">Complete</option>
            <option value="waiting_delivery">Waiting Delivery</option>
            <option value="paid">Paid</option>
          </select>
          
          <div class="input-group">
            <span class="input-group-text">From</span>
            <input type="date" class="form-control" v-model="dateRangeFilter.from" @change="applyFilters">
          </div>
          
          <div class="input-group">
            <span class="input-group-text">To</span>
            <input type="date" class="form-control" v-model="dateRangeFilter.to" @change="applyFilters">
          </div>
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
            <div class="card">
              <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                  <span class="badge bg-secondary me-2">{{ location.location_type }}</span>
                  <strong>{{ location.company_address }}</strong>
                </div>
                <div class="d-flex align-items-center">
                  <span class="badge bg-primary me-3">{{ location.orders.length }} orders</span>
                  <button 
                    class="btn btn-sm btn-outline-primary" 
                    @click="generateLocationQR(location.locationID, location.companyID || 0)"
                    title="Generate QR Code for this location"
                  >
                    <i class="fas fa-qrcode"></i>
                  </button>
                </div>
              </div>
              <div class="card-body p-0">
                <!-- Orders Table -->
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th scope="col" style="width: 50px"></th>
                        <th scope="col">Order ID</th>
                        <th scope="col">Status</th>
                        <th scope="col">Date</th>
                        <th scope="col">Customer</th>
                        <th scope="col" class="text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <template v-for="(order, orderIndex) in location.orders" :key="order.orderID">
                        <!-- Order Row -->
                        <tr>
                          <td class="text-center">
                            <button 
                              class="btn btn-sm btn-link p-0" 
                              @click="toggleOrder(order.orderID)"
                              :aria-expanded="expandedOrders[order.orderID] ? 'true' : 'false'"
                            >
                              <i 
                                class="fas" 
                                :class="expandedOrders[order.orderID] ? 'fa-chevron-down' : 'fa-chevron-right'"
                              ></i>
                            </button>
                          </td>
                          <td><strong>#{{ order.orderID }}</strong></td>
                          <td>
                            <span :class="`badge bg-${getStatusColor(order.calculated_status || order.order_status)}`">
                              {{ formatStatus(order.calculated_status || order.order_status) }}
                            </span>
                          </td>
                          <td>{{ formatDate(order.created_at) }}</td>
                          <td>{{ order.user ? (order.user.fullname || order.user.email) : 'N/A' }}</td>
                          <td class="text-end">
                            <button 
                              class="btn btn-sm btn-outline-primary me-2" 
                              @click="generateOrderQR(order.orderID)"
                              title="Generate QR Code for order verification"
                            >
                              <i class="fas fa-qrcode"></i>
                            </button>
                          </td>
                        </tr>
                        
                        <!-- Expanded Order Items -->
                        <tr v-if="expandedOrders[order.orderID]">
                          <td colspan="6" class="p-0">
                            <div class="p-3 bg-light">
                              <h6 class="mb-3">Order Checkpoints</h6>
                              
                              <!-- Checkpoints -->
                              <div v-for="(checkpoint, checkpointIndex) in order.checkpoints" :key="checkpoint.checkID" class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                  <h6 class="mb-0">
                                    <span class="badge bg-secondary me-2">Checkpoint #{{ checkpoint.arrange_number }}</span>
                                    <span :class="`badge bg-${getStatusColor(checkpoint.checkpoint_status)}`">
                                      {{ formatStatus(checkpoint.checkpoint_status) }}
                                    </span>
                                  </h6>
                                </div>
                                
                                <!-- Items Table -->
                                <div class="table-responsive">
                                  <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                      <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                        <th>Supplier</th>
                                        <th>Slaughterhouse</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr v-for="(item, itemIndex) in checkpoint.items" :key="`${checkpoint.checkID}-${item.itemID}`">
                                        <td>
                                          <div class="d-flex align-items-center">
                                            <div>
                                              <div class="fw-bold">{{ item.item_name }}</div>
                                              <small class="text-muted">{{ item.measurement_value }} {{ item.measurement_type }}</small>
                                            </div>
                                          </div>
                                        </td>
                                        <td>{{ item.quantity }}</td>
                                        <td>{{ formatCurrency(item.price_at_purchase) }}</td>
                                        <td>{{ formatCurrency(item.total_price) }}</td>
                                        <td>
                                          <small>{{ item.supplier_location_address }}</small>
                                        </td>
                                        <td>
                                          <small>{{ item.slaughterhouse_location_address }}</small>
                                        </td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
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
        <div v-if="!loading && locations && locations.length > 0" class="d-flex justify-content-between align-items-center mt-4">
          <div>
            <span class="text-muted">Showing {{ pagination.from || 1 }}-{{ pagination.to || locations.length }} of {{ pagination.total || locations.length }}</span>
          </div>
          <nav aria-label="Order pagination">
            <ul class="pagination mb-0">
              <li class="page-item" :class="{ disabled: currentPage <= 1 || loading }">
                <a class="page-link" href="#" @click.prevent="!loading && changePage(currentPage - 1)">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
              <li 
                v-for="page in paginationRange" 
                :key="page" 
                class="page-item"
                :class="{ active: page === currentPage, disabled: loading }"
              >
                <a class="page-link" href="#" @click.prevent="!loading && changePage(page)">{{ page }}</a>
              </li>
              <li class="page-item" :class="{ disabled: currentPage >= pagination.last_page || loading }">
                <a class="page-link" href="#" @click.prevent="!loading && changePage(currentPage + 1)">
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

export default {
  name: 'OrderManagement',
  components: {
    StatsCard,
    ResponsiveTable,
    LoadingSpinner,
    OrderDetailModal
  },
  setup() {
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
    
    // Generate QR code for location
    const generateLocationQR = (locationID, companyID) => {
      // Set selected order to null since we're generating a location QR
      selectedOrderId.value = null;
      selectedOrder.value = null;
      
      // Call the QR code generation method from OrderDetailModal component
      if (orderDetailModalRef.value) {
        orderDetailModalRef.value.showQRCode(null, locationID, companyID);
      }
    };
    
    const generateOrderQR = (orderID) => {
      // Find the order data to pass to the modal
      const order = findOrderById(orderID);
      selectedOrderId.value = orderID;
      selectedOrder.value = order;
      
      // Call the order verification QR generation method
      if (orderDetailModalRef.value) {
        orderDetailModalRef.value.generateOrderVerificationQR(orderID);
      }
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
        orderStats.value = stats;
      } catch (err) {
        console.error('Error fetching order stats:', err);
      }
    };
    
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
      selectedOrderId,
      selectedOrder,
      expandedOrders,
      orderDetailModalRef,
      
      // Filters
      searchQuery,
      statusFilter,
      dateRangeFilter,
      
      // Pagination
      currentPage,
      perPage,
      pagination,
      paginationRange,
      
      // Methods
      fetchOrders,
      toggleOrder,
      debounceSearch,
      applyFilters,
      changePage,
      viewOrder,
      generateLocationQR,
      generateOrderQR,
      
      // Formatters
      formatLargeNumber,
      formatCurrency,
      formatDate,
      formatStatus,
      getStatusColor
    };
  }
}
</script>

<style scoped>
.order-management {
  padding: 20px 0;
}

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
  color: #6c757d;
  text-decoration: none;
}

.btn-link:hover {
  color: #0d6efd;
}
</style>