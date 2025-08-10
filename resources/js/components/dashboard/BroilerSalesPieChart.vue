<template>
  <div class="card h-100">
    <div class="card-header" style="background-color: var(--light-bg); border-bottom: 1px solid var(--border-color);">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0" style="color: var(--text-color);">
          <i class="fas fa-chart-pie" style="color: var(--primary-color);"></i>
          Broiler Sales
        </h5>
      </div>
    </div>
    <div class="card-body d-flex align-items-center justify-content-center">
      <LoadingSpinner 
        v-if="loading" 
        message="Loading chart data..."
        size="md"
      />
      <div v-else-if="error" class="text-center py-5 text-danger">
        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
        <p>{{ error }}</p>
      </div>
      <div v-else-if="!hasData" class="text-center py-5 text-muted">
        <i class="fas fa-chart-pie fa-2x mb-3"></i>
        <p>No sales data available for this period</p>
      </div>
      <div v-else class="chart-container" style="position: relative; height: 300px; width: 100%">
        <Pie 
          :data="chartData" 
          :options="chartOptions" 
          aria-label="Broiler sales pie chart showing top performing companies"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { Pie } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, Title } from 'chart.js';
import DashboardService from '../../services/dashboardService';
import LoadingSpinner from '../ui/LoadingSpinner.vue';

ChartJS.register(ArcElement, Tooltip, Legend, Title);

export default {
  name: 'BroilerSalesPieChart',
  components: {
    Pie,
    LoadingSpinner
  },
  props: {
    period: {
      type: String,
      required: true,
      default: 'month',
      validator: (value) => ['month', 'quarter', 'year'].includes(value)
    }
  },
  setup(props) {
    const loading = ref(false);
    const error = ref(null);
    const salesData = ref([]);
    
    // Remove local period state and periodOptions
    
    const hasData = computed(() => salesData.value && salesData.value.length > 0);
    
    const chartData = computed(() => {
      if (!hasData.value) return { labels: [], datasets: [] };
      
      return {
        labels: salesData.value.map(item => item.company),
        datasets: [
          {
            data: salesData.value.map(item => item.sales),
            backgroundColor: [
              '#4e73df', // primary
              '#1cc88a', // success
              '#36b9cc', // info
              '#f6c23e', // warning
              '#e74a3b', // danger
            ],
            borderWidth: 1
          }
        ]
      };
    });
    
    const chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        },
        title: {
          display: true,
          text: 'Top Broiler Companies by Sales',
          font: {
            size: 16
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.raw || 0;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = Math.round((value / total) * 100);
              return `${label}: ${value} sales (${percentage}%)`;
            }
          }
        }
      }
    };
    
    const fetchSalesData = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await DashboardService.getBroilerSalesData(props.period);
        salesData.value = response.data || [];
      } catch (err) {
        console.error('Error fetching sales data:', err);
        error.value = 'Failed to load sales data';
      } finally {
        loading.value = false;
      }
    };
    
    // Remove changePeriod function
    
    // Watch for changes to the period prop
    watch(() => props.period, () => {
      fetchSalesData();
    });
    
    onMounted(() => {
      fetchSalesData();
    });
    
    return {
      loading,
      error,
      salesData,
      hasData,
      chartData,
      chartOptions
      // Remove period, periodOptions, and changePeriod from return
    };
  }
};
</script>