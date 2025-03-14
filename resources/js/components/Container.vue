<template>
  <div class="app-container">
    <div class="container-fluid">
      <slot></slot>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Container'
}
</script>

<style scoped>
.app-container {
  padding: 20px;
}

@media (max-width: 768px) {
  .app-container {
    padding: 15px 10px;
  }
}
</style>