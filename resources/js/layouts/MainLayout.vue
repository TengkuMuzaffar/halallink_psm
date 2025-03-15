<template>
  <div class="main-layout d-flex flex-column min-vh-100">
    <Header />
    <Navigation />
    <main class="flex-grow-1">
      <Container>
        <Suspense>
          <template #default>
            <router-view></router-view>
          </template>
          <template #fallback>
            <div class="d-flex justify-content-center align-items-center" style="height: 300px;">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </template>
        </Suspense>
      </Container>
    </main>
    <Footer />
  </div>
</template>

<script>
import { defineComponent, onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import { useRouter, useRoute } from 'vue-router';
import Header from '../components/Header.vue';
import Navigation from '../components/Navigation.vue';
import Container from '../components/Container.vue';
import Footer from '../components/Footer.vue';

export default defineComponent({
  name: 'MainLayout',
  components: {
    Header,
    Navigation,
    Container,
    Footer
  },
  setup() {
    const store = useStore();
    const router = useRouter();
    const route = useRoute();
    
    // Check if the current route requires authentication
    const requiresAuth = computed(() => {
      return route.matched.some(record => record.meta.requiresAuth);
    });
    
    onMounted(async () => {
      // Only check authentication if the route requires it
      if (requiresAuth.value && !store.getters.isAuthenticated) {
        // Redirect to login with the current path as redirect parameter
        router.push({ 
          name: 'Login', 
          query: { redirect: route.fullPath } 
        });
      }
      
      // If authenticated but user data is not loaded, fetch it
      if (store.getters.isAuthenticated && !store.getters.user) {
        try {
          await store.dispatch('fetchUser');
        } catch (error) {
          console.error('Failed to fetch user data:', error);
          // If fetching user data fails, log out
          await store.dispatch('logout');
          router.push({ name: 'Login' });
        }
      }
    });
    
    return {
      // Return computed properties if needed in the template
      isAuthenticated: computed(() => store.getters.isAuthenticated)
    };
  }
});
</script>

<style scoped>
.main-layout {
  background-color: #f5f5f5;
}
</style>
