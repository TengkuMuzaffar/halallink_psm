<template>
  <div class="modal fade" :id="modalId" tabindex="-1" :aria-labelledby="modalId + '-label'" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header theme-header">
          <h5 class="modal-title" :id="modalId + '-label'">{{ title }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>{{ message }}</p>
        </div>
        <div class="modal-footer">
          <button 
            type="button" 
            class="btn btn-secondary" 
            data-bs-dismiss="modal"
            @click="onCancel"
          >
            {{ cancelLabel }}
          </button>
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="onConfirm"
          >
            {{ confirmLabel }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import * as bootstrap from 'bootstrap';

export default {
  name: 'ConfirmationModal',
  props: {
    modalId: {
      type: String,
      default: 'confirmationModal'
    },
    title: {
      type: String,
      default: 'Confirmation'
    },
    message: {
      type: String,
      default: 'Are you sure you want to continue?'
    },
    confirmLabel: {
      type: String,
      default: 'Yes, Continue'
    },
    cancelLabel: {
      type: String,
      default: 'No, Cancel'
    }
  },
  emits: ['confirm', 'cancel'],
  setup(props, { emit }) {
    const modalRef = ref(null);
    
    // Initialize the modal when component is mounted
    onMounted(() => {
      const modalElement = document.getElementById(props.modalId);
      if (modalElement) {
        modalRef.value = new bootstrap.Modal(modalElement);
      }
    });
    
    // Show the modal
    const show = () => {
      if (modalRef.value) {
        modalRef.value.show();
      }
    };
    
    // Hide the modal
    const hide = () => {
      if (modalRef.value) {
        modalRef.value.hide();
      }
    };
    
    // Handle confirm action
    const onConfirm = () => {
      emit('confirm');
      hide();
    };
    
    // Handle cancel action
    const onCancel = () => {
      emit('cancel');
    };
    
    return {
      modalRef,
      show,
      hide,
      onConfirm,
      onCancel
    };
  }
};
</script>

<style scoped>
/* Use CSS variables from App.vue */
.theme-header {
  background-color: var(--primary-color);
  color: white;
  border: none;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

/* Modal close button styling */
.btn-close-white {
  filter: invert(1) grayscale(100%) brightness(200%);
}
</style>