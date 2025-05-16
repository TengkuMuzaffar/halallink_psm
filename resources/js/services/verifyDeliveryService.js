import { fetchData, postData } from '../utils/api';

export const verifyDeliveryService = {
  // Fetch verifications for a specific delivery and location
  async getVerifications(deliveryID, locationID) {
    try {
      const response = await fetchData(`/api/verifications?deliveryID=${deliveryID}&locationID=${locationID}`);
      return response;
    } catch (error) {
      console.error('Error fetching verifications:', error);
      throw error;
    }
  },
  
  // Fetch delivery information
  async getDeliveryInfo(deliveryID) {
    try {
      const response = await fetchData(`/api/deliveries/${deliveryID}`);
      return response;
    } catch (error) {
      console.error('Error fetching delivery info:', error);
      throw error;
    }
  },
  
  // Fetch checkpoint details
  async getCheckpointDetails(checkpointID) {
    try {
      const response = await fetchData(`/api/checkpoints/${checkpointID}`);
      return response;
    } catch (error) {
      console.error('Error fetching checkpoint details:', error);
      throw error;
    }
  },
  
  // Update verification status
  async updateVerification(verifyID, data) {
    try {
      const response = await postData(`/api/verifications/${verifyID}`, data);
      return response;
    } catch (error) {
      console.error('Error updating verification:', error);
      throw error;
    }
  },
  
  // Complete all verifications for a delivery at a location
  async completeVerifications(deliveryID, locationID) {
    try {
      const response = await postData(`/api/deliveries/${deliveryID}/complete-verification`, {
        locationID: locationID
      });
      return response;
    } catch (error) {
      console.error('Error completing verifications:', error);
      throw error;
    }
  }
};