<template>
  <div class="verify-delivery-management">
    <h1 class="mb-4">Verify Delivery</h1>
    
    <!-- Verification Stats could be added here similar to EmployeeStats -->
    
    <!-- Verifications Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Verifications</h5>
        <!-- Optional: Add a button here if needed -->
        <!-- <button class="btn btn-primary" @click="someAction">
          <i class="fas fa-plus me-1"></i> Some Action
        </button> -->
      </div>
      <div class="card-body">
        <!-- Error State -->
        <div v-if="error" class="alert alert-danger" role="alert">
          {{ error }}
        </div>
        
        <!-- Table (always show, with loading state inside) -->
        <ResponsiveTable
          :columns="tableColumns"
          :items="verifications"
          :loading="loading"
          :has-actions="true"
          item-key="verifyID"
          :show-pagination="false"
        >
          <!-- Custom column slots could be added here -->
          
          <!-- Actions slot -->
          <template #actions="{ item }">
            <button class="btn btn-sm btn-outline-primary" @click="openVerifiesModal(item)">
              <i class="fas fa-edit"></i>
            </button>
          </template>
        </ResponsiveTable>
        
        <!-- Add custom pagination controls (always visible when data is loaded) -->
        <div v-if="pagination.last_page > 0" class="d-flex justify-content-between align-items-center mt-3">
          <div>
            <span class="text-muted">Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} entries</span>
          </div>
          <nav aria-label="Table pagination">
            <ul class="pagination mb-0">
              <li class="page-item" :class="{ disabled: currentPage === 1 || loading }">
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
              <li class="page-item" :class="{ disabled: currentPage === pagination.last_page || loading }">
                <a class="page-link" href="#" @click.prevent="!loading && changePage(currentPage + 1)">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
    
    <!-- Verification Details Modal -->
    <VerifiesModalDetail
      :show="showDetailModal"
      :verify-id="selectedVerification ? selectedVerification.verifyID : null"
      :delivery-id="deliveryID"
      :location-id="locationID"
      :token="token"
      @close="closeVerifiesModal"
      @updated="handleVerificationUpdated"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import verifyDeliveryService from '../services/verifyDeliveryService';
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';
import LoadingSpinner from '../components/ui/LoadingSpinner.vue';
import VerifiesModalDetail from '../components/verify/VerifiesModalDetail.vue';

const route = useRoute();
const loading = ref(true);
const error = ref(null);
const deliveryID = ref(null);
const locationID = ref(null);
const token = ref(null);
const verifications = ref([]);

const selectedVerification = ref(null);
const showDetailModal = ref(false);

// Add pagination state
const currentPage = ref(1);
const perPage = ref(10);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0
});

// Computed property for pagination range
const paginationRange = computed(() => {
  const range = [];
  const maxVisiblePages = 5;
  const totalPages = pagination.value.last_page;
  
  if (totalPages <= maxVisiblePages) {
    // Show all pages if total is less than max visible
    for (let i = 1; i <= totalPages; i++) {
      range.push(i);
    }
  } else {
    // Show limited pages with current page in the middle
    let startPage = Math.max(1, pagination.value.current_page - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    // Adjust if we're near the end
    if (endPage === totalPages) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
      range.push(i);
    }
  }
  
  return range;
});

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};

const tableColumns = ref([
  { key: 'verifyID', label: 'Verification ID', sortable: true },
  { key: 'verify_status', label: 'Status', sortable: true },
  // Add more columns if needed
]);

const fetchVerifications = async () => {
  // Check for all required parameters including token
  if (!deliveryID.value || !locationID.value || !token.value) {
    error.value = "Delivery ID, Location ID, and Token are required parameters.";
    loading.value = false;
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const response = await verifyDeliveryService.getVerifications(deliveryID.value, locationID.value, token.value);
    if (response.status === 'error') {
      throw new Error(response.message);
    }
    
    const fetchedData = response.data || response;
    verifications.value = fetchedData;
    
    // Update pagination state
    pagination.value.total = verifications.value.length;
    pagination.value.last_page = Math.ceil(verifications.value.length / perPage.value);
    pagination.value.current_page = currentPage.value;
    pagination.value.from = verifications.value.length > 0 ? (currentPage.value - 1) * perPage.value + 1 : 0;
    pagination.value.to = Math.min(currentPage.value * perPage.value, verifications.value.length);
  } catch (err) {
    console.error('Error fetching verifications:', err);
    error.value = err.message || 'Failed to fetch verification data. Please try again.';
    verifications.value = [];
    
    // Reset pagination on error
    pagination.value = {
      current_page: 1,
      last_page: 1,
      per_page: perPage.value,
      total: 0,
      from: 0,
      to: 0
    };
  } finally {
    loading.value = false;
  }
};

// Change page
const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page || loading.value) return;
  
  currentPage.value = page;
  
  // Update pagination display
  pagination.value.current_page = page;
  pagination.value.from = (page - 1) * perPage.value + 1;
  pagination.value.to = Math.min(page * perPage.value, verifications.value.length);
};

const openVerifiesModal = (item) => {
  selectedVerification.value = item;
  showDetailModal.value = true;
};

const closeVerifiesModal = () => {
  showDetailModal.value = false;
  selectedVerification.value = null;
};

const handleVerificationUpdated = () => {
  fetchVerifications();
};

// Initialize data
onMounted(() => {
  // Get parameters from route query
  deliveryID.value = route.query.deliveryID;
  locationID.value = route.query.locationID;
  token.value = route.query.token;
  
  if (!deliveryID.value || !locationID.value || !token.value) {
    error.value = "Missing required parameters: Delivery ID, Location ID, and Token.";
    loading.value = false;
  } else {
    fetchVerifications();
  }
});
</script>

<style scoped>
.verify-delivery-management h1 {
  color: #123524;
}

/* Pagination styling */
.pagination {
  margin-bottom: 0;
}

.page-link {
  color: #123524;
}

.page-item.active .page-link {
  background-color: #123524;
  border-color: #123524;
  color: #fff;
}

.page-item.disabled .page-link {
  color: #6c757d;
  pointer-events: none;
  background-color: #fff;
  border-color: #dee2e6;
}

.page-link:hover {
  color: #0a1f15;
  background-color: #e9ecef;
  border-color: #dee2e6;
}

.page-link:focus {
  box-shadow: 0 0 0 0.25rem rgba(18, 53, 36, 0.25);
}

@media (max-width: 768px) {
  .verify-delivery-management h1 {
    font-size: 1.75rem;
  }
}
</style>