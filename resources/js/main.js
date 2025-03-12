import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';

// Import Bootstrap
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Create and mount the Vue app
const app = createApp(App);
app.use(router);
app.use(store);
app.mount('#app');

console.log('Vue app initialized');