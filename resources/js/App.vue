<template>
  <router-view></router-view>
</template>

<script>
export default {
  name: 'App',
  created() {
    // Try to fetch user data if token exists
    if (this.$store.state.token) {
      this.$store.dispatch('fetchUser');
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