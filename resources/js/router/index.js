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
const VerifyEmail = () => import('../pages/VerifyEmailPage.vue');
const ItemManagement = () =>  import('../pages/ItemManagement.vue');
const Marketplace = () => import('../pages/MarketplacePage.vue');
// Add payment status page
const PaymentStatus = () => import('../pages/PaymentStatusPage.vue');
const VehicleManagement = () => import('../pages/VehicleManagement.vue');
const DeliveryPage = () => import('../pages/DeliveryPage.vue'); // Import the new page
const OrderManagement = () =>  import('../pages/OrderManagementPage.vue');
const ReportPage = () => import('../pages/ReportPage.vue'); // Import the Report page

// Import the verification page
const VerifyDeliveryPage = () => import('../pages/VerifyDeliveryPage.vue');

// Add this import near the top with other page imports
const CompanyProfile = () => import('../pages/CompanyProfilePage.vue');

const routes = [
  {
    path: '/test/after-payment/:id',
    name: 'TestAfterPayment',
    beforeEnter: (to, from, next) => {
      // Redirect to the actual API endpoint
      window.location.href = `/test/after-payment/${to.params.id}`;
    }
  },
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
    path: '/verify-email',
    name: 'VerifyEmail',
    component: VerifyEmail,
    meta: { requiresAuth: false }
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
        name: 'Root',
        beforeEnter: (to, from, next) => {
          const user = store.getters.user;
          const companyType = user?.company?.company_type;
          
          if (companyType) {
            switch (companyType) {
              case 'admin':
                next({ name: 'CompanyManagement' });
                break;
              case 'logistic':
                next({ name: 'DeliveryManagement' });
                break;
              case 'broiler':
                next({ name: 'OrderManagement' });
                break;
              case 'sme':
                next({ name: 'Marketplace' });
                break;
              case 'slaughterhouse':
                next({ name: 'OrderManagement' });
                break;
              default:
                // If we still need Dashboard as a fallback
                next({ name: 'Dashboard' });
            }
          } else {
            // Fallback if no company type
            next({ name: 'Profile' });
          }
        }
      },
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: Dashboard,
        meta: {
          requiresAuth: true,
          requiresCompanyType: 'admin'
        }
      },
      {
        path: 'profile',
        name: 'Profile',
        component: Profile,
        meta: { title: 'My Profile' }
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
          requiresCompanyType: 'admin'
        }
      },
      {
        path: 'companies/:id',
        name: 'CompanyProfile',
        component: CompanyProfile,
        meta: {
          requiresAuth: true,
          requiresCompanyType: 'admin',
          title: 'Company Profile'
        }
      },
      {
        path: 'poultries',
        name: 'PoultryManagement',
        component: PoultryManagement,
        meta: {
          requiresAuth: true,
          requiresCompanyType: 'admin'
        }
      },
      {
        path: 'items',
        name: 'ItemManagement',
        component: ItemManagement,
        meta: {
          requiresAuth: true,
          title: 'Item Management',
          requiresCompanyType: 'broiler'
        }
      },
      {
        path: 'marketplace',
        name: 'Marketplace',
        component: Marketplace,
        meta: {
          requiresAuth: true,
          requiresCompanyType: 'sme',
          title: 'Marketplace'
        }
      },
      {
        path: '/tasks',
        name: 'TaskManagement',
        component: () => import('../pages/TaskManagementPage.vue'),
        meta: {
          requiresAuth: true,
          requiresCompanyType: 'slaughterhouse', // Only for slaughterhouse
          title: 'Task Management'
        }
      },
      {
        path: '/vehicles',
        name: 'Vehicles',
        component: VehicleManagement,
        meta: {
            requiresAuth: true,
            requiresCompanyType: 'logistic',
            title: 'Vehicle Management'
          }
      },
      {
        path: '/payment-status',
        name: 'PaymentStatus',
        component: PaymentStatus,
        meta: { 
          requiresAuth: true, 
          title: 'Payment Status' 
        }
      },
      {
        path: '/deliveries/:selectionID?',
        name: 'DeliveryManagement',
        component: DeliveryPage,
        meta: {
          requiresAuth: true,
          requiresCompanyType: 'logistic', // Assuming logistic role/company handles this
          title: 'Delivery Management'
        }
      },
      {
        path: 'report',
        name: 'Report',
        component: ReportPage,
        meta: { 
          requiresAuth: true,
          requiresCompanyTypes: ['admin', 'sme'],
          title: 'Reports'
        }
      },
      {
        path: '/awb/:cartId',
        name: 'AWBGenerate',
        beforeEnter: (to, from, next) => {
          const user = store.getters.user;
          const companyType = user?.company?.company_type;
          
          if (companyType === 'broiler') {
            // Redirect to the actual AWB endpoint
            window.location.href = `/awb/${to.params.cartId}`;
          } else {
            next({ name: 'Unauthorized' });
          }
        },
        meta: {
          requiresAuth: true,
          requiresCompanyType: 'broiler',
          title: 'AWB Generation'
        }
      },
      {
        path: '/invoice/:orderId',
        name: 'InvoiceGenerate',
        beforeEnter: (to, from, next) => {
          const user = store.getters.user;
          const companyType = user?.company?.company_type;
          
          if (companyType === 'sme') {
            // Redirect to the actual invoice endpoint
            window.location.href = `/invoice/${to.params.orderId}`;
          } else {
            next({ name: 'Unauthorized' });
          }
        },
        meta: {
          requiresAuth: true,
          requiresCompanyType: 'sme',
          title: 'Invoice Generation'
        }
      },
      {
        path: 'orders',
        name: 'OrderManagement',
        component: OrderManagement,
        meta: { 
          requiresAuth: true,
          requiresCompanyTypes: ['broiler', 'slaughterhouse','sme'],
          title: 'Order Management'
        },
        beforeEnter: (to, from, next) => {
          const user = store.getters.user;
          const companyType = user?.company?.company_type;
          
          if (!companyType || !['broiler', 'slaughterhouse','sme'].includes(companyType)) {
            next({ name: 'Unauthorized' });
            return;
          }
          
          // Set dynamic title based on company type
          to.meta.title = `Order Management ${companyType.charAt(0).toUpperCase() + companyType.slice(1)}`;
          next();
        }
      },
      {
        path: '/verify',
        name: 'VerifyDelivery',
        component: VerifyDeliveryPage,
        meta: {
          requiresAuth: true,
          requiresCompanyType: 'logistic',
          title: 'Verify Delivery'
        },
        beforeEnter: (to, from, next) => {
          // Only allow access if locationID, deliveryID, and token are present in query params
          if (!to.query.locationID || !to.query.deliveryID || !to.query.token) {
            next({ name: 'DeliveryManagement' }); // Redirect to deliveries list or another appropriate page
          } else {
            next();
          }
        }
      },
    ]
  },
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: Unauthorized,
    meta: { requiresAuth: false }
  },
  
  // Add catch-all route for unknown URLs
  // {
  //   path: '/:pathMatch(.*)*',
  //   name: 'NotFound',
  //   component: NotFound,
  //   meta: { requiresAuth: false }
  // }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Navigation guard
router.beforeEach(async (to, from, next) => {
  // Check if route requires authentication
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const redirectIfAuth = to.matched.some(record => record.meta.redirectIfAuth);
  const requiresRole = to.matched.some(record => record.meta.requiresRole);
  const requiresCompanyType = to.matched.some(record => record.meta.requiresCompanyType);
  
  // Get authentication status
  const isAuthenticated = store.getters.isAuthenticated;
  
  // If route requires auth and user is not authenticated
  if (requiresAuth && !isAuthenticated) {
    next({ name: 'Login', query: { redirect: to.fullPath } });
    return;
  }
  
  // If route should redirect authenticated users and user is authenticated
  if (redirectIfAuth && isAuthenticated) {
    // Redirect based on company type instead of Dashboard
    const user = store.getters.user;
    const companyType = user?.company?.company_type;
    
    if (companyType) {
      switch (companyType) {
        case 'admin':
          next({ name: 'CompanyManagement' });
          break;
        case 'logistic':
          next({ name: 'DeliveryManagement' });
          break;
        case 'broiler':
          next({ name: 'OrderManagement' });
          break;
        case 'sme':
          next({ name: 'Marketplace' });
          break;
        case 'slaughterhouse':
          next({ name: 'OrderManagement' });
          break;
        default:
          next({ name: 'Dashboard' }); // Fallback
      }
    } else {
      next({ name: 'Dashboard' }); // Fallback if no company type
    }
    return;
  }
  
  // Check role and company type requirements if user is authenticated
  if (isAuthenticated && (requiresRole || requiresCompanyType)) {
    const user = store.getters.user;
    
    // Check if route requires specific role
    if (requiresRole && to.meta.requiresRole && user.role !== to.meta.requiresRole) {
      next({ name: 'Unauthorized' });
      return;
    }
    
    // Check if route requires specific company type
    if (requiresCompanyType && to.meta.requiresCompanyType) {
      // Handle array of allowed company types
      if (Array.isArray(to.meta.requiresCompanyTypes)) {
        if (!to.meta.requiresCompanyTypes.includes(user.company?.company_type)) {
          next({ name: 'Unauthorized' });
          return;
        }
      } 
      // Handle single company type requirement
      else if (user.company?.company_type !== to.meta.requiresCompanyType) {
        next({ name: 'Unauthorized' });
        return;
      }
    }
  }
  
  // Check email verification status for authenticated users
  if (isAuthenticated && to.name !== 'Profile' && to.name !== 'VerifyEmail') {
    try {
      const response = await fetchData('/api/email/verification-status');
      if (!response.verified) {
        // Redirect to profile page if email is not verified
        next({ name: 'Profile', query: { verifyEmail: 'true' } });
        return;
      }
    } catch (error) {
      console.error('Error checking email verification status:', error);
    }
  }
  
  next();
});

export default router;

