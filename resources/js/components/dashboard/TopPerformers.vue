<template>
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-trophy text-warning me-2"></i>
        Top Performers by Category
      </h5>
    </div>
    <div class="card-body">
      <LoadingSpinner 
        v-if="loading" 
        size="md" 
        message="Loading top performers..." 
      />
      <div v-else>
        <div class="row">
          <div 
            v-for="(category, categoryName) in performanceCategories" 
            :key="categoryName"
            class="col-md-6 mb-4"
          >
            <div class="category-card">
              <div class="category-header">
                <i :class="getCategoryIcon(categoryName)"></i>
                <h6>{{ formatCategoryName(categoryName) }}</h6>
              </div>
              <div class="performers-list">
                <div 
                  v-for="(performer, index) in category.slice(0, 3)" 
                  :key="performer.company.id"
                  class="performer-item"
                >
                  <div class="performer-rank">{{ index + 1 }}</div>
                  <div class="performer-info">
                    <div class="performer-name">{{ performer.company.name }}</div>
                    <div class="performer-type">{{ formatCompanyType(performer.company.type) }}</div>
                  </div>
                  <div class="performer-score">
                    {{ getScoreForCategory(performer, categoryName) }}
                  </div>
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
  name: 'TopPerformers',
  components: {
    LoadingSpinner
  },
  data() {
    return {
      performanceCategories: {},
      loading: false
    };
  },
  async mounted() {
    await this.fetchTopPerformers();
  },
  methods: {
    async fetchTopPerformers() {
      this.loading = true;
      try {
        const reportData = await dashboardService.getPerformanceReport();
        this.processPerformanceData(reportData);
      } catch (error) {
        console.error('Error fetching top performers:', error);
        this.$toast.error('Failed to load top performers');
      } finally {
        this.loading = false;
      }
    },
    processPerformanceData(reportData) {
      // Group performers by different categories
      const allPerformers = [];
      
      Object.values(reportData.company_types || {}).forEach(typeData => {
        allPerformers.push(...typeData);
      });

      this.performanceCategories = {
        overall: allPerformers.sort((a, b) => b.overall_score - a.overall_score),
        revenue: allPerformers.filter(p => p.metrics.financial_metrics)
          .sort((a, b) => (b.metrics.financial_metrics.total_revenue || 0) - (a.metrics.financial_metrics.total_revenue || 0)),
        quality: allPerformers.filter(p => p.metrics.quality_metrics)
          .sort((a, b) => (b.metrics.quality_metrics.verification_success_rate || 0) - (a.metrics.quality_metrics.verification_success_rate || 0)),
        delivery: allPerformers.filter(p => p.metrics.delivery_metrics)
          .sort((a, b) => (b.metrics.delivery_metrics.success_rate || 0) - (a.metrics.delivery_metrics.success_rate || 0))
      };
    },
    getCategoryIcon(category) {
      const icons = {
        overall: 'fas fa-star text-warning',
        revenue: 'fas fa-dollar-sign text-success',
        quality: 'fas fa-check-circle text-info',
        delivery: 'fas fa-truck text-primary'
      };
      return icons[category] || 'fas fa-chart-bar';
    },
    formatCategoryName(category) {
      const names = {
        overall: 'Overall Performance',
        revenue: 'Revenue Leaders',
        quality: 'Quality Excellence',
        delivery: 'Delivery Performance'
      };
      return names[category] || category;
    },
    formatCompanyType(type) {
      const types = {
        broiler: 'Broiler',
        slaughterhouse: 'Slaughterhouse',
        sme: 'SME',
        logistic: 'Logistics'
      };
      return types[type] || type;
    },
    getScoreForCategory(performer, category) {
      switch (category) {
        case 'overall':
          return `${performer.overall_score}%`;
        case 'revenue':
          return `RM ${this.formatCurrency(performer.metrics.financial_metrics?.total_revenue || 0)}`;
        case 'quality':
          return `${performer.metrics.quality_metrics?.verification_success_rate || 0}%`;
        case 'delivery':
          return `${performer.metrics.delivery_metrics?.success_rate || 0}%`;
        default:
          return 'N/A';
      }
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-MY', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount);
    }
  }
};
</script>

<style scoped>
.category-card {
  border: 1px solid #e3e6f0;
  border-radius: 0.35rem;
  padding: 1rem;
  height: 100%;
}

.category-header {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e3e6f0;
}

.category-header i {
  font-size: 1.2rem;
  margin-right: 0.5rem;
}

.category-header h6 {
  margin: 0;
  font-weight: 600;
  color: #5a5c69;
}

.performer-item {
  display: flex;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f8f9fc;
}

.performer-item:last-child {
  border-bottom: none;
}

.performer-rank {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #e3e6f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 600;
  color: #5a5c69;
  margin-right: 0.75rem;
}

.performer-info {
  flex: 1;
}

.performer-name {
  font-weight: 600;
  color: #5a5c69;
  font-size: 0.9rem;
}

.performer-type {
  font-size: 0.75rem;
  color: #858796;
}

.performer-score {
  font-weight: 600;
  color: #5a5c69;
  font-size: 0.9rem;
}
</style>