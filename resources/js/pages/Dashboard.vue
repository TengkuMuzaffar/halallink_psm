<template>
  <div class="dashboard">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
      <h1 class="mb-2 mb-md-0 fs-2 fs-md-1">Transparency Dashboard</h1>
      <div class="badge bg-info text-dark">
        <i class="fas fa-eye me-1"></i>
        Public Performance Metrics
      </div>
    </div>
    
    <!-- Stats Cards Row -->
    <StatsCards :stats="companyStats" class="mb-4" />

    <!-- Charts Row -->
    <div class="row mb-4">
      <div class="col-lg-6 mb-4 mb-lg-0">
        <BroilerSalesPieChart />
      </div>
      <div class="col-lg-6">
        <MarketLineChart />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import StatsCards from '../components/dashboard/StatsCards.vue';
import BroilerSalesPieChart from '../components/dashboard/BroilerSalesPieChart.vue';
import SalesLineChart from '../components/dashboard/MarketLineChart.vue';
import DashboardService from '../services/dashboardService';
import modal from '../utils/modal';
import MarketLineChart from '../components/dashboard/MarketLineChart.vue';

export default {
  name: 'Dashboard',
  components: {
    StatsCards,
    BroilerSalesPieChart,
    MarketLineChart
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
    const companyStats = computed(() => {
      // Add a null check to ensure stats.value is defined
      if (!stats.value) {
        return [];
      }
      
      return [
        {
          title: 'Broiler Companies',
          count: stats.value.broiler || 0,
          icon: 'fas fa-industry',
          bgColor: 'bg-primary'
        },
        {
          title: 'Slaughterhouse',
          count: stats.value.slaughterhouse || 0,
          icon: 'fas fa-warehouse',
          bgColor: 'bg-danger'
        },
        {
          title: 'SME',
          count: stats.value.sme || 0,
          icon: 'fas fa-store',
          bgColor: 'bg-success'
        },
        {
          title: 'Logistics',
          count: stats.value.logistic || 0,
          icon: 'fas fa-truck',
          bgColor: 'bg-warning'
        }
      ];
    });
    
    // Fetch dashboard stats
    const fetchDashboardStats = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const statsData = await DashboardService.getStats();
        stats.value = statsData;
        console.log('Fetched dashboard stats:', statsData);
      } catch (err) {
        console.error('Error fetching dashboard data:', err);
        error.value = 'Failed to load dashboard data';
        modal.danger('Error', 'Failed to load dashboard data. Please try again.');
      } finally {
        loading.value = false;
      }
    };
    
    // Refresh data
    const refreshData = () => {
      fetchDashboardStats();
    };
    
    onMounted(() => {
      fetchDashboardStats();
    });
    
    return {
      loading,
      error,
      stats,
      companyStats,
      refreshData
    };
  }
};
</script>

<style scoped>
.badge {
  font-size: 0.8rem;
  padding: 6px 10px;
}

@media (min-width: 768px) {
  .badge {
    font-size: 0.9rem;
    padding: 8px 12px;
  }
}

.card {
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  border: none;
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}
</style>