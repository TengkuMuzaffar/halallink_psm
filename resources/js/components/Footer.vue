<template>
  <footer class="app-footer">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          &copy; {{ currentYear }} HalalLink. All rights reserved.
        </div>
        <div>
          <span class="version">Version 1.0.0</span>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
import { computed } from 'vue';

export default {
  name: 'Footer',
  setup() {
    const currentYear = computed(() => new Date().getFullYear());
    
    return {
      currentYear
    };
  }
}
</script>

<style scoped>
.app-footer {
  background-color: #f8f9fa;
  border-top: 1px solid #e9ecef;
  padding: 15px 0;
  font-size: 0.875rem;
  color: #6c757d;
}

.version {
  font-size: 0.75rem;
  color: #adb5bd;
}
</style>