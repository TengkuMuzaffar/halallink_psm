import api from '../utils/api';

const deliveryService = {
  /**
   * Get deliveries with optional location filter
   */
  async getDeliveries(page = 1, perPage = 10, locationID = null) {
    let url = '/api/deliveries';
    const params = { page, per_page: perPage };
    
    if (locationID) {
      url = `/api/deliveries/location/${locationID}`;
    }
    
    return await api.get(url, { params });
  },
  
  /**
   * Get all locations
   */
  async getLocations() {
    return await api.get('/api/locations');
  },
  
  /**
   * Get all drivers
   */
  async getDrivers() {
    try {
      const response = await api.get('/api/deliveries/drivers');
      console.log('Raw API response:', response); // Debug the raw response
      return response.data;
    } catch (error) {
      console.error('Error in getDrivers service:', error);
      return { success: false, message: error.message };
    }
  },
  
  /**
   * Get all vehicles
   */
  async getVehicles() {
    return await api.get('/api/vehicles');
  },
  
  /**
   * Get delivery statistics
   */
  async getDeliveryStats() {
    return await api.get('/api/deliveries/stats');
  },
  
  /**
   * Assign a delivery to a driver and vehicle
   */
  async assignDelivery(data) {
    try {
      const response = await api.post('/api/deliveries/assign', data);
      return response;
    } catch (error) {
      console.error('Error in assignDelivery service:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || error.message || 'Failed to assign delivery' 
      };
    }
  },
  
  /**
   * Get deliveries assigned to a specific driver
   */
  async getAssignedDeliveries(driverID) {
    return await api.get(`/api/drivers/${driverID}/deliveries`);
  },
  
  /**
   * Get location details by ID
   */
  async getLocationDetails(locationID) {
    try {
      const response = await api.get(`/api/locations/${locationID}`);
      return response.data;
    } catch (error) {
      console.error('Error fetching location details:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || error.message || 'Failed to fetch location details' 
      };
    }
  },
};

export default deliveryService;