<template>
  <div class="stats-card card h-100">
    <div class="card-body">
      <div class="d-flex align-items-center">
        <div :class="`icon-box ${bgColor}`">
          <i :class="icon" class="text-white"></i>
        </div>
        <div class="ms-3">
          <h6 class="text-muted mb-0">{{ title }}</h6>
          <h2 class="card-title mb-0">{{ count }}</h2>
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
    bgColor: {
      type: String,
      default: 'bg-primary'
    }
  }
}
</script>

<style scoped>
.stats-card {
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  border: none;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stats-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.icon-box {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

@media (max-width: 768px) {
  .icon-box {
    width: 40px;
    height: 40px;
    font-size: 1.2rem;
  }
  
  .card-title {
    font-size: 1.5rem;
  }
}

/* Custom company type colors */
.bg-taupe {
  background-color: #B38B6D !important;
  color: white;
}

.bg-terracotta {
  background-color: #CB6D51 !important;
  color: white;
}

.bg-slate-blue {
  background-color: #6D8BB3 !important;
  color: white;
}

.bg-lavender {
  background-color: #8B6DB3 !important;
  color: white;
}
</style>