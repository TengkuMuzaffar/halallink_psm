<template>
  <div class="stats-card">
    <div class="card h-100">
      <div class="card-body d-flex align-items-center">
        <div class="icon-container" :style="{ backgroundColor: iconBgColor }">
          <i :class="icon" class="stats-icon"></i>
        </div>
        <div class="stats-content">
          <h6 class="stats-title mb-1">{{ title }}</h6>
          <h3 class="stats-count mb-0">{{ count }}</h3>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StatsCard',
  props: {
    title: {
      type: String,
      required: true
    },
    count: {
      type: [Number, String],
      required: true
    },
    icon: {
      type: String,
      default: 'fas fa-chart-bar'
    },
    color: {
      type: String,
      default: '#123524'
    }
  },
  computed: {
    iconBgColor() {
      return this.color + '20'; // Add 20% opacity
    }
  }
}
</script>

<style scoped>
.stats-card {
  transition: transform 0.3s ease;
}

.stats-card:hover {
  transform: translateY(-5px);
}

.icon-container {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  flex-shrink: 0;
}

.stats-icon {
  font-size: 1.5rem;
  color: v-bind(color);
}

.stats-content {
  flex: 1;
}

.stats-title {
  font-size: 0.9rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stats-count {
  font-weight: 600;
  color: #212529;
}

@media (min-width: 992px) {
  .icon-container {
    width: 60px;
    height: 60px;
  }
  
  .stats-icon {
    font-size: 1.75rem;
  }
  
  .stats-title {
    font-size: 1rem;
  }
  
  .stats-count {
    font-size: 1.75rem;
  }
}
</style>