<template>
  <div class="dashboard">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
      <h1 class="mb-2 mb-md-0 fs-2 fs-md-1">Performance Dashboard</h1>
      <button class="btn download-btn" @click="downloadReport">
        <i class="fas fa-download me-1"></i>
        Download Performance Report
      </button>
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
    
    // Download performance report
    const downloadReport = () => {
      try {
        // Show loading indicator
        loading.value = true;
        modal.info('Downloading', 'Preparing performance report for download...');
        
        // In a real implementation, you would call an API endpoint to generate the report
        // For now, we'll simulate a download after a short delay
        setTimeout(() => {
          // This would be replaced with actual API call in production
          window.location.href = '/api/dashboard/download-report';
          
          // Hide loading indicator
          loading.value = false;
          modal.success('Success', 'Performance report download initiated.');
        }, 1000);
      } catch (err) {
        console.error('Error downloading report:', err);
        loading.value = false;
        modal.danger('Error', 'Failed to download performance report. Please try again.');
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
      refreshData,
      downloadReport
    };
  }
};
</script>

<style scoped>
.download-btn {
  font-size: 0.8rem;
  padding: 6px 10px;
  background-color: var(--secondary-color);
  color: var(--primary-color);
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}

.download-btn:hover {
  background-color: var(--accent-color);
  color: white;
}

@media (min-width: 768px) {
  .download-btn {
    font-size: 0.9rem;
    padding: 8px 12px;
  }
}

.card {
  box-shadow: 0 2px 4px var(--border-color);
  border: none;
  background-color: var(--lighter-bg);
}

.card-header {
  background-color: var(--light-bg);
  border-bottom: 1px solid var(--border-color);
  color: var(--text-color);
}
</style>