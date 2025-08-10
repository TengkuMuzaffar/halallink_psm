<template>
  <div class="not-found">
    <div class="text-center">
      <h1 class="display-1">404</h1>
      <h2 class="mb-4">Page Not Found</h2>
      <p class="mb-4">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
      <router-link to="/" class="btn btn-primary">
        <i class="fas fa-home me-2"></i> Go to Dashboard
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotFound'
}
</script>

<style scoped>
.not-found {
  padding: 60px 20px;
  max-width: 600px;
  margin: 0 auto;
}

.display-1 {
  font-size: 6rem;
  font-weight: 700;
  color: #123524;
}
</style>