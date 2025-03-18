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
</style>