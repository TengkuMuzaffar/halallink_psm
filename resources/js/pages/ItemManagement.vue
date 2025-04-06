<template>
  <div class="item-management">
    <h1 class="mb-4">Item Management</h1>
    
    <!-- Item Stats -->
    <div class="row mb-4">
      <div class="col-md-4 col-sm-6 mb-3">
        <StatsCard 
          title="Total Items" 
          :count="formatLargeNumber(itemStats.total_items)" 
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
        <!-- Error State -->
        <div v-if="error" class="alert alert-danger" role="alert">
          {{ error }}
        </div>
        
        <!-- Table (always show, with loading state inside) -->
        <ResponsiveTable
          :columns="columns"
          :items="items"
          :loading="loading"
          :has-actions="true"
          item-key="itemID"
          @search="handleSearch"
          :show-pagination="false"
          :server-side="true"
          @sort="handleSort"
        >
          <!-- Custom filters -->
          <template #filters>
            <div class="d-flex gap-2">
              <select class="form-select form-select-sm" v-model="poultryFilter" @change="applyFilters">
                <option value="">All Poultries</option>
                <option v-for="poultry in poultryTypes" :key="poultry.poultryID" :value="poultry.poultryID">
                  {{ poultry.poultry_name }}
                </option>
              </select>
              
              <select class="form-select form-select-sm" v-model="locationFilter" @change="applyFilters">
                <option value="">All Locations</option>
                <option v-for="location in locations" :key="location.locationID" :value="location.locationID">
                  {{ location.company_address }}
                </option>
              </select>
              
              <select class="form-select form-select-sm" v-model="measurementTypeFilter" @change="applyFilters">
                <option value="">All Types</option>
                <option value="kg">KG</option>
                <option value="unit">Unit</option>
              </select>
            </div>
          </template>
          
          <!-- Custom column slots -->
          <template #poultry="{ item }">
            <div class="d-flex align-items-center">
              <img 
                v-if="item.poultry_image" 
                :src="item.poultry_image" 
                alt="Poultry Image" 
                class="img-thumbnail me-2" 
                style="width: 40px; height: 40px; object-fit: cover;"
              >
              <div v-else class="bg-light d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                <i class="fas fa-feather text-muted"></i>
              </div>
              <span>{{ item.poultry_name }}</span>
            </div>
          </template>
          
          <template #measurement="{ item }">
            <span>{{ item.measurement_value }} {{ item.measurement_type === 'kg' ? 'KG' : 'Units' }}</span>
          </template>
          
          <template #price="{ item }">
            <span>{{ formatCurrency(item.price) }}</span>
          </template>
          
          <template #total="{ item }">
            <span>{{ formatCurrency(item.price * item.measurement_value) }}</span>
          </template>
          
          <!-- Actions slot -->
          <template #actions="{ item }">
            <button class="btn btn-sm btn-outline-primary me-1" @click="editItem(item)">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(item)">
              <i class="fas fa-trash"></i>
            </button>
          </template>
        </ResponsiveTable>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4">
          <div>
            <span class="text-muted">Showing {{ pagination.from || 1 }}-{{ pagination.to || items.length }} of {{ pagination.total || items.length }}</span>
          </div>
          <nav aria-label="Item pagination">
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
import formatter from '../utils/formatter'; // Import the formatter utility
import StatsCard from '../components/ui/StatsCard.vue';
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';
import * as bootstrap from 'bootstrap';

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
    const error = ref(null); // Add error ref
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
    const measurementTypeFilter = ref(''); // Add missing filter
    
    // Filter state
    const searchQuery = ref('');
    const poultryFilter = ref('');
    const measurementFilter = ref('');
    const locationFilter = ref('');
    const itemForm = reactive({
      itemID: null,
      poultryID: '',
      locationID: '',
      measurement_type: 'kg',
      measurement_value: 0,
      price: 0,
      item_image: null
    });
    
    // Sorting
    const sortField = ref('created_at');
    const sortDirection = ref('desc');
    
    // Pagination
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
    
    // Item stats - Initialize with default values to prevent undefined errors
    const itemStats = ref({
      total_items: 0,
      total_kg: 0,
      total_units: 0,
      total_value: 0
    });
    
    // Table columns
    const columns = [
      { key: 'poultry', label: 'Poultry', sortable: false },
      { key: 'location_name', label: 'Location', sortable: false },
      { key: 'measurement', label: 'Quantity', sortable: true, sortKey: 'measurement_value' },
      { key: 'price', label: 'Price', sortable: true },
      { key: 'stock', label: 'Stock', sortable: true }, // Add stock column
      // Removed created_at column
    ];
    
    // Fetch items with pagination, search, and filters
    const fetchItems = async () => {
      try {
        loading.value = true;
        error.value = null;
        
        const params = {
          page: currentPage.value,
          per_page: perPage.value,
          search: searchQuery.value || null,
          poultryID: poultryFilter.value || null,
          locationID: locationFilter.value || null,
          measurement_type: measurementTypeFilter.value || null,
          sort_field: sortField.value,
          sort_direction: sortDirection.value
        };
        
        const response = await api.get('/api/items', { params });
        
        if (response && response.data && response.success) {
          items.value = response.data;
          pagination.value = response.pagination;
        } else {
          items.value = [];
          console.error('Unexpected response format:', response);
        }
      } catch (err) {
        console.error('Error fetching items:', err);
        error.value = err.message || 'Failed to load items. Please try again later.';
        items.value = [];
      } finally {
        loading.value = false;
      }
    };
    
    // Fetch item stats separately
    const fetchItemStats = async () => {
      try {
        const params = {
          search: searchQuery.value || null,
          poultryID: poultryFilter.value || null,
          locationID: locationFilter.value || null,
          measurement_type: measurementTypeFilter.value || null
        };
        
        const statsData = await api.get('/api/items/stats', { params });
        
        if (statsData) {
          itemStats.value = statsData;
        }
      } catch (err) {
        console.error('Error fetching item stats:', err);
      }
    };
    
    // Fetch poultry types for filter dropdown
    const fetchPoultryTypes = async () => {
      try {
        const response = await api.get('/api/poultries');
        if (response && response.data) {
          poultryTypes.value = response.data;
        }
      } catch (err) {
        console.error('Error fetching poultry types:', err);
      }
    };
    
    // Fetch locations for filter dropdown
    const fetchLocations = async () => {
      try {
        const response = await api.get('/api/items/locations');
        if (response) {
          locations.value = response;
        }
      } catch (err) {
        console.error('Error fetching locations:', err);
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
      currentPage.value = 1; // Reset to first page when applying filters
      fetchItems();
      fetchItemStats(); // Also update stats when filters change
      // Short delay to ensure loading state is visible
      setTimeout(() => {
        items.value = filteredData;
        loading.value = false;
      }, 300);
    };
    
    // Handle search from ResponsiveTable
    const handleSearch = (query) => {
      searchQuery.value = query;
      currentPage.value = 1; // Reset to first page when searching
      fetchItems();
      fetchItemStats(); // Also update stats when search changes
    };
    
    // Handle sorting
    const handleSort = ({ field, direction }) => {
      // Map the display field to the actual database field if needed
      if (field === 'measurement') {
        sortField.value = 'measurement_value';
      } else if (field === 'price') {
        sortField.value = field;
      } else {
        sortField.value = 'price'; // Changed default from created_at to price
      }
      
      sortDirection.value = direction;
      fetchItems();
    };
    
    // Change page
    const changePage = (page) => {
      if (page < 1 || page > pagination.value.last_page || loading.value) return;
      
      // Update the current page immediately for UI feedback
      currentPage.value = page;
      
      // Then fetch the data for the new page
      fetchItems();
    };
    
    // Format currency
    const formatCurrency = (value, shorten = false) => {
      if (typeof value !== 'number') {
        // Try to convert to number if it's not already
        value = Number(value);
        if (isNaN(value)) return 'RM 0';
      }
      
      // If shorten is true, format large numbers with suffixes
      if (shorten) {
        let formattedValue;
        if (value >= 1000000000) {
          formattedValue = (value / 1000000000).toFixed(1).replace(/\.0$/, '') + 'B';
        } else if (value >= 1000000) {
          formattedValue = (value / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        } else if (value >= 1000) {
          formattedValue = (value / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        } else {
          formattedValue = value.toFixed(2);
        }
        return 'RM ' + formattedValue;
      }
      
      // Otherwise use standard formatting with MYR currency
      return new Intl.NumberFormat('en-MY', {
        style: 'currency',
        currency: 'MYR',
        currencyDisplay: 'narrowSymbol'
      }).format(value);
    };
    
    // Format date - simplified to use formatter utility
    const formatDate = (dateString) => {
      return formatter.formatDate(dateString, 'medium');
    };
    
    // Handle image change
    const handleImageChange = (event) => {
      const file = event.target.files[0];
      if (!file) return;
      
      // Create preview
      const reader = new FileReader();
      reader.onload = (e) => {
        imagePreview.value = e.target.result;
      };
      reader.readAsDataURL(file);
    };
    
    // Edit item
    const editItem = (item) => {
      isEditing.value = true;
      selectedItem.value = item;
      
      // Reset form with item data
      itemForm.itemID = item.itemID;
      itemForm.poultryID = item.poultryID;
      itemForm.locationID = item.locationID;
      itemForm.measurement_type = item.measurement_type;
      itemForm.measurement_value = item.measurement_value;
      itemForm.price = item.price;
      itemForm.stock = item.stock; // Add stock field
      itemForm.item_image = item.item_image;
      
      // Reset image preview
      imagePreview.value = null;
      
      // Open modal - Use try/catch to handle potential bootstrap errors
      try {
        const modal = new bootstrap.Modal(document.getElementById('itemModal'));
        modal.show();
      } catch (err) {
        console.error('Error showing modal:', err);
      }
    };
    
    // Confirm delete
    const confirmDelete = (item) => {
      modal.confirm(
        'Delete Item',
        `Are you sure you want to delete this item (${item.poultry_name})?`,
        async () => {
          try {
            loading.value = true;
            await api.del(`/api/items/${item.itemID}`);
            
            // Refresh data after successful deletion
            fetchItems();
            fetchItemStats();
            
            modal.toast('Success', 'Item deleted successfully', 'success');
          } catch (err) {
            console.error('Error deleting item:', err);
            modal.toast('Error', 'Failed to delete item', 'danger');
          } finally {
            loading.value = false;
          }
        }
      );
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
        stock: '', // Add stock field
        item_image: null
      });
      
      // Reset image preview
      imagePreview.value = null;
      
      // Set editing mode
      isEditing.value = false;
      
      // Open modal - Use try/catch to handle potential bootstrap errors
      try {
        const modal = new bootstrap.Modal(document.getElementById('itemModal'));
        modal.show();
      } catch (err) {
        console.error('Error showing modal:', err);
      }
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
        
        // Close modal - Use try/catch to handle potential bootstrap errors
        try {
          const modal = bootstrap.Modal.getInstance(document.getElementById('itemModal'));
          if (modal) {
            modal.hide();
          }
        } catch (err) {
          console.error('Error hiding modal:', err);
        }
        
        // Show success message after a short delay to ensure modal is closed
        setTimeout(() => {
          modal.success(
            isEditing.value ? 'Item Updated' : 'Item Added',
            isEditing.value ? 'Item has been updated successfully.' : 'New item has been added successfully.'
          );
          
          // Refresh items after successful save
          fetchItems();
          fetchItemStats();
        }, 300);
        
        formLoading.value = false;
      } catch (err) {
        console.error('Error saving item:', err);
        formLoading.value = false;
        loading.value = false; // Make sure to reset loading state on error
        
        // Show error message
        modal.danger('Error', 'Failed to save item. Please try again.');
        
        // Restore previous state without making additional API calls
        items.value = [...allItems.value];
        applyFilters();
      }
    };
    
    // View item
    const viewItem = (item) => {
      selectedItem.value = item;
      
      // Open modal - Use try/catch to handle potential bootstrap errors
      try {
        const modal = new bootstrap.Modal(document.getElementById('viewItemModal'));
        modal.show();
      } catch (err) {
        console.error('Error showing modal:', err);
      }
    };
    const formatLargeNumber = (value) => {
        if (typeof value !== 'number') {
          // Try to convert to number if it's not already
          value = Number(value);
          if (isNaN(value)) return '0';
        }
        
        // Format with appropriate suffix
        if (value >= 1000000000) {
          return (value / 1000000000).toFixed(1).replace(/\.0$/, '') + 'B';
        }
        if (value >= 1000000) {
          return (value / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        }
        if (value >= 1000) {
          return (value / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        }
        
        // For smaller numbers, just return as is with no decimal places
        return value.toFixed(0);
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
      
      // Fetch data
      await Promise.all([
        fetchPoultryTypes(),
        fetchLocations()
      ]);
      
      await fetchItems();
      await fetchItemStats();
    });
    
    return {
      loading,
      error,
      items,
      poultryTypes,
      locations,
      itemStats,
      columns,
      poultryFilter,
      locationFilter,
      measurementTypeFilter,
      currentPage,
      pagination,
      paginationRange,
      // Form state
      isEditing,
      formLoading,
      imagePreview,
      selectedItem,
      itemForm,
      // Functions
      applyFilters,
      handleSearch,
      handleSort,
      changePage,
      // Use formatter utility functions
      formatCurrency: (value, shorten = false) => formatter.formatCurrency(value, 'MYR', 'en-MY', shorten),
      formatDate,
      formatLargeNumber: (value) => formatter.formatLargeNumber(value),
      editItem,
      confirmDelete,
      openAddModal,
      handleImageChange,
      saveItem,
      viewItem,
      formatPrice: (price) => Number(price).toFixed(2)
    };
  }
};
</script>

<style scoped>
/* Pagination styling to match MarketplacePage */
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

.badge {
  font-size: 0.8rem;
  padding: 0.35em 0.65em;
}

@media (max-width: 768px) {
  .company-management h1 {
    font-size: 1.75rem;
  }
}
</style>