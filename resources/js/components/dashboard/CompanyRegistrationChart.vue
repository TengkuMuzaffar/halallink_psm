<template>
  <div class="card h-100">
    <div class="card-header" style="background-color: var(--light-bg); border-bottom: 1px solid var(--border-color);">
      <div class="d-flex justify-content-between align-items-center">
        <h5 class="mb-0" style="color: var(--text-color);">
          <i class="fas fa-building" style="color: var(--primary-color);"></i>
          Company Registration Trend
        </h5>
      </div>
    </div>
    <div class="card-body d-flex align-items-center justify-content-center">
      <LoadingSpinner 
        v-if="loading" 
        message="Loading registration data..."
        size="md"
      />
      <div v-else-if="error" class="text-center py-5 text-danger">
        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
        <p>{{ error }}</p>
      </div>
      <div v-else-if="!hasData" class="text-center py-5 text-muted">
        <i class="fas fa-building fa-2x mb-3"></i>
        <p>No registration data available for this period</p>
      </div>
      <div v-else class="chart-container" style="position: relative; height: 300px; width: 100%">
        <Line 
          :data="chartData" 
          :options="chartOptions" 
          aria-label="Company registration trend line chart showing registration counts over time"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler } from 'chart.js';
import DashboardService from '../../services/dashboardService';
import LoadingSpinner from '../ui/LoadingSpinner.vue';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

export default {
  name: 'CompanyRegistrationChart',
  components: {
    Line,
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
    const registrationData = ref([]);
    
    const hasData = computed(() => registrationData.value && registrationData.value.length > 0);
    
    const chartData = computed(() => {
      if (!hasData.value) return { labels: [], datasets: [] };
      
      return {
        labels: registrationData.value.map(item => item.date),
        datasets: [
          {
            label: 'Registrations',
            data: registrationData.value.map(item => item.count),
            borderColor: '#36b9cc',
            backgroundColor: 'rgba(54, 185, 204, 0.1)',
            borderWidth: 2,
            pointBackgroundColor: '#36b9cc',
            pointBorderColor: '#fff',
            pointHoverRadius: 5,
            pointHoverBackgroundColor: '#36b9cc',
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
          text: 'Company Registrations Over Time',
          font: {
            size: 16
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return `Registrations: ${context.raw}`;
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
    
    const fetchRegistrationData = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await DashboardService.getCompanyRegistrationTrend(props.period);
        registrationData.value = response.data || [];
      } catch (err) {
        console.error('Error fetching registration data:', err);
        error.value = 'Failed to load registration data';
      } finally {
        loading.value = false;
      }
    };
    
    // Watch for changes to the period prop
    watch(() => props.period, () => {
      fetchRegistrationData();
    });
    
    onMounted(() => {
      fetchRegistrationData();
    });
    
    return {
      loading,
      error,
      registrationData,
      hasData,
      chartData,
      chartOptions
    };
  }
};
</script>