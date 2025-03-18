import { createRouter, createWebHistory } from 'vue-router';
import store from '../store';
import { fetchData } from '../utils/api';

// Layouts
import MainLayout from '../layouts/MainLayout.vue';

// Use lazy loading for page components to avoid circular dependencies
const Login = () => import('../pages/LoginPage.vue');
const Dashboard = () => import('../pages/Dashboard.vue');
const EmployeeManagement = () => import('../pages/EmployeeManagement.vue');
const NotFound = () => import('../pages/NotFound.vue');
const Unauthorized = () => import('../pages/Unauthorized.vue');
const CompanyManagement = () => import('../pages/CompanyManagement.vue');
const Register = () => import('../pages/RegisterPage.vue');
const PoultryManagement = () => import('../pages/PoultryManagement.vue');

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
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
      },
      {
        path: 'companies',
        name: 'CompanyManagement',
        component: CompanyManagement,
        meta: {
          requiresAuth: true,
          requiresRole: 'admin',
          requiresCompanyType: 'admin'
        }
      },
      {
        path: 'poultries',
        name: 'PoultryManagement',
        component: PoultryManagement,
        meta: {
          requiresAuth: true,
          requiresRole: 'admin',
          requiresCompanyType: 'admin'
        }
      },
      // Remove duplicate companies route
      // {
      // path: 'companies',
      // name: 'CompanyManagement',
      // component: CompanyManagement,
      // meta: {
      //   requiresAuth: true,
      //   requiresRole: 'admin',
      //   requiresCompanyType: 'admin'
      // }
      // }
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
    
    // If we have a token but no user data, fetch the user
    if (!store.getters.user && store.getters.token) {
      try {
        await store.dispatch('fetchUser');
      } catch (error) {
        console.error('Failed to fetch user data in router guard:', error);
        return next({ name: 'Login', query: { redirect: to.fullPath } });
      }
    }
    
    const user = store.getters.user;
    
    // Check if route requires specific role
    if (to.meta.requiresRole && user && user.role !== to.meta.requiresRole) {
      return next({ name: 'Unauthorized' });
    }
    
    // Check if route requires specific company type
    if (to.meta.requiresCompanyType) {
      // Check company type in different possible locations
      const companyType = 
        (user.company && user.company.company_type) || 
        user.company_type;
        
      if (companyType !== to.meta.requiresCompanyType) {
        return next({ name: 'Unauthorized' });
      }
    }
  }
  
  // If route doesn't require auth or user is authenticated with correct role
  next();
});

export default router;