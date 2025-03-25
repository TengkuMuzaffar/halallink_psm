<template>
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">Account Settings</h5>
    </div>
    <div class="card-body">
      <div class="list-group">
        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" @click.prevent="$emit('language-settings')">
          <div>
            <i class="bi bi-translate me-2"></i>
            Language
          </div>
          <span class="text-muted">English</span>
        </a>
        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" @click.prevent="$emit('location-settings')">
          <div>
            <i class="bi bi-geo-alt-fill me-2"></i>
            Location
          </div>
          <span class="text-muted">{{ location || 'Not set' }}</span>
        </a>
        <a href="#" class="list-group-item list-group-item-action" @click.prevent="$emit('clear-cache')">
          <i class="bi bi-trash me-2"></i>
          Clear Cache
        </a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AccountSettings',
  props: {
    location: {
      type: String,
      default: ''
    }
  },
  emits: ['language-settings', 'location-settings', 'clear-cache']
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