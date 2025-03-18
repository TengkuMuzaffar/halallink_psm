import axios from 'axios';
import apiClient from './utils/api';

// Make axios available globally
window.axios = axios;
window.apiClient = apiClient;

// Set default headers
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
