import api from '../utils/api';

export const verifyDeliveryService = {
  // Get delivery information
  getDeliveryInfo: async (deliveryID) => {
    try {
      const response = await api.get(`/api/deliveries/${deliveryID}`);
      return response;
    } catch (error) {
      console.error('Error fetching delivery info:', error);
      return {
        status: 'error',
        message: error.message || 'Failed to fetch delivery information'
      };
    }
  },
  
  // Get verifications for a delivery at a specific location
  getVerifications: async (deliveryID, locationID) => {
    try {
      const response = await api.get(`/api/verifications?deliveryID=${deliveryID}&locationID=${locationID}`);
      return response;
    } catch (error) {
      console.error('Error fetching verifications:', error);
      return {
        status: 'error',
        message: error.message || 'Failed to fetch verification data'
      };
    }
  },
  
  // Get checkpoint details
  getCheckpointDetails: async (checkpointID) => {
    try {
      const response = await api.get(`/api/checkpoints/${checkpointID}`);
      return response;
    } catch (error) {
      console.error('Error fetching checkpoint details:', error);
      return {
        status: 'error',
        message: error.message || 'Failed to fetch checkpoint details'
      };
    }
  },
  
  // Update verification
  updateVerification: async (verifyID, data) => {
    try {
      const response = await api.put(`/api/verifications/${verifyID}`, data);
      return response;
    } catch (error) {
      console.error('Error updating verification:', error);
      return {
        status: 'error',
        message: error.message || 'Failed to update verification'
      };
    }
  },
  
  // Complete all verifications for a delivery at a location
  completeVerifications: async (deliveryID, locationID) => {
    try {
      const response = await api.post(`/api/verifications/complete/${locationID}/${deliveryID}`);
      return response;
    } catch (error) {
      console.error('Error completing verifications:', error);
      return {
        status: 'error',
        message: error.message || 'Failed to complete verifications'
      };
    }
  }
};

export default verifyDeliveryService;