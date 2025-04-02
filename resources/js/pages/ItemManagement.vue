<template>
  <div class="item-management">
    <h1 class="mb-4">Item Management</h1>
    
    <!-- Item Stats -->
    <div class="row mb-4">
      <div class="col-md-4 col-sm-6 mb-3">
        <StatsCard 
          title="Total Items" 
          :count="itemStats.total_items" 
          icon="fas fa-boxes" 
          bg-color="bg-primary"
        />
      </div>
    </div>
    
    <!-- Items Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Items</h5>
        <button class="btn btn-primary" @click="openAddModal">
          <i class="fas fa-plus me-1"></i> Add Item
        </button>
      </div>
      <div class="card-body">
        <ResponsiveTable
          :columns="columns"
          :items="items"
          :loading="loading"
          :has-actions="true"
          item-key="itemID"
          @search="handleSearch"
        >
          <!-- Custom filters -->
          <template #filters>
            <div class="d-flex gap-2">
              <select class="form-select form-select-sm" v-model="poultryFilter" @change="applyFilters">
                <option value="">All Poultry Types</option>
                <option v-for="poultry in poultryTypes" :key="poultry.poultryID" :value="poultry.poultryID">
                  {{ poultry.poultry_name }}
                </option>
              </select>
              
              <select class="form-select form-select-sm" v-model="measurementFilter" @change="applyFilters">
                <option value="">All Measurements</option>
                <option value="kg">KG</option>
                <option value="unit">Unit</option>
              </select>
              
              <select class="form-select form-select-sm" v-model="locationFilter" @change="applyFilters">
                <option value="">All Locations</option>
                <option v-for="location in locations" :key="location.locationID" :value="location.locationID">
                  {{ location.company_address }}
                </option>
              </select>
            </div>
          </template>
          
          <!-- Custom column slots -->
          <template #poultry_name="{ item }">
            <div class="d-flex align-items-center">
              <div class="item-image me-2" v-if="item.poultry_image">
                <img :src="item.poultry_image" alt="Poultry" class="rounded" width="40" height="40">
              </div>
              <span>{{ item.poultry_name }}</span>
            </div>
          </template>
          
          <template #price="{ item }">
            <span>RM {{ formatPrice(item.price) }}</span>
          </template>
          
          <template #measurement_value="{ item }">
            <span>{{ item.measurement_value }} {{ item.measurement_type === 'kg' ? 'KG' : 'Units' }}</span>
          </template>
          
          <template #total_value="{ item }">
            <span>RM {{ formatPrice(item.price * item.measurement_value) }}</span>
          </template>
          
          <!-- Actions column -->
          <template #actions="{ item }">
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-info" @click="viewItem(item)">
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn btn-sm btn-primary" @click="editItem(item)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-danger" @click="deleteItem(item)">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </template>
          
          <!-- Empty state -->
          <template #empty>
            <div class="text-center py-4">
              <template v-if="loading">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading items...</p>
              </template>
              <template v-else>
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <p class="mb-0">No items found. Add your first item to get started.</p>
              </template>
            </div>
          </template>
        </ResponsiveTable>
      </div>
    </div>
    
    <!-- Add/Edit Item Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ isEditing ? 'Edit Item' : 'Add New Item' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveItem">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Poultry Type</label>
                  <select class="form-select" v-model="itemForm.poultryID" required>
                    <option value="">Select Poultry Type</option>
                    <option v-for="poultry in poultryTypes" :key="poultry.poultryID" :value="poultry.poultryID">
                      {{ poultry.poultry_name }}
                    </option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Location</label>
                  <select class="form-select" v-model="itemForm.locationID" required>
                    <option value="">Select Location</option>
                    <option v-for="location in locations" :key="location.locationID" :value="location.locationID">
                      {{ location.company_address }}
                    </option>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-4">
                  <label class="form-label">Measurement Type</label>
                  <select class="form-select" v-model="itemForm.measurement_type" required>
                    <option value="kg">KG</option>
                    <option value="unit">Unit</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Measurement Value</label>
                  <input type="number" class="form-control" v-model="itemForm.measurement_value" min="0" step="0.01" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Price (RM)</label>
                  <input type="number" class="form-control" v-model="itemForm.price" min="0" step="0.01" required>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Item Image</label>
                <input type="file" class="form-control" @change="handleImageChange" accept="image/*">
                <div class="form-text">Optional. Max size: 2MB. Formats: JPG, PNG, GIF</div>
              </div>
              
              <div class="mb-3" v-if="itemForm.item_image || imagePreview">
                <label class="form-label">Image Preview</label>
                <div class="image-preview">
                  <img :src="imagePreview || itemForm.item_image" alt="Item Preview" class="img-thumbnail" style="max-height: 200px;">
                </div>
              </div>
              
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="formLoading">
                  <span v-if="formLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  {{ isEditing ? 'Update Item' : 'Add Item' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <!-- View Item Modal -->
    <div class="modal fade" id="viewItemModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Item Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" v-if="selectedItem">
            <div class="text-center mb-4" v-if="selectedItem.item_image">
              <img :src="selectedItem.item_image" alt="Item" class="img-fluid rounded" style="max-height: 200px;">
            </div>
            
            <div class="row mb-3">
              <div class="col-md-6">
                <div class="d-flex align-items-center mb-3">
                  <div class="me-2" v-if="selectedItem.poultry_image">
                    <img :src="selectedItem.poultry_image" alt="Poultry" class="rounded" width="40" height="40">
                  </div>
                  <div>
                    <div class="text-muted small">Poultry Type</div>
                    <div class="fw-bold">{{ selectedItem.poultry_name }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <div class="text-muted small">Location</div>
                  <div class="fw-bold">{{ selectedItem.location_name }}</div>
                </div>
              </div>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-6">
                <div class="mb-3">
                  <div class="text-muted small">Measurement</div>
                  <div class="fw-bold">{{ selectedItem.measurement_value }} {{ selectedItem.measurement_type === 'kg' ? 'KG' : 'Units' }}</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <div class="text-muted small">Price</div>
                  <div class="fw-bold">RM {{ formatPrice(selectedItem.price) }}</div>
                </div>
              </div>
            </div>
            
            <div class="mb-3">
              <div class="text-muted small">Total Value</div>
              <div class="fw-bold">RM {{ formatPrice(selectedItem.price * selectedItem.measurement_value) }}</div>
            </div>
            
            <div class="mb-3">
              <div class="text-muted small">Added On</div>
              <div class="fw-bold">{{ formatDate(selectedItem.created_at) }}</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" @click="editItem(selectedItem)" data-bs-dismiss="modal">Edit</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useStore } from 'vuex';
import api from '../utils/api';
import modal from '../utils/modal';
import StatsCard from '../components/ui/StatsCard.vue';
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';

export default {
  name: 'ItemManagement',
  components: {
    StatsCard,
    ResponsiveTable
  },
  setup() {
    const router = useRouter();
    const store = useStore();
    
    // State variables
    const loading = ref(true);
    const formLoading = ref(false);
    const items = ref([]);
    const allItems = ref([]);
    const poultryTypes = ref([]);
    const locations = ref([]);
    const selectedItem = ref(null);
    const isEditing = ref(false);
    const imagePreview = ref(null);
    const itemModal = ref(null);
    const viewItemModal = ref(null);
    
    // Filter state
    const searchQuery = ref('');
    const poultryFilter = ref('');
    const measurementFilter = ref('');
    const locationFilter = ref('');
    
    // Item stats
    const itemStats = ref({
      total_items: 0,
      total_kg: 0,
      total_units: 0,
      total_value: 0
    });
    
    // Form state
    const itemForm = reactive({
      itemID: null,
      poultryID: '',
      locationID: '',
      measurement_type: 'kg',
      measurement_value: '',
      price: '',
      item_image: null
    });
    
    // Table columns
    const columns = [
      { key: 'poultry_name', label: 'Poultry Type', sortable: true },
      { key: 'price', label: 'Price (RM)', sortable: true },
      { key: 'measurement_value', label: 'Quantity', sortable: true }
    ];
    
    // Fetch items
    const fetchItems = async () => {
      try {
        loading.value = true;
        items.value = []; // Clear the current list while loading
        
        const response = await api.get('/api/items');
        allItems.value = response;
        applyFilters(); // Apply filters to the fetched data
        
        // Update stats
        await fetchItemStats();
        
        loading.value = false;
      } catch (error) {
        console.error('Error fetching items:', error);
        loading.value = false;
        modal.danger('Error', 'Failed to load items');
      }
    };
    
    // Apply filters
    const applyFilters = () => {
      // Set loading to true and clear items when applying filters
      loading.value = true;
      items.value = []; // Clear the current list while loading
      
      let filteredData = [...allItems.value];
      
      // Apply poultry filter
      if (poultryFilter.value) {
        filteredData = filteredData.filter(item => item.poultryID === poultryFilter.value);
      }
      
      // Apply measurement filter
      if (measurementFilter.value) {
        filteredData = filteredData.filter(item => item.measurement_type === measurementFilter.value);
      }
      
      // Apply location filter
      if (locationFilter.value) {
        filteredData = filteredData.filter(item => item.locationID === locationFilter.value);
      }
      
      // Apply search query if it exists
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filteredData = filteredData.filter(item => 
          item.poultry_name?.toLowerCase().includes(query) || 
          item.location_name?.toLowerCase().includes(query)
        );
      }
      
      // Short delay to ensure loading state is visible
      setTimeout(() => {
        items.value = filteredData;
        loading.value = false;
      }, 300);
    };
    
    // Handle search
    const handleSearch = (query) => {
      searchQuery.value = query;
      // Clear items and show loading
      loading.value = true;
      items.value = [];
      applyFilters();
    };
    
    // Save item (add/edit)
    const saveItem = async () => {
      try {
        formLoading.value = true;
        
        // Clear items to show loading state in the table
        items.value = [];
        loading.value = true;
        
        // Create form data for file upload
        const formData = new FormData();
        
        // Add all form fields to formData
        for (const key in itemForm) {
          if (key !== 'item_image' && itemForm[key] !== null) {
            formData.append(key, itemForm[key]);
          }
        }
        
        // Add image if it exists
        if (itemForm.item_image instanceof File) {
          formData.append('item_image', itemForm.item_image);
        }
        
        let response;
        if (isEditing.value) {
          response = await api.post(`/api/items/${itemForm.itemID}`, formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          });
        } else {
          response = await api.post('/api/items', formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          });
        }
        
        // Close modal - Use the Bootstrap modal instance directly
        if (itemModal.value) {
          itemModal.value.hide();
        }
        
        // Reset form before showing success message
        // resetForm();
        
        // Show success message after a short delay to ensure modal is closed
        setTimeout(() => {
          modal.success(
            isEditing.value ? 'Item Updated' : 'Item Added',
            isEditing.value ? 'Item has been updated successfully.' : 'New item has been added successfully.'
          );
          
          // Refresh items after successful save
          fetchItems();
        }, 300);
        
        formLoading.value = false;
      } catch (error) {
        console.error('Error saving item:', error);
        formLoading.value = false;
        loading.value = false; // Make sure to reset loading state on error
        
        // Show error message
        modal.danger('Error', 'Failed to save item. Please try again.');
        
        // Restore previous state without making additional API calls
        items.value = [...allItems.value];
        applyFilters();
      }
    };
    
    // Delete item
    const deleteItem = async (item) => {
      modal.confirm(
        'Delete Item',
        `Are you sure you want to delete this item?`,
        async () => {
          try {
            loading.value = true;
            items.value = []; // Clear items to show loading state
            
            await api.delete(`/api/items/${item.itemID}`);
            
            // Show success message
            modal.success('Item Deleted', 'Item has been deleted successfully.');
            
            // Refresh items
            await fetchItems();
          } catch (error) {
            console.error('Error deleting item:', error);
            modal.danger('Error', 'Failed to delete item. Please try again.');
            
            // Refresh items to ensure consistent state
            await fetchItems();
          }
        }
      );
    };
    
    // Fetch item stats
    const fetchItemStats = async () => {
      try {
        const response = await api.get('/api/items/stats');
        itemStats.value = response;
      } catch (error) {
        console.error('Error fetching item stats:', error);
        modal.danger('Error', 'Failed to load item statistics');
      }
    };
    
    // Fetch poultry types
    const fetchPoultryTypes = async () => {
      try {
        // Changed from '/api/items/poultry-types' to '/api/poultries'
        const response = await api.get('/api/poultries');
        poultryTypes.value = response;
      } catch (error) {
        console.error('Error fetching poultry types:', error);
        modal.danger('Error', 'Failed to load poultry types');
      }
    };
    
    // Fetch company locations
    const fetchLocations = async () => {
      try {
        const response = await api.get('/api/items/locations');
        locations.value = response;
      } catch (error) {
        console.error('Error fetching locations:', error);
        modal.danger('Error', 'Failed to load company locations');
      }
    };
    
    /* REMOVE THIS DUPLICATE FUNCTION
    // Handle search
    const handleSearch = (query) => {
      searchQuery.value = query;
      applyFilters();
    };
    */
    
    // Format price
    const formatPrice = (price) => {
      return parseFloat(price).toFixed(2);
    };
    
    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }).format(date);
    };
    
    // Open add modal
    const openAddModal = () => {
      // Reset form
      Object.assign(itemForm, {
        itemID: null,
        poultryID: '',
        locationID: '',
        measurement_type: 'kg',
        measurement_value: '',
        price: '',
        item_image: null
      });
      
      // Reset image preview
      imagePreview.value = null;
      
      // Set editing mode
      isEditing.value = false;
      
      // Open modal
      if (!itemModal.value) {
        itemModal.value = new bootstrap.Modal(document.getElementById('itemModal'));
      }
      itemModal.value.show();
    };
    
    // View item
    const viewItem = (item) => {
      selectedItem.value = item;
      
      // Open modal
      if (!viewItemModal.value) {
        viewItemModal.value = new bootstrap.Modal(document.getElementById('viewItemModal'));
      }
      viewItemModal.value.show();
    };
    
    // Edit item
    const editItem = (item) => {
      // Set form values
      Object.assign(itemForm, {
        itemID: item.itemID,
        poultryID: item.poultryID,
        locationID: item.locationID,
        measurement_type: item.measurement_type,
        measurement_value: item.measurement_value,
        price: item.price,
        item_image: item.item_image
      });
      
      // Reset image preview
      imagePreview.value = null;
      
      // Set editing mode
      isEditing.value = true;
      
      // Open modal
      if (!itemModal.value) {
        itemModal.value = new bootstrap.Modal(document.getElementById('itemModal'));
      }
      itemModal.value.show();
    };
    
    /* 
    // This is a duplicate deleteItem function - commenting out to fix the error
    const deleteItem = (item) => {
      modal.confirm('Delete Item', `Are you sure you want to delete this item?`, async () => {
        try {
          loading.value = true;
          items.value = []; // Clear the table while loading
          
          await api.delete(`/api/items/${item.itemID}`);
          
          // Refresh the item list
          await fetchItems();
          
          modal.success('Success', 'Item deleted successfully');
        } catch (error) {
          console.error('Error deleting item:', error);
          loading.value = false;
          modal.danger('Error', 'Failed to delete item');
        }
      });
    };
    */
    
    // Handle image change
    const handleImageChange = (event) => {
      const file = event.target.files[0];
      if (!file) return;
      
      // Check if file is an image
      if (!file.type.match('image.*')) {
        modal.danger('Invalid File', 'Please select an image file');
        return;
      }
      
      // Check file size (max 2MB)
      if (file.size > 2 * 1024 * 1024) {
        modal.danger('File Too Large', 'Image size should not exceed 2MB');
        return;
      }
      
      // Store the file
      itemForm.item_image = file;
      
      // Preview the image
      const reader = new FileReader();
      reader.onload = (e) => {
        imagePreview.value = e.target.result;
      };
      reader.readAsDataURL(file);
    };
    
    // Initialize component
    onMounted(async () => {
      // Check if user is from a broiler company
      const user = store.getters.user;
      if (!user || !user.company || user.company.company_type !== 'broiler') {
        modal.warning('Access Restricted', 'This page is only available for broiler companies');
        router.push('/dashboard');
        return;
      }
      
      // Initialize modals
      itemModal.value = new bootstrap.Modal(document.getElementById('itemModal'));
      viewItemModal.value = new bootstrap.Modal(document.getElementById('viewItemModal'));
      
      // Fetch data
      await Promise.all([
        fetchPoultryTypes(),
        fetchLocations()
      ]);
      
      await fetchItems();
    });
    
    return {
      loading,
      formLoading,
      items,
      poultryTypes,
      locations,
      columns,
      itemStats,
      itemForm,
      selectedItem,
      isEditing,
      imagePreview,
      poultryFilter,
      measurementFilter,
      locationFilter,
      fetchItems,
      applyFilters,
      handleSearch,
      formatPrice,
      formatDate,
      openAddModal,
      viewItem,
      editItem,
      deleteItem,
      handleImageChange,
      saveItem
    };
  }
};
</script>

<style scoped>
.item-management h1 {
  color: #123524;
}

.item-image img {
  object-fit: cover;
}

.modal-centered-content {
  text-align: center;
}

@media (max-width: 768px) {
  .item-management h1 {
    font-size: 1.75rem;
  }
  
  .d-flex.gap-2 {
    flex-wrap: wrap;
  }
}
</style>