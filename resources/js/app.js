import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';
// No need to import axios directly here as we're using the api.js utility

const app = createApp(App);
app.use(router);
app.use(store);
app.mount('#app');
