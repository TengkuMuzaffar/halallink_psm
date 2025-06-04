<template>
  <div class="stats-cards">
    <div class="row g-3">
      <div v-for="(stat, index) in stats" :key="index" class="col-xl-3 col-md-6 col-sm-12">
        <StatsCard 
          :title="stat.title" 
          :count="stat.count" 
          :icon="stat.icon" 
          :bg-color="stat.bgColor"
        />
      </div>
    </div>
  </div>
</template>

<script>
import StatsCard from '../ui/StatsCard.vue';

export default {
  name: 'StatsCards',
  components: {
    StatsCard
  },
  props: {
    stats: {
      type: Array,
      required: true
    }
  }
}
</script>

<style scoped>
.stats-cards {
  width: 100%;
  overflow-x: hidden;
}

@media (max-width: 576px) {
  .stats-cards .row {
    margin-right: -10px;
    margin-left: -10px;
  }
}
</style>