<template>
  <div class="cart-modal-container">
    <!-- Add to Cart Modal -->
    <div class="modal fade" id="addToCartModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add to Cart</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="selectedProduct" class="add-to-cart-item">
              <img 
                :src="selectedProduct.image || '/images/no-image.jpg'" 
                :alt="selectedProduct.name"
                class="add-to-cart-image"
              >
              <div class="add-to-cart-details">
                <h5 class="add-to-cart-title">{{ selectedProduct.name }}</h5>
                <p class="add-to-cart-price">RM {{ formatPrice(selectedProduct.price) }}</p>
                <p class="add-to-cart-seller">
                  <i class="bi bi-shop me-1"></i>{{ selectedProduct.seller || 'Unknown Seller' }}
                </p>
                <p class="add-to-cart-location">
                  <i class="bi bi-geo-alt me-1"></i>{{ selectedProduct.location || 'Unknown Location' }}
                </p>
              </div>
            </div>
            
            <div class="text-center mt-4">
              <label for="quantity-input" class="form-label">Quantity:</label>
              <div class="input-group quantity-input">
                <button class="btn btn-outline-secondary" type="button" @click="decreaseQuantity">-</button>
                <input 
                  type="number" 
                  class="form-control" 
                  v-model.number="quantity" 
                  min="1" 
                  max="99"
                >
                <button class="btn btn-outline-secondary" type="button" @click="increaseQuantity">+</button>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="confirmAddToCart">Add to Cart</button>
          </div>
        </div>
      </div>
    </div>

    <!-- View Cart Modal -->
    <div class="modal fade" id="viewCartModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Your Cart ({{ cartItems.length }} items)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body position-relative">
            <!-- Add loading spinner overlay -->
            <loading-spinner 
              v-if="isLoading" 
              message="Saving changes..." 
              size="lg" 
              :overlay="true"
            />
            
            <div v-if="cartItems.length === 0" class="text-center py-4">
              <i class="bi bi-cart3" style="font-size: 3rem; color: #ccc;"></i>
              <p class="mt-3">Your cart is empty</p>
            </div>
            
            <!-- In the template section, update the cart items display -->
            <div v-else>
              <div v-for="item in cartItems" :key="item.cartID" class="cart-item">
                <div class="d-flex">
                  <img 
                    :src="item.item?.poultry?.poultry_image ? `/storage/${item.item.poultry.poultry_image}` : '/images/no-image.jpg'" 
                    :alt="item.item?.poultry?.poultry_name || 'Product'"
                    class="cart-item-image"
                  >
                  <div class="cart-item-details flex-grow-1">
                    <h6 class="cart-item-title">{{ item.item?.poultry?.poultry_name || 'Product' }} ({{ item.item?.measurement_value || 0 }} {{ item.item?.measurement_type || 'units' }})</h6>
                    <p class="cart-item-price">RM {{ formatPrice(item.price_at_purchase) }}</p>
                    <p class="cart-item-seller">
                      <i class="bi bi-shop me-1"></i>{{ item.item?.user?.company?.company_name || 'Unknown Seller' }}
                    </p>
                    <p class="cart-item-location">
                      <i class="bi bi-geo-alt me-1"></i>{{ item.item?.location?.company_address || 'Unknown Location' }}
                    </p>
                  </div>
                  <div class="d-flex flex-column align-items-end">
                    <div class="input-group cart-item-quantity mb-2">
                      <button 
                        class="btn btn-sm btn-outline-secondary" 
                        @click="decreaseCartItemQuantity(item)"
                        :disabled="!isEditing"
                      >-</button>
                      <input 
                        type="number" 
                        class="form-control form-control-sm" 
                        v-model.number="item.quantity" 
                        min="1" 
                        max="99"
                        :disabled="!isEditing"
                      >
                      <button 
                        class="btn btn-sm btn-outline-secondary" 
                        @click="increaseCartItemQuantity(item)"
                        :disabled="!isEditing"
                      >+</button>
                    </div>
                    <button 
                      class="btn btn-sm btn-outline-danger"
                      @click="removeCartItem(item.cartID)"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
              
              <div class="cart-total">
                Total: RM {{ formatPrice(cartTotal) }}
              </div>
              
              <!-- Add edit mode controls -->
              <div class="d-flex justify-content-end mt-3" v-if="cartItems.length > 0">
                <button 
                  v-if="!isEditing" 
                  class="btn btn-outline-primary me-2"
                  @click="startEditing"
                >
                  <i class="bi bi-pencil me-1"></i> Edit Quantities
                </button>
                <button 
                  v-if="isEditing" 
                  class="btn btn-success me-2"
                  @click="saveChanges"
                >
                  <i class="bi bi-check-lg me-1"></i> Save Changes
                </button>
                <button 
                  v-if="isEditing" 
                  class="btn btn-outline-secondary"
                  @click="cancelEditing"
                >
                  Cancel
                </button>
              </div>
            </div>
            
            <!-- Update the modal footer -->
            <div class="modal-footer">
              <button 
                v-if="cartItems.length > 0 && !isEditing" 
                type="button" 
                class="btn btn-primary"
                @click="proceedToCheckout"
              >
                Proceed to Checkout
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import * as bootstrap from 'bootstrap';
import marketplaceService from '../../utils/marketplaceService';
import modal from '../../utils/modal'; // Import the modal utility
import LoadingSpinner from '../ui/LoadingSpinner.vue'; // Import the LoadingSpinner component

export default {
  name: 'CartModal',
  components: {
    LoadingSpinner
  },
  setup() {
    // State variables
    const selectedProduct = ref(null);
    const quantity = ref(1);
    const cartItems = ref([]);
    const originalCartItems = ref([]); // Store original cart items for cancel functionality
    const isEditing = ref(false);
    const isLoading = ref(false); // Add loading state
    const addToCartModal = ref(null);
    const viewCartModal = ref(null);
    
    // Computed properties
    const cartTotal = computed(() => {
      return cartItems.value.reduce((total, item) => {
        return total + (item.price_at_purchase * item.quantity);
      }, 0);
    });
    
    // Initialize modals
    const initModals = () => {
      // Initialize add to cart modal
      const addToCartEl = document.getElementById('addToCartModal');
      if (addToCartEl) {
        addToCartModal.value = new bootstrap.Modal(addToCartEl);
        
        // Reset quantity when modal is hidden
        addToCartEl.addEventListener('hidden.bs.modal', () => {
          quantity.value = 1;
          selectedProduct.value = null;
        });
      }
      
      // Initialize view cart modal
      const viewCartEl = document.getElementById('viewCartModal');
      if (viewCartEl) {
        viewCartModal.value = new bootstrap.Modal(viewCartEl);
      }
    };
    
    // Show add to cart modal
    const showAddToCartModal = (product) => {
      selectedProduct.value = product;
      quantity.value = 1;
      
      if (!addToCartModal.value) {
        initModals();
      }
      
      addToCartModal.value.show();
    };
    
    // Show view cart modal
    const showViewCartModal = async () => {
      try {
        // Fetch cart items
        const response = await marketplaceService.getCartItems();
        
        // Check if response has the expected structure
        if (response && Array.isArray(response.cart_items)) {
          cartItems.value = response.cart_items;
          // Make a deep copy of cart items for cancel functionality
          originalCartItems.value = JSON.parse(JSON.stringify(response.cart_items));
        } else {
          // Default to empty array if cart_items is not available or not an array
          cartItems.value = [];
          originalCartItems.value = [];
          console.warn('Cart items data is not in the expected format:', response);
        }
        
        // Reset editing mode
        isEditing.value = false;
        
        if (!viewCartModal.value) {
          initModals();
        }
        
        viewCartModal.value.show();
      } catch (error) {
        console.error('Error fetching cart items:', error);
        cartItems.value = []; // Set to empty array in case of error
        originalCartItems.value = [];
        
        // Still show the modal with empty cart
        if (!viewCartModal.value) {
          initModals();
        }
        viewCartModal.value.show();
      }
    };
    
    // Decrease quantity
    const decreaseQuantity = () => {
      if (quantity.value > 1) {
        quantity.value--;
      }
    };
    
    // Increase quantity
    const increaseQuantity = () => {
      if (quantity.value < 99) {
        quantity.value++;
      }
    };
    
    // Decrease cart item quantity
    const decreaseCartItemQuantity = (item) => {
      if (item.quantity > 1) {
        item.quantity--;
      }
    };
    
    // Increase cart item quantity
    const increaseCartItemQuantity = (item) => {
      if (item.quantity < 99) {
        item.quantity++;
      }
    };
    
    // Start editing cart
    const startEditing = () => {
      isEditing.value = true;
    };
    
    // Cancel editing and revert changes
    const cancelEditing = () => {
      // Restore original cart items
      cartItems.value = JSON.parse(JSON.stringify(originalCartItems.value));
      isEditing.value = false;
    };
    
    // Save cart changes
    const saveChanges = async () => {
      try {
        // Show loading spinner instead of modal
        isLoading.value = true;
        
        // Process each item that has changed
        const updatePromises = cartItems.value.map(async (item) => {
          // Find original item to compare
          const originalItem = originalCartItems.value.find(orig => orig.cartID === item.cartID);
          
          // Only update if quantity has changed
          if (originalItem && originalItem.quantity !== item.quantity) {
            try {
              await marketplaceService.updateCartItem(item.cartID, item.quantity);
            } catch (error) {
              console.error(`Error updating item ${item.cartID}:`, error);
              throw error;
            }
          }
        });
        
        // Wait for all updates to complete
        await Promise.all(updatePromises);
        
        // Refresh cart data
        const response = await marketplaceService.getCartItems();
        if (response && Array.isArray(response.cart_items)) {
          cartItems.value = response.cart_items;
          originalCartItems.value = JSON.parse(JSON.stringify(response.cart_items));
        }
        
        // Use notifyCartUpdate instead of updateCartBadge
        marketplaceService.notifyCartUpdate(response);
        
        // Exit edit mode
        isEditing.value = false;
        
        // Hide loading spinner
        isLoading.value = false;
        
        // Show success message
        // modal.success('Success', 'Cart updated successfully');
      } catch (error) {
        console.error('Error saving cart changes:', error);
        
        // Hide loading spinner
        isLoading.value = false;
        
        // Show error message
        modal.danger('Error', 'Failed to update cart. Please try again.');
      }
    };
    
    // Confirm add to cart
    const confirmAddToCart = async () => {
      try {
        if (!selectedProduct.value) {
          console.error('No product selected');
          return;
        }
        
        // Validate quantity
        if (!quantity.value || quantity.value < 1) {
          console.error('Invalid quantity:', quantity.value);
          modal.danger('Error', 'Please enter a valid quantity.');
          return;
        }
        
        // Handle different property names (id vs itemID)
        const itemID = selectedProduct.value.itemID || selectedProduct.value.id;
        
        // Ensure we have a valid itemID
        if (!itemID) {
          console.error('Invalid product ID:', selectedProduct.value);
          modal.danger('Error', 'Invalid product information. Please try again.');
          return;
        }
        
        // Create a product object with the correct itemID and quantity
        const product = {
          ...selectedProduct.value,
          itemID: itemID,
          quantity: quantity.value
        };
        
        // Log the data being sent to the API for debugging
        console.log('Sending to API:', {
          itemID: itemID,
          quantity: quantity.value
        });
        
        // Use marketplaceService instead of direct API call
        const result = await marketplaceService.addToCart(product);
        
        addToCartModal.value.hide();
        
        // Refresh cart count - safely handle potential undefined values
        try {
          const cartData = await marketplaceService.getCartItems();
          // Use notifyCartUpdate instead of updateCartBadge
          marketplaceService.notifyCartUpdate(cartData);
        } catch (err) {
          console.warn('Error refreshing cart count:', err);
        }
        
        // Animate cart button
        marketplaceService.animateCartButton();
      } catch (error) {
        console.error('Error adding to cart:', error);
        
        // Extract validation errors if available
        if (error.response && error.response.status === 422) {
          const validationErrors = error.response.data.errors;
          console.error('Validation errors:', validationErrors);
          
          // Display specific validation error messages
          if (validationErrors) {
            const errorMessages = Object.values(validationErrors)
              .flat()
              .join('\n');
            modal.danger('Validation Error', errorMessages);
          } else {
            modal.danger('Error', 'Failed to add item to cart. Please try again.');
          }
        } else {
          modal.danger('Error', 'Failed to add item to cart. Please try again.');
        }
      }
    };
    
    // Update cart item - no longer used directly
    const updateCartItem = async (item) => {
      // This method is kept for compatibility but no longer used directly
      console.log('Direct updateCartItem is deprecated, use saveChanges instead');
    };
    
    // Remove cart item
    const removeCartItem = async (cartID) => {
      try {
        await marketplaceService.removeCartItem(cartID);
        
        // Refresh cart items
        const response = await marketplaceService.getCartItems();
        cartItems.value = response.cart_items || [];
        originalCartItems.value = JSON.parse(JSON.stringify(cartItems.value));
        
        // Use notifyCartUpdate instead of updateCartBadge
        marketplaceService.notifyCartUpdate(response);
        
        // Exit edit mode
        isEditing.value = false;
        
        // Hide loading spinner
        isLoading.value = false;
        
        // Show success message
        // modal.success('Success', 'Item removed from cart');
      } catch (error) {
        console.error('Error removing cart item:', error);
        modal.danger('Error', 'Failed to remove item from cart. Please try again.');
      }
    };
    
    // Proceed to checkout
    const proceedToCheckout = () => {
      viewCartModal.value.hide();
      window.location.href = '/checkout';
    };
    
    // Format price
    const formatPrice = (price) => {
      return parseFloat(price).toFixed(2);
    };
    
    return {
      selectedProduct,
      quantity,
      cartItems,
      cartTotal,
      isEditing,
      isLoading, // Expose loading state to template
      showAddToCartModal,
      showViewCartModal,
      decreaseQuantity,
      increaseQuantity,
      decreaseCartItemQuantity,
      increaseCartItemQuantity,
      startEditing,
      cancelEditing,
      saveChanges,
      confirmAddToCart,
      updateCartItem,
      removeCartItem,
      proceedToCheckout,
      formatPrice
    };
  }
}
</script>

<style scoped>
.cart-modal-container {
  /* This container is invisible but holds the modals */
}

.add-to-cart-item {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

.add-to-cart-image {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: 5px;
  margin-right: 15px;
}

.add-to-cart-details {
  flex: 1;
}

.add-to-cart-title {
  font-weight: 600;
  font-size: 1.1rem;
  margin-bottom: 5px;
}

.add-to-cart-price {
  font-weight: 700;
  color: #123524;
  margin-bottom: 10px;
}

.add-to-cart-seller, .add-to-cart-location {
  font-size: 0.85rem;
  color: #666;
  margin-bottom: 5px;
}

.quantity-input {
  width: 120px;
  text-align: center;
  margin: 0 auto;
}

.quantity-input .form-control {
  text-align: center;
}

.cart-item {
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.cart-item:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.cart-item-image {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 5px;
}

.cart-item-details {
  padding-left: 15px;
}

.cart-item-title {
  font-weight: 600;
  margin-bottom: 5px;
}

.cart-item-price {
  font-weight: 700;
  color: #123524;
  margin-bottom: 5px;
}

.cart-item-seller {
  font-size: 0.85rem;
  color: #666;
}

.cart-item-quantity {
  width: 100px; /* Increase from 80px to 100px */
  min-width: 100px; /* Add min-width to prevent shrinking */
}

/* Add responsive styles for different screen sizes */
@media (max-width: 768px) {
  .cart-item-quantity {
    width: 80px;
    min-width: 80px;
  }
}

/* Make sure the input is always visible */
.cart-item-quantity .form-control {
  text-align: center;
  padding-left: 2px;
  padding-right: 2px;
}

.cart-total {
  font-size: 1.2rem;
  font-weight: 700;
  color: #123524;
  text-align: right;
  margin-top: 15px;
}

.modal-header {
  background-color: #123524;
  color: white;
}

.btn-close {
  filter: invert(1) brightness(200%);
}

.btn-primary {
  background-color: #123524;
  border-color: #123524;
}

.btn-primary:hover, .btn-primary:focus {
  background-color: #0d2a1c;
  border-color: #0d2a1c;
}
</style>