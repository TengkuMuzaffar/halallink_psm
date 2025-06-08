import api from '../utils/api';
import modal from '../utils/modal';
/**
 * Service for marketplace-related API calls
 */
export default {
  // Add event listeners for cart updates
  cartUpdateListeners: [],
  cachedLocations: null,
  lastLocationsFetch: null,
  locationCacheTimeout: 5 * 60 * 1000, // 5 minutes cache timeout
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
   * Fetch user's delivery locations with caching
   * @returns {Promise} - Promise with locations data
   */
 async fetchUserLocations() {
    try {
      // Check if we have valid cached data
      const now = Date.now();
      if (this.cachedLocations && this.lastLocationsFetch && 
          (now - this.lastLocationsFetch) < this.locationCacheTimeout) {
        console.log('Using cached locations');
        return this.cachedLocations;
      }

      const response = await api.get('/api/profile');
      
      // Extract and cache locations
      if (response && response.locations) {
        this.cachedLocations = response.locations;
        this.lastLocationsFetch = now;
        return this.cachedLocations;
      }
      
      return [];
    } catch (error) {
      console.error('Error fetching user locations:', error);
      modal.danger('Error', 'Failed to load delivery locations. Please try again.');
      return [];
    }
  },

  /**
   * Clear the locations cache
   */
  clearLocationsCache() {
    this.cachedLocations = null;
    this.lastLocationsFetch = null;
  },

  /**
   * Add item to cart
   * @param {Object} product - Product to add to cart
   * @returns {Promise} - Promise with cart update result
   */
  /**
   * Add an item to the cart
   * @param {Object} product - Product to add to cart
   * @param {Object} options - Additional options
   * @param {boolean} options.suppressErrorModal - Whether to suppress error modal
   * @returns {Promise} - Promise with response data
   */
  async addToCart(product, options = {}) {
    try {
      console.log('Adding to cart:', product);
      
      // Use order_quantity if available, otherwise fall back to quantity
      const orderQuantity = product.order_quantity || product.quantity || 1;
      
      const response = await api.post('/api/cart/add', {
        itemID: product.itemID || product.id,
        order_quantity: orderQuantity // Send the order_quantity parameter to the API
      });
      
      // Show success message
      modal.success('Success', 'Item added to cart successfully');
      
      // Notify listeners about cart update
      this.notifyCartUpdate(response.data);
      
      return response.data;
    } catch (error) {
      console.error('Error adding to cart:', error);
      
      // Only show error modal if not suppressed
      if (!options.suppressErrorModal) {
        // Show only one error message with proper details
        let errorMessage = 'Failed to add item to cart. Please try again later.';
        
        if (error.response && error.response.data) {
          const errorData = error.response.data;
          // Use the most specific error message available
          errorMessage = errorData.error || errorData.message || errorMessage;
        }
        
        // Show a single error modal with the most relevant message
        modal.danger('Error', errorMessage);
      }
      
      // Return a rejected promise with the error
      return Promise.reject(error);
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
      // console.log('Raw cart items response:', response);
      
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
          // console.log('Response without data property:', response);
          
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
            // console.warn('Cart API response has unexpected format:', response);
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
   * @param {Object} options - Additional options
   * @param {boolean} options.suppressErrorModal - Whether to suppress error modal
   * @returns {Promise} - Promise with updated cart data
   */
  async updateCartItem(cartID, quantity, options = {}) {
    try {
      const response = await api.put(`/api/cart/update`, {
        cartID: cartID,
        quantity: quantity
      });
      
      // Notify listeners about cart update instead of directly updating badge
      if (response && response.data) {
        this.notifyCartUpdate(response.data);
      }
      
      return response.data;
    } catch (error) {
      console.error('Error updating cart item:', error);
      
      // Only show error modal if not suppressed
      if (!options.suppressErrorModal) {
        let errorMessage = 'Failed to update cart item. Please try again.';
        
        if (error.response && error.response.data) {
          const errorData = error.response.data;
          // Use the most specific error message available
          errorMessage = errorData.error || errorData.message || errorMessage;
        }
        
        // Show a single error modal with the most relevant message
        modal.danger('Error', errorMessage);
      }
      
      // Return a rejected promise with the error
      return Promise.reject(error);
    }
  },
  
  /**
   * Remove item from cart
   * @param {Number} cartID - Cart item ID
   * @param {Object} options - Additional options
   * @param {boolean} options.suppressErrorModal - Whether to suppress error modal
   * @returns {Promise} - Promise with updated cart data
   */
  async removeCartItem(cartID, options = {}) {
    try {
      const response = await api.delete(`/api/cart/remove/${cartID}`);
      
      // Notify listeners about cart update instead of directly updating badge
      if (response && response.data) {
        this.notifyCartUpdate(response.data);
      } else {
        // If cart data is not available in response, refresh cart data
        try {
          const cartData = await this.getCartItems();
          // getCartItems already calls notifyCartUpdate
        } catch (err) {
          console.warn('Error refreshing cart count after removal:', err);
          // Don't show an error modal here to prevent multiple modals
        }
      }
      
      return response.data;
    } catch (error) {
      console.error('Error removing cart item:', error);
      
      // Only show error modal if not suppressed
      if (!options.suppressErrorModal) {
        let errorMessage = 'Failed to remove item from cart. Please try again.';
        
        if (error.response && error.response.data) {
          const errorData = error.response.data;
          // Use the most specific error message available
          errorMessage = errorData.error || errorData.message || errorMessage;
        }
        
        // Show a single error modal with the most relevant message
        modal.danger('Error', errorMessage);
      }
      
      // Return a rejected promise with the error
      return Promise.reject(error);
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
  },

  /**
   * Process checkout for cart items
   * @param {Number} locationID - Selected delivery location ID
   * @returns {Promise} - Promise with checkout result
   */
  /**
   * Process checkout with selected location
   * @param {Number} locationID - Selected delivery location ID
   * @returns {Promise} - Promise with checkout result
   */
  async checkout(locationID) {
    try {
      if (!locationID) {
        throw new Error('Please select a delivery location');
      }
      
      console.log('Processing checkout with locationID:', locationID);
      
      // Show loading modal
      // modal.loading('Processing Payment', 'Please wait while we connect to the payment gateway...');
      
      // Call the payment creation endpoint with location ID
      const response = await api.post('/api/payment/create', {
        locationID: locationID
      });
      
      // Close loading modal
      modal.close();
      
      console.log('Payment API response:', response);
      
      // Check if the response contains a redirect URL
      if (response && response.redirect_url) {
        console.log('Response data:', response.redirect_url);
        // Redirect to the payment gateway
        window.location.href = response.redirect_url;
         
      } else {
        // Handle unexpected response
        console.error('Invalid response format:', response);
        modal.danger('Checkout Error', 'Unable to process payment. Please try again.');
        throw new Error('Invalid payment response format');
      }
    } catch (error) {
      // Close loading modal
      modal.close();
      
      console.error('Error processing checkout:', error);
      
      // Extract error message from response if available
      let errorMessage = 'Unable to process payment. Please try again.';
      
      if (error.response && error.response.data) {
        console.error('Error response:', error.response.data);
        
        if (error.response.data && error.response.data.message) {
          errorMessage = error.response.data.message;
        }
      }
      
      modal.danger('Checkout Error', errorMessage);
      throw error;
    }
  },
  
  /**
   * Verify payment status
   * @param {Object} params - Payment parameters from ToyyibPay callback
   * @param {string} params.billcode - Bill code from ToyyibPay
   * @param {string} params.order_id - Order reference number
   * @param {string} params.status_id - Payment status ID (1=success, 2=pending, 3=failed)
   * @param {string} params.transaction_id - Transaction ID from ToyyibPay
   * @returns {Promise} - Promise with payment verification result
   */
  async verifyPayment(params = {}) {
    try {
      // Show loading indicator
      // modal.loading('Verifying Payment', 'Please wait while we verify your payment...');
      
      const { billcode, order_id, status_id, transaction_id } = params;
      
      // Log the parameters for debugging
      console.log('Verifying payment with params:', params);
      
      if (!billcode || !order_id) {
        modal.close();
        modal.danger('Verification Error', 'Missing required payment information');
        return Promise.reject(new Error('Missing required payment parameters'));
      }
      
      // Call the payment verification endpoint
      const response = await api.get('/api/payment/verify', {
        params: {
          billcode,
          order_id,
          transaction_id
        }
      });
      
      // // Close loading modal
      // modal.close();
      
      if (response && response.data) {
        if (response.data.success) {
          // Payment verified successfully
          modal.success('Payment Successful', `Your payment of ${response.data.amount} has been processed successfully.`);
          
          // Refresh cart data after successful payment
          try {
            await this.getCartItems();
          } catch (err) {
            console.warn('Error refreshing cart after payment:', err);
          }
          
          return response.data;
        } else {
          // Payment verification failed
          modal.warning('Payment Verification', response.data.message || 'Payment verification failed. Please contact support.');
          return Promise.reject(new Error(response.data.message || 'Payment verification failed'));
        }
      } else {
        // Handle unexpected response
        console.error('Invalid payment verification response:', response);
        modal.danger('Verification Error', 'Unable to verify payment. Please contact support.');
        return Promise.reject(new Error('Invalid response from payment verification'));
      }
    } catch (error) {
      // // Close loading modal
      // modal.close();
      
      console.error('Error verifying payment:', error);
      
      // Show appropriate error message
      if (error.response && error.response.data && error.response.data.message) {
        modal.danger('Verification Error', error.response.data.message);
      } else {
        modal.danger('Verification Error', 'An error occurred while verifying your payment. Please contact support.');
      }
      
      return Promise.reject(error);
    }
  },
  
  /**
   * Handle payment status from ToyyibPay callback
   * @param {Object} urlParams - URL parameters from payment callback
   * @returns {Promise} - Promise with payment status result
   */
  async handlePaymentStatus(urlParams) {
    try {
      const statusId = urlParams.status_id;
      const billCode = urlParams.billcode;
      const orderId = urlParams.order_id;
      const message = urlParams.msg;
      const transactionId = urlParams.transaction_id;
      
      console.log('Payment status callback received:', {
        statusId,
        billCode,
        orderId,
        message,
        transactionId
      });
      
      // Check if payment was successful (status_id=1 means success)
      if (statusId === '1' && message === 'ok') {
        // Verify the payment with backend
        return await this.verifyPayment({
          billcode: billCode,
          order_id: orderId,
          status_id: statusId,
          transaction_id: transactionId
        });
      } else {
        // Payment failed or is pending
        const statusMessage = statusId === '2' ? 'Payment is pending.' : 'Payment was not successful.';
        modal.warning('Payment Status', statusMessage);
        return Promise.reject(new Error(statusMessage));
      }
    } catch (error) {
      console.error('Error handling payment status:', error);
      modal.danger('Payment Error', 'An error occurred while processing your payment status.');
      return Promise.reject(error);
    }
  }
};