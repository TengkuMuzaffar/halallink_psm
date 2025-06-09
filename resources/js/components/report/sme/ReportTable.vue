<template>
  <div class="report-table-container">
    <div class="card">
      <div class="card-header theme-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Available Reports</h5>
        <button class="btn btn-sm theme-btn-outline" @click="refreshData">
          <i class="fas fa-sync-alt me-2"></i> <span class="d-none d-md-inline">Refresh</span>
        </button>
      </div>
      
      <div class="card-body">
        <ResponsiveTable
          :columns="columns"
          :items="reportValidities"
          :loading="loading"
          :total-items="totalItems"
          :current-page="currentPage"
          :per-page="perPage"
          :server-side="true"
          @page-changed="handlePageChange"
          @search="handleSearch"
        >
          <!-- Custom column slots -->
          <template #start_timestamp="{ item }">
            {{ formatDate(item.start_timestamp, true) }}
          </template>
          
          <template #end_timestamp="{ item }">
            {{ formatDate(item.end_timestamp, true) }}
          </template>
          
          <template #approval="{ item }">
            <span 
              class="badge" 
              :class="item.approval ? 'bg-success' : 'bg-warning'"
            >
              {{ item.approval ? 'Approved' : 'Pending' }}
            </span>
          </template>
          
          <!-- Actions slot -->
          <template #actions="{ item }">
            <div class="d-flex justify-content-end">
              <button 
                class="btn btn-sm btn-outline-primary me-2" 
                @click="downloadReport(item)"
                :disabled="!item.approval"
                :title="item.approval ? 'Download Report' : 'Report not approved yet'"
              >
                <i class="fas fa-download"></i>
              </button>
              <button 
                class="btn btn-sm btn-outline-success" 
                @click="downloadQrCode(item)"
                :disabled="!item.approval"
                :title="item.approval ? 'Download QR Code' : 'Report not approved yet'"
              >
                <i class="fas fa-qrcode"></i>
              </button>
            </div>
          </template>
        </ResponsiveTable>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import ResponsiveTable from '../../ui/ResponsiveTable.vue';
import reportService from '../../../services/reportService';
import modal from '../../../utils/modal';
import { formatDate } from '../../../utils/dateUtils';

export default {
  name: 'SmeReportTable',
  components: {
    ResponsiveTable
  },
  setup() {
    // Table data
    const reportValidities = ref([]);
    const loading = ref(true);
    const error = ref(null);
    
    // Pagination
    const currentPage = ref(1);
    const perPage = ref(10);
    const totalItems = ref(0);
    
    // Search
    const searchQuery = ref('');
    
    // Table columns
    const columns = [
      { key: 'start_timestamp', label: 'Start Date', sortable: true },
      { key: 'end_timestamp', label: 'End Date', sortable: true },
      { key: 'approval', label: 'Status', sortable: true },
    ];
    const refreshData = () => {
      // Reset to first page when refreshing
      currentPage.value = 1;
      // Clear any error messages
      error.value = null;
      // Fetch fresh data
      fetchReportValidities();
    };

    // Fetch report validities for the current SME company
    const fetchReportValidities = async () => {
      loading.value = true;
      try {
        const params = {
          page: currentPage.value,
          per_page: perPage.value,
          search: searchQuery.value
        };
        
        const response = await reportService.getReportValidities(params);
        reportValidities.value = response.data;
        totalItems.value = response.pagination.total;
      } catch (err) {
        error.value = err.message || 'Failed to fetch report validities';
        modal.danger('Error', error.value);
      } finally {
        loading.value = false;
      }
    };
    
    // Handle page change
    const handlePageChange = (page) => {
      currentPage.value = page;
      fetchReportValidities();
    };
    
    // Handle search
    const handleSearch = (query) => {
      searchQuery.value = query;
      currentPage.value = 1; // Reset to first page on new search
      fetchReportValidities();
    };
    
    // Download report (only if approved)
    const downloadReport = async (item) => {
      if (!item.approval) {
        modal.warning('Report Not Available', 'This report has not been approved yet.');
        return;
      }
      
      try {
        loading.value = true;
        // Use the new downloadReportPdf method
        reportService.downloadReportPdf(item.reportValidityID);
        modal.success('Success', 'Report download initiated.');
      } catch (err) {
        modal.danger('Error', 'Failed to download report');
      } finally {
        loading.value = false;
      }
    };
    
    // Download QR code (only if approved)
    const downloadQrCode = async (item) => {
      if (!item.approval) {
        modal.warning('QR Code Not Available', 'This report has not been approved yet.');
        return;
      }
      
      try {
        loading.value = true;
        // Use the new downloadReportQrCode method with await
        const response = await reportService.downloadReportQrCode(item.reportValidityID);
        
        // Log the response to see what's being returned
        // console.log('QR Code Response:', response);
        
        modal.success('Success', 'QR code download initiated.');
      } catch (err) {
        // Log the detailed error information
        console.error('QR Code Error:', err);
        console.error('Error details:', {
          message: err.message,
          status: err.response?.status,
          statusText: err.response?.statusText,
          data: err.response?.data
        });
        
        modal.danger('Error', 'Failed to download QR code: ' + (err.message || 'Unknown error'));
      } finally {
        loading.value = false;
      }
    };
    
    // Initialize
    onMounted(() => {
      fetchReportValidities();
    });
    
    return {
      reportValidities,
      loading,
      error,
      columns,
      currentPage,
      perPage,
      totalItems,
      handlePageChange,
      handleSearch,
      downloadReport,
      downloadQrCode, // Add the new method to the return object
      formatDate,
      refreshData
    };
  }
};
</script>

<style scoped>
/* Use CSS variables from App.vue */
.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border: none;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}
.theme-btn-outline {
  background-color: transparent;
  border: 1px solid var(--secondary-color);
  color: var(--secondary-color);
  border-radius: 5px;
  padding: 4px 10px;
  transition: all 0.2s ease;
}

/* Refresh button styling */
.theme-btn-outline {
  color: var(--secondary-color);
  border-color: var(--secondary-color);
  background-color: transparent;
}

.theme-btn-outline:hover {
  color: var(--primary-color);
  background-color: var(--secondary-color);
  border-color: var(--secondary-color);
}

.report-details ul {
  max-height: 200px;
  overflow-y: auto;
  padding-left: 1.5rem;
}

/* Modal close button styling */
.btn-close-white {
  filter: invert(1) grayscale(100%) brightness(200%);
}
</style>