import api from '../utils/api';

class DashboardService {
  /**
   * Get basic dashboard statistics
   */
  async getStats() {
    try {
      const response = await api.get('/api/dashboard/stats');
      console.log('Dashboard stats:', response);
      return response;
    } catch (error) {
      console.error('Error fetching dashboard stats:', error);
      throw error;
    }
  }

  /**
   * Get broiler sales data for charts
   * @param {string} period - 'month', 'quarter', or 'year'
   */
  async getBroilerSalesData(period = 'month') {
    try {
      const response = await api.get(`/api/dashboard/broiler-sales?period=${period}`);
      console.log('Broiler sales data:', response);
      return response;
    } catch (error) {
      console.error('Error fetching broiler sales data:', error);
      throw error;
    }
  }
  
  /**
   * Get marketplace activity data (order counts)
   * @param {string} period - 'day', 'month', 'quarter', or 'year'
   */
  async getMarketplaceActivity(period = 'month') {
    try {
      const response = await api.get(`/api/dashboard/marketplace/activity?period=${period}`);
      console.log('Marketplace activity data:', response);
      return response;
    } catch (error) {
      console.error('Error fetching marketplace activity data:', error);
      throw error;
    }
  }
}

export default new DashboardService();