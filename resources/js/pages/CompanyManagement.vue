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
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Companies</h5>
        <button class="btn btn-primary" @click="openAddModal">
          <i class="fas fa-plus me-1"></i> Add Company
        </button>
      </div>
      <div class="card-body">
        <ResponsiveTable
          :columns="columns"
          :items="companies"
          :loading="loading"
          :has-actions="true"
          item-key="companyID"
          @search="handleSearch"
        >
          <!-- Custom filters -->
          <template #filters>
            <div class="d-flex gap-2">
              <select class="form-select form-select-sm" v-model="typeFilter" @change="applyFilters">
                <option value="">All Types</option>
                <option value="broiler">Broiler</option>
                <option value="slaughterhouse">Slaughterhouse</option>
                <option value="SME">SME</option>
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
          
          <template #created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>
          
          <!-- Actions slot -->
          <template #actions="{ item }">
            <button 
              v-if="item.admin"
              class="btn btn-sm me-1" 
              :class="item.admin.status === 'active' ? 'btn-outline-warning' : 'btn-outline-success'"
              @click="toggleCompanyStatus(item)"
            >
              <i :class="item.admin.status === 'active' ? 'fas fa-ban' : 'fas fa-check'"></i>
              <!-- {{ item.admin.status === 'active' ? 'Deactivate' : 'Activate' }} -->
            </button>
            <button class="btn btn-sm btn-outline-danger" @click="deleteCompany(item)">
              <i class="fas fa-trash"></i>
            </button>
          </template>
          
          <!-- Empty state slot -->
          <template #empty>
            <div class="text-center py-4">
              <template v-if="loading">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading companies...</p>
              </template>
              <template v-else>
                <i class="fas fa-building text-muted mb-2" style="font-size: 2rem;"></i>
                <p class="text-muted">No companies found</p>
              </template>
            </div>
          </template>
        </ResponsiveTable>
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
    const searchQuery = ref(''); // Add this line
    
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
      { key: 'company_type', label: 'Type', sortable: true },
      { key: 'email', label: 'Email', sortable: true },
      { key: 'Phone', label: 'Phone', sortable: true }, // Changed from tel_number to Phone to match the template slot name
      { key: 'status', label: 'Status', sortable: true, class: 'text-center' },
      { key: 'created_at', label: 'Created', sortable: true }
    ];
    
    // Fetch companies and stats
    const fetchCompanies = async () => {
      // Clear existing data first
      companies.value = [];
      loading.value = true;
      error.value = null;
      
      try {
        // Build query parameters
        const params = {};
        if (typeFilter.value) params.company_type = typeFilter.value;
        if (statusFilter.value) params.status = statusFilter.value;
        if (searchQuery.value) params.search = searchQuery.value;
        
        // Use Promise.all with our API utilities to fetch both resources in parallel
        const [companiesData, statsData] = await api.fetchMultiple([
          { url: '/api/companies', options: {
            params,
            onError: (err) => {
              console.error('Error fetching companies:', err);
              error.value = 'Failed to load companies. Please try again.';
              
              // Show error message with modal
              modal.danger(
                'Error Loading Companies',
                'Failed to load company data. Please try again.'
              );
            }
          }},
          { url: '/api/companies/stats', options: {
            onError: (err) => {
              console.error('Error fetching company stats:', err);
              error.value = 'Failed to load company statistics. Please try again.';
            }
          }}
        ]);
        
        if (Array.isArray(companiesData)) {
          companies.value = companiesData;
          console.log('Fetched companies:', companiesData);
        }
        
        if (typeof statsData === 'object' && statsData !== null) {
          stats.value = statsData;
        }
      } catch (err) {
        // Errors are already handled by onError callbacks
      } finally {
        loading.value = false;
      }
    };
    
    // Apply filters
    const applyFilters = async () => {
      // Clear existing data first
      companies.value = [];
      loading.value = true;
      error.value = null;
      
      try {
        // Build query parameters
        const params = {};
        if (typeFilter.value) params.company_type = typeFilter.value;
        if (statusFilter.value) params.status = statusFilter.value;
        if (searchQuery.value) params.search = searchQuery.value;
        
        const filteredCompanies = await api.get('/api/companies', {
          params,
          onError: (err) => {
            console.error('Error applying filters:', err);
            error.value = 'Failed to filter companies. Please try again.';
            
            // Show error message
            modal.danger('Error', 'Failed to filter companies. Please try again.');
          }
        });
        
        companies.value = filteredCompanies || [];
      } catch (err) {
        // Error is already handled by onError callback
      } finally {
        loading.value = false;
      }
    };
    
    // Handle search from ResponsiveTable
    const handleSearch = (query) => {
      searchQuery.value = query;
      applyFilters();
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
    
    // Modal actions
    const openAddModal = () => {
      console.log('Opening add company modal');
      // Implement modal logic here
    };
    
    const editCompany = (company) => {
      console.log('Editing company:', company);
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
        const statsData = await api.get('/api/companies/stats', {
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
    
    // Add toggleCompanyStatus to the return statement
    return {
      loading,
      error,
      companies,
      companyStats,
      columns,
      typeFilter,
      statusFilter,
      formatDate,
      getTypeBadgeClass,
      getStatusBadgeClass,
      openAddModal,
      editCompany,
      deleteCompany,
      toggleCompanyStatus, // Add this line
      applyFilters,
      handleSearch // Add this line
    };
  }
};
</script>

<style scoped>
.company-management h1 {
  color: #123524;
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