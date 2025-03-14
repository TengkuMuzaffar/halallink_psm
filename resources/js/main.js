import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';
import axios from 'axios';

// Import Bootstrap
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Set up axios defaults
axios.defaults.baseURL = '/';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Add token to requests if available
axios.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Initialize the app after checking authentication
const initializeApp = async () => {
  // Try to fetch user data if token exists
  if (store.state.token) {
    try {
      await store.dispatch('fetchUser');
    } catch (error) {
      console.error('Failed to fetch user data:', error);
      // Continue app initialization even if user fetch fails
    }
  }

  // Create and mount the Vue app
  const app = createApp(App);
  app.use(router);
  app.use(store);
  app.mount('#app');

  console.log('Vue app initialized');
};

// Start the application
initializeApp();