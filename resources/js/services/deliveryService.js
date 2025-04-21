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
    return await api.get('/api/drivers');
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
   * Assign a delivery
   */
  async assignDelivery(assignmentData) {
    return await api.post('/api/deliveries/assign', assignmentData);
  },
  
  /**
   * Get deliveries assigned to a specific driver
   */
  async getAssignedDeliveries(driverID) {
    return await api.get(`/api/drivers/${driverID}/deliveries`);
  }
};

export default deliveryService;