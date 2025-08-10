import api from '../utils/api';

/**
 * Service for company-related API calls
 */
const companyService = {
  /**
   * Get a company by ID
   * @param {number} id - Company ID
   * @param {Object} params - Query parameters
   * @returns {Promise} - Promise with company data
   */
  getCompany(id, params = { location_type: 'no' }) {
    return api.get(`/api/companies/${id}`, { params });
  },

  /**
   * Get orders for a company
   * @param {number} id - Company ID
   * @param {Object} params - Query parameters
   * @returns {Promise} - Promise with orders data
   */
  getCompanyOrders(id, params = {}) {
    return api.get(`/api/companies/${id}/orders`, { params });
  },

  /**
   * Get items for a slaughterhouse company
   * @param {number} locationId - Slaughterhouse location ID
   * @param {Object} params - Query parameters
   * @returns {Promise} - Promise with items data
   */
  getSlaughterhouseItems(locationId, params = {}) {
    return api.get(`/api/locations/${locationId}/items`, { params });
  },

  /**
   * Get tasks for a slaughterhouse company
   * @param {number} locationId - Slaughterhouse location ID
   * @param {Object} params - Query parameters
   * @returns {Promise} - Promise with tasks data
   */
  getSlaughterhouseTask(locationId, params = {}) {
    return api.get(`/api/locations/${locationId}/tasks`, { params });
  },

  /**
   * Get deliveries for a logistics company
   * @param {number} id - Company ID
   * @param {Object} params - Query parameters
   * @returns {Promise} - Promise with deliveries data
   */
  getCompanyDeliveries(id, params = {}) {
    return api.get(`/api/companies/${id}/deliveries`, { params });
  },

  /**
   * Get certifications for a company
   * @param {number} id - Company ID
   * @param {Object} params - Query parameters
   * @returns {Promise} - Promise with certifications data
   */
  getCompanyCertifications(id, params = {}) {
    return api.get(`/api/companies/${id}/certifications`, { params });
  },
  /**
   * Get details for a specific order
   * @param {number} orderId - Order ID
   * @param {Object} params - Query parameters
   * @returns {Promise} - Promise with order details
   */
  getOrderDetails(orderId, params = {}) {
    return api.get(`/api/companies/${params.companyId || ''}/orders/${orderId}`, { params });
  },
};

export default companyService;