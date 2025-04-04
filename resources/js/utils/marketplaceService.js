import api from './api';
import modal from './modal';

/**
 * Service for marketplace-related API calls
 */
export default {
  // Add event listeners for cart updates
  cartUpdateListeners: [],
  
  /**
   * Register a callback for cart updates
   * @param {Function} callback - Function to call when cart is updated
   */
  onCartUpdate(callback) {
    if (typeof callback === 'function') {
      this.cartUpdateListeners.push(callback);
    }
  },
  
  /**
   * Notify all listeners about cart updates
   * @param {Object} cartData - Updated cart data
   */
  notifyCartUpdate(cartData) {
    this.cartUpdateListeners.forEach(callback => {
      try {
        callback(cartData);
      } catch (error) {
        console.error('Error in cart update callback:', error);
      }
    });
  },
  
  /**
   * Fetch marketplace items with filters
   * @param {Object} params - Query parameters
   * @returns {Promise} - Promise with response data
   */
  async fetchItems(params = {}) {
    try {
      const response = await api.get('/api/marketplace/items', { params });
      return response;
    } catch (error) {
      console.error('Error fetching marketplace items:', error);
      throw error;
    }
  },
  
  /**
   * Fetch poultry types for filtering
   * @returns {Promise} - Promise with poultry types data
   */
  async fetchPoultryTypes() {
    try {
      const response = await api.get('/api/poultries');
      return response.data || [];
    } catch (error) {
      console.error('Error fetching poultry types:', error);
      modal.danger('Error', 'Failed to load poultry types. Please refresh the page.');
      return [];
    }
  },
  
  /**
   * Add item to cart
   * @param {Object} product - Product to add to cart
   * @returns {Promise} - Promise with cart update result
   */
  async addToCart(product) {
    try {
      // Get cart modal component
      const cartModal = document.getElementById('cart-modal-component')?.__vue__;
      
      if (cartModal) {
        // Show add to cart modal
        cartModal.showAddToCartModal(product);
        return { success: true }; // Return a valid response object
      }
      
      // Validate product object and handle different property names
      if (!product) {
        console.error('Invalid product object:', product);
        modal.danger('Error', 'Invalid product information. Please try again.');
        return;
      }
      
      // Handle different property names (id vs itemID)
      const itemID = product.itemID || product.id;
      
      if (!itemID) {
        console.error('Invalid product ID:', product);
        modal.danger('Error', 'Invalid product information. Please try again.');
        return;
      }
      
      // Log the data being sent to the API for debugging
      console.log('Sending to API:', {
        itemID: itemID,
        quantity: product.quantity || 1
      });
      
      // Fallback if component not found
      const response = await api.post('/api/cart/add', {
        itemID: itemID,
        quantity: product.quantity || 1
      });
      
      // The response format is correct, but we need to handle it properly
      if (response && response.data) {
        // Update cart badge count if cart_count exists
        if (response.data.cart_count !== undefined) {
          this.updateCartBadge(response.data.cart_count);
        }
        
        // Notify listeners about cart update
        this.notifyCartUpdate(response.data);
        
        // Animate cart button
        this.animateCartButton();
        
        return response.data;
      } else {
        console.warn('Empty response received');
        return null;
      }
    } catch (error) {
      console.error('Error adding to cart:', error);
      modal.danger('Error', 'Failed to add item to cart. Please try again.');
      throw error;
    }
  },
  
  /**
   * Get cart items
   * @returns {Promise} - Promise with cart items
   */
  async getCartItems() {
    try {
      const response = await api.get('/api/cart/items');
      
      // Log the raw response for debugging
      console.log('Raw cart items response:', response);
      
      // Check if response exists
      if (response) {
        // If response.data exists, use it
        if (response.data) {
          // Log the response.data for debugging
          console.log('Cart items response.data:', response.data);
          
          // The API is correctly returning a success response with cart data
          if (response.data.success) {
            const cartData = {
              cart_items: response.data.cart_items || [],
              cart_count: response.data.cart_count || 0,
              cart_total: response.data.cart_total || 0
            };
            
            // Notify listeners about cart update
            this.notifyCartUpdate(cartData);
            
            return cartData;
          } else {
            // Return default structure if response indicates failure
            console.warn('Cart API response indicates failure:', response.data.message);
            return {
              cart_items: [],
              cart_count: 0,
              cart_total: 0
            };
          }
        } else {
          // If response is the data itself (no data property)
          // This handles cases where the API might return the data directly
          console.log('Response without data property:', response);
          
          if (response.success) {
            return {
              cart_items: response.cart_items || [],
              cart_count: response.cart_count || 0,
              cart_total: response.cart_total || 0
            };
          } else if (Array.isArray(response)) {
            // If response is an array, assume it's the cart items
            return {
              cart_items: response,
              cart_count: response.length,
              cart_total: 0
            };
          } else {
            console.warn('Cart API response has unexpected format:', response);
            return {
              cart_items: [],
              cart_count: 0,
              cart_total: 0
            };
          }
        }
      } else {
        // Return default structure if response is null or undefined
        console.warn('Cart API response is null or undefined');
        return {
          cart_items: [],
          cart_count: 0,
          cart_total: 0
        };
      }
    } catch (error) {
      console.error('Error fetching cart items:', error);
      // Return default structure in case of error
      return {
        cart_items: [],
        cart_count: 0,
        cart_total: 0
      };
    }
  },
  
  /**
   * Update cart item quantity
   * @param {Number} cartID - Cart item ID
   * @param {Number} quantity - New quantity
   * @returns {Promise} - Promise with updated cart data
   */
  async updateCartItem(cartID, quantity) {
    try {
      const response = await api.put(`/api/cart/update`, {
        cartID: cartID,
        quantity: quantity
      });
      
      // Update cart badge count if cart_count exists in the response
      if (response && response.data && response.data.cart_count !== undefined) {
        this.updateCartBadge(response.data.cart_count);
        
        // Notify listeners about cart update
        this.notifyCartUpdate(response.data);
      }
      
      return response.data;
    } catch (error) {
      console.error('Error updating cart item:', error);
      modal.danger('Error', 'Failed to update cart item. Please try again.');
      throw error;
    }
  },
  
  /**
   * Remove item from cart
   * @param {Number} cartID - Cart item ID
   * @returns {Promise} - Promise with updated cart data
   */
  async removeCartItem(cartID) {
    try {
      const response = await api.delete(`/api/cart/remove/${cartID}`);
      
      // Update cart badge count if cart_count exists in the response
      if (response && response.data && response.data.cart_count !== undefined) {
        this.updateCartBadge(response.data.cart_count);
        
        // Notify listeners about cart update
        this.notifyCartUpdate(response.data);
      } else {
        // If cart_count is not available, refresh cart data to get the count
        try {
          const cartData = await this.getCartItems();
          if (cartData && cartData.cart_count !== undefined) {
            this.updateCartBadge(cartData.cart_count);
            
            // Notify listeners about cart update
            this.notifyCartUpdate(cartData);
          }
        } catch (err) {
          console.warn('Error refreshing cart count after removal:', err);
        }
      }
      
      return response.data;
    } catch (error) {
      console.error('Error removing cart item:', error);
      modal.danger('Error', 'Failed to remove item from cart. Please try again.');
      throw error;
    }
  },
  
  /**
   * View cart items in modal
   * @returns {Promise} - Promise with modal result
   */
  async viewCart() {
    try {
      // Get cart modal component
      const cartModal = document.getElementById('cart-modal-component')?.__vue__;
      
      if (cartModal) {
        // Show view cart modal
        cartModal.showViewCartModal();
        return;
      }
      
      // Fallback if component not found
      window.location.href = '/cart';
    } catch (error) {
      console.error('Error viewing cart:', error);
      modal.danger('Error', 'Failed to load cart items. Please try again.');
      throw error;
    }
  },
  
  /**
   * Update cart badge count
   * @param {Number} count - Number of items in cart
   */
  updateCartBadge(count) {
    // Find cart badge element
    let badgeEl = document.querySelector('.cart-badge');
    
    // If badge doesn't exist, create it
    if (!badgeEl && count > 0) {
      const cartBtn = document.querySelector('.cart-button');
      if (cartBtn) {
        badgeEl = document.createElement('span');
        badgeEl.className = 'cart-badge';
        cartBtn.appendChild(badgeEl);
      }
    }
    
    // Update badge count or remove if zero
    if (badgeEl) {
      if (count > 0) {
        badgeEl.textContent = count > 99 ? '99+' : count;
        badgeEl.style.display = 'flex';
      } else {
        badgeEl.style.display = 'none';
      }
    }
  },
  
  /**
   * Animate cart button with shake effect
   */
  animateCartButton() {
    const cartBtn = document.querySelector('.cart-button');
    if (cartBtn) {
      // Remove existing animation class
      cartBtn.classList.remove('cart-shake');
      
      // Force reflow to restart animation
      void cartBtn.offsetWidth;
      
      // Add animation class
      cartBtn.classList.add('cart-shake');
    }
  }
};