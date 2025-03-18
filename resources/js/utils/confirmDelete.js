import { fetchData } from './api';

/**
 * Handle delete confirmation and API call
 * @param {Object} options - Configuration options
 * @returns {Promise} - Promise with the delete operation result
 */
export const confirmDelete = async ({
  item,                       // The item to delete
  itemName,                   // Display name of the item (e.g., company.company_name)
  itemType = 'item',          // Type of item (e.g., 'company', 'employee')
  deleteUrl,                  // API endpoint for deletion
  onSuccess = null,           // Success callback
  onError = null,             // Error callback
  confirmMessage = null,      // Custom confirmation message
  successMessage = null,      // Custom success message
  customConfirm = null        // Custom confirmation function (e.g., for a modal)
}) => {
  // Generate default messages if not provided
  const defaultConfirmMessage = `Are you sure you want to delete ${itemName || `this ${itemType}`}?`;
  const message = confirmMessage || defaultConfirmMessage;
  
  // Use custom confirm function or browser's confirm
  const confirmed = customConfirm ? await customConfirm(message) : window.confirm(message);
  
  if (!confirmed) {
    return false;
  }
  
  try {
    // Make the delete request
    await fetchData(deleteUrl, {
      method: 'delete',
      onSuccess: (data) => {
        // Show success message
        if (successMessage) {
          alert(successMessage);
        }
        
        // Call success callback if provided
        if (onSuccess && typeof onSuccess === 'function') {
          onSuccess(data);
        }
      },
      onError
    });
    
    return true;
  } catch (error) {
    console.error(`Error deleting ${itemType}:`, error);
    alert(`Failed to delete ${itemType}. Please try again.`);
    return false;
  }
};