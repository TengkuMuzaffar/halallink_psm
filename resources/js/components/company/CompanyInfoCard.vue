<template>
  <div class="card h-100">
    <div class="card-header theme-header">
      <h5 class="mb-0">Company Information</h5>
    </div>
    <div class="card-body">
      <div class="row">
        <!-- Company Image -->
        <div class="col-md-3 text-center mb-3 mb-md-0">
          <img 
            :src="company.company_image || '/images/blank.jpg'" 
            alt="Company Logo" 
            class="img-fluid rounded company-image"
          >
        </div>
        <!-- Company Details -->
        <div class="col-md-9">
          <div class="row mb-3">
            <div class="col-md-4 fw-bold">Company Name:</div>
            <div class="col-md-8">{{ company.company_name }}</div>
          </div>
          <div class="row mb-3">
            <div class="col-md-4 fw-bold">Company Type:</div>
            <div class="col-md-8">
              <span :class="getTypeBadgeClass(company.company_type)">{{ company.company_type }}</span>
            </div>
          </div>
          <div class="row mb-3" v-if="company.admin">
            <div class="col-md-4 fw-bold">Admin Email:</div>
            <div class="col-md-8">{{ company.admin.email }}</div>
          </div>
          <div class="row mb-3" v-if="company.admin">
            <div class="col-md-4 fw-bold">Admin Phone:</div>
            <div class="col-md-8">{{ company.admin.tel_number }}</div>
          </div>
          <div class="row mb-3" v-if="company.admin">
            <div class="col-md-4 fw-bold">Status:</div>
            <div class="col-md-8">
              <span :class="getStatusBadgeClass(company.admin.status)">{{ company.admin.status }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CompanyInfoCard',
  props: {
    company: {
      type: Object,
      required: true
    }
  },
  methods: {
    getTypeBadgeClass(type) {
      const classes = 'badge ';
      switch (type) {
        case 'broiler': return classes + 'bg-primary';
        case 'slaughterhouse': return classes + 'bg-danger';
        case 'sme': return classes + 'bg-success';
        case 'logistic': return classes + 'bg-warning';
        default: return classes + 'bg-secondary';
      }
    },
    getStatusBadgeClass(status) {
      const classes = 'badge ';
      switch (status) {
        case 'active': return classes + 'bg-success';
        case 'inactive': return classes + 'bg-danger';
        default: return classes + 'bg-secondary';
      }
    }
  }
};
</script>

<style scoped>
.company-image {
  max-height: 150px;
  object-fit: contain;
}

.badge {
  font-size: 0.8rem;
  padding: 0.35em 0.65em;
}
</style>