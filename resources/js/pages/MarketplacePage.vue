<template>
  <div class="marketplace-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Marketplace</h1>
      <button class="btn btn-primary cart-button" @click="viewCart">
        <i class="fas fa-shopping-cart me-2"></i> View Cart
        <span  class="cart-badge">{{ cartItemCount }}</span>
      </button>
    </div>
    
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
    
    <!-- Cart Modal Component -->
    <CartModal id="cart-modal-component" ref="cartModal" />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import SearchFilterBar from '../components/marketplace/SearchFilterBar.vue';
import ProductGrid from '../components/marketplace/ProductGrid.vue';
import MarketplacePagination from '../components/marketplace/MarketplacePagination.vue';
import CartModal from '../components/marketplace/CartModal.vue';
import marketplaceService from '../utils/marketplaceService';

export default {
  name: 'MarketplacePage',
  components: {
    SearchFilterBar,
    ProductGrid,
    MarketplacePagination,
    CartModal
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
    const cartItemCount = ref(0); // Add cart item count ref
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      from: 1,
      to: 0,
      total: 0
    });
    const cartModal = ref(null);

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
    const addToCart = (product) => {
      if (cartModal.value) {
        cartModal.value.showAddToCartModal(product);
      } else {
        marketplaceService.addToCart(product);
      }
    };
    
    // View cart
    const viewCart = () => {
      if (cartModal.value) {
        cartModal.value.showViewCartModal();
      } else {
        marketplaceService.viewCart();
      }
    };
    
    // Initialize cart badge on load
    const initCartBadge = async () => {
      try {
        const cartData = await marketplaceService.getCartItems();
        
        // Ensure we're counting unique items, not total quantity
        if (cartData && cartData.cart_items && Array.isArray(cartData.cart_items)) {
          // Set cart item count to the number of unique items in cart
          cartItemCount.value = cartData.cart_items.length;
          console.log('Cart badge initialized with item count:', cartItemCount.value);
        } else {
          // Default to 0 if cart_items is not available or not an array
          cartItemCount.value = 0;
        }
      } catch (err) {
        console.error('Error initializing cart badge:', err);
        // Set cart badge to 0 in case of error
        cartItemCount.value = 0;
      }
    };

    // Update the onCartUpdate callback to always use cart_items.length
    marketplaceService.onCartUpdate((data) => {
      // Set a default value of 0
      let count = 0;
      
      if (data && data.cart_items && Array.isArray(data.cart_items)) {
        // Always use the length of cart_items array (number of unique items)
        count = data.cart_items.length;
        console.log('Cart badge updated with item count:', count);
      } else if (data && typeof data.cart_count === 'number') {
        // Only use cart_count as fallback if cart_items is not available
        count = data.cart_count;
        console.log('Cart badge updated with cart_count fallback:', count);
      } else if (data && data.cart_item) {
        // Handle case when only a single item is added to an empty cart
        count = 1;
        console.log('Cart badge updated for first item added:', count);
      }
      
      // Force update to ensure reactivity
      setTimeout(() => {
        cartItemCount.value = count || 0;
      }, 0);
    });
    
    // Initialize data on component mount
    onMounted(() => {
      fetchItems();
      fetchPoultryTypes();
      initCartBadge();
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
      cartModal,
      cartItemCount,
      handleSearch,
      handleSearchClick,
      handleFilters,
      changePage,
      viewProductDetails,
      addToCart,
      viewCart
    };
  }
};
</script>

<style scoped>
.marketplace-page {
  padding-bottom: 2rem;
}

.btn-primary {
  background-color: #123524;
  border-color: #123524;
}

.btn-primary:hover, .btn-primary:focus {
  background-color: #0d2a1c;
  border-color: #0d2a1c;
}

.cart-button {
  position: relative;
  padding-right: 15px; /* Add some padding to ensure badge doesn't overlap text */
}

.cart-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background-color: #dc3545;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2; /* Ensure badge appears above other elements */
}

@keyframes cartShake {
  0% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  50% { transform: translateX(5px); }
  75% { transform: translateX(-5px); }
  100% { transform: translateX(0); }
}

.cart-shake {
  animation: cartShake 0.5s ease-in-out;
}
</style>