<template>
  <div class="report-table-container">
    <div class="card">
      <div class="card-header theme-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Report Validities</h5>
        <div>
          <button class="btn btn-sm theme-btn-outline me-2" @click="refreshData">
            <i class="fas fa-sync-alt me-2"></i><span class="d-none d-md-inline">Refresh</span>
          </button>
          <button class="btn btn-primary" @click="openAddReportModal">
            <i class="fas fa-plus"></i><span class="d-none d-md-inline">Add Report</span>
          </button>
        </div>
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
          
          <template #companies="{ item }">
            <span class="badge bg-info">
              {{ item.reports ? item.reports.length : 0 }} Companies
            </span>
          </template>
          
          <!-- Actions slot -->
          <template #actions="{ item }">
            <div class="d-flex justify-content-end">
              <button 
                class="btn btn-sm btn-outline-primary me-1" 
                @click="viewReportDetails(item)"
                title="View Details"
              >
                <i class="fas fa-eye"></i>
              </button>
              <button 
                class="btn btn-sm me-1" 
                :class="item.approval ? 'btn-outline-warning' : 'btn-outline-success'"
                @click="toggleApproval(item)"
                :title="item.approval ? 'Mark as Pending' : 'Approve'"
              >
                <i :class="item.approval ? 'fas fa-times' : 'fas fa-check'"></i>
              </button>
              <button 
                class="btn btn-sm btn-outline-danger" 
                @click="confirmDelete(item)"
                title="Delete"
              >
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </template>
        </ResponsiveTable>
      </div>
    </div>

    <!-- Custom Add Report Modal -->
    <div class="modal fade" id="addReportModal" tabindex="-1" aria-labelledby="addReportModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header theme-header">
            <h5 class="modal-title" id="addReportModalLabel">Add New Report</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="addReportForm" @submit.prevent="handleCreateReport">
              <div class="mb-3">
                <label for="startTimestamp" class="form-label">Start Date</label>
                <input type="datetime-local" class="form-control" id="startTimestamp" v-model="formData.startTimestamp" required>
              </div>
              <div class="mb-3">
                <label for="endTimestamp" class="form-label">End Date</label>
                <input type="datetime-local" class="form-control" id="endTimestamp" v-model="formData.endTimestamp" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Approval Status</label>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="approvalStatus" v-model="formData.approval">
                  <label class="form-check-label" for="approvalStatus">Approved</label>
                </div>
              </div>
              
              <!-- Use our new component here -->
              <CompanySearchAndSelection 
                v-model:selectedCompanies="selectedCompanies"
              />
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="handleCreateReport">Create Report</button>
          </div>
        </div>
      </div>
    </div>

    <!-- View Report Modal -->
    <div class="modal fade" id="viewReportModal" tabindex="-1" aria-labelledby="viewReportModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header theme-header">
            <h5 class="modal-title" id="viewReportModalLabel">Report Details</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="viewReportModalBody">
            <ReportDetailsView 
              v-if="currentReportId" 
              :reportId="currentReportId"
              @loading="loading = $event"
            />
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Confirmation Modal -->
<ConfirmationModal
  modalId="confirmCreateReportModal"
  title="Confirm Report Creation"
  message="Are you sure you want to create this report?"
  confirmLabel="Yes, Create Report"
  cancelLabel="No, Review Again"
  @confirm="submitCreateReport"
  @cancel="closeConfirmationModal"
/>
</template>

<script>
import { ref, onMounted, computed, nextTick } from 'vue';
import ResponsiveTable from '../../ui/ResponsiveTable.vue';
import CompanySearchAndSelection from './CompanySearchAndSelection.vue';
import ConfirmationModal from './ConfirmationModal.vue';
import reportService from '../../../services/reportService';
import modal from '../../../utils/modal';
import { formatDate } from '../../../utils/dateUtils';
import * as bootstrap from 'bootstrap';
import ReportDetailsView from './ReportDetailsView.vue';

export default {
  name: 'AdminReportTable',
  components: {
    CompanySearchAndSelection,
    ReportDetailsView,
    ResponsiveTable,
    ConfirmationModal
  },
  setup() {
    // Table data
    const reportValidities = ref([]);
    const loading = ref(true);
    const error = ref(null);
    // Add to the refs:
    const currentReportId = ref(null);
    // Pagination
    const currentPage = ref(1);
    const perPage = ref(10);
    const totalItems = ref(0);
    
    // Search
    const searchQuery = ref('');
    
    // Modal references
    const addReportModalRef = ref(null);
    const viewReportModalRef = ref(null);
    
    // Form data
    const formData = ref({
      startTimestamp: '',
      endTimestamp: '',
      approval: false
    });
    
    // Selected companies for new report
    const selectedCompanies = ref([]);
    
    // Table columns
    const columns = [
      { key: 'start_timestamp', label: 'Start Date', sortable: true },
      { key: 'end_timestamp', label: 'End Date', sortable: true },
      { key: 'approval', label: 'Status', sortable: true },
      { key: 'companies', label: 'Companies', sortable: false },
    ];
    
    // Fetch report validities
    const fetchReportValidities = async () => {
      loading.value = true;
      try {
        const params = {
          page: currentPage.value,
          per_page: perPage.value,
          search: searchQuery.value
        };
        
        const response = await reportService.getAdminReportValidities(params);
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
    
    // View report details
    const viewReportDetails = async (item) => {
      try {
        // Get the modal element
        const modalElement = document.getElementById('viewReportModal');
        const modalBody = document.getElementById('viewReportModalBody');
        
        // First set to null to force component to unmount
        currentReportId.value = null;
        
        // Use nextTick to ensure the component is unmounted before setting new ID
        await nextTick();
        
        // Set current reportId
        currentReportId.value = item.reportValidityID;
        
        // Show the modal
        if (!viewReportModalRef.value) {
          viewReportModalRef.value = new bootstrap.Modal(modalElement);
        }
        viewReportModalRef.value.show();
      } catch (err) {
        modal.danger('Error', 'Failed to load report details');
      }
    };
    
    // Toggle approval status
    const toggleApproval = async (item) => {
      try {
        loading.value = true;
        const newApprovalStatus = !item.approval;
        await reportService.updateReportValidity(item.reportValidityID, {
          approval: newApprovalStatus
        });
        
        // Update local state
        const index = reportValidities.value.findIndex(r => r.reportValidityID === item.reportValidityID);
        if (index !== -1) {
          reportValidities.value[index].approval = newApprovalStatus;
        }
        
        modal.success('Success', `Report ${newApprovalStatus ? 'approved' : 'marked as pending'} successfully`);
      } catch (err) {
        modal.danger('Error', 'Failed to update approval status');
      } finally {
        loading.value = false;
      }
    };
    
    // Confirm delete - using modal.js for confirmation dialogs
    const confirmDelete = (item) => {
      modal.confirm(
        'Delete Report',
        'Are you sure you want to delete this report? This action cannot be undone.',
        async () => {
          try {
            loading.value = true;
            await reportService.deleteReportValidity(item.reportValidityID);
            
            // Remove from local state
            reportValidities.value = reportValidities.value.filter(
              r => r.reportValidityID !== item.reportValidityID
            );
            
            modal.success('Success', 'Report deleted successfully');
          } catch (err) {
            modal.danger('Error', 'Failed to delete report');
          } finally {
            loading.value = false;
          }
        }
      );
    };
    
    // Handle create report - now opens confirmation modal
    const handleCreateReport = () => {
    // Form validation
    if (!formData.value.startTimestamp || !formData.value.endTimestamp) {
    modal.warning('Warning', 'Please fill in all required fields');
    return;
    }
    
    // Check if companies are selected
    if (selectedCompanies.value.length === 0) {
    modal.warning('Warning', 'Please select at least one company');
    return;
    }
    
    // Store the form data in a separate variable to prevent it from being lost
    const formDataCopy = {
        startTimestamp: formData.value.startTimestamp,
        endTimestamp: formData.value.endTimestamp,
        approval: formData.value.approval
    };
    
    const selectedCompaniesIds = selectedCompanies.value.map(company => company.companyID);
    
    // Hide the add report modal first
    if (addReportModalRef.value) {
        addReportModalRef.value.hide();
    }
    
    // Show confirmation modal after a short delay to ensure the add modal is fully closed
    setTimeout(() => {
        const confirmModal = document.getElementById('confirmCreateReportModal');
        if (confirmModal) {
            // Store the data in a global variable that can be accessed by submitCreateReport
            window.reportFormData = {
                formData: formDataCopy,
                selectedCompanyIds: selectedCompaniesIds
            };
            
            const confirmModalInstance = bootstrap.Modal.getInstance(confirmModal) || new bootstrap.Modal(confirmModal);
            confirmModalInstance.show();
        }
    }, 300);
    };
    
    // This function will be called when user confirms in the confirmation modal
    const submitCreateReport = async () => {
        try {
            loading.value = true;
            
            // Use the stored form data instead of the potentially reset formData
            const storedData = window.reportFormData || {};
            
            // Create report
            await reportService.createReportValidity({
                start_timestamp: storedData.formData?.startTimestamp || formData.value.startTimestamp,
                end_timestamp: storedData.formData?.endTimestamp || formData.value.endTimestamp,
                approval: storedData.formData?.approval || formData.value.approval,
                company_ids: storedData.selectedCompanyIds || selectedCompanies.value.map(company => company.companyID)
            });
            
            // Clean up the stored data
            delete window.reportFormData;
            
            // Close add report modal
            addReportModalRef.value.hide();
            
            // Refresh data
            fetchReportValidities();
            
            // Show success message
            modal.success('Success', 'Report created successfully');
            
            // Reset form
            resetForm();
        } catch (err) {
            console.error('Report creation error details:', err);
            
            // Check if this is a validation error (422)
            if (err.response && err.response.status === 422 && err.response.data.errors) {
                // Get validation errors
                const validationErrors = err.response.data.errors;
                
                // Create a formatted error message
                let errorMessage = 'Validation failed:<br>';
                for (const field in validationErrors) {
                    errorMessage += `- ${validationErrors[field].join('<br>- ')}<br>`;
                }
                
                modal.danger('Validation Error', errorMessage);
            } else {
                modal.danger('Error', 'Failed to create report: ' + (err.message || 'Unknown error'));
            }
        } finally {
            loading.value = false;
        }
    };    
    // This function will be called when user cancels in the confirmation modal
    const closeConfirmationModal = () => {
    // The modal will be closed automatically by Bootstrap's data-bs-dismiss
    // Reopen the add report modal after a short delay to ensure the confirmation modal is fully closed
    setTimeout(() => {
    if (addReportModalRef.value) {
    addReportModalRef.value.show();
    }
    }, 300);
    };
    
    // Open add report modal
    const openAddReportModal = () => {
      // Get the modal element
      const modalElement = document.getElementById('addReportModal');
      
      // Initialize the modal if not already done
      if (!addReportModalRef.value) {
        addReportModalRef.value = new bootstrap.Modal(modalElement);
        
        // Reset form when modal is hidden
        modalElement.addEventListener('hidden.bs.modal', resetForm);
      }
      
      // Show the modal
      addReportModalRef.value.show();
    };
    const refreshData = () => {
      // Reset to first page when refreshing
      currentPage.value = 1;
      // Clear any error messages
      error.value = null;
      // Fetch fresh data
      fetchReportValidities();
    };
    // Reset form
    const resetForm = () => {
      formData.value = {
        startTimestamp: '',
        endTimestamp: '',
        approval: false
      };
      selectedCompanies.value = [];
    };
    
    // Initialize
    onMounted(() => {
      fetchReportValidities();
    });
    
    // At the end of your setup function, add currentReportId to the return statement
    return {
      reportValidities,
      loading,
      error,
      currentPage,
      perPage,
      totalItems,
      searchQuery,
      columns,
      formData,
      selectedCompanies,
      fetchReportValidities,
      handlePageChange,
      handleSearch,
      viewReportDetails,
      toggleApproval,
      confirmDelete,
      openAddReportModal,
      handleCreateReport,
      submitCreateReport,
      closeConfirmationModal,
      formatDate,
      refreshData,
      currentReportId  // Add this line
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
.report-details p {
  margin-bottom: 0.5rem;
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

