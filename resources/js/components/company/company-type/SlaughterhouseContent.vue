<template>
  <div class="card h-100">
    <div class="card-header theme-header">
      <h5 class="mb-0">Slaughterhouse Tasks</h5>
    </div>
    <div class="card-body">
      <!-- Table view for medium and large screens -->
      <div class="d-none d-md-block">
        <ResponsiveTable
          :columns="columns"
          :items="items"
          :loading="loading"
          item-key="checkID"
          :has-actions="false"
        >
          <!-- Custom column slots -->
          <template #custom_status="{ item }">
            <span :class="getTaskStatusBadgeClass(item.custom_status)">
              {{ item.custom_status }}
            </span>
          </template>
          <template #task.task_type="{ item }">
            {{ item.task?.task_type || 'N/A' }}
          </template>
          <template #item_details="{ item }">
            <button 
              class="btn btn-sm btn-outline-primary" 
              @click.stop="openItemDetailsModal(item)"
              :disabled="!item.item_details || item.item_details.length === 0"
            >
              <i class="fas fa-eye me-1"></i> View Items
            </button>
          </template>
          <!-- Empty state slot -->
          <template #empty>
            <div class="text-center py-4">
              <i class="fas fa-tasks text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted">No tasks found</p>
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
          <p class="mt-2">Loading tasks...</p>
        </div>
        <div v-else-if="items.length === 0" class="text-center py-4">
          <i class="fas fa-tasks text-muted mb-2" style="font-size: 2rem;"></i>
          <p class="text-muted">No tasks found</p>
        </div>
        <div v-else class="row">
          <div v-for="item in items" :key="item.checkID" class="col-12 col-sm-6 mb-3">
            <div class="slaughterhouse-task-card border rounded p-3 h-100">
              <div class="d-flex justify-content-between mb-3">
                <h6 class="mb-0">Task #{{ item.task?.taskID }}</h6>
                <span :class="getTaskStatusBadgeClass(item.custom_status)">
                  {{ item.custom_status }}
                </span>
              </div>
              <div class="row mb-2">
                <div class="col-6 text-muted">Task Type:</div>
                <div class="col-6 text-end">{{ item.task?.task_type || 'N/A' }}</div>
              </div>
              <div class="row mb-3">
                <div class="col-6 text-muted">Item Details:</div>
                <div class="col-6 text-end">
                  <button 
                    class="btn btn-sm btn-outline-primary w-100" 
                    @click.stop="openItemDetailsModal(item)"
                    :disabled="!item.item_details || item.item_details.length === 0"
                  >
                    <i class="fas fa-eye me-1"></i> View Items
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Item Details Modal -->
    <div class="modal fade" id="itemDetailsModal" tabindex="-1" aria-labelledby="itemDetailsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header theme-header">
            <h5 class="modal-title text-white" id="itemDetailsModalLabel">Item Details</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="!selectedItem || !selectedItem.item_details || selectedItem.item_details.length === 0" class="text-center py-4">
              <i class="fas fa-box text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted">No item details available</p>
            </div>
            <div v-else>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Image</th>
                      <th>Name</th>
                      <th>Measurement</th>
                      <th>Price</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in selectedItem.item_details" :key="item.itemID">
                      <td>
                        <img 
                          :src="item.item_image || '/images/blank.jpg'" 
                          alt="Item Image" 
                          class="img-thumbnail" 
                          style="width: 50px; height: 50px; object-fit: cover;"
                        >
                      </td>
                      <td>
                        <div class="fw-bold">{{ item.poultry ? item.poultry.poultry_name : 'Unknown' }}</div>
                        <small class="text-muted">ID: {{ item.itemID }}</small>
                      </td>
                      <td>{{ item.measurement_value }} {{ item.measurement_type }}</td>
                      <td>{{ formatCurrency(item.price) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ResponsiveTable from '../../ui/ResponsiveTable.vue';

export default {
  name: 'SlaughterhouseContent',
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
        { key: 'custom_status', label: 'Status', sortable: true },
        { key: 'task.task_type', label: 'Task Type', sortable: true },
        { key: 'item_details', label: 'Item Details', sortable: false }
      ],
      selectedItem: null,
      itemDetailsModal: null
    };
  },
  methods: {
    getTaskStatusBadgeClass(status) {
      const classes = 'badge ';
      switch (status) {
        case 'completed': return classes + 'bg-success';
        case 'in_progress': return classes + 'bg-primary';
        case 'pending': return classes + 'bg-warning';
        case 'cancelled': return classes + 'bg-danger';
        case 'not delivered': return classes + 'bg-secondary';
        default: return classes + 'bg-secondary';
      }
    },
    openItemDetailsModal(item) {
      this.selectedItem = item;
      this.itemDetailsModal.show();
    },
    formatCurrency(value) {
      if (!value) return 'RM 0.00';
      return new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' }).format(value);
    }
  },
  mounted() {
    // Initialize the modal
    this.itemDetailsModal = new bootstrap.Modal(document.getElementById('itemDetailsModal'));
  }
};
</script>

<style scoped>
.slaughterhouse-task-card {
  transition: all 0.2s ease;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.slaughterhouse-task-card:hover {
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.badge {
  font-size: 0.8rem;
  padding: 0.35em 0.65em;
}

.theme-header {
  background-color: #123524;
  color: #EFE3C2;
  border-bottom: none;
}

/* Modal styles */
.modal-content {
  border-radius: 0.5rem;
  overflow: hidden;
}

.modal-header.theme-header .btn-close-white {
  filter: brightness(0) invert(1);
}

.table-responsive {
  max-height: 400px;
  overflow-y: auto;
}
</style>