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
    
    <!-- Poultries Table -->
    <div class="card">
      <div class="card-header theme-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Poultries</h5>
        <button class="btn btn-primary" @click="openAddModal">
          <i class="fas fa-plus"></i> <span class="d-none d-md-inline">Add Poultry</span>
        </button>
      </div>
      <div class="card-body position-relative">
        <!-- Error State -->
        <div v-if="error" class="alert alert-danger" role="alert">
          {{ error }}
        </div>
        
        <!-- Loading Spinner -->
        <LoadingSpinner v-if="loading" overlay size="md" message="Loading poultries..." />
        
        <!-- Table (always show, with loading state inside) -->
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
        
        <!-- Pagination - Updated to match MarketplacePage style -->
        <div v-if="!loading && poultries.length > 0" class="d-flex justify-content-between align-items-center mt-4">
          <div>
            <span class="text-muted">Showing {{ pagination.from || 1 }}-{{ pagination.to || poultries.length }} of {{ pagination.total || poultries.length }}</span>
          </div>
          <nav aria-label="Poultry pagination">
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
import api from '../utils/api';
import modal from '../utils/modal';

export default {
  name: 'PoultryManagement',
  components: {
    StatsCard,
    ResponsiveTable,
    LoadingSpinner
  },
  setup() {
    const loading = ref(false);
    const error = ref(null);
    const poultries = ref([]);
    const searchQuery = ref('');
    
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
        let startPage = Math.max(1, currentPage.value - Math.floor(maxVisiblePages / 2));
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
    
    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    };
    
    // Edit poultry
    const editPoultry = (poultry) => {
      modal.show({
        type: 'info',
        title: 'Edit Poultry',
        message: `
          <form id="edit-poultry-form">
            <div class="mb-3">
              <label for="edit-poultry-name" class="form-label">Poultry Name</label>
              <input type="text" class="form-control" id="edit-poultry-name" value="${poultry.poultry_name}" required>
            </div>
            <div class="mb-3">
              <label for="edit-poultry-image-file" class="form-label">Upload New Image</label>
              <input type="file" class="form-control" id="edit-poultry-image-file" accept="image/*">
            </div>
            ${poultry.poultry_image ? `
            <div class="mb-3">
              <label class="form-label">Current Image</label>
              <div>
                <img src="${poultry.poultry_image}" alt="Current Poultry Image" class="img-thumbnail" style="max-width: 200px;">
              </div>
            </div>
            ` : ''}
          </form>
        `,
        buttons: [
          {
            label: 'Cancel',
            type: 'secondary',
            dismiss: true
          },
          {
            label: 'Save',
            type: 'primary',
            onClick: async (_, modalInstance) => {
              const nameInput = document.getElementById('edit-poultry-name');
              const imageFileInput = document.getElementById('edit-poultry-image-file');
              
              if (!nameInput.value.trim()) {
                nameInput.classList.add('is-invalid');
                return;
              }
              
              loading.value = true;
              // Clear existing data to show loading state
              poultries.value = [];
              
              try {
                // Create form data for file upload
                const formData = new FormData();
                formData.append('poultry_name', nameInput.value.trim());
                formData.append('_method', 'PUT'); // For Laravel to recognize as PUT request
                
                // If file is selected, add it to form data
                if (imageFileInput.files[0]) {
                  formData.append('poultry_image_file', imageFileInput.files[0]);
                }
                
                await api.post(`/api/poultries/${poultry.poultryID}`, formData, {
                  headers: {
                    'Content-Type': 'multipart/form-data'
                  },
                  onSuccess: (updatedPoultry) => {
                    // Refresh the entire list instead of just updating local array
                    fetchPoultries();
                    
                    // Show success message
                    modal.success('Success', 'Poultry updated successfully');
                  },
                  onError: (err) => {
                    console.error('Error updating poultry:', err);
                    error.value = 'Failed to update poultry. Please try again.';
                    
                    // Show error message
                    modal.danger('Error', 'Failed to update poultry. Please try again.');
                    
                    // Reload the data even on error to ensure consistent state
                    fetchPoultries();
                  }
                });
                
                modalInstance.hide();
              } catch (err) {
                // Error is already handled by onError callback
                // Reload the data even on error to ensure consistent state
                fetchPoultries();
              }
            }
          }
        ]
      });
    };
    
    // Confirm delete
    const confirmDelete = (poultry) => {
      modal.confirm(
        'Delete Poultry',
        `Are you sure you want to delete "${poultry.poultry_name}"?`,
        async () => {
          loading.value = true;
          // Clear existing data to show loading state
          poultries.value = [];
          
          try {
            await api.delete(`/api/poultries/${poultry.poultryID}`, {
              onSuccess: () => {
                // Refresh the entire list instead of just removing from local array
                fetchPoultries();
                
                // Show success message
                modal.success('Success', 'Poultry deleted successfully');
              },
              onError: (err) => {
                console.error('Error deleting poultry:', err);
                error.value = 'Failed to delete poultry. Please try again.';
                
                // Show error message
                modal.danger('Error', 'Failed to delete poultry. Please try again.');
                
                // Reload the data even on error to ensure consistent state
                fetchPoultries();
              }
            });
          } catch (err) {
            // Error is already handled by onError callback
            // Reload the data even on error to ensure consistent state
            fetchPoultries();
          }
        },
        null,
        {
          confirmLabel: 'Delete',
          confirmType: 'danger'
        }
      );
    };
    
    // Open add modal
    const openAddModal = () => {
      modal.show({
        type: 'info',
        title: 'Add New Poultry',
        message: `
          <form id="add-poultry-form">
            <div class="mb-3">
              <label for="poultry-name" class="form-label">Poultry Name</label>
              <input type="text" class="form-control" id="poultry-name" required>
            </div>
            <div class="mb-3">
              <label for="poultry-image-file" class="form-label">Upload Image</label>
              <input type="file" class="form-control" id="poultry-image-file" accept="image/*">
            </div>
          </form>
        `,
        buttons: [
          {
            label: 'Cancel',
            type: 'secondary',
            dismiss: true
          },
          {
            label: 'Save',
            type: 'primary',
            onClick: async (_, modalInstance) => {
              const nameInput = document.getElementById('poultry-name');
              const imageFileInput = document.getElementById('poultry-image-file');
              
              if (!nameInput.value.trim()) {
                nameInput.classList.add('is-invalid');
                return;
              }
              
              loading.value = true;
              // Clear existing data to show loading state
              poultries.value = [];
              
              try {
                // Create form data for file upload
                const formData = new FormData();
                formData.append('poultry_name', nameInput.value.trim());
                
                // If file is selected, add it to form data
                if (imageFileInput.files[0]) {
                  formData.append('poultry_image_file', imageFileInput.files[0]);
                }
                
                await api.post('/api/poultries', formData, {
                  headers: {
                    'Content-Type': 'multipart/form-data'
                  },
                  onSuccess: (newPoultry) => {
                    // Refresh the entire list instead of just adding to local array
                    fetchPoultries();
                    
                    // Show success message
                    modal.success('Success', 'Poultry added successfully');
                  },
                  onError: (err) => {
                    console.error('Error adding poultry:', err);
                    error.value = 'Failed to add poultry. Please try again.';
                    
                    // Show error message
                    modal.danger('Error', 'Failed to add poultry. Please try again.');
                    
                    // Reload the data even on error to ensure consistent state
                    fetchPoultries();
                  }
                });
                
                modalInstance.hide();
              } catch (err) {
                // Error is already handled by onError callback
                // Reload the data even on error to ensure consistent state
                fetchPoultries();
              }
            }
          }
        ]
      });
    };
    
    // Initialize data
    onMounted(() => {
      fetchPoultries();
      fetchPoultryStats();
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
      paginationRange,
      handleSearch,
      changePage,
      formatDate,
      editPoultry,
      confirmDelete,
      openAddModal
    };
  }
};
</script>

<style scoped>
.theme-header {
  background-color: var(--primary-color);
  color: white;
  border-bottom: none;
}
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
</style>