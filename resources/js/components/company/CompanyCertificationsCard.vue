<template>
  <div class="card h-100">
    <div class="card-header theme-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Company Certifications</h5>
    </div>
    <div class="card-body">
      <!-- Updated with LoadingSpinner -->
      <LoadingSpinner v-if="loading" size="sm" message="Loading certifications..." />
      
      <div v-else-if="error" class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ error }}
      </div>
      <div v-else-if="certifications.length === 0" class="text-center py-3">
        <i class="fas fa-certificate text-muted mb-2" style="font-size: 2rem;"></i>
        <p class="text-muted">No certifications found</p>
      </div>
      <div v-else class="row">
        <div v-for="cert in certifications" :key="cert.certID" class="col-md-6 col-lg-6 mb-3">
          <div class="cert-card border rounded p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h6 class="mb-0">{{ cert.cert_type }}</h6>
              <span class="badge bg-info">ID: {{ cert.certID }}</span>
            </div>
            <div class="cert-file-container">
              <a 
                v-if="cert.cert_file" 
                :href="cert.cert_file" 
                target="_blank" 
                class="btn btn-sm btn-outline-primary w-100"
              >
                <i class="fas fa-file-pdf me-1"></i> View Certificate
              </a>
              <div v-else class="text-muted small">No file attached</div>
            </div>
            <div class="text-muted small mt-2">
              Added: {{ formatDate(cert.created_at) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import LoadingSpinner from '../ui/LoadingSpinner.vue';

export default {
  name: 'CompanyCertificationsCard',
  components: {
    LoadingSpinner
  },
  props: {
    certifications: {
      type: Array,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: null
    }
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-MY');
    }
  }
};
</script>

<style scoped>
.theme-header {
  background-color: #123524;
  color: #EFE3C2;
  border-bottom: none;
}
.cert-card {
  transition: all 0.2s ease;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.cert-card:hover {
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.cert-file-container {
  margin-top: 10px;
}
</style>