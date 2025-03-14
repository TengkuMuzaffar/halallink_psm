<template>
  <div class="card stat-card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h6 class="card-subtitle mb-2 text-muted">{{ title }}</h6>
          <h2 class="card-title mb-0">{{ count }}</h2>
        </div>
        <div class="icon-container" :class="iconClass">
          <i :class="icon"></i>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StatCard',
  props: {
    title: {
      type: String,
      required: true
    },
    count: {
      type: Number,
      required: true
    },
    icon: {
      type: String,
      default: 'fas fa-users'
    },
    iconClass: {
      type: String,
      default: 'bg-primary'
    }
  }
}
</script>

<style scoped>
.stat-card {
  transition: transform 0.3s;
  border: none;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.stat-card:hover {
  transform: translateY(-5px);
}

.icon-container {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.icon-container i {
  font-size: 1.5rem;
}

.bg-primary {
  background-color: #123524;
}

.bg-success {
  background-color: #85A947;
}

.bg-info {
  background-color: #17a2b8;
}

.bg-warning {
  background-color: #ffc107;
}
</style>