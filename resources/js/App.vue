<template>
  <router-view></router-view>
</template>

<script>
export default {
  name: 'App',
  created() {
    // Try to fetch user data if token exists but no user data
    if (this.$store.state.token && !this.$store.state.user) {
      this.$store.dispatch('fetchUser').catch(error => {
        console.error('Failed to fetch user data in App.vue:', error);
      });
    }
  }
}
</script>

<style>
/* Global styles */
body {
  font-family: 'Nunito', sans-serif;
  background-color: #f8f9fa;
}
.card-header {
  --primary-color: #123524;
  --secondary-color: #EFE3C2;
  --accent-color: #3E7B27;
  --text-color: #333;
  --light-text: #666;
  --border-color: rgba(18, 53, 36, 0.2);
  --light-bg: rgba(239, 227, 194, 0.2);
  --lighter-bg: rgba(239, 227, 194, 0.1);
  
  border: none;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  background-color: #fff;
}
</style>