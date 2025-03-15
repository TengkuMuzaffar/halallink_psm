<template>
  <header class="app-header">
    <div class="container-fluid px-4">
      <div class="d-flex justify-content-between align-items-center">
        <div class="logo">
          <img src="/images/HalalLink_v1.png" alt="HalalLink" height="40">
        </div>
        
        <div class="user-menu" v-if="isAuthenticated">
          <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="avatar me-2">
                <img src="/images/avatar-placeholder.png" alt="User Avatar" class="rounded-circle" width="32" height="32">
              </div>
              <span>{{ user?.name || 'User' }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><router-link class="dropdown-item" to="/profile">Profile</router-link></li>
              <li><hr class="dropdown-divider"></li>
              <li><a href="#" class="dropdown-item" @click.prevent="handleLogout">Sign Out</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script>
import { computed, onUnmounted } from 'vue';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';

export default {
  name: 'AppHeader',
  setup() {
    const store = useStore();
    const router = useRouter();
    
    const isAuthenticated = computed(() => store.getters.isAuthenticated);
    const user = computed(() => store.getters.user);
    
    // Track if component is mounted
    let isMounted = true;
    
    onUnmounted(() => {
      isMounted = false;
    });
    
    const handleLogout = async (event) => {
      try {
        await store.dispatch('logout');
        if (isMounted) {
          router.push({ name: 'Login' });
        }
      } catch (error) {
        console.error('Logout error:', error);
      }
    };
    
    return {
      isAuthenticated,
      user,
      handleLogout
    };
  }
};
</script>

<style scoped>
.app-header {
  background-color: #fff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 10px 0;
}

.logo {
  display: flex;
  align-items: center;
}

.user-menu .dropdown-toggle {
  background: none;
  border: none;
  color: #333;
  cursor: pointer;
}

.user-menu .dropdown-toggle:hover,
.user-menu .dropdown-toggle:focus {
  background: none;
  color: #123524;
  text-decoration: none;
}

.user-menu .dropdown-toggle::after {
  margin-left: 0.5em;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  overflow: hidden;
  background-color: #e9ecef;
}
</style>