<template>
  <div class="card h-100">
    <div class="card-header theme-header">
      <h5 class="mb-0">Delivery Schedule</h5>
    </div>
    <div class="card-body">
      <!-- Table view for medium and large screens -->
      <div class="d-none d-md-block">
        <ResponsiveTable
          :columns="columns"
          :items="items"
          :loading="loading"
          item-key="deliveryID"
          :has-actions="false"
        >
          <!-- Custom column slots -->
          <template #scheduled_date="{ item }">
            {{ formatDateTime(item.scheduled_date) }}
          </template>
          <template #status="{ item }">
            <span :class="getDeliveryStatusBadgeClass(item)">
              {{ getDeliveryStatus(item) }}
            </span>
          </template>
          <!-- Empty state slot -->
          <template #empty>
            <div class="text-center py-4">
              <i class="fas fa-truck text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted">No deliveries found</p>
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
          <p class="mt-2">Loading deliveries...</p>
        </div>
        <div v-else-if="items.length === 0" class="text-center py-4">
          <i class="fas fa-truck text-muted mb-2" style="font-size: 2rem;"></i>
          <p class="text-muted">No deliveries found</p>
        </div>
        <div v-else class="row">
          <div v-for="item in items" :key="item.deliveryID" class="col-12 col-sm-6 mb-3">
            <div class="logistic-delivery-card border rounded p-3 h-100">
              <div class="d-flex justify-content-between mb-3">
                <h6 class="mb-0">Delivery #{{ item.deliveryID }}</h6>
                <span :class="getDeliveryStatusBadgeClass(item)">
                  {{ getDeliveryStatus(item) }}
                </span>
              </div>
              <div class="row mb-2">
                <div class="col-6 text-muted">Scheduled:</div>
                <div class="col-6 text-end">{{ formatDateTime(item.scheduled_date) }}</div>
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
  name: 'LogisticContent',
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
        { key: 'deliveryID', label: 'Delivery ID', sortable: true },
        { key: 'scheduled_date', label: 'Scheduled Date', sortable: true },
        { key: 'status', label: 'Status', sortable: true }
      ]
    };
  },
  methods: {
    formatDateTime(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleString('en-MY');
    },
    getDeliveryStatus(delivery) {
      if (delivery.end_timestamp) return 'Completed';
      if (delivery.start_timestamp) return 'In Progress';
      return 'Scheduled';
    },
    getDeliveryStatusBadgeClass(delivery) {
      const classes = 'badge ';
      const status = this.getDeliveryStatus(delivery);
      
      switch (status) {
        case 'Completed': return classes + 'bg-success';
        case 'In Progress': return classes + 'bg-primary';
        case 'Scheduled': return classes + 'bg-warning';
        default: return classes + 'bg-secondary';
      }
    }
  }
};
</script>

<style scoped>
.logistic-delivery-card {
  transition: all 0.2s ease;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.logistic-delivery-card:hover {
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.badge {
  font-size: 0.8rem;
  padding: 0.35em 0.65em;
}
</style>