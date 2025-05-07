<template>
  <div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
      <StatsCard
        title="Pending Assignment"
        :count="stats.pending"
        icon="fas fa-clock"
        bgColor="bg-warning"
      />
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
      <StatsCard
        title="In Progress"
        :count="stats.inProgress"
        icon="fas fa-truck"
        bgColor="bg-info"
      />
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
      <StatsCard
        title="Completed Today"
        :count="stats.completedToday"
        icon="fas fa-check-circle"
        bgColor="bg-success"
      />
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
      <StatsCard
        title="Issues"
        :count="stats.issues"
        icon="fas fa-exclamation-triangle"
        bgColor="bg-danger"
      />
    </div>
  </div>
</template>

<script>
import StatsCard from '../../components/ui/StatsCard.vue';

export default {
  name: 'DeliveryStatsCard',
  components: {
    StatsCard
  },
  props: {
    stats: {
      type: Object,
      required: true,
      default: () => ({
        pending: 0,
        inProgress: 0,
        completedToday: 0,
        issues: 0
      })
    }
  }
}
</script>