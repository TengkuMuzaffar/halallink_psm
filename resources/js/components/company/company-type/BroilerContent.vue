<template>
  <div class="card h-100">
    <div class="card-header theme-header">
      <h5 class="mb-0">Ordered Items Log</h5>
    </div>
    <div class="card-body">
      <!-- Table view for medium and large screens -->
      <div class="d-none d-md-block">
        <ResponsiveTable
          :columns="columns"
          :items="items"
          :loading="loading"
          item-key="cartID"
          :has-actions="false"
        >
          <!-- Custom column slots -->
          <template #item_image="{ item }">
            <img 
              :src="item.item.item_image || '/images/blank.jpg'" 
              alt="Item Image" 
              class="img-thumbnail" 
              style="width: 50px; height: 50px; object-fit: cover;"
            >
          </template>
          <template #price="{ item }">
            {{ formatCurrency(item.item.price) }}
          </template>
          <template #total="{ item }">
            {{ formatCurrency(item.item.price * item.quantity) }}
          </template>
          <template #order_status="{ item }">
            <span :class="getOrderStatusBadgeClass(item.order.order_status)">
              {{ item.order.order_status }}
            </span>
          </template>
          <template #order_date="{ item }">
            {{ formatDate(item.order.order_timestamp) }}
          </template>
          <!-- Empty state slot -->
          <template #empty>
            <div class="text-center py-4">
              <i class="fas fa-box text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted">No ordered items found</p>
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
          <p class="mt-2">Loading items...</p>
        </div>
        <div v-else-if="items.length === 0" class="text-center py-4">
          <i class="fas fa-box text-muted mb-2" style="font-size: 2rem;"></i>
          <p class="text-muted">No ordered items found</p>
        </div>
        <div v-else class="row">
          <div v-for="item in items" :key="item.cartID" class="col-12 col-sm-6 mb-3">
            <div class="broiler-item-card border rounded p-3 h-100">
              <div class="d-flex mb-3">
                <div class="me-3">
                  <img 
                    :src="item.item.item_image || '/images/blank.jpg'" 
                    alt="Item Image" 
                    class="img-thumbnail" 
                    style="width: 60px; height: 60px; object-fit: cover;"
                  >
                </div>
                <div>
                  <h6 class="mb-1">{{ item.item.poultry ? item.item.poultry.poultry_name : 'Unknown' }}</h6>
                  <span :class="getOrderStatusBadgeClass(item.order.order_status)">
                    {{ item.order.order_status }}
                  </span>
                </div>
              </div>
              <div class="row mb-2">
                <div class="col-6 text-muted">Quantity:</div>
                <div class="col-6 text-end fw-bold">{{ item.quantity }}</div>
              </div>
              <div class="row mb-2">
                <div class="col-6 text-muted">Price:</div>
                <div class="col-6 text-end">{{ formatCurrency(item.item.price) }}</div>
              </div>
              <div class="row mb-2">
                <div class="col-6 text-muted">Total:</div>
                <div class="col-6 text-end fw-bold">{{ formatCurrency(item.item.price * item.quantity) }}</div>
              </div>
              <div class="row">
                <div class="col-6 text-muted">Order Date:</div>
                <div class="col-6 text-end">{{ formatDate(item.order.order_timestamp) }}</div>
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
  name: 'BroilerContent',
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
        { key: 'item_image', label: 'Image', sortable: false },
        { key: 'item.poultry.poultry_name', label: 'Poultry', sortable: true },
        { key: 'quantity', label: 'Quantity', sortable: true },
        { key: 'price', label: 'Price', sortable: true },
        { key: 'total', label: 'Total', sortable: true },
        { key: 'order_status', label: 'Status', sortable: true },
        { key: 'order_date', label: 'Order Date', sortable: true },
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
.broiler-item-card {
  transition: all 0.2s ease;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.broiler-item-card:hover {
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.badge {
  font-size: 0.8rem;
  padding: 0.35em 0.65em;
}
</style>