<template>
  <div class="delivery-management ">
    <h1 class="mb-4">Delivery Management</h1>

    <!-- Delivery Stats -->
    <div class="row mb-4">
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard
          title="Pending Assignment"
          :count="deliveryStats.pending"
          icon="fas fa-clock"
          bg-color="bg-warning"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard
          title="In Progress"
          :count="deliveryStats.inProgress"
          icon="fas fa-truck-loading"
          bg-color="bg-info"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard
          title="Completed Today"
          :count="deliveryStats.completedToday"
          icon="fas fa-check-circle"
          bg-color="bg-success"
        />
      </div>
      <div class="col-md-3 col-sm-6 mb-3">
        <StatsCard
          title="Issues Reported"
          :count="deliveryStats.issues"
          icon="fas fa-exclamation-triangle"
          bg-color="bg-danger"
        />
      </div>
    </div>

    <!-- Deliveries Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Deliveries</h5>
        <button class="btn btn-primary" @click="openAssignModal" :disabled="loading">
          <i class="fas fa-plus me-1"></i> Assign Delivery Run
        </button>
      </div>
      <div class="card-body position-relative">
        <!-- Error State -->
        <div v-if="error" class="alert alert-danger" role="alert">
          {{ error }}
        </div>

        <!-- Loading Spinner -->
        <LoadingSpinner v-if="loading" overlay size="md" message="Loading deliveries..." />

        <!-- Table -->
        <ResponsiveTable
          :columns="columns"
          :items="deliveries"
          :loading="false"
          :has-actions="true"
          item-key="deliveryID"
          @search="handleSearch"
          :show-pagination="false" 
          :server-side="true"
        >
          <!-- Custom column slots -->
          <template #status="{ item }">
            <span :class="getStatusBadge(item.status)">{{ formatStatus(item.status) }}</span>
          </template>

          <template #driver="{ item }">
            {{ item.driver ? item.driver.name : 'N/A' }}
          </template>

          <template #vehicle="{ item }">
            {{ item.vehicle ? `${item.vehicle.vehicle_brand} (${item.vehicle.vehicle_plate})` : 'N/A' }}
          </template>

          <template #start_timestamp="{ item }">
            {{ formatTimestamp(item.start_timestamp) }}
          </template>

          <template #arrive_timestamp="{ item }">
            {{ formatTimestamp(item.arrive_timestamp) }}
          </template>

          <!-- Actions slot -->
          <template #actions="{ item }">
            <button class="btn btn-sm btn-outline-info me-1" @click="viewDeliveryDetails(item)" title="View Details">
              <i class="fas fa-eye"></i>
            </button>
            <button v-if="canUpdate(item)" class="btn btn-sm btn-outline-primary me-1" @click="updateDeliveryStatus(item)" title="Update Status">
              <i class="fas fa-edit"></i>
            </button>
             <button v-if="canCancel(item)" class="btn btn-sm btn-outline-danger" @click="cancelDelivery(item)" title="Cancel Delivery">
              <i class="fas fa-times-circle"></i>
            </button>
          </template>
        </ResponsiveTable>

        <!-- Pagination -->
        <div v-if="!loading && deliveries.length > 0" class="d-flex justify-content-between align-items-center mt-4">
          <div>
            <span class="text-muted">Showing {{ pagination.from || 1 }}-{{ pagination.to || deliveries.length }} of {{ pagination.total || deliveries.length }}</span>
          </div>
          <nav aria-label="Delivery pagination">
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
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue';
import StatsCard from '../components/ui/StatsCard.vue';
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';
import LoadingSpinner from '../components/ui/LoadingSpinner.vue';
import api from '../utils/api'; // Assuming you have this utility
import modal from '../utils/modal'; // Assuming you have this utility

export default {
  name: 'DeliveryManagement',
  components: {
    StatsCard,
    ResponsiveTable,
    LoadingSpinner
  },
  setup() {
    const loading = ref(false);
    const error = ref(null);
    const deliveries = ref([]); // Holds the list of deliveries
    const searchQuery = ref('');

    // Pagination state
    const currentPage = ref(1);
    const perPage = ref(10); // Items per page
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: 0,
      to: 0
    });

    // Computed property for pagination range (similar to PoultryManagement)
    const paginationRange = computed(() => {
      const range = [];
      const maxVisiblePages = 5;
      const totalPages = pagination.value.last_page;

      if (!totalPages || totalPages <= 1) return [];

      if (totalPages <= maxVisiblePages) {
        for (let i = 1; i <= totalPages; i++) range.push(i);
      } else {
        let startPage = Math.max(1, currentPage.value - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        if (endPage === totalPages) startPage = Math.max(1, endPage - maxVisiblePages + 1);
        for (let i = startPage; i <= endPage; i++) range.push(i);
      }
      return range;
    });

    // Placeholder stats
    const deliveryStats = ref({
      pending: 0,
      inProgress: 0,
      completedToday: 0,
      issues: 0
    });

    // Table columns definition
    const columns = [
      { key: 'deliveryID', label: 'ID', sortable: true },
      { key: 'status', label: 'Status', sortable: true },
      { key: 'driver', label: 'Driver', sortable: false }, // Assuming driver name isn't directly sortable here
      { key: 'vehicle', label: 'Vehicle', sortable: false },
      { key: 'start_timestamp', label: 'Started At', sortable: true },
      { key: 'arrive_timestamp', label: 'Arrived At', sortable: true },
      // Add more columns as needed (e.g., Origin, Destination, Checkpoints Count)
    ];

    // --- Placeholder Methods ---

    const fetchDeliveries = async () => {
      console.log('Fetching deliveries for page:', currentPage.value, 'Search:', searchQuery.value);
      loading.value = true;
      error.value = null;
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000));
      // Replace with actual API call:
      // try {
      //   const params = { page: currentPage.value, per_page: perPage.value, search: searchQuery.value || null };
      //   const response = await api.get('/api/deliveries', { params });
      //   deliveries.value = response.data;
      //   pagination.value = response.pagination;
      // } catch (err) {
      //   console.error('Error fetching deliveries:', err);
      //   error.value = 'Failed to load deliveries.';
      //   deliveries.value = [];
      // } finally {
      //   loading.value = false;
      // }

      // Placeholder data:
      deliveries.value = []; // Start empty or with placeholder if needed
      pagination.value = { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 };
      loading.value = false;
      // error.value = 'Data fetching not implemented yet.'; // Uncomment to show error
    };

    const fetchDeliveryStats = async () => {
      console.log('Fetching delivery stats...');
      // Replace with actual API call:
      // try {
      //   const statsData = await api.get('/api/deliveries/stats');
      //   deliveryStats.value = statsData;
      // } catch (err) {
      //   console.error('Error fetching delivery stats:', err);
      // }
      deliveryStats.value = { pending: 5, inProgress: 3, completedToday: 12, issues: 1 }; // Placeholder
    };

    const handleSearch = (query) => {
      searchQuery.value = query;
      currentPage.value = 1;
      fetchDeliveries();
      // fetchDeliveryStats(); // Optionally update stats on search
    };

    const changePage = (page) => {
      if (page < 1 || page > pagination.value.last_page || loading.value) return;
      currentPage.value = page;
      fetchDeliveries();
    };

    const openAssignModal = () => {
      console.log('Open Assign Delivery Modal');
      modal.info('Assign Delivery', 'Delivery assignment form will be here.'); // Placeholder modal
    };

    const viewDeliveryDetails = (item) => {
      console.log('View details for delivery:', item.deliveryID);
      modal.info('Delivery Details', `Details for Delivery ID: ${item.deliveryID}\nStatus: ${item.status || 'N/A'}`); // Placeholder
    };

    const updateDeliveryStatus = (item) => {
      console.log('Update status for delivery:', item.deliveryID);
       modal.info('Update Status', `Status update form for Delivery ID: ${item.deliveryID}`); // Placeholder
    };

     const cancelDelivery = (item) => {
      console.log('Cancel delivery:', item.deliveryID);
      modal.confirm(
        'Cancel Delivery',
        `Are you sure you want to cancel Delivery ID: ${item.deliveryID}?`,
        () => { console.log('Confirmed cancellation'); /* Add API call here */ },
        null,
        { confirmLabel: 'Yes, Cancel', confirmType: 'danger' }
      );
    };

    // Helper functions for display logic
    const getStatusBadge = (status) => {
      // Return appropriate Bootstrap badge class based on status
      switch (status?.toLowerCase()) {
        case 'pending': return 'badge bg-secondary';
        case 'assigned': return 'badge bg-info text-dark';
        case 'in_progress':
        case 'departed': return 'badge bg-primary';
        case 'arrived':
        case 'completed': return 'badge bg-success';
        case 'delayed': return 'badge bg-warning text-dark';
        case 'cancelled':
        case 'failed': return 'badge bg-danger';
        default: return 'badge bg-light text-dark';
      }
    };

    const formatStatus = (status) => {
        if (!status) return 'Unknown';
        return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()); // Capitalize words
    };

    const formatTimestamp = (timestamp) => {
        if (!timestamp) return 'N/A';
        try {
            return new Date(timestamp).toLocaleString();
        } catch (e) {
            return 'Invalid Date';
        }
    };

    const canUpdate = (item) => {
        // Logic to determine if status can be updated (e.g., not completed or cancelled)
        const lowerStatus = item.status?.toLowerCase();
        return lowerStatus !== 'completed' && lowerStatus !== 'cancelled' && lowerStatus !== 'failed';
    };

     const canCancel = (item) => {
        // Logic to determine if delivery can be cancelled (e.g., only before departure)
        const lowerStatus = item.status?.toLowerCase();
        return lowerStatus === 'pending' || lowerStatus === 'assigned';
    };

    // Fetch initial data on component mount
    onMounted(() => {
      fetchDeliveries();
      fetchDeliveryStats();
    });

    return {
      loading,
      error,
      deliveries,
      deliveryStats,
      columns,
      searchQuery,
      currentPage,
      pagination,
      paginationRange,
      handleSearch,
      changePage,
      openAssignModal,
      viewDeliveryDetails,
      updateDeliveryStatus,
      cancelDelivery,
      getStatusBadge,
      formatStatus,
      formatTimestamp,
      canUpdate,
      canCancel
    };
  }
};
</script>

<style scoped>
/* Add styles similar to PoultryManagement or specific to Delivery */
.delivery-management .card {
  box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.pagination {
  margin-bottom: 0;
}

.page-link {
  color: #123524; /* Match theme */
}

.page-item.active .page-link {
  background-color: #123524; /* Match theme */
  border-color: #123524;
  color: #fff;
}

.badge {
    font-size: 0.8em;
    padding: 0.4em 0.6em;
}
</style>