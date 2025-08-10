<template>
  <button 
    class="btn btn-primary" 
    :type="type" 
    :disabled="loading || disabled"
    @click="$emit('click', $event)"
  >
    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
    <slot>Submit</slot>
  </button>
</template>

<script>
export default {
  name: 'PrimaryButton',
  props: {
    type: {
      type: String,
      default: 'button'
    },
    loading: {
      type: Boolean,
      default: false
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  emits: ['click']
}
</script>