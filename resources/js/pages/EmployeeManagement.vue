<template>
  <div class="employee-management">
    <h1 class="mb-4">Employee Management</h1>
    
    <!-- Employee Stats -->
    <EmployeeStats :stats="employeeStats" class="mb-4" />
    
    <!-- Employees Table -->
    <EmployeeTable
      :employees="employees"
      :loading="loading"
      :columns="columns"
      @add="copyRegistrationLink"
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
    </EmployeeTable>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import EmployeeStats from '../components/employee/EmployeeStats.vue';
import EmployeeTable from '../components/employee/EmployeeTable.vue';
import api from '../utils/api';
import modal from '../utils/modal';

export default {
  name: 'EmployeeManagement',
  components: {
    EmployeeStats,
    EmployeeTable
  },
  setup() {
    const store = useStore();
    const loading = ref(true);
    const employees = ref([]);
    const allEmployees = ref([]); // Store all employees for local filtering
    const statusFilter = ref('');
    const searchQuery = ref('');
    
    // Employee stats - change from reactive array to reactive object
    const employeeStats = ref({
      total: 0,
      active: 0,
      inactive: 0,
      managers: 0
    });
    
    // Table columns - removed role column
    const columns = [
      { key: 'fullname', label: 'Name', sortable: true },
      { key: 'email', label: 'Email', sortable: true },
      { key: 'tel_number', label: 'Phone', sortable: false },
      { key: 'status', label: 'Status', sortable: true },
      { key: 'created_at', label: 'Joined', sortable: true }
    ];
    
    // Fetch employees
    const fetchEmployees = async () => {
      try {
        loading.value = true;
        const response = await api.get('/api/employees');
        allEmployees.value = response;
        applyFilters(); // Apply filters to the fetched data
        
        // Update stats
        updateStats();
        
        loading.value = false;
      } catch (error) {
        console.error('Error fetching employees:', error);
        loading.value = false;
        modal.danger('Error', 'Failed to load employees');
      }
    };
    
    // Update employee stats
    const updateStats = () => {
      const total = allEmployees.value.length;
      const active = allEmployees.value.filter(emp => emp.status === 'active').length;
      const inactive = allEmployees.value.filter(emp => emp.status === 'inactive').length;
      const managers = allEmployees.value.filter(emp => emp.role === 'manager').length;
      
      employeeStats.value = {
        total: total,
        active: active,
        inactive: inactive,
        managers: managers
      };
    };
    
    // Apply filters
    const applyFilters = () => {
      let filteredData = [...allEmployees.value];
      
      // Apply status filter
      if (statusFilter.value) {
        filteredData = filteredData.filter(emp => emp.status === statusFilter.value);
      }
      
      // Apply search query if it exists
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filteredData = filteredData.filter(emp => 
          emp.fullname?.toLowerCase().includes(query) || 
          emp.email?.toLowerCase().includes(query) ||
          emp.tel_number?.toLowerCase().includes(query)
        );
      }
      
      employees.value = filteredData;
    };
    
    // Handle search
    const handleSearch = (query) => {
      searchQuery.value = query;
      applyFilters();
    };
    
    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }).format(date);
    };
    
    // Get badge class for status
    const getStatusBadgeClass = (status) => {
      return status === 'active' ? 'badge bg-success' : 'badge bg-danger';
    };
    
    // Toggle employee status
    const toggleEmployeeStatus = async (employee) => {
      try {
        const newStatus = employee.status === 'active' ? 'inactive' : 'active';
        const confirmMessage = newStatus === 'active' 
          ? `Are you sure you want to activate ${employee.fullname}?`
          : `Are you sure you want to deactivate ${employee.fullname}?`;
        
        modal.confirm('Confirm Status Change', confirmMessage, async (event, modalInstance) => {
          try {
            loading.value = true;
            modalInstance.hide();
            
            await api.patch(`/api/employees/${employee.userID}/status`, {
              status: newStatus
            });
            
            // Update employee status locally
            const index = allEmployees.value.findIndex(e => e.userID === employee.userID);
            if (index !== -1) {
              allEmployees.value[index].status = newStatus;
              applyFilters(); // Re-apply filters to update the view
              updateStats(); // Update stats
            }
            
            modal.success('Success', `Employee status updated to ${newStatus}`);
            loading.value = false;
          } catch (error) {
            console.error('Error updating employee status:', error);
            loading.value = false;
            modal.danger('Error', 'Failed to update employee status');
          }
        });
      } catch (error) {
        console.error('Error in toggleEmployeeStatus:', error);
        modal.danger('Error', 'An unexpected error occurred');
      }
    };
    
    // Edit employee
    const editEmployee = (employee) => {
      // Implementation for editing employee
      modal.info('Edit Employee', 'Edit employee functionality will be implemented soon.');
    };
    
    // Delete employee
    const deleteEmployee = (employee) => {
      modal.confirm('Delete Employee', `Are you sure you want to delete ${employee.fullname}?`, async (event, modalInstance) => {
        try {
          loading.value = true;
          modalInstance.hide();
          
          await api.delete(`/api/employees/${employee.userID}`);
          
          // Remove employee from local array
          allEmployees.value = allEmployees.value.filter(e => e.userID !== employee.userID);
          applyFilters(); // Re-apply filters to update the view
          updateStats(); // Update stats
          
          modal.success('Success', 'Employee deleted successfully');
          loading.value = false;
        } catch (error) {
          console.error('Error deleting employee:', error);
          loading.value = false;
          modal.danger('Error', 'Failed to delete employee');
        }
      });
    };
    
    // Copy registration link
    const copyRegistrationLink = () => {
      const registrationLink = `${window.location.origin}/register-employee?companyID=${store.getters.user.companyID}`;
      
      navigator.clipboard.writeText(registrationLink)
        .then(() => {
          modal.success('Success', 'Registration link copied to clipboard');
        })
        .catch(err => {
          console.error('Could not copy text: ', err);
          modal.danger('Error', 'Failed to copy registration link');
        });
    };
    
    // Fetch employees on component mount
    onMounted(() => {
      fetchEmployees();
    });
    
    return {
      loading,
      employees,
      employeeStats,
      columns,
      statusFilter,
      applyFilters,
      handleSearch,
      formatDate,
      getStatusBadgeClass,
      toggleEmployeeStatus,
      editEmployee,
      deleteEmployee,
      copyRegistrationLink
    };
  }
};
</script>

<style scoped>
.employee-management h1 {
  color: #123524;
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