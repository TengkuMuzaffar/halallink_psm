<template>
  <div class="marketplace-page">
    <h1 class="mb-4">Marketplace</h1>
    
    <!-- Search and Filter Bar -->
    <SearchFilterBar
      :poultry-types="poultryTypes"
      :initial-search-query="searchQuery"
      :initial-poultry-type="selectedPoultryType"
      :initial-sort-by="sortBy"
      @search-input="handleSearch"
      @search-click="handleSearchClick"
      @filter-change="handleFilters"
    />

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
    <ProductGrid
      :products="products"
      :loading="loading"
      :error="error"
      @view-details="viewProductDetails"
      @add-to-cart="addToCart"
    />

    <!-- Pagination -->
    <MarketplacePagination
      v-if="!loading && !error && products.length > 0"
      :current-page="pagination.current_page"
      :last-page="pagination.last_page"
      :from="pagination.from"
      :to="pagination.to"
      :total="pagination.total"
      :item-count="products.length"
      @page-change="changePage"
    />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import SearchFilterBar from '../components/marketplace/SearchFilterBar.vue';
import ProductGrid from '../components/marketplace/ProductGrid.vue';
import MarketplacePagination from '../components/marketplace/MarketplacePagination.vue';
import marketplaceService from '../utils/marketplaceService';

export default {
  name: 'MarketplacePage',
  components: {
    SearchFilterBar,
    ProductGrid,
    MarketplacePagination
  },
  setup() {
    // State variables
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
      from: 1,
      to: 0,
      total: 0
    });

    // Fetch items with pagination, search, and filters
    const fetchItems = async (page = 1) => {
      try {
        loading.value = true;
        error.value = null;
        
        // Ensure page is a number
        const pageNumber = parseInt(page) || 1;
        
        const params = {
          page: pageNumber,
          search: searchQuery.value || null,
          poultry_type: selectedPoultryType.value || null,
          sort: sortBy.value || 'newest'
        };
        
        const response = await marketplaceService.fetchItems(params);
        
        if (response && response.data) {
          products.value = response.data;
          pagination.value = response.pagination || {
            current_page: 1,
            last_page: 1,
            from: 1,
            to: response.data.length,
            total: response.data.length
          };
        } else {
          products.value = [];
          error.value = 'Unexpected response format';
        }
      } catch (err) {
        console.error('Error fetching items:', err);
        error.value = 'Failed to load products. Please try again later.';
        products.value = [];
      } finally {
        loading.value = false;
      }
    };

    // Fetch poultry types for filter dropdown
    const fetchPoultryTypes = async () => {
      try {
        poultryTypes.value = await marketplaceService.fetchPoultryTypes();
      } catch (err) {
        console.error('Error fetching poultry types:', err);
      }
    };

    // Handle search input
    const handleSearch = (query) => {
      searchQuery.value = query;
    };

    // Handle search button click
    const handleSearchClick = () => {
      fetchItems(1); // Reset to first page when searching
    };

    // Handle filters change
    const handleFilters = ({ poultryType, sortBy: newSortBy }) => {
      selectedPoultryType.value = poultryType;
      sortBy.value = newSortBy;
      fetchItems(1); // Reset to first page when applying filters
    };

    // Change page
    const changePage = (page) => {
      if (page < 1 || page > pagination.value.last_page || loading.value) return;
      fetchItems(page);
    };

    // View product details
    const viewProductDetails = (product) => {
      // Implement product details view (could navigate to a details page)
      console.log('View details for product:', product);
      // Example: router.push(`/marketplace/product/${product.id}`);
    };

    // Add to cart
    const addToCart = async (product) => {
      try {
        await marketplaceService.addToCart(product);
      } catch (err) {
        // Error is already handled in the service
      }
    };

    // Initialize data on component mount
    onMounted(() => {
      fetchItems();
      fetchPoultryTypes();
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
      handleSearch,
      handleSearchClick,
      handleFilters,
      changePage,
      viewProductDetails,
      addToCart
    };
  }
};
</script>

<style scoped>
.marketplace-page {
  padding-bottom: 2rem;
}
</style>