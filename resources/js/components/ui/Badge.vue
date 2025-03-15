<template>
  <span class="badge" :class="badgeClass">
    <slot></slot>
  </span>
</template>

<script>
export default {
  name: 'Badge',
  props: {
    type: {
      type: String,
      default: 'primary',
      validator: value => ['primary', 'secondary', 'success', 'danger', 'warning', 'info'].includes(value)
    },
    pill: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    badgeClass() {
      return {
        [`bg-${this.type}`]: true,
        'rounded-pill': this.pill
      };
    }
  }
}
</script>