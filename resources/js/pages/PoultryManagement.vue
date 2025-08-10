<template>
  <div class="poultry-management">
    <h1 class="mb-4">Poultry Management</h1>
    
    <!-- Poultry Stats -->
    <div class="row mb-4">
      <div class="col-md-4 col-sm-6 mb-3">
        <StatsCard 
          title="Total Poultries" 
          :count="poultryStats.total" 
          icon="fas fa-feather" 
          bg-color="bg-primary"
        />
      </div>
     
    </div>
    
    <!-- Poultries Table/Card View -->
    <div class="card theme-card">
      <div class="card-header theme-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fs-4">Poultries</h5>
        <button class="btn btn-primary easy-touch" @click="openAddModal">
          <i class="fas fa-plus"></i> <span class="d-none d-md-inline ms-1">Add Poultry</span>
        </button>
      </div>
      <div class="card-body position-relative p-0">
        <!-- Error State -->
        <div v-if="error" class="alert alert-danger m-3" role="alert">
          {{ error }}
        </div>
        
        <!-- Loading Spinner -->
        <LoadingSpinner v-if="loading" overlay size="md" message="Loading poultries..." />
        
        <!-- Desktop Table View -->
        <div class="d-none d-md-block">
          <ResponsiveTable
            :columns="columns"
            :items="poultries"
            :loading="false"
            :has-actions="true"
            item-key="poultryID"
            @search="handleSearch"
            :show-pagination="false"
            :server-side="true"
          >
            <!-- Custom column slots -->
            <template #poultry_image="{ item }">
              <div class="d-flex align-items-center">
                <img 
                  v-if="item.poultry_image" 
                  :src="item.poultry_image" 
                  alt="Poultry Image" 
                  class="img-thumbnail me-2" 
                  style="width: 50px; height: 50px; object-fit: cover;"
                >
                <div v-else class="bg-light d-flex align-items-center justify-content-center me-2" style="width: 50px; height: 50px;">
                  <i class="fas fa-feather text-muted"></i>
                </div>
              </div>
            </template>
            
            <template #created_at="{ item }">
              {{ formatDate(item.created_at) }}
            </template>
            
            <!-- Actions slot -->
            <template #actions="{ item }">
              <button class="btn btn-sm btn-outline-primary me-1" @click="editPoultry(item)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(item)">
                <i class="fas fa-trash"></i>
              </button>
            </template>
          </ResponsiveTable>
        </div>
        
        <!-- Mobile Card View -->
        <div class="d-md-none">
          <!-- Add search input for mobile -->
          <div class="p-3">
            <div class="input-group mb-3">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
              <input 
                type="text" 
                class="form-control" 
                placeholder="Search poultries..." 
                v-model="searchQuery"
                @input="handleSearch(searchQuery)"
              >
            </div>
          </div>
          
          <div v-if="!loading && poultries.length === 0" class="p-4 text-center">
            <p class="text-muted mb-0 fs-5">No poultries found</p>
          </div>
          <ul v-else class="list-group list-group-flush">
            <li v-for="poultry in poultries" :key="poultry.poultryID" class="list-group-item theme-list-item p-3">
              <div class="d-flex align-items-center">
                <!-- Poultry Image -->
                <div class="me-3">
                  <img 
                    v-if="poultry.poultry_image" 
                    :src="poultry.poultry_image" 
                    alt="Poultry Image" 
                    class="img-thumbnail" 
                    style="width: 70px; height: 70px; object-fit: cover;"
                  >
                  <div v-else class="bg-light d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                    <i class="fas fa-feather text-muted fa-2x"></i>
                  </div>
                </div>
                
                <!-- Poultry Details -->
                <div class="flex-grow-1">
                  <div class="fw-bold fs-5">{{ poultry.poultry_name }}</div>
                  <div class="text-muted fs-6">
                    <i class="far fa-calendar-alt me-1"></i> {{ formatDate(poultry.created_at) }}
                  </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex">
                  <button class="btn btn-outline-primary easy-touch me-2" @click="editPoultry(poultry)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-outline-danger easy-touch" @click="confirmDelete(poultry)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </li>
          </ul>
        </div>
        
        <!-- Pagination -->
        <div v-if="!loading && poultries.length > 0" class="p-3 theme-pagination-container">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-muted">Showing {{ pagination.from || 1 }}-{{ pagination.to || poultries.length }} of {{ pagination.total || poultries.length }}</span>
            </div>
            <Pagination 
              :pagination="pagination" 
              :maxVisiblePages="3" 
              @page-changed="changePage" 
              class="large-pagination"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Custom Modal for Poultry Details -->
  <div class="modal fade" id="poultryModal" tabindex="-1" aria-labelledby="poultryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header theme-header">
          <h5 class="modal-title fs-4" id="poultryModalLabel">{{ modalTitle }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="poultry-form">
            <div class="mb-3">
              <label for="poultry-name" class="form-label">Poultry Name</label>
              <input type="text" class="form-control" id="poultry-name" v-model="modalPoultry.poultry_name" required>
            </div>
            <div class="mb-3">
              <label for="poultry-image-file" class="form-label">Upload Image</label>
              <input type="file" class="form-control" id="poultry-image-file" accept="image/*" @change="handleImageChange">
            </div>
            <div v-if="modalPoultry.poultry_image" class="mb-3">
              <label class="form-label">Current Image</label>
              <div>
                <img :src="modalPoultry.poultry_image" alt="Current Poultry Image" class="img-thumbnail" style="max-width: 200px;">
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary easy-touch-lg" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i> Cancel
          </button>
          <button type="button" class="btn btn-primary easy-touch-lg" @click="savePoultry">
            <i class="fas fa-save me-1"></i> Save
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Confirmation Modal -->
  <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header theme-header">
          <h5 class="modal-title fs-4" id="confirmDeleteModalLabel">Confirm Deletion</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Warning:</strong> This action cannot be undone. The poultry will be permanently deleted.
          </div>
          <p class="fs-5">Are you sure you want to delete "{{ poultryToDelete?.poultry_name }}"?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary easy-touch-lg" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i> Cancel
          </button>
          <button type="button" class="btn btn-danger easy-touch-lg" @click="deletePoultry">
            <i class="fas fa-trash-alt me-1"></i> Delete Permanently
          </button>
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
import Pagination from '../components/ui/Pagination.vue';
import api from '../utils/api';
import modal from '../utils/modal';
import { formatDate } from '../utils/formatter';

export default {
  name: 'PoultryManagement',
  components: {
    StatsCard,
    ResponsiveTable,
    LoadingSpinner,
    Pagination
  },
  setup() {
    const loading = ref(false);
    const error = ref(null);
    const poultries = ref([]);
    const searchQuery = ref('');
    
    // Modal state
    const modalTitle = ref('Add New Poultry');
    const modalPoultry = ref({
      poultryID: null,
      poultry_name: '',
      poultry_image: null
    });
    const poultryModal = ref(null);
    const confirmModal = ref(null);
    const poultryToDelete = ref(null);
    const selectedImageFile = ref(null);
    
    // Add pagination state
    const currentPage = ref(1);
    const perPage = ref(10); // Default to 10 items per page
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: 0,
      to: 0
    });
    
    // Poultry stats
    const poultryStats = ref({
      total: 0,
    });
    
    // Table columns
    const columns = [
      { key: 'poultry_image', label: 'Image', sortable: false },
      { key: 'poultry_name', label: 'Name', sortable: true },
      { key: 'created_at', label: 'Created', sortable: true }
    ];
    
    // Fetch poultries with pagination and search
    const fetchPoultries = async () => {
      try {
        loading.value = true;
        error.value = null;
        
        const params = {
          page: currentPage.value,
          per_page: perPage.value,
          search: searchQuery.value || null
        };
        
        const response = await api.get('/api/poultries', { params });
        
        if (response.data && response.success) {
          poultries.value = response.data;
          pagination.value = response.pagination;
        } else {
          poultries.value = [];
          console.error('Unexpected response format:', response);
        }
      } catch (err) {
        console.error('Error fetching poultries:', err);
        error.value = err.message || 'Failed to load poultries. Please try again later.';
        poultries.value = [];
      } finally {
        loading.value = false;
      }
    };
    
    // Fetch poultry stats separately
    const fetchPoultryStats = async () => {
      try {
        const params = {
          search: searchQuery.value || null
        };
        
        const statsData = await api.get('/api/poultries/all/stats', { params });
        
        if (statsData) {
          poultryStats.value = statsData;
        }
      } catch (err) {
        console.error('Error fetching poultry stats:', err);
      }
    };
    
    // Handle search from ResponsiveTable
    const handleSearch = (query) => {
      searchQuery.value = query;
      currentPage.value = 1; // Reset to first page when searching
      fetchPoultries();
      fetchPoultryStats(); // Also update stats when search changes
    };
    
    // Change page
    const changePage = (page) => {
      if (page < 1 || page > pagination.value.last_page || loading.value) return;
      
      // Update the current page immediately for UI feedback
      currentPage.value = page;
      
      // Then fetch the data for the new page
      fetchPoultries();
    };
    
    // Handle image change in modal
    const handleImageChange = (event) => {
      selectedImageFile.value = event.target.files[0] || null;
    };
    
    // Open add modal
    const openAddModal = () => {
      modalTitle.value = 'Add New Poultry';
      modalPoultry.value = {
        poultryID: null,
        poultry_name: '',
        poultry_image: null
      };
      selectedImageFile.value = null;
      
      // Reset file input
      setTimeout(() => {
        const fileInput = document.getElementById('poultry-image-file');
        if (fileInput) fileInput.value = '';
      }, 100);
      
      poultryModal.value.show();
    };
    
    // Edit poultry
    const editPoultry = (poultry) => {
      modalTitle.value = 'Edit Poultry';
      modalPoultry.value = { ...poultry };
      selectedImageFile.value = null;
      
      // Reset file input
      setTimeout(() => {
        const fileInput = document.getElementById('poultry-image-file');
        if (fileInput) fileInput.value = '';
      }, 100);
      
      poultryModal.value.show();
    };
    
    // Save poultry (add or edit)
    const savePoultry = async () => {
      const nameInput = document.getElementById('poultry-name');
      
      if (!modalPoultry.value.poultry_name.trim()) {
        nameInput.classList.add('is-invalid');
        return;
      }
      
      loading.value = true;
      // Clear existing data to show loading state
      poultries.value = [];
      
      try {
        // Create form data for file upload
        const formData = new FormData();
        formData.append('poultry_name', modalPoultry.value.poultry_name.trim());
        
        // If editing, add method override
        if (modalPoultry.value.poultryID) {
          formData.append('_method', 'PUT');
        }
        
        // If file is selected, add it to form data
        if (selectedImageFile.value) {
          formData.append('poultry_image_file', selectedImageFile.value);
        }
        
        const url = modalPoultry.value.poultryID 
          ? `/api/poultries/${modalPoultry.value.poultryID}` 
          : '/api/poultries';
        
        await api.post(url, formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          },
          onSuccess: () => {
            // Refresh the entire list
            fetchPoultries();
            
            // Show success message
            const action = modalPoultry.value.poultryID ? 'updated' : 'added';
            modal.success('Success', `Poultry ${action} successfully`);
          },
          onError: (err) => {
            console.error(`Error ${modalPoultry.value.poultryID ? 'updating' : 'adding'} poultry:`, err);
            error.value = `Failed to ${modalPoultry.value.poultryID ? 'update' : 'add'} poultry. Please try again.`;
            
            // Show error message
            modal.danger('Error', `Failed to ${modalPoultry.value.poultryID ? 'update' : 'add'} poultry. Please try again.`);
            
            // Reload the data even on error to ensure consistent state
            fetchPoultries();
          }
        });
        
        poultryModal.value.hide();
      } catch (err) {
        // Error is already handled by onError callback
        // Reload the data even on error to ensure consistent state
        fetchPoultries();
      }
    };
    
    // Confirm delete
    const confirmDelete = (poultry) => {
      poultryToDelete.value = poultry;
      confirmModal.value.show();
    };
    
    // Delete poultry
    const deletePoultry = async () => {
      if (!poultryToDelete.value) return;
      
      loading.value = true;
      // Clear existing data to show loading state
      poultries.value = [];
      
      try {
        await api.delete(`/api/poultries/${poultryToDelete.value.poultryID}`, {
          onSuccess: () => {
            // Refresh the entire list
            fetchPoultries();
            
            // Show success message
            modal.success('Success', 'Poultry deleted successfully');
          },
          onError: (err) => {
            console.error('Error deleting poultry:', err);
            
            // Check if this is a 422 error (Unprocessable Entity) with associated items
            if (err.response && err.response.status === 422 && err.response.data) {
              const errorData = err.response.data;
              error.value = errorData.message;
              
              // Show specific error message with item count if available
              const itemCount = errorData.item_count || 'multiple';
              const errorMessage = `${errorData.message}. This poultry has ${itemCount} associated item(s).`;
              
              modal.danger('Cannot Delete', errorMessage);
            } else {
              error.value = 'Failed to delete poultry. Please try again.';
              
              // Show generic error message
              modal.danger('Error', 'Failed to delete poultry. Please try again.');
            }
            
            // Reload the data even on error to ensure consistent state
            fetchPoultries();
          }
        });
        
        confirmModal.value.hide();
        poultryToDelete.value = null;
      } catch (err) {
        // Error is already handled by onError callback
        // Reload the data even on error to ensure consistent state
        fetchPoultries();
      }
    };
    
    // Initialize data and modals
    onMounted(() => {
      fetchPoultries();
      fetchPoultryStats();
      
      // Initialize modals
      poultryModal.value = new bootstrap.Modal(document.getElementById('poultryModal'));
      confirmModal.value = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    });
    
    return {
      loading,
      error,
      poultries,
      poultryStats,
      columns,
      searchQuery,
      currentPage,
      pagination,
      handleSearch,
      changePage,
      formatDate,
      editPoultry,
      confirmDelete,
      openAddModal,
      modalTitle,
      modalPoultry,
      handleImageChange,
      savePoultry,
      poultryToDelete,
      deletePoultry
    };
  }
};
</script>

<style scoped>
.poultry-management h1 {
  color: #123524;
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
  --selected-bg: #2A6B3A;
  --selected-text: #FFFFFF;
  --selected-border: #4CAF50;
  --selected-highlight: rgba(76, 175, 80, 0.3);
  
  border-color: var(--border-color);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
}

/* Button styles */
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

/* List styles */
.theme-list-item {
  border-color: var(--border-color);
  transition: all 0.3s ease;
}

/* Pagination */
.theme-pagination-container {
  background-color: var(--lighter-bg);
  border-top: 1px solid var(--border-color);
}

.pagination {
  margin-bottom: 0;
}

.page-link {
  color: var(--primary-color);
}

.page-item.active .page-link {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
}

/* Touch-friendly elements */
.easy-touch {
  min-height: 44px;
  min-width: 44px;
  padding: 0.5rem 1rem;
  font-size: 1rem;
}

.easy-touch-lg {
  min-height: 54px;
  min-width: 120px;
  padding: 0.75rem 1.5rem;
  font-size: 1.1rem;
}

/* Mobile optimizations */
@media (max-width: 767.98px) {
  .card-header {
    padding: 1rem;
  }
  
  .easy-touch, .easy-touch-lg {
    padding: 0.75rem;
  }
  
  /* Position important actions in the bottom half of the screen for thumb access
  .theme-pagination-container {
    position: sticky;
    bottom: 0;
    background-color: var(--light-bg);
    padding: 1rem;
    z-index: 10;
  } */
  
  /* Make the entire list item a touch target */
  .list-group-item {
    padding: 1rem;
  }
  
  /* Increase spacing between items */
  .list-group-item + .list-group-item {
    margin-top: 0.5rem;
  }
  
  /* Ensure text is readable on small screens */
  .fs-5 {
    font-size: 1.1rem !important;
  }
  
  .fs-6 {
    font-size: 1rem !important;
  }
}
</style>