<template>
  <div class="report-details-container">
    <LoadingSpinner v-if="loading" overlay size="lg" />
    
    <div v-else class="report-details-content">
      <!-- Report Information Section -->
      <div class="report-info-section">
        <h5 class="section-title">Report Information</h5>
        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Issued By:</span>
            <span class="info-value">{{ report.user ? report.user.email : 'N/A' }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Phone:</span>
            <span class="info-value">{{ report.user ? report.user.tel_number : 'N/A' }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Start Date:</span>
            <span class="info-value">{{ formatDate(report.start_timestamp) }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">End Date:</span>
            <span class="info-value">{{ formatDate(report.end_timestamp) }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Status:</span>
            <span class="info-value status-badge" :class="report.approval ? 'status-approved' : 'status-pending'">
              {{ report.approval ? 'Approved' : 'Pending' }}
            </span>
          </div>
        </div>
      </div>
      
      <!-- Companies Section -->
      <div class="companies-section">
        <h5 class="section-title">Related Companies</h5>
        <div class="search-container mb-3">
          <input 
            type="text" 
            class="form-control" 
            placeholder="Search companies..." 
            v-model="companySearchQuery"
          >
        </div>
        
        <div class="companies-list">
          <div v-if="filteredCompanies.length === 0" class="no-companies">
            No companies found
          </div>
          <div v-else class="company-item" v-for="report in filteredCompanies" :key="report.reportID">
            <div class="company-name">{{ report.company ? report.company.company_name : 'Unknown Company' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue';
import reportService from '../../../services/reportService';
import LoadingSpinner from '../../ui/LoadingSpinner.vue';

export default {
  name: 'ReportDetailsView',
  components: {
    LoadingSpinner
  },
  props: {
    reportId: {
      type: [Number, String],
      required: true
    }
  },
  emits: ['loading'],
  setup(props, { emit }) {
    const report = ref({});
    const loading = ref(true);
    const companySearchQuery = ref('');
    
    // Fetch report details
    // Fetch report details
    const fetchReportDetails = async () => {
      loading.value = true;
      emit('loading', true);
      
      try {
        const response = await reportService.getReportValidity(props.reportId);
        // Update this line to access the correct properties in the response
        report.value = response.report_validity;
        // console.log(report.value.reports);
        // If you need to access companies separately
        // companies.value = response.companies;
      } catch (error) {
        console.error('Error fetching report details:', error);
      } finally {
        loading.value = false;
        emit('loading', false);
      }
    };
    
    // Format date
    const formatDate = (timestamp, includeTime = false) => {
      if (!timestamp) return 'N/A';
      
      const date = new Date(timestamp);
      const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric'
      };
      
      if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
      }
      
      return date.toLocaleDateString('en-US', options);
    };
    
    // Filter companies based on search query
    const filteredCompanies = computed(() => {
      if (!report.value || !report.value.reports) return [];
      
      if (!companySearchQuery.value.trim()) {
        return report.value.reports;
      }
      
      const query = companySearchQuery.value.toLowerCase();
      return report.value.reports.filter(report => 
        report.company && report.company.company_name && 
        report.company.company_name.toLowerCase().includes(query)
      );
    });
    
    // Fetch data when component is created
    fetchReportDetails();
    
    // Watch for changes in reportId
    watch(() => props.reportId, () => {
      fetchReportDetails();
    });
    
    return {
      report,
      loading,
      companySearchQuery,
      filteredCompanies,
      formatDate
    };
  }
};
</script>

<style scoped>
.report-details-container {
  position: relative;
  min-height: 200px;
}

/* Remove the .loading-overlay class as it's no longer needed */

.report-details-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.section-title {
  color: var(--primary-color);
  border-bottom: 2px solid var(--primary-color);
  padding-bottom: 0.5rem;
  margin-bottom: 1rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-label {
  font-weight: bold;
  color: #555;
  margin-bottom: 0.25rem;
}

.info-value {
  font-size: 1.1rem;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-weight: 500;
}

.status-approved {
  background-color: #d4edda;
  color: #155724;
}

.status-pending {
  background-color: #fff3cd;
  color: #856404;
}

.companies-section {
  margin-top: 1rem;
}

.companies-list {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
}

.company-item {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #dee2e6;
}

.company-item:last-child {
  border-bottom: none;
}

.company-name {
  font-weight: 500;
}

.no-companies {
  padding: 1rem;
  text-align: center;
  color: #6c757d;
}
</style>