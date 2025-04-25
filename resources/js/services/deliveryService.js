import { fetchData } from '../utils/api';

const deliveryService = {
  /**
   * Get deliveries for assignment
   * @param {Object} params - Query parameters
   * @returns {Promise} - API response
   */
  async getTrips(params = {}) {
    return fetchData('/api/deliveries/trips', {
      params
    });
  },
  
  /**
   * Get created deliveries
   * @param {Number} page - Page number
   * @param {Number} perPage - Items per page
   * @returns {Promise} - API response
   */
  async getCreatedDeliveries(page = 1, perPage = 10) {
    return fetchData('/api/deliveries/created', {
      params: {
        page,
        per_page: perPage
      }
    });
  },
  
  /**
   * Get delivery statistics
   * @returns {Promise} - API response
   */
  async getDeliveryStats() {
    return fetchData('/api/deliveries/stats');
  },
  
  /**
   * Get locations
   * @returns {Promise} - API response
   */
  async getLocations() {
    return fetchData('/api/locations');
  },
  
  /**
   * Get drivers (users with driver role)
   * @returns {Promise} - API response
   */
  async getDrivers() {
    return fetchData('/api/users/drivers');
  },
  
  /**
   * Get vehicles
   * @returns {Promise} - API response
   */
  async getVehicles() {
    return fetchData('/api/vehicles');
  },
  
  /**
   * Assign delivery
   * @param {Object} data - Assignment data
   * @returns {Promise} - API response
   */
  async assignDelivery(data) {
    return fetchData('/api/deliveries/assign', {
      method: 'post',
      data
    });
  },
  
  /**
   * Update delivery status
   * @param {Object} data - Status update data
   * @returns {Promise} - API response
   */
  async updateDeliveryStatus(deliveryID, data) {
    return fetchData(`/api/deliveries/${deliveryID}/status`, {
      method: 'put',
      data
    });
  },
  
  /**
   * Get delivery details
   * @param {Number} deliveryID - Delivery ID
   * @returns {Promise} - API response
   */
  async getDeliveryDetails(deliveryID) {
    return fetchData(`/api/deliveries/${deliveryID}`);
  },
  
  /**
   * Create a new delivery
   * @param {Object} data - Delivery data (fromLocation, toLocation, scheduledDate)
   * @returns {Promise} - API response
   */
  async createDelivery(data) {
    return fetchData('/api/deliveries/create', {
      method: 'post',
      data
    });
  },
};

export default deliveryService;