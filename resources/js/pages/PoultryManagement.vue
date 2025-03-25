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
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Poultries</h5>
        <button class="btn btn-primary" @click="openAddModal">
          <i class="fas fa-plus me-1"></i> Add Poultry
        </button>
      </div>
      <div class="card-body">
        <ResponsiveTable
          :columns="columns"
          :items="poultries"
          :loading="loading"
          :has-actions="true"
          item-key="poultryID"
        >
          <!-- Custom filters -->
          <template #filters>
            <div class="d-flex gap-2">
              <select class="form-select form-select-sm" v-model="nameFilter" @change="applyFilters">
                <option value="">All Poultries</option>
                <option v-for="poultry in uniquePoultryNames" :key="poultry" :value="poultry">
                  {{ poultry }}
                </option>
              </select>
            </div>
          </template>
          
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
            <button class="btn btn-sm btn-outline-danger" @click="deletePoultry(item)">
              <i class="fas fa-trash"></i>
            </button>
          </template>
          
          <!-- Empty state slot -->
          <template #empty>
            <div class="text-center py-4">
              <i class="fas fa-feather text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted">No poultries found</p>
            </div>
          </template>
        </ResponsiveTable>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue';
import StatsCard from '../components/ui/StatsCard.vue';
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';
import api from '../utils/api';
import modal from '../utils/modal';

export default {
  name: 'PoultryManagement',
  components: {
    StatsCard,
    ResponsiveTable
  },
  setup() {
    const loading = ref(true);
    const error = ref(null);
    const poultries = ref([]);
    const nameFilter = ref('');
    
    const poultryStats = reactive({
      total: 0
    });
    
    const columns = [
      { key: 'poultry_image', label: 'Image', sortable: false },
      { key: 'poultry_name', label: 'Name', sortable: true },
      { key: 'created_at', label: 'Created', sortable: true }
    ];
    
    // Computed property for unique poultry names for filter
    const uniquePoultryNames = computed(() => {
      const names = poultries.value.map(p => p.poultry_name);
      return [...new Set(names)];
    });
    
    // Fetch poultries
    const fetchPoultries = async () => {
      loading.value = true;
      error.value = null;
      
      // Clear existing data before loading new data
      poultries.value = [];
      
      try {
        const response = await api.get('/api/poultries', {
          onError: (err) => {
            console.error('Error fetching poultries:', err);
            error.value = 'Failed to load poultries. Please try again.';
            
            // Show error message with modal
            modal.danger(
              'Error Loading Poultries',
              'Failed to load poultry data. Please try again.',
              {
                buttons: [
                  {
                    label: 'Retry',
                    type: 'primary',
                    onClick: () => {
                      fetchPoultries();
                    }
                  },
                  {
                    label: 'Dismiss',
                    type: 'secondary',
                    dismiss: true
                  }
                ]
              }
            );
          }
        });
        
        poultries.value = response;
        
        // Update stats
        poultryStats.total = poultries.value.length;
      } catch (err) {
        // Error is already handled by onError callback
      } finally {
        loading.value = false;
      }
    };
    
    // Apply filters
    const applyFilters = async () => {
      loading.value = true;
      
      try {
        // Build query parameters
        const params = {};
        if (nameFilter.value) params.poultry_name = nameFilter.value;
        
        const response = await api.get('/api/poultries', {
          params,
          onError: (err) => {
            console.error('Error applying filters:', err);
            error.value = 'Failed to filter poultries. Please try again.';
          }
        });
        
        poultries.value = response;
        
        // Update stats based on filtered results
        poultryStats.total = poultries.value.length;
      } catch (err) {
        // Error is already handled by onError callback
      } finally {
        loading.value = false;
      }
    };
    
    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    };
    
    // Modal actions
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
    
    const deletePoultry = (poultry) => {
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
    
    onMounted(() => {
      fetchPoultries();
    });
    
    return {
      loading,
      error,
      poultries,
      poultryStats,
      columns,
      nameFilter,
      uniquePoultryNames,
      formatDate,
      openAddModal,
      editPoultry,
      deletePoultry,
      applyFilters
    };
  }
}
</script>

<style scoped>
.poultry-management h1 {
  color: #123524;
}

@media (max-width: 768px) {
  .poultry-management h1 {
    font-size: 1.75rem;
  }
}
</style>