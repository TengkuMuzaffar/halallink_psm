<template>
  <div class="card h-100">
    <div class="card-header">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <i class="fas fa-chart-line text-primary me-2"></i>
          Marketplace Activity
        </h5>
        <div class="btn-group">
          <button 
            v-for="option in periodOptions" 
            :key="option.value"
            class="btn btn-sm" 
            :class="period === option.value ? 'btn-primary' : 'btn-outline-primary'"
            @click="changePeriod(option.value)"
          >
            {{ option.label }}
          </button>
        </div>
      </div>
    </div>
    <div class="card-body d-flex align-items-center justify-content-center">
      <LoadingSpinner 
        v-if="loading" 
        message="Loading activity data..."
        size="md"
      />
      <div v-else-if="error" class="text-center py-5 text-danger">
        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
        <p>{{ error }}</p>
      </div>
      <div v-else-if="!hasData" class="text-center py-5 text-muted">
        <i class="fas fa-chart-line fa-2x mb-3"></i>
        <p>No order activity available for this period</p>
      </div>
      <div v-else class="chart-container" style="position: relative; height: 300px; width: 100%">
        <Line 
          :data="chartData" 
          :options="chartOptions" 
          aria-label="Marketplace activity line chart showing order counts over time"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js';
import DashboardService from '../../services/dashboardService';
import LoadingSpinner from '../ui/LoadingSpinner.vue';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

export default {
  name: 'MarketLineChart',
  components: {
    Line,
    LoadingSpinner
  },
  setup() {
    const loading = ref(false);
    const error = ref(null);
    const activityData = ref([]);
    const period = ref('month');
    
    const periodOptions = [
      { value: 'month', label: 'Month' },
      { value: 'quarter', label: 'Quarter' },
      { value: 'year', label: 'Year' }
    ];
    
    const hasData = computed(() => activityData.value && activityData.value.length > 0);
    
    const chartData = computed(() => {
      if (!hasData.value) return { labels: [], datasets: [] };
      
      return {
        labels: activityData.value.map(item => item.date),
        datasets: [
          {
            label: 'Order Count',
            data: activityData.value.map(item => item.count),
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            borderWidth: 2,
            pointBackgroundColor: '#4e73df',
            pointBorderColor: '#fff',
            pointHoverRadius: 5,
            pointHoverBackgroundColor: '#4e73df',
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 2,
            pointRadius: 3,
            pointHitRadius: 10,
            fill: true,
            tension: 0.4
          }
        ]
      };
    });
    
    const chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        title: {
          display: true,
          text: 'Order Activity Over Time',
          font: {
            size: 16
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return `Orders: ${context.raw}`;
            }
          }
        }
      },
      scales: {
        x: {
          grid: {
            display: false
          }
        },
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0 // Only show whole numbers
          }
        }
      }
    };
    
    const fetchActivityData = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await DashboardService.getMarketplaceActivity(period.value);
        activityData.value = response.data || [];
      } catch (err) {
        console.error('Error fetching activity data:', err);
        error.value = 'Failed to load activity data';
      } finally {
        loading.value = false;
      }
    };
    
    const changePeriod = (newPeriod) => {
      period.value = newPeriod;
    };
    
    watch(period, () => {
      fetchActivityData();
    });
    
    onMounted(() => {
      fetchActivityData();
    });
    
    return {
      loading,
      error,
      activityData,
      period,
      periodOptions,
      hasData,
      chartData,
      chartOptions,
      changePeriod
    };
  }
};
</script>