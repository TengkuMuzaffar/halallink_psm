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
    
    <!-- Orders Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Orders</h5>
      </div>
      <div class="card-body">
        <!-- Error State -->
        <div v-if="error" class="alert alert-danger" role="alert">
          {{ error }}
        </div>
        
        <!-- Table (always show, with loading state inside) -->
        <ResponsiveTable
          :columns="columns"
          :items="orders"
          :loading="loading"
          :has-actions="true"
          item-key="orderID"
          @search="handleSearch"
          :show-pagination="false"
          :server-side="true"
          @sort="handleSort"
        >
          <!-- Custom filters -->
          <template #filters>
            <div class="d-flex gap-2">
              <select class="form-select form-select-sm" v-model="statusFilter" @change="applyFilters">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="complete">Complete</option>
                <option value="waiting_delivery">Waiting Delivery</option>
                <option value="paid">Paid</option>
              </select>
              
              <div class="input-group input-group-sm">
                <span class="input-group-text">From</span>
                <input type="date" class="form-control" v-model="dateRangeFilter.from" @change="applyFilters">
              </div>
              
              <div class="input-group input-group-sm">
                <span class="input-group-text">To</span>
                <input type="date" class="form-control" v-model="dateRangeFilter.to" @change="applyFilters">
              </div>
            </div>
          </template>
          
          <!-- Custom column slots -->
          <template #order_status="{ item }">
            <span :class="`badge bg-${getStatusColor(item.calculated_status || item.order_status)}`">
              {{ formatStatus(item.calculated_status || item.order_status) }}
            </span>
          </template>
          
          <template #total_amount="{ item }">
            <span>{{ formatCurrency(item.payment ? item.payment.payment_amount : item.total_amount) }}</span>
          </template>
          
          <template #created_at="{ item }">
            <span>{{ formatDate(item.created_at) }}</span>
          </template>
          
          <template #customer="{ item }">
            <span>{{ item.user ? (item.user.fullname || item.user.email) : 'N/A' }}</span>
          </template>
          
          <template #locations="{ item }">
            <span class="badge bg-info">{{ item.locations ? item.locations.length : 0 }} locations</span>
          </template>
          
          <!-- Actions slot -->
          <template #actions="{ item }">
            <button class="btn btn-sm btn-outline-primary me-1" @click="viewOrder(item.orderID)">
              <i class="fas fa-eye"></i>
            </button>
          </template>
        </ResponsiveTable>
        
        <!-- Pagination -->
        <div v-if="!loading && orders.length > 0" class="d-flex justify-content-between align-items-center mt-4">
          <div>
            <span class="text-muted">Showing {{ pagination.from || 1 }}-{{ pagination.to || orders.length }} of {{ pagination.total || orders.length }}</span>
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
      :order-id="selectedOrderId" 
      :order-data="selectedOrder" 
      modal-id="orderPageDetailModal" 
    />
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
    const orders = ref([]);
    const orderStats = ref({
      total_orders: 0,
      pending_orders: 0,
      completed_orders: 0,
      processing_orders: 0,
      waiting_orders: 0
    });
    const selectedOrderId = ref(null);
    const selectedOrder = ref(null);
    
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
    
    // Table columns definition
    const columns = ref([
      { key: 'orderID', label: 'Order ID', sortable: true },
      { key: 'order_status', label: 'Status', sortable: true },
      { key: 'total_amount', label: 'Amount', sortable: true },
      { key: 'created_at', label: 'Date', sortable: true },
      { key: 'customer', label: 'Customer', sortable: false },
      { key: 'locations', label: 'Locations', sortable: false }
    ]);
    
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
        'waiting_delivery': 'primary',
        'paid': 'dark'
      };
      
      return statusColors[status.toLowerCase()] || 'secondary';
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
        
        if (response.data) {
          // Process the data array
          orders.value = response.data;
          
          // Update pagination if available
          if (response.pagination) {
            pagination.value = response.pagination;
            pagination.value.from = (pagination.value.current_page - 1) * pagination.value.per_page + 1;
            pagination.value.to = Math.min(pagination.value.from + pagination.value.per_page - 1, pagination.value.total);
          }
        } else {
          orders.value = [];
          console.error('Unexpected response format:', response);
        }
      } catch (err) {
        console.error('Error fetching orders:', err);
        error.value = err.message || 'Failed to load orders. Please try again later.';
        orders.value = [];
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
    
    // Handle search from ResponsiveTable
    const handleSearch = (query) => {
      searchQuery.value = query;
      currentPage.value = 1; // Reset to first page when searching
      fetchOrders();
    };
    
    // Handle sort from ResponsiveTable
    const handleSort = ({ field, direction }) => {
      sortField.value = field;
      sortDirection.value = direction;
      fetchOrders();
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
    
    // View order details
    const viewOrder = (orderId) => {
      selectedOrderId.value = orderId;
      selectedOrder.value = orders.value.find(o => o.orderID === orderId);
      
      // Open modal
      const modal = new bootstrap.Modal(document.getElementById('orderPageDetailModal'));
      modal.show();
    };
    
    // Lifecycle hooks
    onMounted(() => {
      fetchOrders();
      fetchOrderStats();
    });
    
    return {
      // State
      loading,
      error,
      orders,
      orderStats,
      selectedOrderId,
      selectedOrder,
      
      // Filters
      searchQuery,
      statusFilter,
      dateRangeFilter,
      
      // Pagination
      currentPage,
      perPage,
      pagination,
      paginationRange,
      
      // Table
      columns,
      
      // Methods
      fetchOrders,
      handleSearch,
      handleSort,
      applyFilters,
      changePage,
      viewOrder,
      
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
</style>