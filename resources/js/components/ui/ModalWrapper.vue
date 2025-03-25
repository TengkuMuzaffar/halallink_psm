<template>
  <div class="modal-wrapper">
    <div 
      class="modal fade" 
      :class="{ 'show': show }" 
      tabindex="-1" 
      :style="{ display: show ? 'block' : 'none' }"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ title }}</h5>
            <button 
              type="button" 
              class="btn-close" 
              @click="$emit('close')"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <slot></slot>
          </div>
          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-secondary" 
              @click="$emit('close')"
            >
              {{ cancelText }}
            </button>
            <button 
              type="button" 
              :class="`btn btn-${confirmType}`" 
              @click="$emit('confirm')"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </div>
    <div 
      class="modal-backdrop fade" 
      :class="{ 'show': show }" 
      :style="{ display: show ? 'block' : 'none' }"
    ></div>
  </div>
</template>

<script>
export default {
  name: 'ModalWrapper',
  props: {
    title: {
      type: String,
      required: true
    },
    show: {
      type: Boolean,
      default: false
    },
    confirmText: {
      type: String,
      default: 'Confirm'
    },
    cancelText: {
      type: String,
      default: 'Cancel'
    },
    confirmType: {
      type: String,
      default: 'primary'
    }
  },
  emits: ['close', 'confirm']
}
</script>

<style scoped>
.modal-backdrop.show {
  opacity: 0.5;
}

.modal.show {
  background-color: rgba(0, 0, 0, 0.5);
}
</style>