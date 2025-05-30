import axios from 'axios';
import router from '../router';
import store from '../store';

// Create a custom axios instance with default config
const apiClient = axios.create({
  baseURL: '/',
  headers: {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json'
  },
  withCredentials: true
});

// Add request interceptor for authentication
apiClient.interceptors.request.use(
  config => {
    // Add token to requests if available
    const token = store.state.token;
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  error => {
    return Promise.reject(error);
  }
);

// Add response interceptor for global error handling
apiClient.interceptors.response.use(
  response => response,
  async error => {
    // Check if we should skip auth redirect for this request
    const skipAuthRedirect = error.config?.skipAuthRedirect;
    
    // Handle 401 Unauthorized errors (session timeout, etc.)
    if (error.response && error.response.status === 401 && !skipAuthRedirect) {
      // Clear any auth data from localStorage
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      
      // Clear store state if a logout action exists
      if (store && store.dispatch) {
        try {
          await store.dispatch('logout', { silent: true });
        } catch (e) {
          console.error('Error dispatching logout:', e);
        }
      }
      
      // Force clear axios default authorization header
      if (apiClient.defaults.headers.common['Authorization']) {
        delete apiClient.defaults.headers.common['Authorization'];
      }
      
      // Only redirect if we're not already on the login page
      const currentPath = window.location.pathname;
      if (currentPath !== '/login') {
        window.location.href = '/login?message=' + encodeURIComponent('Your session has expired. Please log in again.');
      }
    }
    
    return Promise.reject(error);
  }
);

/**
 * Centralized data fetching function with error handling
 * @param {string} url - API endpoint to fetch data from
 * @param {Object} options - Additional options for the request
 * @returns {Promise} - Promise with the response data
 */
export const fetchData = async (url, options = {}) => {
  const { 
    method = 'get', 
    params = {}, 
    data = {}, 
    headers = {},
    showLoader = true,
    timeout = 30000,
    onSuccess = null,
    onError = null
  } = options;
  
  try {
    // Make the request
    const response = await apiClient({
      method,
      url,
      params,
      data,
      headers,
      timeout
    });
    
    // Call success callback if provided
    if (onSuccess && typeof onSuccess === 'function') {
      onSuccess(response.data);
    }
    
    return response.data;
  } catch (error) {
    console.error(`Error fetching data from ${url}:`, error);
    
    // Call error callback if provided
    if (onError && typeof onError === 'function') {
      onError(error);
    }
    
    throw error;
  }
};

/**
 * Fetch multiple resources in parallel
 * @param {Array} requests - Array of request objects with url and options
 * @returns {Promise} - Promise with all response data
 */
export const fetchMultiple = async (requests) => {
  try {
    const promises = requests.map(req => fetchData(req.url, req.options));
    return await Promise.all(promises);
  } catch (error) {
    console.error('Error fetching multiple resources:', error);
    throw error;
  }
};

/**
 * Shorthand for GET requests
 * @param {string} url - API endpoint
 * @param {Object} options - Request options
 * @returns {Promise} - Promise with response data
 */
export const get = (url, options = {}) => {
  return fetchData(url, { ...options, method: 'get' });
};

/**
 * Shorthand for POST requests
 * @param {string} url - API endpoint
 * @param {Object} data - Request payload
 * @param {Object} options - Additional options
 * @returns {Promise} - Promise with response data
 */
export const post = (url, data = {}, options = {}) => {
  return fetchData(url, { ...options, method: 'post', data });
};

/**
 * Shorthand for PUT requests
 * @param {string} url - API endpoint
 * @param {Object} data - Request payload
 * @param {Object} options - Additional options
 * @returns {Promise} - Promise with response data
 */
export const put = (url, data = {}, options = {}) => {
  return fetchData(url, { ...options, method: 'put', data });
};

/**
 * Shorthand for PATCH requests
 * @param {string} url - API endpoint
 * @param {Object} data - Request payload
 * @param {Object} options - Additional options
 * @returns {Promise} - Promise with response data
 */
export const patch = (url, data = {}, options = {}) => {
  return fetchData(url, { ...options, method: 'patch', data });
};

/**
 * Shorthand for DELETE requests
 * @param {string} url - API endpoint
 * @param {Object} options - Additional options
 * @returns {Promise} - Promise with response data
 */
export const del = (url, options = {}) => {
  return fetchData(url, { ...options, method: 'delete' });
};

/**
 * Check if an image URL is valid and accessible
 * @param {string} imageUrl - URL of the image to check
 * @param {number} timeout - Request timeout in milliseconds
 * @returns {Promise<boolean>} - Promise resolving to true if image exists, false otherwise
 */
export const checkImageExists = async (imageUrl, timeout = 5000) => {
  if (!imageUrl) return false;
  
  try {
    // Create a new instance without auth headers to avoid CORS issues with image requests
    const imageClient = axios.create({
      timeout: timeout,
      responseType: 'blob'
    });
    
    const response = await imageClient.head(imageUrl);
    
    // Check if response is successful and content type is an image
    return response.status === 200 && 
           response.headers['content-type'] && 
           response.headers['content-type'].startsWith('image/');
  } catch (error) {
    console.log(`Image not found or inaccessible: ${imageUrl}`);
    return false;
  }
};

// Export default object with all methods
export default {
  fetchData,
  fetchMultiple,
  get,
  post,
  put,
  patch,
  delete: del,
  checkImageExists,
  client: apiClient
};