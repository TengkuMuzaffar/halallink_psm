import { createRouter, createWebHistory } from 'vue-router';
import store from '../store';

// Layouts
import MainLayout from '../layouts/MainLayout.vue';

// Pages
import Login from '../pages/LoginPage.vue';
import Dashboard from '../pages/Dashboard.vue';
import EmployeeManagement from '../pages/EmployeeManagement.vue';
import NotFound from '../pages/NotFound.vue';
import Unauthorized from '../pages/Unauthorized.vue';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: { name: 'Dashboard' }
      },
      {
        path: 'dashboard',
        name: 'Dashboard',  // This should be 'Dashboard' with capital D
        component: Dashboard,
        meta: { requiresAuth: true }
      },
      {
        path: 'employees',
        name: 'EmployeeManagement',
        component: EmployeeManagement,
        meta: { 
          requiresAuth: true,
          requiresRole: 'admin'
        }
      }
    ]
  },
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: Unauthorized
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFound
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Navigation guard for authentication and role-based access
router.beforeEach(async (to, from, next) => {
  // Check if route requires authentication
  if (to.matched.some(record => record.meta.requiresAuth)) {
    // Check if user is logged in
    if (!store.getters.isAuthenticated) {
      return next({ name: 'Login', query: { redirect: to.fullPath } });
    }
    
    // Check if route requires specific role
    if (to.meta.requiresRole && store.getters.user.role !== to.meta.requiresRole) {
      return next({ name: 'Unauthorized' });
    }
    
    // Check if route requires specific company type
    if (to.meta.requiresCompanyType && store.getters.user.company?.company_type !== to.meta.requiresCompanyType) {
      return next({ name: 'Unauthorized' });
    }
  }
  
  // If route doesn't require auth or user is authenticated with correct role
  next();
});

export default router;