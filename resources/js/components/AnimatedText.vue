<template>
  <div class="animated-text">
    <transition name="fade" mode="out-in">
      <div :key="currentIndex" class="text-item">
        {{ displayedTexts[currentIndex] }}
      </div>
    </transition>
  </div>
</template>

<script>
export default {
  name: 'AnimatedText',
  props: {
    texts: {
      type: Array,
      required: true,
      default: () => []
    },
    interval: {
      type: Number,
      default: 3000
    }
  },
  data() {
    return {
      currentIndex: 0,
      timer: null
    }
  },
  computed: {
    displayedTexts() {
      return this.texts;
    }
  },
  mounted() {
    this.startAnimation();
  },
  beforeUnmount() {
    this.stopAnimation();
  },
  methods: {
    startAnimation() {
      this.timer = setInterval(() => {
        this.currentIndex = (this.currentIndex + 1) % this.texts.length;
      }, this.interval);
    },
    stopAnimation() {
      clearInterval(this.timer);
    }
  }
}
</script>

<style scoped>
.animated-text {
  height: 80px; /* Fixed height */
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
  overflow: hidden; /* Prevent overflow */
}

.text-item {
  text-align: center;
  font-family: 'Poppins', sans-serif;
  font-size: 18px;
  font-weight: 600;
  line-height: 1.5;
  padding: 0 15px;
  white-space: pre-line;
  letter-spacing: 0.3px;
  color: rgba(255, 255, 255, 0.95);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  max-width: 100%; /* Ensure text stays within container */
  max-height: 100%; /* Ensure text stays within container */
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>