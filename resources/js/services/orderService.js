import { fetchData } from '../utils/api';

export const orderService = {
  /**
   * Fetch locations based on company type (Step 1)
   * @param {Object} params - Query parameters for filtering
   * @returns {Promise} - Promise with locations data
   */
  fetchLocationsByCompanyType(params = {}) {
    return fetchData('/api/orders/location/company/type', { params });
  },
  
  /**
   * Fetch orders for a specific location (Step 2)
   * @param {number} locationID - Location ID
   * @param {Object} params - Query parameters including pagination and filters
   * @returns {Promise} - Promise with location orders data
   */
  fetchOrdersByLocationID(locationID, params = {}) {
    return fetchData(`/api/orders/location/${locationID}`, { params });
  },
  
  /**
   * Fetch orders with optional filters and pagination (legacy - keep for compatibility)
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
  },
  
  /**
   * Generate QR code for location
   * @param {number} locationID - Location ID
   * @param {number} companyID - Company ID
   * @returns {Promise} - Promise with QR code data
   */
  generateLocationQR(locationID, companyID) {
    return fetchData(`/api/qrcode/process/${locationID}/${companyID || 0}`);
  }
};