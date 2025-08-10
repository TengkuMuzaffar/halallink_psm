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
  async getCreatedDeliveries(page = 1, perPage = 3) {
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
   * @param {String} scheduled_date - Date to check driver availability
   * @returns {Promise} - API response
   */
  async getDrivers(scheduled_date) {
    return fetchData('/api/users/get/drivers', {
      params: {
        scheduled_date
      }
    });
  },
  
  /**
   * Get vehicles
   * @param {String} scheduled_date - Date to check vehicle availability
   * @returns {Promise} - API response
   */
  async getVehicles(scheduled_date) {
    return fetchData('api/deliveries/get/vehicles', {
      params: {
        scheduled_date
      }
    });
  },

  /**
   * Assign a single trip to a delivery
   * @param {Object} data - Assignment data (deliveryID, tripID)
   * @returns {Promise} - API response
   */
  async assignSingleTrip(data) {
    return fetchData('/api/deliveries/assign-trip', {
      method: 'POST',
      data
    });
  },

  /**
   * Update delivery status
   * @param {Object} data - Status update data
   * @returns {Promise} - API response
   */
  async updateDeliveryStatus(deliveryID, data) {
    return fetchData(`/api/deliveries/${deliveryID}/start`, {
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
  
  /**
   * Get specific order information for assignment
   * @param {Object} params - Parameters including locationID and orderID
   * @returns {Promise} - Promise with order data
   */
  async getOrderForAssignment(params = {}) {
    const { locationID, orderID } = params;
    
    if (!locationID || !orderID) {
      return {
        success: false,
        message: 'Location ID and Order ID are required'
      };
    }
    
    try {
      // First get all trips to find the specific order
      const response = await this.getTrips({ locationID });
      
      if (!response.success || !response.data) {
        return {
          success: false,
          message: 'Failed to fetch order data'
        };
      }
      
      // Find the location data
      const locationData = response.data.find(loc => loc.locationID == locationID);
      if (!locationData || !locationData.orders) {
        return {
          success: false,
          message: 'Location or orders not found'
        };
      }
      
      // Find the specific order
      const orderData = locationData.orders[orderID];
      if (!orderData) {
        return {
          success: false,
          message: 'Order not found'
        };
      }
      
      // Return the order data with success flag
      return {
        success: true,
        data: {
          locationID,
          locationAddress: locationData.company_address,
          orderID,
          orderData
        }
      };
    } catch (error) {
      console.error('Error fetching order for assignment:', error);
      return {
        success: false,
        message: 'An error occurred while fetching order data'
      };
    }
  },
  
  /**
   * Assign a single trip to a delivery, and update delivery details.
   * @param {Object} data - Assignment data (deliveryID, tripID, userID, vehicleID, scheduledDate)
   * @returns {Promise} - API response
   */
  async assignDelivery(data) {
    return fetchData('/api/deliveries/assign', {
      method: 'POST',
      data
    });
  },

  /**
   * Create a new delivery record.
   * @param {Object} deliveryData - Data for creating the delivery (e.g., phase, status)
   * @returns {Promise} - API response
   */
  async createDelivery(deliveryData) {
    return fetchData('/api/deliveries', { // Assuming POST /api/deliveries creates a delivery
      method: 'POST',
      data: deliveryData,
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
  
  /**
   * Get deliveries for execution
   * @param {Object} params - Query parameters
   * @returns {Promise} - API response
   */
  async getExecutionDeliveries(filters = {}) {
    return fetchData('/api/deliveries/get/execution', {
      params: {
        statusFilter: filters.status || '',
        dateFilter: filters.date || '',
        searchTerm: filters.searchTerm || '', // Add searchTerm parameter
      }
    });
  },
  
  /**
   * Start a delivery
   * @param {Number} deliveryID - Delivery ID
   * @returns {Promise} - API response
   */
  async startDelivery(deliveryID) {
    return fetchData(`/api/deliveries/${deliveryID}/start`, {
      method: 'post',
      data: {
        start_timestamp: new Date().toISOString()
      }
    });
  },

  /**
   * Cancel a delivery (unassign all associated trips)
   * @param {Number} deliveryID - Delivery ID
   * @returns {Promise} - API response
   */
  async cancelDelivery(deliveryID) {
    return fetchData(`/api/deliveries/${deliveryID}/cancel`, {
      method: 'post'
    });
  },

  /**
   * Delete a delivery (permanently remove the delivery record)
   * @param {Number} deliveryID - Delivery ID
   * @returns {Promise} - API response
   */
  async deleteDelivery(deliveryID) {
    return fetchData(`/api/deliveries/${deliveryID}/delete`, {
      method: 'delete'
    });
  },
};

export default deliveryService;