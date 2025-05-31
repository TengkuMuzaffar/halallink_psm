<template>
  <div class="dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Transparency Dashboard</h1>
      <div class="badge bg-info text-dark">
        <i class="fas fa-eye me-1"></i>
        Public Performance Metrics
      </div>
    </div>
    
    <!-- Stats Cards Row -->
    <StatsCards :stats="companyStats" class="mb-4" />
    
    <!-- Top Performers Section -->
    <div class="row mb-4">
      <div class="col-12">
        <TopPerformers 
          :top-performers="topPerformers" 
          :loading="loading"
          class="mb-4" 
        />
      </div>
    </div>

    <!-- Performance Leaderboard -->
    <div class="row mb-4">
      <div class="col-12">
        <PerformanceLeaderboard 
          :performance-data="performanceData" 
          :loading="loading"
          class="mb-4" 
        />
      </div>
    </div>

    <!-- Industry Benchmarks -->
    <div class="row mb-4">
      <div class="col-12">
        <IndustryBenchmarks 
          :benchmarks="industryBenchmarks" 
          :loading="loading"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import StatsCards from '../components/dashboard/StatsCards.vue';
import TopPerformers from '../components/dashboard/TopPerformers.vue';
import PerformanceLeaderboard from '../components/dashboard/PerformanceLeaderboard.vue';
import IndustryBenchmarks from '../components/dashboard/IndustryBenchmarks.vue';
import DashboardService from '../services/dashboardService';
import modal from '../utils/modal';

export default {
  name: 'Dashboard',
  components: {
    StatsCards,
    TopPerformers,
    PerformanceLeaderboard,
    IndustryBenchmarks
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
    const topPerformers = ref({});
    const performanceData = ref([]);
    const industryBenchmarks = ref({});
    
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
    
    // Fetch all dashboard data
    const fetchDashboardData = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        // Fetch all data in parallel
        const [statsData, topPerformersData, performanceMetrics, benchmarks] = await Promise.all([
          DashboardService.getStats(),
          DashboardService.getTopPerformers(),
          DashboardService.getPerformanceMetrics(),
          DashboardService.getIndustryBenchmarks()
        ]);
        
        stats.value = statsData;
        topPerformers.value = topPerformersData;
        performanceData.value = performanceMetrics;
        industryBenchmarks.value = benchmarks;
        
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
      fetchDashboardData();
    };
    
    onMounted(() => {
      fetchDashboardData();
    });
    
    return {
      loading,
      error,
      stats,
      companyStats,
      topPerformers,
      performanceData,
      industryBenchmarks,
      refreshData
    };
  }
};
</script>

<style scoped>
.dashboard {
  padding: 20px;
}

.badge {
  font-size: 0.9rem;
  padding: 8px 12px;
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