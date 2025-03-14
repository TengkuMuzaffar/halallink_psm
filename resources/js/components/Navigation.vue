<template>
  <nav class="app-nav">
    <div class="container-fluid">
      <ul class="nav-menu">
        <li class="nav-item" v-for="item in navItems" :key="item.path">
          <router-link :to="item.path" class="nav-link" :class="{ active: isActive(item.path) }">
            <i :class="item.icon"></i>
            <span>{{ item.label }}</span>
          </router-link>
        </li>
      </ul>
    </div>
  </nav>
</template>

<script>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStore } from 'vuex';

export default {
  name: 'Navigation',
  setup() {
    const route = useRoute();
    const store = useStore();
    
    const user = computed(() => store.getters.user || {});
    
    // Define navigation items based on user role and company type
    const navItems = computed(() => {
      const items = [
        { label: 'Dashboard', path: '/dashboard', icon: 'fas fa-tachometer-alt' }
      ];
      
      // Only show employee management for admins
      if (user.value.role === 'admin') {
        items.push({ label: 'Employee Management', path: '/employees', icon: 'fas fa-users' });
      }
      
      return items;
    });
    
    const isActive = (path) => {
      return route.path === path || route.path.startsWith(`${path}/`);
    };
    
    return {
      navItems,
      isActive
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
</style>