<template>
  <div class="employee-stats">
    <div class="row">
      <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
        <StatsCard 
          title="Total Employees" 
          :count="stats.total || 0" 
          icon="fas fa-users" 
          bg-color="bg-primary"
        />
      </div>
      <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
        <StatsCard 
          title="Active Employees" 
          :count="stats.active || 0" 
          icon="fas fa-user-check" 
          bg-color="bg-success"
        />
      </div>
      <div class="col-md-4 col-sm-12">
        <StatsCard 
          title="Inactive Employees" 
          :count="stats.inactive || 0" 
          icon="fas fa-user-times" 
          bg-color="bg-danger"
        />
      </div>
    </div>
  </div>
</template>

<script>
import StatsCard from '../ui/StatsCard.vue';

export default {
  name: 'EmployeeStats',
  components: {
    StatsCard
  },
  props: {
    stats: {
      type: Object,
      default: () => ({
        total: 0,
        active: 0,
        inactive: 0
      })
    }
  }
}
</script>