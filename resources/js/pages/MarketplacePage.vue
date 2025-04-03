<template>
  <div class="marketplace-page">
    <h1 class="mb-4">Marketplace</h1>
    
    <!-- Search and Filter Bar -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="input-group">
              <input 
                type="text" 
                class="form-control" 
                placeholder="Search products..." 
                v-model="searchQuery"
                @input="handleSearch"
              >
              <button class="btn btn-primary" type="button" @click="fetchItems">
                <i class="bi bi-search"></i> Search
              </button>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex gap-2 justify-content-md-end">
              <select class="form-select" 
                      v-model="selectedPoultryType" 
                      @change="handleFilters">
                <option value="">All Poultry Types</option>
                <option v-for="type in poultryTypes" 
                        :key="type.poultryID" 
                        :value="type.poultryID">
                  {{ type.poultry_name }}
                </option>
              </select>
              <select class="form-select" 
                      v-model="sortBy" 
                      @change="handleFilters">
                <option value="newest">Newest First</option>
                <option value="price_low">Price: Low to High</option>
                <option value="price_high">Price: High to Low</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="alert alert-danger" role="alert">
      {{ error }}
    </div>
    
    <!-- Products Grid -->
    <div v-if="!loading && !error" class="row g-4">
      <div v-for="product in products" 
           :key="product.id" 
           class="col-sm-6 col-md-4 col-xl-3">
        <div class="card h-100 product-card">
          <div class="product-image-container">
            <img 
              :src="product.image || '/images/no-image.jpg'" 
              :alt="product.name"
              class="product-image"
            >
          </div>
          <div class="card-body d-flex flex-column">
            <h5 class="card-title product-title" :title="product.name">
              {{ product.name }}
            </h5>
            <p class="card-text product-price mb-2">
              RM {{ formatPrice(product.price) }}
            </p>
            <p class="card-text product-quantity mb-2">
              {{ product.quantity }} {{ product.unit }}
            </p>
            <p class="card-text product-seller mb-2" :title="product.seller">
              <i class="bi bi-shop me-1"></i>{{ product.seller }}
            </p>
            <p class="card-text product-location mb-3" :title="product.location">
              <i class="bi bi-geo-alt me-1"></i>{{ product.location }}
            </p>
            <div class="mt-auto">
              <button class="btn btn-primary w-100 mb-2">View Details</button>
              <button class="btn btn-outline-primary w-100" @click="addToCart(product)">
                <i class="bi bi-cart-plus me-1"></i> Add to Cart
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- No Results -->
    <div v-if="!loading && !error && products.length === 0" class="text-center py-5">
      <div class="alert alert-info">
        No products found. Try adjusting your search criteria.
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="!loading && !error && products.length > 0" class="d-flex justify-content-between align-items-center mt-4">
      <div>
        <span class="text-muted">Showing {{ pagination.from || 1 }}-{{ pagination.to || products.length }} of {{ pagination.total || products.length }}</span>
      </div>
      <nav aria-label="Product pagination">
        <ul class="pagination mb-0">
          <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
            <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>
          <li v-for="page in paginationRange" 
              :key="page" 
              class="page-item" 
              :class="{ active: page === pagination.current_page }">
            <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
            <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import api from '../utils/api';

export default {
  name: 'MarketplacePage',
  setup() {
    const products = ref([]);
    const poultryTypes = ref([]);
    const loading = ref(true);
    const error = ref(null);
    const searchQuery = ref('');
    const selectedPoultryType = ref('');
    const sortBy = ref('newest');
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 5,
      total: 0,
      from: 1,
      to: 0
    });

    // Calculate pagination range similar to EmployeeManagement.vue
    const paginationRange = computed(() => {
      const range = [];
      const maxVisiblePages = 5;
      const currentPage = pagination.value.current_page;
      const lastPage = pagination.value.last_page;
      
      if (lastPage <= maxVisiblePages) {
        // If we have fewer pages than the max visible, show all pages
        for (let i = 1; i <= lastPage; i++) {
          range.push(i);
        }
      } else {
        // Calculate start and end of the range
        let start = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let end = start + maxVisiblePages - 1;
        
        // Adjust if end is beyond the last page
        if (end > lastPage) {
          end = lastPage;
          start = Math.max(1, end - maxVisiblePages + 1);
        }
        
        for (let i = start; i <= end; i++) {
          range.push(i);
        }
      }
      
      return range;
    });

    const formatPrice = (price) => {
      return Number(price).toFixed(2);
    };

    const fetchPoultryTypes = async () => {
      try {
        const response = await api.get('/api/poultries');
        console.log('Poultry types response:', response);
        // Directly assign the response array since it's already in the correct format
        if (Array.isArray(response)) {
          poultryTypes.value = response;
        } else {
          console.error('Invalid poultry types response format:', response);
          poultryTypes.value = [];
        }
      } catch (err) {
        console.error('Error fetching poultry types:', err);
        poultryTypes.value = [];
      }
    };

    const handleFilters = () => {
      try {
        console.log('Current filters:', {
          searchQuery: searchQuery.value,
          poultry_type: selectedPoultryType.value,
          sort: sortBy.value
        });
        fetchItems(1); // Reset to first page when filters change
      } catch (err) {
        console.error('Error applying filters:', err);
        error.value = 'Failed to apply filters. Please try again.';
      }
    };

    // In script setup section
    const fetchItems = async (page = 1) => {
      try {
        loading.value = true;
        error.value = null;
        
        const params = {
          page,
          search: searchQuery.value || null,
          poultry_type: selectedPoultryType.value || null,
          sort: sortBy.value || 'newest'
        };
        
        console.log('Fetching items with params:', params);
        
        const response = await api.get('/api/marketplace/items', { params });
        
        // Check the response structure
        console.log('Raw API response:', response);
        
        // Handle the data and pagination properly
        if (response.data) {
          // Assuming the response has a data property for items
          products.value = response.data || [];
          
          // Update pagination if it exists in the response
          if (response.pagination) {
            pagination.value = response.pagination;
          }
        } else {
          throw new Error('Invalid response format');
        }
        
      } catch (err) {
        console.error('Error fetching items:', err);
        error.value = 'Failed to load products. Please try again later.';
        products.value = [];
      } finally {
        loading.value = false;
      }
    };

    const changePage = (page) => {
      console.log('Changing to page:', page);
      console.log('Current page:', pagination.value.current_page);
      console.log('Last page:', pagination.value.last_page);
      if (page >= 1 && page <= pagination.value.last_page) {
        fetchItems(page);
      }
    };

    // Debounce search input
    let searchTimeout;
    const handleSearch = () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        fetchItems(1); // Reset to first page when searching
      }, 300);
    };

    onMounted(() => {
      fetchPoultryTypes();
      fetchItems();
    });

    return {
      products,
      poultryTypes,
      loading,
      error,
      searchQuery,
      selectedPoultryType,
      sortBy,
      pagination,
      paginationRange,
      formatPrice,
      changePage,
      fetchItems,
      handleSearch,
      handleFilters // Make sure this is included in the return statement
    };
  }
};
</script>

<style scoped>
.marketplace-page h1 {
  color: #123524;
}

.product-card {
  transition: transform 0.2s, box-shadow 0.2s;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.product-image-container {
  height: 200px;
  overflow: hidden;
}

.product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-title {
  font-size: 1.1rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.product-price {
  font-size: 1.2rem;
  font-weight: 700;
  color: #123524;
}

.product-quantity, .product-seller, .product-location {
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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