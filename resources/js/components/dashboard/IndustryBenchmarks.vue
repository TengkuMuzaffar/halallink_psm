<template>
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-chart-bar text-info me-2"></i>
        Industry Benchmarks
      </h5>
    </div>
    <div class="card-body">
      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <div v-else>
        <div class="row">
          <div 
            v-for="(benchmark, type) in benchmarks" 
            :key="type"
            class="col-md-6 col-lg-3 mb-4"
          >
            <div class="benchmark-card">
              <div class="benchmark-header">
                <i :class="getTypeIcon(type)"></i>
                <h6 class="benchmark-title">{{ formatType(type) }}</h6>
              </div>
              <div class="benchmark-metrics">
                <div class="metric-item">
                  <div class="metric-label">Order Success</div>
                  <div class="metric-value">{{ benchmark.avg_order_success_rate }}%</div>
                  <div class="metric-bar">
                    <div 
                      class="metric-fill bg-primary" 
                      :style="{ width: benchmark.avg_order_success_rate + '%' }"
                    ></div>
                  </div>
                </div>
                <div class="metric-item">
                  <div class="metric-label">Delivery Rate</div>
                  <div class="metric-value">{{ benchmark.avg_delivery_success_rate }}%</div>
                  <div class="metric-bar">
                    <div 
                      class="metric-fill bg-success" 
                      :style="{ width: benchmark.avg_delivery_success_rate + '%' }"
                    ></div>
                  </div>
                </div>
                <div class="metric-item">
                  <div class="metric-label">Quality Score</div>
                  <div class="metric-value">{{ benchmark.avg_verification_success_rate }}%</div>
                  <div class="metric-bar">
                    <div 
                      class="metric-fill bg-info" 
                      :style="{ width: benchmark.avg_verification_success_rate + '%' }"
                    ></div>
                  </div>
                </div>
                <div class="metric-item">
                  <div class="metric-label">Payment Success</div>
                  <div class="metric-value">{{ benchmark.avg_payment_success_rate }}%</div>
                  <div class="metric-bar">
                    <div 
                      class="metric-fill bg-warning" 
                      :style="{ width: benchmark.avg_payment_success_rate + '%' }"
                    ></div>
                  </div>
                </div>
                <div class="metric-item">
                  <div class="metric-label">Avg Certifications</div>
                  <div class="metric-value">{{ benchmark.avg_certifications }}</div>
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
export default {
  name: 'IndustryBenchmarks',
  props: {
    benchmarks: {
      type: Object,
      default: () => ({})
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    formatType(type) {
      const typeMap = {
        'broiler': 'Broiler Companies',
        'slaughterhouse': 'Slaughterhouses',
        'SME': 'SME Companies',
        'logistic': 'Logistics'
      };
      return typeMap[type] || type;
    },
    getTypeIcon(type) {
      const iconMap = {
        'broiler': 'fas fa-industry text-primary',
        'slaughterhouse': 'fas fa-warehouse text-danger',
        'SME': 'fas fa-store text-success',
        'logistic': 'fas fa-truck text-warning'
      };
      return iconMap[type] || 'fas fa-building';
    }
  }
};
</script>

<style scoped>
.benchmark-card {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
  height: 100%;
  border: 1px solid #e9ecef;
}

.benchmark-header {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

.benchmark-header i {
  font-size: 24px;
  margin-right: 10px;
}

.benchmark-title {
  margin: 0;
  font-weight: 600;
  color: #495057;
}

.metric-item {
  margin-bottom: 15px;
}

.metric-label {
  font-size: 12px;
  color: #6c757d;
  margin-bottom: 4px;
}

.metric-value {
  font-weight: bold;
  color: #212529;
  margin-bottom: 4px;
}

.metric-bar {
  height: 6px;
  background: #e9ecef;
  border-radius: 3px;
  overflow: hidden;
}

.metric-fill {
  height: 100%;
  transition: width 0.3s ease;
}
</style>