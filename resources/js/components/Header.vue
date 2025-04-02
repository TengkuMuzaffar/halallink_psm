<template>
  <header class="app-header">
    <div class="container-fluid px-4">
      <div class="d-flex justify-content-between align-items-center">
        <div class="logo">
          <img src="/images/HalalLink_v1.png" alt="HalalLink" height="40">
        </div>
        
        <div class="user-menu" v-if="isAuthenticated">
          <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="avatar me-2">
                <img 
                  :src="userProfileImage" 
                  alt="User Avatar" 
                  class="rounded-circle" 
                  width="32" 
                  height="32"
                >
              </div>
              <span class="display-name-truncate" :title="displayName">{{ displayName }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><router-link class="dropdown-item" to="/profile"><i class="bi bi-person me-2"></i>My Profile</router-link></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="#" @click.prevent="handleLogout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script>
import { computed, onMounted, onUnmounted } from 'vue';
import { useStore } from 'vuex';
import { useRouter } from 'vue-router';
import * as bootstrap from 'bootstrap';

export default {
  name: 'AppHeader',
  setup() {
    const store = useStore();
    const router = useRouter();
    
    const isAuthenticated = computed(() => store.getters.isAuthenticated);
    const user = computed(() => store.getters.user);
    console.log('User from store here:', user.value);
    // Determine which profile image to display based on user role
    const userProfileImage = computed(() => {
      if (!user.value) return '/images/blank.jpg';

      // Check for admin company image first
      if (user.value.role === 'admin' && user.value.company?.company_image) {
        return user.value.company.company_image.startsWith('http')
          ? user.value.company.company_image
          : `/storage/${user.value.company.company_image}`;
      }

      // Fallback to user's personal image
      if (user.value.image) {
        return user.value.image.startsWith('http')
          ? user.value.image
          : `/storage/${user.value.image}`;
      }

      // Ultimate fallback to blank.jpg
      return '/images/blank.jpg';
    });
    
    // Display name based on user role
    const displayName = computed(() => {
      if (!user.value) return 'User';
      
      if (user.value.role === 'admin' && user.value.company?.company_name) {
        return user.value.company.company_name;
      } else {
        return user.value.fullname || 'User';
      }
    });
    
    // Track if component is mounted
    let isMounted = true;
    
    onMounted(() => {
      // Initialize all dropdowns
      document.querySelectorAll('.dropdown-toggle').forEach(dropdownToggle => {
        new bootstrap.Dropdown(dropdownToggle);
      });
    });
    
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
      userProfileImage,
      displayName,
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
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.display-name-truncate {
  max-width: 100px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: inline-block;
}

@media (max-width: 576px) {
  .display-name-truncate {
    max-width: 100px;
  }
}
</style>