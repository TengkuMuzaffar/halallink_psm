<template>
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-chart-line text-primary me-2"></i>
        Performance Leaderboard
      </h5>
    </div>
    <div class="card-body">
      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <div v-else>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>Rank</th>
                <th>Company</th>
                <th>Type</th>
                <th>Overall Score</th>
                <th>Order Success</th>
                <th>Delivery Rate</th>
                <th>Quality Score</th>
                <th>Payment Success</th>
                <th>Certifications</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="(item, index) in performanceData.slice(0, 20)" 
                :key="item.company.id"
                :class="getRankClass(index)"
              >
                <td>
                  <div class="rank-display">
                    <span v-if="index < 3" class="trophy-icon">
                      <i :class="getTrophyIcon(index)"></i>
                    </span>
                    <span v-else class="rank-number">{{ index + 1 }}</span>
                  </div>
                </td>
                <td>
                  <div class="company-cell">
                    <img 
                      v-if="item.company.image" 
                      :src="item.company.image" 
                      :alt="item.company.name"
                      class="company-logo"
                    >
                    <div>
                      <div class="company-name">{{ item.company.name }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge" :class="getTypeClass(item.company.type)">
                    {{ item.company.type }}
                  </span>
                </td>
                <td>
                  <div class="score-cell">
                    <div class="score-value">{{ item.overall_score }}%</div>
                    <div class="progress progress-sm">
                      <div 
                        class="progress-bar bg-primary" 
                        :style="{ width: item.overall_score + '%' }"
                      ></div>
                    </div>
                  </div>
                </td>
                <td>{{ item.metrics.order_metrics.success_rate }}%</td>
                <td>{{ item.metrics.delivery_metrics.success_rate }}%</td>
                <td>{{ item.metrics.quality_metrics.verification_success_rate }}%</td>
                <td>{{ item.metrics.financial_metrics.payment_success_rate }}%</td>
                <td>
                  <span class="badge bg-info">
                    {{ item.metrics.certification_metrics.total_certifications }}
                  </span>
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
export default {
  name: 'PerformanceLeaderboard',
  props: {
    performanceData: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    getRankClass(index) {
      if (index === 0) return 'table-warning';
      if (index === 1) return 'table-secondary';
      if (index === 2) return 'table-light';
      return '';
    },
    getTrophyIcon(index) {
      const icons = [
        'fas fa-trophy text-warning',
        'fas fa-medal text-secondary', 
        'fas fa-award text-warning'
      ];
      return icons[index];
    },
    getTypeClass(type) {
      const classes = {
        'broiler': 'bg-primary',
        'slaughterhouse': 'bg-danger',
        'SME': 'bg-success',
        'logistic': 'bg-warning text-dark'
      };
      return classes[type] || 'bg-secondary';
    }
  }
};
</script>

<style scoped>
.company-cell {
  display: flex;
  align-items: center;
}

.company-logo {
  width: 32px;
  height: 32px;
  border-radius: 4px;
  margin-right: 10px;
  object-fit: cover;
}

.company-name {
  font-weight: 600;
  color: #212529;
}

.rank-display {
  display: flex;
  align-items: center;
  justify-content: center;
}

.trophy-icon {
  font-size: 18px;
}

.rank-number {
  font-weight: bold;
  color: #495057;
}

.score-cell {
  min-width: 120px;
}

.score-value {
  font-weight: bold;
  margin-bottom: 4px;
}

.progress-sm {
  height: 6px;
}

.table th {
  border-top: none;
  font-weight: 600;
  color: #495057;
}

.table-hover tbody tr:hover {
  background-color: rgba(0,123,255,0.05);
}
</style>