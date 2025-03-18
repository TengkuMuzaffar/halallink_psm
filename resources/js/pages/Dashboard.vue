<template>
  <div class="dashboard">
    <h1 class="mb-4">Dashboard</h1>
    
    <!-- Stats Cards Row -->
    <StatsCards :stats="companyStats" class="mb-4" />
    
    <!-- Performance Charts will go here -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Company Performance</h5>
      </div>
      <div class="card-body">
        <p class="text-muted text-center py-5" v-if="loading">
          Loading charts...
        </p>
        <p class="text-muted text-center py-5" v-else>
          Performance charts will be added here
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import StatsCards from '../components/dashboard/StatsCards.vue';
import api from '../utils/api';
import modal from '../utils/modal';

export default {
  name: 'Dashboard',
  components: {
    StatsCards
  },
  setup() {
    const loading = ref(true);
    const error = ref(null);
    const stats = ref({
      broiler: 0,
      slaughterhouse: 0,
      sme: 0,
      logistic: 0
    });
    
    // Transform stats for StatsCards component
    const companyStats = computed(() => [
      {
        title: 'Broiler Companies',
        count: stats.value.broiler,
        icon: 'fas fa-industry',
        bgColor: 'bg-primary'
      },
      {
        title: 'Slaughterhouse',
        count: stats.value.slaughterhouse,
        icon: 'fas fa-warehouse',
        bgColor: 'bg-danger'
      },
      {
        title: 'SME',
        count: stats.value.sme,
        icon: 'fas fa-store',
        bgColor: 'bg-success'
      },
      {
        title: 'Logistics',
        count: stats.value.logistic,
        icon: 'fas fa-truck',
        bgColor: 'bg-warning'
      }
    ]);
    
    // Fetch dashboard data
    const fetchDashboardData = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        // Use the enhanced API client
        const response = await api.get('/api/dashboard/stats', {
          onError: (err) => {
            console.error('Failed to fetch dashboard stats:', err);
            error.value = 'Failed to load dashboard data. Please try again.';
            
            // Show error message with modal
            modal.danger(
              'Error Loading Dashboard',
              'Failed to load dashboard data. Please try again.',
              {
                buttons: [
                  {
                    label: 'Retry',
                    type: 'primary',
                    onClick: () => {
                      fetchDashboardData();
                    }
                  },
                  {
                    label: 'Dismiss',
                    type: 'secondary',
                    dismiss: true
                  }
                ]
              }
            );
          }
        });
        
        if (typeof response === 'object') {
          stats.value = response;
        }
      } catch (err) {
        // Error is already handled by onError callback
      } finally {
        loading.value = false;
      }
    };
    
    onMounted(() => {
      fetchDashboardData();
    });
    
    return {
      loading,
      error,
      stats,
      companyStats
    };
  }
};
</script>

<style scoped>
.dashboard h1 {
  color: #123524;
}

.card {
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  border: none;
}

.card-header {
  background-color: #fff;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  padding: 1rem 1.5rem;
}
</style>