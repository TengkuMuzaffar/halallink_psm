<template>
  <div class="stats-cards">
    <div class="row">
      <div v-for="(stat, index) in stats" :key="index" class="col-md-3 col-sm-6 mb-3 mb-md-0">
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