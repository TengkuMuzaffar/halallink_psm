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
              <select class="form-select" v-model="selectedPoultryType" @change="fetchItems">
                <option value="">All Categories</option>
                <option v-for="type in poultryTypes" 
                        :key="type.poultryID" 
                        :value="type.poultryID">
                  {{ type.poultry_name }}
                </option>
              </select>
              <select class="form-select" v-model="sortBy" @change="fetchItems">
                <option value="newest">Newest</option>
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
    <div v-if="!loading && !error" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
      <div class="col" v-for="product in products" :key="product.id">
        <div class="card h-100 product-card">
          <div class="product-image-container">
            <img :src="product.image" class="card-img-top product-image" :alt="product.name">
          </div>
          <div class="card-body">
            <h5 class="card-title product-title">{{ product.name }}</h5>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="product-price">RM {{ product.price.toFixed(2) }}</span>
              <span class="product-quantity">{{ product.quantity }} {{ product.unit }}</span>
            </div>
            <p class="card-text product-seller">
              <i class="bi bi-shop me-1"></i> {{ product.seller }}
            </p>
            <div class="d-flex justify-content-between align-items-center">
              <span class="product-location">
                <i class="bi bi-geo-alt me-1"></i> {{ product.location }}
              </span>
            </div>
          </div>
          <div class="card-footer bg-transparent border-top-0">
            <div class="d-grid gap-2">
              <button class="btn btn-primary">
                <i class="bi bi-cart-plus me-1"></i> Add to Cart
              </button>
              <button class="btn btn-outline-secondary">
                <i class="bi bi-eye me-1"></i> View Details
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- No Results -->
    <div v-if="!loading && !error && products.length === 0" class="text-center py-5">
      <p class="text-muted">No products found</p>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import api from '../utils/api';

export default {
  name: 'MarketplacePage',
  setup() {
    const products = ref([]);
    const poultryTypes = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const searchQuery = ref('');
    const selectedPoultryType = ref('');
    const sortBy = ref('newest');

    const fetchPoultryTypes = async () => {
      try {
        const response = await api.get('/api/marketplace/poultry-types', {
          onError: (error) => {
            console.error('Error fetching poultry types:', error);
          }
        });
        poultryTypes.value = response;
      } catch (err) {
        console.error('Failed to fetch poultry types:', err);
      }
    };

    const fetchItems = async () => {
      loading.value = true;
      error.value = null;
      try {
        const response = await api.get('/api/marketplace/items', {
          params: {
            search: searchQuery.value,
            poultry_type: selectedPoultryType.value,
            sort: sortBy.value
          },
          onError: (err) => {
            error.value = 'Failed to load products. Please try again later.';
          }
        });
        products.value = response;
      } catch (err) {
        error.value = 'Failed to load products. Please try again later.';
      } finally {
        loading.value = false;
      }
    };

    // Debounce search input
    let searchTimeout;
    const handleSearch = () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        fetchItems();
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
      handleSearch,
      fetchItems
    };
  }
};
</script>