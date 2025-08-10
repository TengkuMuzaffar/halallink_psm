<template>
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-chart-line text-primary me-2"></i>
        Performance Leaderboard - All Stakeholders
      </h5>
    </div>
    <div class="card-body">
      <LoadingSpinner 
        v-if="loading" 
        size="md" 
        message="Loading performance data..." 
      />
      <div v-else>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>Rank</th>
                <th>Company</th>
                <th>Type</th>
                <th>Performance Score</th>
                <th>Trend</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="(performer, index) in topPerformers" 
                :key="performer.company.id"
                class="performance-row"
              >
                <td>
                  <div class="rank-badge" :class="getRankClass(index)">
                    {{ index + 1 }}
                  </div>
                </td>
                <td>
                  <div class="company-info">
                    <strong>{{ performer.company.name }}</strong>
                  </div>
                </td>
                <td>
                  <span class="badge" :class="getTypeBadgeClass(performer.company.type)">
                    {{ formatCompanyType(performer.company.type) }}
                  </span>
                </td>
                <td>
                  <div class="score-container">
                    <span class="score-value">{{ performer.overall_score }}%</span>
                    <div class="score-bar">
                      <div 
                        class="score-fill" 
                        :class="getScoreClass(performer.overall_score)"
                        :style="{ width: performer.overall_score + '%' }"
                      ></div>
                    </div>
                  </div>
                </td>
                <td>
                  <i class="fas fa-arrow-up text-success" v-if="performer.overall_score >= 80"></i>
                  <i class="fas fa-arrow-right text-warning" v-else-if="performer.overall_score >= 60"></i>
                  <i class="fas fa-arrow-down text-danger" v-else></i>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import LoadingSpinner from '../ui/LoadingSpinner.vue';
import dashboardService from '../../services/dashboardService';

export default {
  name: 'PerformanceLeaderboard',
  components: {
    LoadingSpinner
  },
  data() {
    return {
      topPerformers: [],
      loading: false
    };
  },
  async mounted() {
    await this.fetchPerformanceData();
  },
  methods: {
    async fetchPerformanceData() {
      this.loading = true;
      try {
        const reportData = await dashboardService.getPerformanceReport();
        this.topPerformers = reportData.top_performers || [];
      } catch (error) {
        console.error('Error fetching performance data:', error);
        this.$toast.error('Failed to load performance data');
      } finally {
        this.loading = false;
      }
    },
    getRankClass(index) {
      if (index === 0) return 'rank-gold';
      if (index === 1) return 'rank-silver';
      if (index === 2) return 'rank-bronze';
      return 'rank-default';
    },
    getTypeBadgeClass(type) {
      const classes = {
        broiler: 'bg-warning',
        slaughterhouse: 'bg-danger',
        sme: 'bg-success',
        logistic: 'bg-primary'
      };
      return classes[type] || 'bg-secondary';
    },
    getScoreClass(score) {
      if (score >= 80) return 'bg-success';
      if (score >= 60) return 'bg-warning';
      return 'bg-danger';
    },
    formatCompanyType(type) {
      const types = {
        broiler: 'Broiler',
        slaughterhouse: 'Slaughterhouse',
        sme: 'SME',
        logistic: 'Logistics'
      };
      return types[type] || type;
    }
  }
};
</script>

<style scoped>
.performance-row:hover {
  background-color: #f8f9fc;
}

.rank-badge {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  color: white;
}

.rank-gold { background: linear-gradient(45deg, #ffd700, #ffed4e); }
.rank-silver { background: linear-gradient(45deg, #c0c0c0, #e8e8e8); }
.rank-bronze { background: linear-gradient(45deg, #cd7f32, #daa520); }
.rank-default { background: #6c757d; }

.company-info strong {
  color: #5a5c69;
}

.score-container {
  min-width: 120px;
}

.score-value {
  font-weight: 600;
  color: #5a5c69;
}

.score-bar {
  height: 6px;
  background-color: #e3e6f0;
  border-radius: 3px;
  margin-top: 4px;
  overflow: hidden;
}

.score-fill {
  height: 100%;
  transition: width 0.3s ease;
}
</style>