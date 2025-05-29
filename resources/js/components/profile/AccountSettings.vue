<template>
  <div class="card mb-4 theme-card">
    <div class="card-header theme-header">
      <h5 class="mb-0">Account Settings</h5>
    </div>
    <div class="card-body theme-body">
      <div class="list-group">
        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center theme-list-item" @click.prevent="$emit('language-settings')">
          <div>
            <i class="bi bi-translate me-2"></i>
            Language
          </div>
          <span class="text-muted">English</span>
        </a>
        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center theme-list-item" @click.prevent="$emit('location-settings')">
          <div>
            <i class="bi bi-geo-alt-fill me-2"></i>
            Location
          </div>
          <span class="text-muted">{{ location || 'Not set' }}</span>
        </a>
        <a href="#" class="list-group-item list-group-item-action theme-list-item" @click.prevent="$emit('clear-cache')">
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
/* CSS Variables */
:root {
  --primary-color: #123524;
  --secondary-color: #EFE3C2;
  --accent-color: #3E7B27;
  --text-color: #333;
  --light-text: #666;
  --border-color: rgba(18, 53, 36, 0.2);
  --light-bg: rgba(239, 227, 194, 0.2);
  --lighter-bg: rgba(239, 227, 194, 0.1);
}

/* Theme Card Styles */
.theme-card {
  border: 1px solid var(--border-color);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: box-shadow 0.3s ease;
}

.theme-card:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.theme-header {
  background: linear-gradient(135deg, var(--primary-color), #1a4a2e);
  color: var(--secondary-color);
  border-bottom: 1px solid var(--border-color);
}

.theme-body {
  background-color: #fff;
}

/* Button styles */
.theme-btn-accent {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
  color: var(--secondary-color);
  transition: all 0.3s ease;
}

.theme-btn-accent:hover {
  background-color: #2e5c1d;
  border-color: #2e5c1d;
  color: var(--secondary-color);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* List Item Styles */
.theme-list-item {
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}

.theme-list-item:hover {
  background-color: var(--lighter-bg);
  border-color: var(--accent-color);
  transform: translateX(5px);
}

.list-group-item {
  transition: background-color 0.2s;
}
</style>