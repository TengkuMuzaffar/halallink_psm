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
const RegisterEmployee = () => import('../pages/RegisterEmployeePage.vue');
const PoultryManagement = () => import('../pages/PoultryManagement.vue');
const Profile = () => import('../pages/ProfilePage.vue');

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false, redirectIfAuth: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { requiresAuth: false, redirectIfAuth: true }
  },
  {
    path: '/register-employee',
    name: 'RegisterEmployee',
    component: RegisterEmployee,
    meta: { requiresAuth: false, redirectIfAuth: false },
    beforeEnter: (to, from, next) => {
      // Only allow access if formID is present in query params
      if (!to.query.formID) {
        next({ name: 'Login' });
      } else {
        next();
      }
    }
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: () => import('../pages/ForgotPasswordPage.vue'),
    meta: { requiresAuth: false, redirectIfAuth: true }
  },
  // Add reset password route
  {
    path: '/reset-password',
    name: 'ResetPassword',
    component: () => import('../pages/ResetPasswordPage.vue'),
    meta: { requiresAuth: false, redirectIfAuth: false },
    beforeEnter: (to, from, next) => {
      // Only allow access if token and email are present in query params
      if (!to.query.token || !to.query.email) {
        next({ name: 'ForgotPassword' });
      } else {
        next();
      }
    }
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
        path: 'profile',
        name: 'Profile',
        component: Profile,
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
  // Check if user is authenticated
  const isAuthenticated = store.getters.isAuthenticated;
  
  // Redirect authenticated users away from auth pages (login, register, forgot password)
  if (to.meta.redirectIfAuth && isAuthenticated) {
    return next({ name: 'Dashboard' });
  }
  
  // Check if route requires authentication
  if (to.matched.some(record => record.meta.requiresAuth)) {
    // Check if user is logged in
    if (!isAuthenticated) {
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