<template>
  <div class="unauthorized">
    <div class="text-center">
      <h1 class="display-1">403</h1>
      <h2 class="mb-4">Access Denied</h2>
      <p class="mb-4">You don't have permission to access this page. Please contact your administrator if you believe this is an error.</p>
      <router-link to="/" class="btn btn-primary">
        <i class="fas fa-home me-2"></i> Go to Dashboard
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Unauthorized'
}
</script>

<style scoped>
.unauthorized {
  padding: 60px 20px;
  max-width: 600px;
  margin: 0 auto;
}

.display-1 {
  font-size: 6rem;
  font-weight: 700;
  color: #dc3545;
}
</style>