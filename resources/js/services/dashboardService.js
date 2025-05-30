import api from '../utils/api';

class DashboardService {
  /**
   * Get basic dashboard statistics
   */
  async getStats() {
    try {
      const response = await api.get('/api/dashboard/stats');
      return response.data;
    } catch (error) {
      console.error('Error fetching dashboard stats:', error);
      throw error;
    }
  }

  /**
   * Get top performing companies
   */
  async getTopPerformers() {
    try {
      const response = await api.get('/api/dashboard/top-performers');
      return response.data;
    } catch (error) {
      console.error('Error fetching top performers:', error);
      throw error;
    }
  }

  /**
   * Get comprehensive performance metrics
   */
  async getPerformanceMetrics() {
    try {
      const response = await api.get('/api/dashboard/performance-metrics');
      return response.data;
    } catch (error) {
      console.error('Error fetching performance metrics:', error);
      throw error;
    }
  }

  /**
   * Get industry benchmarks
   */
  async getIndustryBenchmarks() {
    try {
      const response = await api.get('/api/dashboard/industry-benchmarks');
      return response.data;
    } catch (error) {
      console.error('Error fetching industry benchmarks:', error);
      throw error;
    }
  }

  /**
   * Get company performance by type
   */
  async getCompanyPerformanceByType(companyType) {
    try {
      const response = await api.get(`/api/dashboard/performance-by-type/${companyType}`);
      return response.data;
    } catch (error) {
      console.error(`Error fetching performance for ${companyType}:`, error);
      throw error;
    }
  }
}

export default new DashboardService();