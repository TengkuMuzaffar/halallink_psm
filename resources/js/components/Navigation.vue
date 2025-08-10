<template>
  <nav class="app-nav">
    <div class="container-fluid">
      <ul class="nav-menu">
        <li class="nav-item" v-for="item in navItems" :key="item.path">
          <router-link :to="item.path" class="nav-link" :class="{ active: isActive(item.path) }">
            <i :class="item.icon"></i>
            <span>
              <span class="root-label">{{ item.rootLabel }}</span>
              <span class="management-text">{{ item.managementText }}</span>
            </span>
          </router-link>
        </li>
      </ul>
    </div>
  </nav>
</template>

<script>
import { computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useStore } from 'vuex';

export default {
  name: 'Navigation',
  setup() {
    const route = useRoute();
    const store = useStore();
    
    // Fetch user data when component mounts if we have a token but no user data
    onMounted(async () => {
      if (store.getters.isAuthenticated && !store.getters.user) {
        try {
          await store.dispatch('fetchUser');
        } catch (error) {
          console.error('Failed to fetch user data in Navigation:', error);
        }
      }
    });
    
    const user = computed(() => store.getters.user || {});
    const userCompany = computed(() => {
      // More robust company data extraction
      if (user.value?.company) {
        return user.value.company;
      } else if (user.value?.companyID) {
        // If we only have companyID but not the full company object
        return { companyID: user.value.companyID };
      }
      return {};
    });
    
    // Helper function to split label into root and management text
    const splitLabel = (label) => {
      if (label.includes('Management')) {
        const parts = label.split(' Management');
        return {
          rootLabel: parts[0],
          managementText: ' Management'
        };
      }
      return {
        rootLabel: label,
        managementText: ''
      };
    };
    
    // Define navigation items based on user role and company type
    const navItems = computed(() => {
      const baseItems = [
        // Dashboard removed as it's no longer the default landing page
      ];
      
      // Only show employee management for admins
      if (user.value && user.value.role === 'admin') {

        baseItems.push({ label: 'Employee Management', path: '/employees', icon: 'fas fa-users' });
      }
      
      const companyType = userCompany.value?.company_type;
  
      if (companyType === 'admin') {
        baseItems.push({ label: 'Dashboard', path: '/dashboard', icon: 'fas fa-tachometer-alt' });
        baseItems.push({ label: 'Company Management', path: '/companies', icon: 'fas fa-building' });
        baseItems.push({ label: 'Poultry Management', path: '/poultries', icon: 'fas fa-feather' });
      }
      
      if (companyType === 'broiler') {
        baseItems.push({ label: 'Items Management', path: '/items', icon: 'fas fa-boxes' });
      }
      
      // Add Vehicle Management for logistics companies
      if (companyType === 'logistic') {
        baseItems.push({ label: 'Vehicle Management', path: '/vehicles', icon: 'fas fa-truck' });
        baseItems.push({ label: 'Delivery Management', path: '/deliveries', icon: 'fas fa-route' });
      }
      
      // Add Marketplace for SME users
      if (companyType === 'sme') {
        baseItems.push({ label: 'Marketplace', path: '/marketplace', icon: 'fas fa-store' });
      }
      // Add Order Management link for broiler and sme
      if (companyType === 'slaughterhouse'|| companyType === 'broiler' || companyType === 'sme') {
        baseItems.push({ label: 'Order Management', path: '/orders', icon: 'fas fa-receipt' });
      }
        // Add Task Management for slaughterhouse only
        if (companyType === 'slaughterhouse') {
          baseItems.push({ label: 'Task Management', path: '/tasks', icon: 'fas fa-tasks' });
        }
      
      // Add Report link for admin and sme company types
      if (companyType === 'admin' || companyType === 'sme') {
        baseItems.push({ label: 'Reports', path: '/report', icon: 'fas fa-chart-bar' });
      }
      
      // Process each item to split labels
      return baseItems.map(item => {
        const { rootLabel, managementText } = splitLabel(item.label);
        return {
          ...item,
          rootLabel,
          managementText
        };
      });
    });
    
    // Watch for changes to the user and log for debugging
    watch(user, (newUser) => {
      // console.log('User updated in Navigation:', newUser);
      // console.log('Company data:', userCompany.value);
    });
    
    const isActive = (path) => {
      return route.path === path || route.path.startsWith(`${path}/`);
    };
    
    return {
      navItems,
      isActive,
      userCompany
    };
  }
}
</script>

<style scoped>
.app-nav {
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.nav-menu {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
  overflow-x: auto;
}

.nav-item {
  margin-right: 5px;
}

.nav-link {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  color: #495057;
  text-decoration: none;
  border-radius: 4px;
  transition: all 0.2s;
}

.nav-link i {
  margin-right: 8px;
}

.nav-link:hover {
  background-color: #e9ecef;
}

.nav-link.active {
  background-color: #123524;
  color: white;
}

@media (max-width: 768px) {
  .nav-link span {
    display: none;
  }
  
  .nav-link i {
    margin-right: 0;
    font-size: 1.2rem;
  }
}

/* Hide "Management" text on medium screens */
@media (max-width: 992px) and (min-width: 769px) {
  .management-text {
    display: none;
  }
}
</style>