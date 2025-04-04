import api from './api';
import modal from './modal';

/**
 * Service for marketplace-related API calls
 */
export default {
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
      const response = await api.post('/api/cart/add', {
        itemID: product.id,
        quantity: 1
      });
      
      modal.success('Added to Cart', `${product.name} has been added to your cart.`);
      return response;
    } catch (error) {
      console.error('Error adding to cart:', error);
      modal.danger('Error', 'Failed to add item to cart. Please try again.');
      throw error;
    }
  }
};