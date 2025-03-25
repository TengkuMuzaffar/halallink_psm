<template>
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Security</h5>
    </div>
    <div class="card-body">
      <div class="list-group">
        <a href="#" class="list-group-item list-group-item-action" @click.prevent="$emit('change-password')">
          <i class="bi bi-shield-lock-fill me-2"></i>
          Change Password
        </a>
        <a href="#" class="list-group-item list-group-item-action" @click.prevent="$emit('logout')">
          <i class="bi bi-box-arrow-right me-2 text-danger"></i>
          <span class="text-danger">Log Out</span>
        </a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SecuritySettings',
  emits: ['change-password', 'logout']
}
</script>

<style scoped>
.list-group-item {
  transition: background-color 0.2s;
}

.list-group-item:hover {
  background-color: #f8f9fa;
}
</style>