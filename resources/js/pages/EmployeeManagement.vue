<template>
  <div class="employee-management">
    <h1 class="mb-4">Employee Management</h1>
    
    <!-- Employee Stats -->
    <EmployeeStats :stats="employeeStats" class="mb-4" />
    
    <!-- Employees Table -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Employees</h5>
        <button class="btn btn-primary" @click="copyRegistrationLink">
          <i class="fas fa-plus me-1"></i> Add Employee
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
          :items="employees"
          :loading="loading"
          :has-actions="true"
          item-key="userID"
          @search="handleSearch"
          :show-pagination="false"
          :server-side="true"
        >
          <!-- Custom filters -->
          <template #filters>
            <div class="d-flex gap-2">
              <select class="form-select form-select-sm" v-model="statusFilter" @change="applyFilters">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </template>
          
          <!-- Custom column slots -->
          <template #status="{ item }">
            <span :class="getStatusBadgeClass(item.status)">{{ item.status }}</span>
          </template>
          
          <template #created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>
          
          <!-- Actions slot -->
          <template #actions="{ item }">
            <button 
              class="btn btn-sm me-1" 
              :class="item.status === 'active' ? 'btn-outline-warning' : 'btn-outline-success'"
              @click="toggleEmployeeStatus(item)"
            >
              <i :class="item.status === 'active' ? 'fas fa-ban' : 'fas fa-check'"></i>
            </button>
            <button class="btn btn-sm btn-outline-primary me-1" @click="editEmployee(item)">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" @click="deleteEmployee(item)">
              <i class="fas fa-trash"></i>
            </button>
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
import { ref, reactive, onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import EmployeeStats from '../components/employee/EmployeeStats.vue';
import EmployeeTable from '../components/employee/EmployeeTable.vue';
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';
import api from '../utils/api';
import modal from '../utils/modal';

export default {
  name: 'EmployeeManagement',
  components: {
    EmployeeStats,
    EmployeeTable,
    ResponsiveTable
  },
  // Update the setup function to separate the stats fetching
  setup() {
      const store = useStore();
      const loading = ref(true);
      const error = ref(null);
      const employees = ref([]);
      const statusFilter = ref('');
      const searchQuery = ref('');
      
      // Add pagination state
      const currentPage = ref(1);
      const perPage = ref(5); // Default to 10 items per page
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
      
      // Employee stats
      const employeeStats = ref({
        total: 0,
        active: 0,
        inactive: 0
      });
      
      // Table columns
      const columns = [
        { key: 'fullname', label: 'Name', sortable: true },
        { key: 'email', label: 'Email', sortable: true },
        { key: 'tel_number', label: 'Phone', sortable: false },
        { key: 'status', label: 'Status', sortable: true },
        { key: 'created_at', label: 'Joined', sortable: true }
      ];
      
      // Fetch employees with pagination, search, and filters
      const fetchEmployees = async () => {
        try {
          loading.value = true;
          error.value = null;
          
          const params = {
            page: currentPage.value,
            per_page: perPage.value,
            search: searchQuery.value || null,
            status: statusFilter.value || null
          };
          
          const response = await api.get('/api/employees', { params });
          
          console.log('API Response:', response);
          
          if (response.data && response.success) {
            employees.value = response.data;
            pagination.value = response.pagination;
            fetchEmployeeStats();
          } else {
            employees.value = [];
            console.error('Unexpected response format:', response);
          }
        } catch (err) {
          console.error('Error fetching employees:', err);
          error.value = err.message || 'Failed to load employees. Please try again later.';
          employees.value = [];
        } finally {
          loading.value = false;
        }
      };
      
      // Fetch employee stats separately
      const fetchEmployeeStats = async () => {
        try {
          const params = {
            search: searchQuery.value || null,
            status: statusFilter.value || null
          };
          
          const statsData = await api.get('/api/employees/all/stats', { params });
          
          if (statsData) {
            employeeStats.value = statsData;
          }
        } catch (err) {
          console.error('Error fetching employee stats:', err);
        }
      };
      
      // Apply filters
      const applyFilters = () => {
        currentPage.value = 1; // Reset to first page when applying filters
        fetchEmployees();
        fetchEmployeeStats(); // Also update stats when filters change
      };
      
      // Handle search from ResponsiveTable
      const handleSearch = (query) => {
        searchQuery.value = query;
        currentPage.value = 1; // Reset to first page when searching
        fetchEmployees();
        fetchEmployeeStats(); // Also update stats when search changes
      };
      
      // Change page
      const changePage = (page) => {
        if (page < 1 || page > pagination.value.last_page || loading.value) return;
        
        // Update the current page immediately for UI feedback
        currentPage.value = page;
        
        // Then fetch the data for the new page
        fetchEmployees();
      };
      
      // Initialize data
      onMounted(() => {
        fetchEmployees();
        fetchEmployeeStats();
      });
      
      // Format date
      const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString();
      };
      
      // Get badge classes
      const getStatusBadgeClass = (status) => {
        const classes = 'badge ';
        switch (status) {
          case 'active': return classes + 'bg-success';
          case 'inactive': return classes + 'bg-danger';
          default: return classes + 'bg-secondary';
        }
      };
      
      // Copy registration link
      const copyRegistrationLink = () => {
        console.log('Copying registration link:', store.state);
        const registrationLink = `${window.location.origin}/register-employee?formID=${store.state.user.company.formID}`;
        
        // Use the modal utility for the copy operation
        modal.show({
          type: 'info',
          title: 'Add New Employee',
          message: `
            <p>Share this registration link with your new employee:</p>
            <div class="input-group mb-3">
              <input type="text" class="form-control" value="${registrationLink}" id="registration-link" readonly>
              <button class="btn btn-outline-secondary" type="button" id="copy-link-btn">
                <i class="fas fa-copy"></i> Copy
              </button>
            </div>
          `,
          onShown: () => {
            // Add click handler for the copy button
            const copyBtn = document.getElementById('copy-link-btn');
            const linkInput = document.getElementById('registration-link');
            
            if (copyBtn && linkInput) {
              copyBtn.addEventListener('click', () => {
                linkInput.select();
                document.execCommand('copy');
                
                // Change button text temporarily
                const originalHTML = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                copyBtn.classList.replace('btn-outline-secondary', 'btn-success');
                
                setTimeout(() => {
                  copyBtn.innerHTML = originalHTML;
                  copyBtn.classList.replace('btn-success', 'btn-outline-secondary');
                }, 2000);
              });
            }
          }
        });
      };
      
      // Toggle employee status
      const toggleEmployeeStatus = async (employee) => {
        const newStatus = employee.status === 'active' ? 'inactive' : 'active';
        const actionText = newStatus === 'active' ? 'activate' : 'deactivate';
        
        modal.confirm(
          `${newStatus === 'active' ? 'Activate' : 'Deactivate'} Employee`,
          `Are you sure you want to ${actionText} ${employee.name}?`,
          async () => {
            loading.value = true;
            
            try {
              await api.fetchData(`/api/employees/${employee.userID}/status`, {
                method: 'patch',
                data: { status: newStatus },
                onSuccess: (data) => {
                  // Refresh the entire list
                  fetchEmployees();
                  
                  // Show success message
                  modal.success('Success', `Employee ${actionText}d successfully`);
                },
                onError: (err) => {
                  console.error(`Error ${actionText}ing employee:`, err);
                  modal.danger('Error', `Failed to ${actionText} employee. Please try again.`);
                  
                  // Reload the data even on error to ensure consistent state
                  fetchEmployees();
                }
              });
            } catch (err) {
              // Error is already handled by onError callback
              fetchEmployees();
            }
          },
          null,
          {
            confirmLabel: newStatus === 'active' ? 'Activate' : 'Deactivate',
            confirmType: newStatus === 'active' ? 'success' : 'warning'
          }
        );
      };
      
      // Edit employee
      const editEmployee = (employee) => {
        console.log('Editing employee:', employee);
        // Implement edit logic here
      };
      
      // Delete employee
      const deleteEmployee = async (employee) => {
        modal.confirm(
          'Delete Employee',
          `Are you sure you want to delete ${employee.name}?`,
          async () => {
            loading.value = true;
            
            try {
              await api.fetchData(`/api/employees/${employee.userID}`, {
                method: 'delete',
                onSuccess: (data) => {
                  // Show success message
                  modal.success('Success', 'Employee deleted successfully');
                  
                  // Refresh the entire list
                  fetchEmployees();
                },
                onError: (err) => {
                  console.error('Error deleting employee:', err);
                  modal.danger('Error', 'Failed to delete employee. Please try again.');
                  
                  // Restore data in case of error
                  fetchEmployees();
                }
              });
            } catch (err) {
              // Error is already handled by onError callback
              fetchEmployees();
            }
          },
          null,
          {
            confirmLabel: 'Delete',
            confirmType: 'danger'
          }
        );
      };
      
      onMounted(() => {
        fetchEmployees();
      });
      
      return {
        loading,
        error,
        employees,
        employeeStats,
        columns,
        statusFilter,
        pagination,
        paginationRange,
        currentPage,
        formatDate,
        getStatusBadgeClass,
        copyRegistrationLink,
        editEmployee,
        deleteEmployee,
        toggleEmployeeStatus,
        applyFilters,
        handleSearch,
        changePage
      };
    }
};
</script>

<style scoped>
.employee-management h1 {
  color: #123524;
}

/* Pagination styling */
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
  .employee-management h1 {
    font-size: 1.75rem;
  }
}
</style>