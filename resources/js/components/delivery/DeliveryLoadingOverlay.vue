<template>
  <transition name="fade">
    <div v-if="show" class="delivery-loading-overlay">
      <div class="overlay-content">
        <div class="spinner-container">
          <div class="spinner-border" :class="sizeClass" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p v-if="message" class="loading-message mt-3">{{ message }}</p>
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
export default {
  name: 'DeliveryLoadingOverlay',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    size: {
      type: String,
      default: 'lg',
      validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
    },
    message: {
      type: String,
      default: 'Loading delivery information...'
    }
  },
  computed: {
    sizeClass() {
      const sizes = {
        sm: 'spinner-border-sm',
        md: '',
        lg: 'spinner-border-lg',
        xl: 'spinner-border-xl'
      };
      return sizes[this.size] || '';
    }
  }
}
</script>

<style scoped>
/* Delivery theme colors */
:root {
  --primary-color: #123524;
  --secondary-color: #EFE3C2;
  --accent-color: #3E7B27;
}

.delivery-loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.95);
  z-index: 10000;
  display: flex;
  justify-content: center;
  align-items: center;
  backdrop-filter: blur(3px);
}

.overlay-content {
  text-align: center;
  padding: 2rem;
  border-radius: 8px;
  background-color: rgba(255, 255, 255, 0.9);
  box-shadow: 0 4px 20px rgba(18, 53, 36, 0.15);
}

.spinner-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.loading-message {
  color: var(--primary-color);
  font-weight: bold;
  font-size: 1.2rem;
  margin-top: 1.5rem;
  margin-bottom: 0;
  max-width: 300px;
}

.spinner-border-lg {
  width: 4rem;
  height: 4rem;
  color: var(--accent-color);
  border-width: 0.3rem;
}

.spinner-border-xl {
  width: 5rem;
  height: 5rem;
  color: var(--accent-color);
  border-width: 0.4rem;
}

/* Fade transition */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>