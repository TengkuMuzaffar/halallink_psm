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
      <div class="card-header theme-header d-flex justify-content-between align-items-center">
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
                <option value="">All Company Locations</option>
                <option v-for="location in companyLocations" :key="location.locationID" :value="location.locationID">
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

          <!-- Location column template -->
          <template #location="{ item }">
            <div>
              <div>{{ item.location ? item.location.company_address : '-' }}</div>
              <small class="text-muted" v-if="item.slaughterhouse">
                Slaughterhouse: {{ item.slaughterhouse.company_address }}
              </small>
            </div>
          </template>
          
          <!-- Custom column slots -->
          <template #poultry="{ item }">
            <div>
              <div class="d-flex align-items-center">
                <img 
                  v-if="item.item_image" 
                  :src="item.item_image" 
                  alt="Item Image" 
                  class="img-thumbnail me-2" 
                  style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;"
                  @click="previewImage = item.item_image"
                >
                <div v-else class="bg-light d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                  <i class="fas fa-feather text-muted"></i>
                </div>
                <span>{{ item.poultry_name }}</span>
              </div>
              
              <!-- Mobile-only information (shown on small screens) -->
              <div class="d-md-none mt-2">
                <div class="small text-muted mb-1">
                  <strong>Price:</strong> {{ formatCurrency(item.price) }}
                </div>
                <div class="small text-muted mb-1">
                  <strong>Weight:</strong> {{ item.measurement_value }} {{ item.measurement_type === 'kg' ? 'KG' : 'Units' }}
                </div>
                <div class="small text-muted">
                  <strong>Stock:</strong> {{ item.stock }}
                </div>
              </div>
            </div>
          </template>
          
          <template #measurement="{ item }">
            <span>{{ item.measurement_value }} {{ item.measurement_type === 'kg' ? 'KG' : 'Units' }}</span>
          </template>
          
          <template #price="{ item }">
            <span>{{ formatCurrency(item.price) }}</span>
          </template>
          
          <template #stock="{ item }">
            <span>{{ item.stock }}</span>
          </template>

          // Make sure you have the actions template in your ResponsiveTable
          <template #actions="{ item }">
            <div class="btn-group">
              <button class="btn btn-sm btn-outline-primary" @click="editItem(item)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(item.itemID)">
                <i class="fas fa-trash"></i>
              </button>
            </div>
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
    
    <!-- Item Form Modal -->
    <CustomModal
      :modal-id="'itemModal'"
      :is-editing="isEditing"
      :loading="formLoading"
      :poultry-types="poultryTypes"
      :company-locations="companyLocations"
      :slaughterhouse-locations="slaughterhouseLocations"
      :initial-data="itemForm"
      @submit="saveItem"
      @image-change="handleImageChange"
    />

    <!-- Image Preview Modal -->
    <div 
      v-if="previewImage" 
      class="modal fade show d-block" 
      style="background-color: rgba(0,0,0,0.5);"
      @click.self="previewImage = ''"
    >
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
          <div class="text-end p-2">
            <button 
              type="button" 
              class="btn-close bg-white" 
              @click="previewImage = ''"
              aria-label="Close"
            ></button>
          </div>
          <img 
            :src="previewImage" 
            class="img-fluid" 
            alt="Full size preview"
            style="max-height: 90vh; object-fit: contain;"
          >
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useStore } from 'vuex';
import modal from '../utils/modal';
import formatter from '../utils/formatter'; // Import the formatter utility
import { itemService } from '../services/itemService'; // Add this import
import StatsCard from '../components/ui/StatsCard.vue';
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';
import CustomModal from '../components/item/custom_modal.vue';
export default {
  name: 'ItemManagement',
  components: {
    StatsCard,
    ResponsiveTable,
    CustomModal
  },
  setup() {
    const router = useRouter();
    const store = useStore();
    
    // Add this new ref
    const previewImage = ref('');
    
    // State variables
    const loading = ref(true);
    const error = ref(null); // Add error ref
    const formLoading = ref(false);
    const items = ref([]);
    const poultryTypes = ref([]);
    const locations = ref([]);
    const selectedItem = ref(null);
    const isEditing = ref(false);
    const imagePreview = ref(null);
    const measurementTypeFilter = ref(''); // Add missing filter
    
    // Filter state
    const searchQuery = ref('');
    const poultryFilter = ref('');
    const locationFilter = ref('');
    const slaughterhouseLocations = ref([]);
    const companyLocations = ref([]);
    // Update itemForm initialization
    const itemForm = reactive({
      itemID: null,
      poultryID: '',
      locationID: '',
      slaughterhouse_locationID: '', // Add this line
      measurement_type: 'kg',
      measurement_value: '',
      price: '',
      stock: '',
      item_image: null
    });
    const closeButton = ref(null);
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
    
    // Move itemStats initialization to the top with other state variables
    const itemStats = ref({
      total_items: 0,
      total_kg: 0,
      total_units: 0,
      total_value: 0
    });

   
 

    // Update fetchItems params
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
        
        const { items: fetchedItems, pagination: paginationData } = await itemService.fetchItems(params);
        items.value = fetchedItems;
        pagination.value = paginationData || itemService.getDefaultPagination();
      } catch (err) {
        error.value = err.message || 'Failed to load items. Please try again later.';
        items.value = [];
      } finally {
        loading.value = false;
      }
    };

    // Update fetchItemStats to use service
    const fetchItemStats = async () => {
      try {
        const params = {
          search: searchQuery.value || null,
          poultryID: poultryFilter.value || null,
          locationID: locationFilter.value || null,
          measurement_type: measurementTypeFilter.value || null
        };
        
        const stats = await itemService.fetchItemStats(params);
        itemStats.value = stats;
      } catch (err) {
        console.error('Error fetching item stats:', err);
      }
    };
    
    // Update fetchLocations to use service
    const fetchLocations = async () => {
      try {
        const { companyLocations: companyLocs, slaughterhouseLocations: slaughterLocs } = 
          await itemService.fetchLocations();
        companyLocations.value = companyLocs;
        slaughterhouseLocations.value = slaughterLocs;
      } catch (err) {
        modal.toast('Failed to fetch locations', 'danger');
      }
    };
    
    // Update fetchPoultryTypes to use service
    const fetchPoultryTypes = async () => {
      try {
        const poultries = await itemService.fetchPoultryTypes();
        poultryTypes.value = poultries;
      } catch (err) {
        console.error('Error fetching poultry types:', err);
        modal.toast('Failed to fetch poultry types', 'danger');
      }
    };
    
    // Update saveItem to use service
    const saveItem = async (formData) => {
      // console.log('Received formData in saveItem:', formData);
      // console.log('FormData type check:', typeof formData);
      // console.log('FormData keys:', Object.keys(formData));
      
      try {
        formLoading.value = true;
        items.value = [];
        loading.value = true;
    
        // Use the formData passed from the modal instead of itemForm
        await itemService.saveItem(formData, isEditing.value);
    
        // Close modal
        const modalInstance = bootstrap.Modal.getInstance(document.getElementById('itemModal'));
        if (modalInstance) modalInstance.hide();
    
        // Refresh data after a short delay to allow modal to close
        setTimeout(() => {
          fetchItems();
          fetchItemStats();
        }, 300);
      } finally {
        formLoading.value = false;
        loading.value = false;
      }
    };

    // Update confirmDelete to use service
    const confirmDelete = async (itemID) => {
      modal.confirm(
        'Delete Item',
        'Are you sure you want to delete this item? This action cannot be undone.',
        async () => {
          try {
            loading.value = true;
            await itemService.deleteItem(itemID);
            
            // Only refresh the data after successful deletion
            await fetchItems();
            await fetchItemStats();
          } finally {
            loading.value = false;
          }
        },
        null,
        {
          type: 'warning',
          confirmLabel: 'Delete',
          confirmType: 'danger',
          cancelLabel: 'Cancel'
        }
      );
    };
    
    // Add this function definition before the return statement
        const applyFilters = () => {
          currentPage.value = 1;
          fetchItems();
          fetchItemStats();
        };
        
        // Add onMounted hook to fetch initial data
        onMounted(() => {
          fetchItems();
          fetchItemStats();
          fetchPoultryTypes();
          fetchLocations();
        });
        
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
        const handleImageChange = (file) => {
          if (!file) {
            itemForm.item_image = null;
            imagePreview.value = null;
            return;
          }
          
          // Update the form data with the file
          itemForm.item_image = file;
          
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
        // Table columns - Move this before the return statement
        const columns = [
          { key: 'poultry', label: 'Poultry', sortable: false },
          { key: 'measurement', label: 'Weight', sortable: true, sortKey: 'measurement_value' },
          { key: 'price', label: 'Price', sortable: true },
          { key: 'stock', label: 'Stock', sortable: true }
        ];
    
    
    
    return {
      slaughterhouseLocations,
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
      previewImage,
      selectedItem,
      itemForm,
      // Functions
      fetchItems,
      fetchItemStats,  // Add this
      applyFilters,
      handleSearch,
      handleSort,
      changePage,
      formatCurrency: (value, shorten = false) => formatter.formatCurrency(value, 'MYR', 'en-MY', shorten),
      formatDate,
      formatLargeNumber: (value) => formatter.formatLargeNumber(value),
      editItem,
      confirmDelete,
      openAddModal,
      closeButton,
      handleImageChange,
      saveItem,
      formatPrice: (price) => Number(price).toFixed(2),
      companyLocations
    };
    }
};
</script>

<style scoped>
.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
}
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
  
  /* Ensure the table is readable on small screens */
  .card-body {
    padding: 0.75rem;
  }
  
  /* Add some space between rows for better readability */
  .responsive-table table tr {
    margin-bottom: 0.5rem;
  }
  
  /* Make sure the action buttons are properly sized */
  .btn-group .btn-sm {
    padding: 0.25rem 0.5rem;
  }
}
</style>