import { createRouter, createWebHistory } from 'vue-router';
import authMiddleware from '../middleware/auth';

// Layouts
import AuthLayout from '../layouts/AuthLayout.vue';
import MainLayout from '../layouts/MainLayout.vue';

// Pages
import LoginPage from '../pages/LoginPage.vue';  // Updated import
import Dashboard from '../pages/Dashboard.vue';
import RegisterPage from '../pages/RegisterPage.vue';

const routes = [
  {
    path: '/',
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'login',
        component: LoginPage,  // Updated component
        meta: { requiresGuest: true }
      },
      {
        path: 'register',
        name: 'register',
        component: RegisterPage,
        meta: { requiresGuest: true }
      }
    ]
  },
  {
    path: '/dashboard',
    component: MainLayout,
    children: [
      {
        path: '',
        name: 'dashboard',
        component: Dashboard,
        meta: { requiresAuth: true }
      }
    ]
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Apply the auth middleware to all routes
router.beforeEach(authMiddleware);

export default router;