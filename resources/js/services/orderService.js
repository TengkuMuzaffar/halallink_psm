import { fetchData } from '../utils/api';

export const orderService = {
  /**
   * Fetch orders with optional filters and pagination
   * @param {Object} params - Query parameters
   * @returns {Promise} - Promise with response data
   */
  fetchOrders(params = {}) {
    return fetchData('/api/orders', { params });
  },
  
  /**
   * Fetch order statistics
   * @returns {Promise} - Promise with order stats
   */
  fetchOrderStats() {
    return fetchData('/api/orders/stats');
  },
  
  /**
   * Fetch order with checkpoints
   * @param {number} orderId - Order ID
   * @returns {Promise} - Promise with order data including checkpoints
   */
  fetchOrderWithCheckpoints(orderId) {
    return fetchData(`/api/orders/${orderId}/checkpoints`);
  },
  
  /**
   * Get order by ID
   * @param {number} id - Order ID
   * @returns {Promise} - Promise with order data
   */
  getOrderById(id) {
    return fetchData(`/api/orders/${id}`);
  },
  
  /**
   * Create a new order
   * @param {Object} orderData - Order data
   * @returns {Promise} - Promise with created order
   */
  createOrder(orderData) {
    return fetchData('/api/orders', {
      method: 'post',
      data: orderData
    });
  },
  
  /**
   * Update an existing order
   * @param {number} id - Order ID
   * @param {Object} orderData - Updated order data
   * @returns {Promise} - Promise with updated order
   */
  updateOrder(id, orderData) {
    return fetchData(`/api/orders/${id}`, {
      method: 'put',
      data: orderData
    });
  }
};