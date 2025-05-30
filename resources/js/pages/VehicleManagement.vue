<template>
  <div class="vehicle-management">
    <h1 class="mb-4">Vehicle Management</h1>
    
    <!-- Vehicle Stats -->
    <div class="row mb-4">
      <div class="col-md-4 col-sm-6 mb-3">
        <StatsCard 
          title="Total Vehicles" 
          :count="vehicleStats.total" 
          icon="fas fa-truck" 
          bg-color="bg-primary"
        />
      </div>
      <!-- Removed the Average Load Capacity card -->
    </div>
    
    <!-- Vehicles Table -->
    <div class="card">
      <div class="card-header theme-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Vehicles</h5>
        <button class="btn btn-primary" @click="openAddModal">
          <i class="fas fa-plus me-1"></i> Add Vehicle
        </button>
      </div>
      <div class="card-body position-relative">
        <!-- Error State -->
        <div v-if="error" class="alert alert-danger" role="alert">
          {{ error }}
        </div>
        
        <!-- Loading Spinner -->
        <LoadingSpinner v-if="loading" overlay size="md" message="Loading vehicles..." />
        
        <!-- Table (always show, with loading state inside) -->
        <ResponsiveTable
          :columns="columns"
          :items="vehicles"
          :loading="false"
          :has-actions="true"
          item-key="vehicleID"
          @search="handleSearch"
          @min-max-change="handleMinMaxChange"
          :show-pagination="false"
          :server-side="true"
          :showFilters="true"
          :showMinMaxFilters="true"
          minLabel="Min Weight (kg)"
          maxLabel="Max Weight (kg)"
          minPlaceholder="Min"
          maxPlaceholder="Max"
          searchPlaceholder="Search by plate number..."
          minMaxField="vehicle_load_weight"
        >
          <!-- Custom column slots -->
          <template #vehicle_load_weight="{ item }">
            {{ formatWeight(item.vehicle_load_weight) }}
          </template>
          
        
          
          <!-- Actions slot -->
          <template #actions="{ item }">
            <button class="btn btn-sm btn-outline-primary me-1" @click="editVehicle(item)">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(item)">
              <i class="fas fa-trash"></i>
            </button>
          </template>
          
          <!-- Empty state -->
          <template #empty>
            <div class="text-center py-4">
              <i class="fas fa-truck text-muted mb-3" style="font-size: 2rem;"></i>
              <p class="text-muted">No vehicles found. Try adjusting your filters or add a new vehicle.</p>
            </div>
          </template>
        </ResponsiveTable>
        
        <!-- Pagination - Updated to match PoultryManagement style -->
        <div v-if="!loading && vehicles.length > 0" class="d-flex justify-content-between align-items-center mt-4">
          <div>
            <span class="text-muted">Showing {{ pagination.from || 1 }}-{{ pagination.to || vehicles.length }} of {{ pagination.total || vehicles.length }}</span>
          </div>
          <nav aria-label="Vehicle pagination">
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
    
    <!-- Add/Edit Vehicle Modal -->
    <div class="modal fade" id="vehicleModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header theme-header">
            <h5 class="modal-title">{{ isEditing ? 'Edit Vehicle' : 'Add New Vehicle' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitVehicleForm">
              <!-- Company Information (read-only) -->
              <div class="mb-3">
                <label class="form-label">Company</label>
                <input 
                  type="text" 
                  class="form-control" 
                  :value="userCompany.company_name || 'Your Company'" 
                  disabled
                >
                <small class="text-muted">Vehicles are automatically assigned to your company</small>
              </div>
              
              <!-- Vehicle Plate -->
              <div class="mb-3">
                <label for="vehicle_plate" class="form-label">Vehicle Plate Number</label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="vehicle_plate" 
                  v-model="vehicleForm.vehicle_plate"
                  :class="{ 'is-invalid': formErrors.vehicle_plate }"
                  required
                >
                <div class="invalid-feedback" v-if="formErrors.vehicle_plate">
                  {{ formErrors.vehicle_plate[0] }}
                </div>
              </div>
              
              <!-- Load Weight -->
              <div class="mb-3">
                <label for="vehicle_load_weight" class="form-label">Load Capacity (kg)</label>
                <input 
                  type="number" 
                  class="form-control" 
                  id="vehicle_load_weight" 
                  v-model.number="vehicleForm.vehicle_load_weight"
                  :class="{ 'is-invalid': formErrors.vehicle_load_weight }"
                  min="0"
                  step="0.01"
                  required
                >
                <div class="invalid-feedback" v-if="formErrors.vehicle_load_weight">
                  {{ formErrors.vehicle_load_weight[0] }}
                </div>
              </div>
              
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="formSubmitting">
                  <span v-if="formSubmitting" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                  {{ isEditing ? 'Update Vehicle' : 'Add Vehicle' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete the vehicle with plate number <strong>{{ selectedVehicle?.vehicle_plate }}</strong>?</p>
            <p class="mb-0 text-danger"><small>This action cannot be undone.</small></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" @click="deleteVehicle" :disabled="formSubmitting">
              <span v-if="formSubmitting" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { fetchData } from '../utils/api';
import { showModal, showToast } from '../utils/modal';
import StatsCard from '../components/ui/StatsCard.vue';
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';
import LoadingSpinner from '../components/ui/LoadingSpinner.vue';
import Badge from '../components/ui/Badge.vue';
import * as bootstrap from 'bootstrap';
import { useStore } from 'vuex'; // Add this import

export default {
  name: 'VehicleManagement',
  components: {
    StatsCard,
    ResponsiveTable,
    LoadingSpinner,
    Badge
  },
  setup() {
    // Get store to access user data
    const store = useStore();
    
    // State
    const vehicles = ref([]);
    const vehicleStats = ref({ total: 0, average_weight: 0 });
    const loading = ref(true);
    const error = ref(null);
    const companies = ref([]);
    const selectedVehicle = ref(null);
    const isEditing = ref(false);
    const formSubmitting = ref(false);
    const formErrors = ref({});
    
    // Get current user and company
    const currentUser = computed(() => store.getters.user || {});
    const userCompany = computed(() => store.getters.userCompany || {});
    
    // Modals
    let vehicleModal = null;
    let deleteModal = null;
    
    // Pagination
    const pagination = ref({
      current_page: 1,
      from: 1,
      last_page: 1,
      per_page: 10,
      to: 0,
      total: 0
    });
    
    const currentPage = computed(() => pagination.value.current_page);
    
    const paginationRange = computed(() => {
      const range = [];
      const totalPages = pagination.value.last_page || 1;
      const current = currentPage.value;
      
      const delta = 2;
      const left = current - delta;
      const right = current + delta + 1;
      
      for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= left && i < right)) {
          range.push(i);
        }
      }
      
      return range;
    });
    
    // Table columns
    const columns = [
      { key: 'vehicle_plate', label: 'Plate Number', sortable: true },
      { key: 'vehicle_load_weight', label: 'Load Capacity (kg)', sortable: true },
    ];
    
    // Form
    const vehicleForm = reactive({
      companyID: '',
      vehicle_plate: '',
      vehicle_load_weight: 0
    });
    
    // Filters
    const filters = reactive({
      search: '',
      min_weight: '',
      max_weight: '',
      sort_field: 'vehicleID',
      sort_direction: 'desc',
      page: 1,
      per_page: 10
    });
    
    // Methods
    const fetchVehicles = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await fetchData('/api/vehicles', {
          params: {
            ...filters,
            page: filters.page
          }
        });
        
        vehicles.value = response.vehicles.data;
        pagination.value = {
          current_page: response.vehicles.current_page,
          from: response.vehicles.from,
          last_page: response.vehicles.last_page,
          per_page: response.vehicles.per_page,
          to: response.vehicles.to,
          total: response.vehicles.total
        };
        vehicleStats.value = response.stats;
      } catch (err) {
        error.value = 'Failed to load vehicles. Please try again.';
        console.error('Error fetching vehicles:', err);
      } finally {
        loading.value = false;
      }
    };
    
    // We can remove fetchCompanies since we don't need it anymore
    
    const resetForm = () => {
      // Set companyID to the current user's company ID
      vehicleForm.companyID = userCompany.value?.companyID || '';
      vehicleForm.vehicle_plate = '';
      vehicleForm.vehicle_load_weight = 0;
      formErrors.value = {};
    };
    
    const openAddModal = () => {
      isEditing.value = false;
      resetForm();
      vehicleModal.show();
    };
    
    const editVehicle = (vehicle) => {
      isEditing.value = true;
      selectedVehicle.value = vehicle;
      
      // For editing, we keep the original companyID if it's the user's company
      // This allows admins to edit vehicles that might belong to other companies
      vehicleForm.companyID = vehicle.companyID;
      vehicleForm.vehicle_plate = vehicle.vehicle_plate;
      vehicleForm.vehicle_load_weight = vehicle.vehicle_load_weight;
      
      formErrors.value = {};
      vehicleModal.show();
    };
    
    const confirmDelete = (vehicle) => {
      selectedVehicle.value = vehicle;
      deleteModal.show();
    };
    
    const submitVehicleForm = async () => {
      formSubmitting.value = true;
      formErrors.value = {};
      
      try {
        let response;
        
        if (isEditing.value) {
          response = await fetchData(`/api/vehicles/${selectedVehicle.value.vehicleID}`, {
            method: 'put',
            data: vehicleForm
          });
          
          showToast('Vehicle updated successfully', 'success');
        } else {
          response = await fetchData('/api/vehicles', {
            method: 'post',
            data: vehicleForm
          });
          
          showToast('Vehicle added successfully', 'success');
        }
        
        vehicleModal.hide();
        fetchVehicles();
      } catch (err) {
        if (err.response && err.response.status === 422) {
          formErrors.value = err.response.data.errors;
        } else {
          showToast('An error occurred. Please try again.', 'danger');
        }
        console.error('Form submission error:', err);
      } finally {
        formSubmitting.value = false;
      }
    };
    
    const deleteVehicle = async () => {
      if (!selectedVehicle.value) return;
      
      formSubmitting.value = true;
      
      try {
        await fetchData(`/api/vehicles/${selectedVehicle.value.vehicleID}`, {
          method: 'delete'
        });
        
        deleteModal.hide();
        showToast('Vehicle deleted successfully', 'success');
        fetchVehicles();
      } catch (err) {
        showToast('Failed to delete vehicle', 'danger');
        console.error('Delete error:', err);
      } finally {
        formSubmitting.value = false;
      }
    };
    
    const changePage = (page) => {
      if (page < 1 || page > pagination.value.last_page) return;
      
      filters.page = page;
      fetchVehicles();
    };
    
    const handleSort = ({ key, direction }) => {
      filters.sort_field = key;
      filters.sort_direction = direction;
      fetchVehicles();
    };
    
    // Replace debounceSearch with handleSearch to use ResponsiveTable's search
    const handleSearch = (searchQuery) => {
      filters.search = searchQuery;
      filters.page = 1; // Reset to first page when searching
      fetchVehicles();
    };
    const handleMinMaxChange = ({ min, max }) => {
        filters.min_weight = min;
        filters.max_weight = max;
        filters.page = 1; // Reset to first page when changing filters
        fetchVehicles();
    };
    const formatWeight = (weight) => {
      if (weight === null || weight === undefined) return '0 kg';
      return `${parseFloat(weight).toLocaleString()} kg`;
    };
    
    // Lifecycle hooks
    onMounted(() => {
      // Initialize modals
      vehicleModal = new bootstrap.Modal(document.getElementById('vehicleModal'));
      deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
      
      // Fetch initial data
      fetchVehicles();
      // We don't need to fetch companies anymore
      
      // Clean up modals when component is destroyed
      return () => {
        if (vehicleModal) vehicleModal.dispose();
        if (deleteModal) deleteModal.dispose();
      };
    });
    
    return {
      // State
      vehicles,
      vehicleStats,
      loading,
      error,
      selectedVehicle,
      isEditing,
      formSubmitting,
      formErrors,
      vehicleForm,
      filters,
      pagination,
      currentPage,
      paginationRange,
      columns,
      currentUser,
      userCompany, // Add this to the returned object
      handleMinMaxChange,
      // Methods
      fetchVehicles,
      openAddModal,
      editVehicle,
      confirmDelete,
      submitVehicleForm,
      deleteVehicle,
      changePage,
      handleSort,
      handleSearch, // New method to handle search from ResponsiveTable
      formatWeight
    };
  }
};
</script>

<style scoped>
.pagination {
  margin-bottom: 0;
}
.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
}
.page-link {
  color: #123524;
}

.page-item.active .page-link {
  background-color: #123524;
  border-color: #123524;
  color: #fff;
}
.vehicle-management {
  padding-bottom: 2rem;
}

.actions-column {
  width: 100px;
  text-align: center;
}

@media (max-width: 768px) {
  .filters-row {
    flex-direction: column;
  }
  
  .filters-row > div {
    margin-bottom: 1rem;
  }
}
</style>