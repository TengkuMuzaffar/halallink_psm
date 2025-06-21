<template>
  <div class="card h-100">
    <div class="card-header theme-header">
      <h5 class="mb-0">Order History</h5>
    </div>
    <div class="card-body">
      <!-- Table view for medium and large screens -->
      <div class="d-none d-md-block">
        <ResponsiveTable
          :columns="columns"
          :items="items"
          :loading="loading"
          item-key="orderID"
          :has-actions="false"
        >
          <!-- Custom column slots -->
          <template #order_status="{ item }">
            <span :class="getOrderStatusBadgeClass(item.order_status)">
              {{ item.order_status }}
            </span>
          </template>
          <template #order_date="{ item }">
            {{ formatDate(item.order_timestamp) }}
          </template>
          <template #payment_amount="{ item }">
            {{ formatCurrency(item.payment?.payment_amount || 0) }}
          </template>
          <template #actions="{ item }">
            <button 
              class="btn btn-sm btn-outline-primary" 
              @click="$emit('view-order-details', item.orderID)"
            >
              <i class="fas fa-eye me-1"></i> View Details
            </button>
          </template>
          <!-- Empty state slot -->
          <template #empty>
            <div class="text-center py-4">
              <i class="fas fa-shopping-cart text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted">No orders found</p>
            </div>
          </template>
        </ResponsiveTable>
      </div>
      
      <!-- Card grid view for small screens -->
      <div class="d-md-none">
        <div v-if="loading" class="text-center py-3">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading orders...</p>
        </div>
        <div v-else-if="items.length === 0" class="text-center py-4">
          <i class="fas fa-shopping-cart text-muted mb-2" style="font-size: 2rem;"></i>
          <p class="text-muted">No orders found</p>
        </div>
        <div v-else class="row">
          <div v-for="item in items" :key="item.orderID" class="col-12 col-sm-6 mb-3">
            <div class="sme-order-card border rounded p-3 h-100">
              <div class="d-flex justify-content-between mb-3">
                <h6 class="mb-0">Order #{{ item.orderID }}</h6>
                <span :class="getOrderStatusBadgeClass(item.order_status)">
                  {{ item.order_status }}
                </span>
              </div>
              <div class="row mb-2">
                <div class="col-6 text-muted">Date:</div>
                <div class="col-6 text-end">{{ formatDate(item.order_timestamp) }}</div>
              </div>
              <div class="row mb-2">
                <div class="col-6 text-muted">Items:</div>
                <div class="col-6 text-end">{{ item.items_count || 0 }}</div>
              </div>
              <div class="row mb-3">
                <div class="col-6 text-muted">Total:</div>
                <div class="col-6 text-end fw-bold">{{ formatCurrency(item.payment?.payment_amount || 0) }}</div>
              </div>
              <div class="d-grid">
                <button 
                  class="btn btn-sm btn-outline-primary" 
                  @click="$emit('view-order-details', item.orderID)"
                >
                  <i class="fas fa-eye me-1"></i> View Details
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ResponsiveTable from '../../ui/ResponsiveTable.vue';

export default {
  name: 'SmeContent',
  components: {
    ResponsiveTable
  },
  props: {
    items: {
      type: Array,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      columns: [
        { key: 'orderID', label: 'Order ID', sortable: true },
        { key: 'order_status', label: 'Status', sortable: true },
        { key: 'order_date', label: 'Order Date', sortable: true },
        { key: 'items_count', label: 'Items', sortable: true },
        { key: 'payment_amount', label: 'Total', sortable: true },
        { key: 'actions', label: 'Actions', sortable: false }
      ]
    };
  },
  methods: {
    formatCurrency(value) {
      if (!value) return 'RM 0.00';
      return new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' }).format(value);
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-MY');
    },
    getOrderStatusBadgeClass(status) {
      const classes = 'badge ';
      switch (status) {
        case 'paid': return classes + 'bg-success';
        case 'pending': return classes + 'bg-warning';
        case 'cancelled': return classes + 'bg-danger';
        case 'delivered': return classes + 'bg-info';
        default: return classes + 'bg-secondary';
      }
    }
  }
};
</script>

<style scoped>
.theme-header {
  background-color: #123524;
  color: #EFE3C2;
  border-bottom: none;
}
.sme-order-card {
  transition: all 0.2s ease;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.sme-order-card:hover {
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.badge {
  font-size: 0.8rem;
  padding: 0.35em 0.65em;
}
</style>