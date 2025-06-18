<template>
  <div class="company-management">
    <h1 class="mb-4">Company Management</h1>
    
    <!-- Company Stats -->
    <div class="row mb-4">
      <div v-for="(stat, index) in companyStats" :key="index" class="col-md-3 col-sm-6 mb-3">
        <StatsCard 
          :title="stat.title" 
          :count="stat.count" 
          :icon="stat.icon" 
          :bg-color="stat.bgColor"
        />
      </div>
    </div>
    
    <!-- Companies Table -->
    <div class="card">
      <div class="card-header theme-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Companies</h5>
        <button class="btn btn-sm theme-btn-outline" @click="refresh" :disabled="loading">
          <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-sync-alt'"></i>
          <span class="d-none d-md-inline ms-2">{{ loading ? 'Refreshing...' : 'Refresh' }}</span>
        </button>
      </div>
      <div class="card-body">
        <!-- Error State -->
        <div v-if="error" class="alert alert-danger" role="alert">
          {{ error }}
        </div>
        
        <!-- Table (always show, with loading state inside) -->
        <ResponsiveTable
          :columns="columns"
          :items="companies"
          :loading="loading"
          :has-actions="true"
          item-key="companyID"
          @search="handleSearch"
          :show-pagination="false"
          :server-side="true"
        >
          <!-- Custom filters -->
          <template #filters>
            <div class="d-flex gap-2">
              <select class="form-select form-select-sm" v-model="typeFilter" @change="applyFilters">
                <option value="">All Types</option>
                <option value="broiler">Broiler</option>
                <option value="slaughterhouse">Slaughterhouse</option>
                <option value="sme">SME</option>
                <option value="logistic">Logistic</option>
              </select>
              
              <select class="form-select form-select-sm" v-model="statusFilter" @change="applyFilters">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </template>
          
          <!-- Custom column slots -->
          <template #company_type="{ item }">
            <span :class="getTypeBadgeClass(item.company_type)">{{ item.company_type }}</span>
          </template>
          
          <template #status="{ item }">
            <div v-if="item.admin">
              <span :class="getStatusBadgeClass(item.admin.status)">{{ item.admin.status }}</span>
            </div>
          </template>
          
          <template #email="{ item }">
            <div v-if="item.admin">
              <div class="d-flex align-items-center">
                <span>{{ item.admin.email }}</span>
              </div>
            </div>
          </template>
          
          <template #Phone="{ item }">
            <div v-if="item.admin">
              <div class="d-flex align-items-center">
                <span>{{ item.admin.tel_number }}</span>
              </div>
            </div>
          </template>
          
          
          
          <!-- Actions slot -->
          <template #actions="{ item }">
            <div class="d-flex justify-content-end">
              <button 
                v-if="item.admin"
                class="btn btn-sm me-1" 
                :class="item.admin.status === 'active' ? 'btn-outline-warning' : 'btn-outline-success'"
                @click="toggleCompanyStatus(item)"
              >
                <i :class="item.admin.status === 'active' ? 'fas fa-ban' : 'fas fa-check'"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" @click="deleteCompany(item)">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </template>
          
          <!-- Empty state slot -->
          <template #empty>
            <div class="text-center py-4">
              <i class="fas fa-building text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted">No companies found</p>
            </div>
          </template>
        </ResponsiveTable>
        
        <!-- Add custom pagination controls (always visible) -->
        <div v-if="pagination.last_page > 0" class="d-flex justify-content-between align-items-center mt-3">
          <div>
            <span class="text-muted">Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} entries</span>
          </div>
          <nav aria-label="Table pagination">
            <ul class="pagination mb-0">
              <li class="page-item" :class="{ disabled: currentPage === 1 || loading }">
                <a class="page-link" href="#" @click.prevent="!loading && changePage(currentPage - 1)">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
              <li 
                v-for="page in paginationRange" 
                :key="page" 
                class="page-item"
                :class="{ active: page === currentPage, disabled: loading }"
              >
                <a class="page-link" href="#" @click.prevent="!loading && changePage(page)">{{ page }}</a>
              </li>
              <li class="page-item" :class="{ disabled: currentPage === pagination.last_page || loading }">
                <a class="page-link" href="#" @click.prevent="!loading && changePage(currentPage + 1)">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import StatsCard from '../components/ui/StatsCard.vue';
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';
import api from '../utils/api';
import modal from '../utils/modal';

export default {
  name: 'CompanyManagement',
  components: {
    StatsCard,
    ResponsiveTable
  },
  setup() {
    const loading = ref(true);
    const error = ref(null);
    const companies = ref([]);
    const typeFilter = ref('');
    const statusFilter = ref('');
    const searchQuery = ref('');
    
    // Add pagination state
    const currentPage = ref(1);
    const perPage = ref(10); // Default to 3 items per page
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: 0,
      to: 0
    });
    
    // Computed property for pagination range
    const paginationRange = computed(() => {
      const range = [];
      const maxVisiblePages = 5;
      const totalPages = pagination.value.last_page;
      
      if (totalPages <= maxVisiblePages) {
        // Show all pages if total is less than max visible
        for (let i = 1; i <= totalPages; i++) {
          range.push(i);
        }
      } else {
        // Show limited pages with current page in the middle
        let startPage = Math.max(1, pagination.value.current_page - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        // Adjust if we're near the end
        if (endPage === totalPages) {
          startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
          range.push(i);
        }
      }
      
      return range;
    });
    
    const stats = ref({
      broiler: 0,
      slaughterhouse: 0,
      sme: 0,
      logistic: 0
    });
    
    // Transform stats for StatsCards
    const companyStats = computed(() => [
      {
        title: 'Broiler Companies',
        count: stats.value.broiler,
        icon: 'fas fa-industry',
        bgColor: 'bg-primary'
      },
      {
        title: 'Slaughterhouse',
        count: stats.value.slaughterhouse,
        icon: 'fas fa-warehouse',
        bgColor: 'bg-danger'
      },
      {
        title: 'SME',
        count: stats.value.sme,
        icon: 'fas fa-store',
        bgColor: 'bg-success'
      },
      {
        title: 'Logistics',
        count: stats.value.logistic,
        icon: 'fas fa-truck',
        bgColor: 'bg-warning'
      }
    ]);
    
    // In the columns definition, change the key for the phone column
    const columns = [
      { key: 'company_name', label: 'Company Name', sortable: true },
      { key: 'company_type', label: 'Type', sortable: false },
      { key: 'email', label: 'Email', sortable: false },
      { key: 'Phone', label: 'Phone', sortable: false }, // Changed from tel_number to Phone to match the template slot name
      { key: 'status', label: 'Status', sortable: false, class: 'text-center' },
    ];
    
    // Fetch companies and stats
    const fetchCompanies = async () => {
      try {
        // Clear existing data first and show loading
        companies.value = [];
        loading.value = true;
        error.value = null;
        
        const params = {
          page: currentPage.value,
          per_page: perPage.value,
          search: searchQuery.value || null,
          company_type: typeFilter.value || null,
          status: statusFilter.value || null
        };
        
        const response = await api.get('/api/companies', { params });
        
        if (response.data && response.success) {
          companies.value = response.data;
          pagination.value = response.pagination;
          
          // Also fetch updated stats whenever companies are fetched
          await fetchCompanyStats();
        } else {
          companies.value = [];
          console.error('Unexpected response format:', response);
        }
      } catch (err) {
        console.error('Error fetching companies:', err);
        error.value = err.message || 'Failed to load companies. Please try again later.';
        companies.value = [];
      } finally {
        loading.value = false;
      }
    };
    const refresh = () => {
      fetchCompanies();

    };
    // Apply filters - simplified to use fetchCompanies
    const applyFilters = () => {
      // Reset to first page when applying filters
      currentPage.value = 1;
      // Use the fetchCompanies function to maintain consistent behavior
      fetchCompanies();
    };
    
    // Handle search from ResponsiveTable
    const handleSearch = (query) => {
      searchQuery.value = query;
      // Reset to first page when searching
      currentPage.value = 1;
      fetchCompanies();
    };
    
    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    };
    
    // Get badge classes
    const getTypeBadgeClass = (type) => {
      const classes = 'badge ';
      switch (type) {
        case 'broiler': return classes + 'bg-primary';
        case 'slaughterhouse': return classes + 'bg-danger';
        case 'SME': return classes + 'bg-success';
        case 'logistic': return classes + 'bg-warning';
        default: return classes + 'bg-secondary';
      }
    };
    
    const getStatusBadgeClass = (status) => {
      const classes = 'badge ';
      switch (status) {
        case 'active': return classes + 'bg-success';
        case 'inactive': return classes + 'bg-danger';
        default: return classes + 'bg-secondary';
      }
    };
    
    // // Modal actions
    // const openAddModal = () => {
    //   console.log('Opening add company modal');
    //   // Implement modal logic here
    // };
    
    const editCompany = (company) => {
      // console.log('Editing company:', company);
      // Implement edit logic here
    };
    
    // Delete company function - keep only this one
    const deleteCompany = async (company) => {
      // Use the modal utility for confirmation
      modal.confirm(
        'Delete Company',
        `Are you sure you want to delete ${company.company_name}? This will also delete the associated admin user.`,
        async () => {
          // Clear existing data first and show loading
          companies.value = [];
          loading.value = true;
          
          try {
            await api.fetchData(`/api/companies/${company.companyID}`, {
              method: 'delete',
              onSuccess: (data) => {
                // Show success message
                modal.success('Success', 'Company deleted successfully');
                
                // Refresh the entire list and stats
                fetchCompanies();
              },
              onError: (err) => {
                console.error('Error deleting company:', err);
                modal.danger('Error', 'Failed to delete company. Please try again.');
                // Restore data in case of error
                fetchCompanies();
              }
            });
          } catch (err) {
            // Error is already handled by onError callback
            fetchCompanies();
          }
        },
        null,
        {
          confirmLabel: 'Delete',
          confirmType: 'danger'
        }
      );
    };
    
    // Separate function to fetch only stats
    const fetchCompanyStats = async () => {
      try {
        const statsData = await api.get('/api/companies/all/stats', {
          onError: (err) => {
            console.error('Error fetching company stats:', err);
            error.value = 'Failed to load company statistics. Please try again.';
          }
        });
        
        if (typeof statsData === 'object') {
          stats.value = statsData;
        }
      } catch (err) {
        // Error is already handled by onError callback
      }
    };
    
    onMounted(() => {
      fetchCompanies();
    });
    
    // In the setup function, add this new function
    const toggleCompanyStatus = async (company) => {
      if (!company.admin) return;
      
      const newStatus = company.admin.status === 'active' ? 'inactive' : 'active';
      const actionText = newStatus === 'active' ? 'activate' : 'deactivate';
      
      modal.confirm(
        `${newStatus === 'active' ? 'Activate' : 'Deactivate'} Company`,
        `Are you sure you want to ${actionText} ${company.company_name}?`,
        async () => {
          // Clear existing data to show loading state
          companies.value = [];
          loading.value = true;
          
          try {
            await api.fetchData(`/api/companies/${company.companyID}/status`, {
              method: 'patch',
              data: { status: newStatus },
              onSuccess: (data) => {
                // Refresh the entire list instead of just updating local array
                fetchCompanies();
                
                // Show success message
                modal.success('Success', `Company ${actionText}d successfully`);
              },
              onError: (err) => {
                console.error(`Error ${actionText}ing company:`, err);
                modal.danger('Error', `Failed to ${actionText} company. Please try again.`);
                
                // Reload the data even on error to ensure consistent state
                fetchCompanies();
              }
            });
          } catch (err) {
            // Error is already handled by onError callback
            // Reload the data even on error to ensure consistent state
            fetchCompanies();
          }
        },
        null,
        {
          confirmLabel: newStatus === 'active' ? 'Activate' : 'Deactivate',
          confirmType: newStatus === 'active' ? 'success' : 'warning'
        }
      );
    };
    
    // Add the changePage function before the return statement
    // Update the changePage function
    const changePage = (page) => {
      if (page < 1 || page > pagination.value.last_page || loading.value) return;
      
      // Update the current page immediately for UI feedback
      currentPage.value = page;
      
      // Then fetch the data for the new page
      fetchCompanies();
    };
    
    // Add toggleCompanyStatus to the return statement
    return {
      loading,
      error,
      companies,
      companyStats,
      columns,
      typeFilter,
      statusFilter,
      pagination,
      paginationRange,
      currentPage, // Add this line to expose currentPage to the template
      formatDate,
      getTypeBadgeClass,
      getStatusBadgeClass,
      // openAddModal,
      editCompany,
      deleteCompany,
      toggleCompanyStatus,
      applyFilters,
      handleSearch,
      changePage,
      fetchCompanyStats,
      refresh
    };
  }
};
</script>

<style scoped>
.company-management h1 {
  color: #123524;
}
.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
}
/* Button styles */
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
/* Pagination styling to match MarketplacePage */
.pagination {
  margin-bottom: 0;
}

.page-link {
  color: #123524;
}

.page-item.active .page-link {
  background-color: #123524;
  border-color: #123524;
  color: #fff;
}

.page-item.disabled .page-link {
  color: #6c757d;
  pointer-events: none;
  background-color: #fff;
  border-color: #dee2e6;
}

.page-link:hover {
  color: #0a1f15;
  background-color: #e9ecef;
  border-color: #dee2e6;
}

.page-link:focus {
  box-shadow: 0 0 0 0.25rem rgba(18, 53, 36, 0.25);
}

.badge {
  font-size: 0.8rem;
  padding: 0.35em 0.65em;
}

@media (max-width: 768px) {
  .company-management h1 {
    font-size: 1.75rem;
  }
}
</style>