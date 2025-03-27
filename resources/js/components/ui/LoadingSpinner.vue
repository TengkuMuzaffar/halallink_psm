<template>
  <div class="loading-spinner-container" :class="{ overlay: overlay }">
    <div class="spinner-container">
      <div class="spinner-border" :class="sizeClass" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p v-if="message" class="loading-message mt-2">{{ message }}</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LoadingSpinner',
  props: {
    size: {
      type: String,
      default: 'md',
      validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    message: {
      type: String,
      default: ''
    },
    overlay: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    sizeClass() {
      const sizes = {
        sm: 'spinner-border-sm',
        md: '',
        lg: 'spinner-border-lg'
      };
      return sizes[this.size] || '';
    }
  }
}
</script>

<style scoped>
.loading-spinner-container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  padding: 2rem 0;
}

.loading-spinner-container.overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.8);
  z-index: 1000;
  padding: 0;
}

.spinner-container {
  text-align: center;
}

.loading-message {
  color: #6c757d;
  margin-bottom: 0;
}

.spinner-border-lg {
  width: 3rem;
  height: 3rem;
}
</style>