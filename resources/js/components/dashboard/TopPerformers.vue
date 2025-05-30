<template>
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-trophy text-warning me-2"></i>
        Top Performers
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
          <!-- Order Volume Leaders -->
          <div class="col-md-6 col-lg-3 mb-3">
            <div class="performance-category">
              <h6 class="category-title">
                <i class="fas fa-shopping-cart text-primary me-1"></i>
                Order Volume
              </h6>
              <div class="top-companies">
                <div 
                  v-for="(company, index) in topPerformers.order_volume" 
                  :key="company.companyID"
                  class="company-item"
                >
                  <div class="rank-badge">{{ index + 1 }}</div>
                  <div class="company-info">
                    <div class="company-name">{{ company.company_name }}</div>
                    <div class="company-type">{{ company.company_type }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Delivery Excellence -->
          <div class="col-md-6 col-lg-3 mb-3">
            <div class="performance-category">
              <h6 class="category-title">
                <i class="fas fa-truck text-success me-1"></i>
                Delivery Excellence
              </h6>
              <div class="top-companies">
                <div 
                  v-for="(company, index) in topPerformers.delivery_excellence" 
                  :key="company.companyID"
                  class="company-item"
                >
                  <div class="rank-badge">{{ index + 1 }}</div>
                  <div class="company-info">
                    <div class="company-name">{{ company.company_name }}</div>
                    <div class="company-type">{{ company.company_type }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Quality Assurance -->
          <div class="col-md-6 col-lg-3 mb-3">
            <div class="performance-category">
              <h6 class="category-title">
                <i class="fas fa-shield-alt text-info me-1"></i>
                Quality Assurance
              </h6>
              <div class="top-companies">
                <div 
                  v-for="(company, index) in topPerformers.quality_assurance" 
                  :key="company.companyID"
                  class="company-item"
                >
                  <div class="rank-badge">{{ index + 1 }}</div>
                  <div class="company-info">
                    <div class="company-name">{{ company.company_name }}</div>
                    <div class="company-type">{{ company.company_type }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Financial Performance -->
          <div class="col-md-6 col-lg-3 mb-3">
            <div class="performance-category">
              <h6 class="category-title">
                <i class="fas fa-dollar-sign text-warning me-1"></i>
                Revenue Leaders
              </h6>
              <div class="top-companies">
                <div 
                  v-for="(company, index) in topPerformers.financial_performance" 
                  :key="company.companyID"
                  class="company-item"
                >
                  <div class="rank-badge">{{ index + 1 }}</div>
                  <div class="company-info">
                    <div class="company-name">{{ company.company_name }}</div>
                    <div class="company-type">{{ company.company_type }}</div>
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
export default {
  name: 'TopPerformers',
  props: {
    topPerformers: {
      type: Object,
      default: () => ({})
    },
    loading: {
      type: Boolean,
      default: false
    }
  }
};
</script>

<style scoped>
.performance-category {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
  height: 100%;
}

.category-title {
  font-weight: 600;
  margin-bottom: 15px;
  color: #495057;
}

.company-item {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
  padding: 8px;
  background: white;
  border-radius: 6px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.rank-badge {
  background: #007bff;
  color: white;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  margin-right: 10px;
}

.company-info {
  flex: 1;
}

.company-name {
  font-weight: 600;
  font-size: 14px;
  color: #212529;
}

.company-type {
  font-size: 12px;
  color: #6c757d;
  text-transform: capitalize;
}
</style>