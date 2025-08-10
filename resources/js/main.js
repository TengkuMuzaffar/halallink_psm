import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';
import api from './utils/api';

// Import Bootstrap CSS and JS properly
import 'bootstrap/dist/css/bootstrap.min.css';
import * as bootstrap from 'bootstrap';

// Import Bootstrap Icons CSS
import 'bootstrap-icons/font/bootstrap-icons.css';

// Make bootstrap available globally
window.bootstrap = bootstrap;

// Add global error handler for unhandled promise rejections
window.addEventListener('unhandledrejection', event => {
  console.error('Unhandled Promise Rejection:', event.reason);
  // You could also log to a monitoring service here
});

// Initialize the app after checking authentication
const initializeApp = async () => {
  // Try to fetch user data if token exists with a timeout
  if (store.state.token) {
    try {
      // Add timeout to prevent hanging promises
      const fetchUserPromise = store.dispatch('fetchUser');
      const timeoutPromise = new Promise((_, reject) => 
        setTimeout(() => reject(new Error('User fetch timeout')), 10000)
      );
      
      await Promise.race([fetchUserPromise, timeoutPromise]);
    } catch (error) {
      console.error('Failed to fetch user data:', error);
      // Continue app initialization even if user fetch fails
    }
  }

  // Create and mount the Vue app
  const app = createApp(App);
  
  // Make API available globally in components
  app.config.globalProperties.$api = api;
  
  // Add bootstrap to Vue global properties
  app.config.globalProperties.$bootstrap = bootstrap;
  
  // Add global error handler
  app.config.errorHandler = (err, vm, info) => {
    console.error('Vue Error:', err);
    console.error('Component:', vm);
    console.error('Info:', info);
  };
  
  app.use(router);
  app.use(store);
  app.mount('#app');

  console.log('Vue app initialized with enhanced API framework');
};

// Start the application with error handling
initializeApp().catch(error => {
  console.error('Failed to initialize application:', error);
  // You might want to show a user-friendly error message here
  document.body.innerHTML = '<div style="text-align: center; padding: 20px;">An error occurred while loading the application. Please refresh the page or try again later.</div>';
});