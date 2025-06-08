import api from '../utils/api';
import modal from '../utils/modal';
import formatter from '../utils/formatter';

export const itemService = {
    // Event listeners for item updates
    itemUpdateListeners: [],

    /**
     * Register a callback for item updates
     * @param {Function} callback - Function to call when items are updated
     */
    onItemUpdate(callback) {
        if (typeof callback === 'function') {
            this.itemUpdateListeners.push(callback);
        }
    },

    /**
     * Notify all listeners about item updates
     * @param {Object} itemData - Updated item data
     */
    notifyItemUpdate(itemData) {
        this.itemUpdateListeners.forEach(callback => {
            try {
                callback(itemData);
            } catch (error) {
                console.error('Error in item update callback:', error);
            }
        });
    },

    /**
     * Fetch items with filters and pagination
     * @param {Object} params - Query parameters
     * @returns {Promise} - Promise with response data
     */
    async fetchItems(params = {}) {
        try {
            const response = await api.get('/api/items', { params });
            
            // Properly log the response data as JSON
            // console.log("Items response:", JSON.stringify(response, null, 2));
            
            // Check if we have data and extract it correctly
            if (response?.data) {
                return {
                    items: response.data,
                    pagination: response.pagination || this.getDefaultPagination()
                };
            } else {
                return { 
                    items: [], 
                    pagination: this.getDefaultPagination() 
                };
            }
        } catch (error) {
            console.error('Error fetching items:', error);
            throw error;
        }
    },

    /**
     * Fetch item statistics
     * @param {Object} params - Query parameters
     * @returns {Promise} - Promise with stats data
     */
    async fetchItemStats(params = {}) {
        try {
            const response = await api.get('/api/items/stats', { params });
            return response || this.getDefaultItemStats();
        } catch (error) {
            console.error('Error fetching item stats:', error);
            throw error;
        }
    },
    
    /**
     * Fetch poultry types for dropdown
     * @returns {Promise} - Promise with poultry types
     */
    async fetchPoultryTypes() {
        try {
            const response = await api.get('/api/poultries');
            return response?.data || [];
        } catch (error) {
            console.error('Error fetching poultry types:', error);
            throw error;
        }
    },
    
    /**
     * Fetch locations for filtering
     * @returns {Promise} - Promise with locations data
     */
    async fetchLocations() {
        try {
            // Fetch company locations
            const companyResponse = await api.get('/api/items/company/locations');
            
            // Fetch slaughterhouse locations
            const slaughterhouseResponse = await api.get('/api/items/slaughterhouse/locations');
            
            return {
                companyLocations: companyResponse?.data || [],
                slaughterhouseLocations: slaughterhouseResponse?.data || []
            };
        } catch (error) {
            console.error('Error fetching locations:', error);
            // Use modal dialog instead of toast
            modal.danger('Error', 'Failed to load locations. Please try again.');
            return { companyLocations: [], slaughterhouseLocations: [] };
        }
    },

    /**
     * Save or update an item
     * @param {Object} itemData - Item data to save
     * @param {boolean} isEditing - Whether this is an edit operation
     * @returns {Promise} - Promise with response data
     */
    async saveItem(itemData, isEditing = false) {
        console.log('itemService.saveItem called with:', itemData);
        console.log('isEditing:', isEditing);
        
        const formData = new FormData();
        
        console.log('Processing itemData entries:');
        for (const [key, value] of Object.entries(itemData)) {
            console.log(`  ${key}:`, value, `(type: ${typeof value})`);
            if (key !== 'item_image' && value !== null) {
                formData.append(key, value);
                console.log(`    Added ${key} to FormData`);
            }
        }
        
        if (itemData.item_image instanceof File) {
            formData.append('item_image', itemData.item_image);
            console.log('Added item_image file to FormData:', itemData.item_image.name);
        } else {
            console.log('item_image is not a File instance:', itemData.item_image);
        }
        
        console.log('Final FormData entries:');
        for (let pair of formData.entries()) {
            console.log(`  ${pair[0]}:`, pair[1]);
        }
    
        try {
            const response = await (isEditing ? 
                api.post(`/api/items/${itemData.itemID}`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                }) :
                api.post('/api/items', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                })
            );
    
            // Notify listeners about the update
            this.notifyItemUpdate(response.data);
            
            // Show success modal instead of toast
            modal.success(
                isEditing ? 'Item Updated' : 'Item Created',
                isEditing ? 'Item has been updated successfully.' : 'Item has been created successfully.'
            );
            
            return response;
        } catch (error) {
            console.error('Error saving item:', error);
            console.error('Error response:', error.response?.data);
            
            // Handle validation errors (422 Unprocessable Content)
            if (error.response?.status === 422 && error.response?.data?.errors) {
                // Format validation errors for display
                const errorMessages = Object.values(error.response.data.errors)
                    .flat()
                    .join('\n• ');
                
                // Show validation errors in a modal
                modal.danger(
                    'Validation Error',
                    `Please correct the following errors:\n\n• ${errorMessages}`
                );
            } else {
                // Show general error modal
                modal.danger(
                    `Failed to ${isEditing ? 'Update' : 'Create'} Item`,
                    error.response?.data?.message || error.message || 'An unexpected error occurred.'
                );
            }
            throw error;
        }
    },

    /**
     * Delete an item
     * @param {number|string} itemID - ID of item to delete
     * @returns {Promise} - Promise with response data
     */
    async deleteItem(itemID) {
        try {
            const response = await api.delete(`/api/items/${itemID}`);
            
            // Notify listeners about the deletion
            this.notifyItemUpdate({ deleted: itemID });
            
            // Show success modal instead of toast
            modal.success(
                'Item Deleted',
                'Item has been deleted successfully.'
            );
            
            return response;
        } catch (error) {
            console.error('Error deleting item:', error);
            
            // Show error modal instead of toast
            modal.danger(
                'Delete Failed',
                error.response?.data?.message || error.message || 'Failed to delete item.'
            );
            throw error;
        }
    },

    // Format functions
    formatCurrency: (value, shorten = false) => formatter.formatCurrency(value, 'MYR', 'en-MY', shorten),
    formatDate: (dateString) => formatter.formatDate(dateString, 'medium'),
    formatLargeNumber: (value) => formatter.formatLargeNumber(value),
    formatPrice: (price) => Number(price).toFixed(2),

    // Default state getters
    getDefaultItemForm() {
        return {
            itemID: null,
            poultryID: '',
            locationID: '',
            slaughterhouse_locationID: '',
            measurement_type: 'kg',
            measurement_value: '',
            price: '',
            stock: '',
            item_image: null
        };
    },

    getDefaultItemStats() {
        return {
            total_items: 0,
            total_kg: 0,
            total_units: 0,
            total_value: 0
        };
    },

    getDefaultPagination() {
        return {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
            from: 1,
            to: 0
        };
    }
};