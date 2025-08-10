import api from '../utils/api';
import modal from '../utils/modal';

const certificationService = {
  /**
   * Fetch certifications for the authenticated user's company
   * @returns {Promise} - Promise with certifications data
   */
  async fetchCertifications() {
    try {
      const response = await api.get('/api/profile/certifications');
      return response.certifications || [];
    } catch (error) {
      console.error('Error fetching certifications:', error);
      modal.danger('Error', 'Failed to load certifications. Please try again.');
      return [];
    }
  },
  
  /**
   * Update certifications for the authenticated user's company
   * @param {FormData} formData - Form data with certifications
   * @returns {Promise} - Promise with response data
   */
  async updateCertifications(formData) {
    try {
      const response = await api.post('/api/profile/certifications', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
      return response;
    } catch (error) {
      console.error('Error updating certifications:', error);
      throw error;
    }
  },
  
  /**
   * Delete a certification
   * @param {number} certID - Certification ID
   * @returns {Promise} - Promise with response data
   */
  async deleteCertification(certID) {
    try {
      const response = await api.delete(`/api/profile/certifications/${certID}`);
      return response;
    } catch (error) {
      console.error('Error deleting certification:', error);
      throw error;
    }
  }
};

export default certificationService;