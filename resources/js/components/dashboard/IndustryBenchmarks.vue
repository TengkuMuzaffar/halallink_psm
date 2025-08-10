<template>
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="fas fa-chart-bar text-info me-2"></i>
        Stakeholder Performance by Type
      </h5>
      <button 
        @click="downloadReport" 
        :disabled="downloading"
        class="btn btn-outline-primary btn-sm"
      >
        <i class="fas fa-download me-1"></i>
        {{ downloading ? 'Downloading...' : 'Download Report' }}
      </button>
    </div>
    <div class="card-body">
      <LoadingSpinner 
        v-if="loading" 
        size="md" 
        message="Loading stakeholder performance..." 
      />
      <div v-else>
        <div class="row">
          <div 
            v-for="(data, type) in stakeholderStats" 
            :key="type"
            class="col-md-6 col-lg-3 mb-4"
          >
            <div class="stakeholder-card">
              <div class="stakeholder-header">
                <i :class="getTypeIcon(type)"></i>
                <h6 class="stakeholder-title">{{ formatType(type) }}</h6>
              </div>
              <div class="stakeholder-metrics">
                <div class="metric-item">
                  <div class="metric-label">Companies</div>
                  <div class="metric-value">{{ data.total_companies || 0 }}</div>
                </div>
                <div class="metric-item">
                  <div class="metric-label">Active Users</div>
                  <div class="metric-value">{{ data.active_users || 0 }}</div>
                </div>
                <div class="metric-item">
                  <div class="metric-label">Performance Score</div>
                  <div class="metric-value">{{ data.performance_score || 0 }}%</div>
                  <div class="metric-bar">
                    <div 
                      class="metric-fill bg-success" 
                      :style="{ width: (data.performance_score || 0) + '%' }"
                    ></div>
                  </div>
                </div>
                <div class="metric-item" v-if="type !== 'logistic'">
                  <div class="metric-label">Total Revenue</div>
                  <div class="metric-value">RM {{ formatCurrency(data.total_revenue || 0) }}</div>
                </div>
                <div class="metric-item" v-if="type === 'logistic'">
                  <div class="metric-label">Delivery Rate</div>
                  <div class="metric-value">{{ data.delivery_success_rate || 0 }}%</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import LoadingSpinner from '../ui/LoadingSpinner.vue';
import dashboardService from '../../services/dashboardService';

export default {
  name: 'IndustryBenchmarks',
  components: {
    LoadingSpinner
  },
  data() {
    return {
      stakeholderStats: {},
      loading: false,
      downloading: false
    };
  },
  async mounted() {
    await this.fetchStakeholderStats();
  },
  methods: {
    async fetchStakeholderStats() {
      this.loading = true;
      try {
        this.stakeholderStats = await dashboardService.getStakeholderStats();
      } catch (error) {
        console.error('Error fetching stakeholder stats:', error);
        this.$toast.error('Failed to load stakeholder statistics');
      } finally {
        this.loading = false;
      }
    },
    async downloadReport() {
      this.downloading = true;
      try {
        const result = await dashboardService.downloadPerformanceReport();
        this.$toast.success(`Report downloaded: ${result.filename}`);
      } catch (error) {
        console.error('Error downloading report:', error);
        this.$toast.error('Failed to download performance report');
      } finally {
        this.downloading = false;
      }
    },
    getTypeIcon(type) {
      const icons = {
        broiler: 'fas fa-egg text-warning',
        slaughterhouse: 'fas fa-industry text-danger',
        sme: 'fas fa-store text-success',
        logistic: 'fas fa-truck text-primary'
      };
      return icons[type] || 'fas fa-building';
    },
    formatType(type) {
      const types = {
        broiler: 'Broiler Farms',
        slaughterhouse: 'Slaughterhouses',
        sme: 'SME Retailers',
        logistic: 'Logistics'
      };
      return types[type] || type;
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-MY', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount);
    }
  }
};
</script>

<style scoped>
.stakeholder-card {
  border: 1px solid #e3e6f0;
  border-radius: 0.35rem;
  padding: 1rem;
  height: 100%;
  transition: all 0.3s ease;
}

.stakeholder-card:hover {
  box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
  transform: translateY(-2px);
}

.stakeholder-header {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
}

.stakeholder-header i {
  font-size: 1.5rem;
  margin-right: 0.5rem;
}

.stakeholder-title {
  margin: 0;
  font-weight: 600;
  color: #5a5c69;
}

.metric-item {
  margin-bottom: 0.75rem;
}

.metric-label {
  font-size: 0.8rem;
  color: #858796;
  margin-bottom: 0.25rem;
}

.metric-value {
  font-size: 1.1rem;
  font-weight: 600;
  color: #5a5c69;
}

.metric-bar {
  height: 4px;
  background-color: #e3e6f0;
  border-radius: 2px;
  margin-top: 0.25rem;
  overflow: hidden;
}

.metric-fill {
  height: 100%;
  transition: width 0.3s ease;
}
</style>