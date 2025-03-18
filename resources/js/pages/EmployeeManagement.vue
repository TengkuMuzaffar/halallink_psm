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
      @add="openAddModal"
    >
      <!-- Custom filters -->
      <template #filters>
        <div class="d-flex gap-2">
          <select class="form-select form-select-sm" v-model="roleFilter" @change="applyFilters">
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="employee">Employee</option>
          </select>
          
          <select class="form-select form-select-sm" v-model="statusFilter" @change="applyFilters">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </template>
      
      <!-- Custom column slots -->
      <template #role="{ item }">
        <span :class="getRoleBadgeClass(item.role)">{{ item.role }}</span>
      </template>
      
      <template #status="{ item }">
        <span :class="getStatusBadgeClass(item.status)">{{ item.status }}</span>
      </template>
      
      <template #created_at="{ item }">
        {{ formatDate(item.created_at) }}
      </template>
      
      <!-- Actions slot -->
      <template #actions="{ item }">
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
import { ref, reactive, onMounted } from 'vue';
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
    const loading = ref(true);
    const error = ref(null);
    const employees = ref([]);
    const roleFilter = ref('');
    const statusFilter = ref('');
    
    const employeeStats = reactive({
      total: 0,
      active: 0,
      inactive: 0
    });
    
    const columns = [
      { key: 'fullname', label: 'Name', sortable: true },
      { key: 'email', label: 'Email', sortable: true },
      { key: 'role', label: 'Role', sortable: true },
      { key: 'status', label: 'Status', sortable: true, class: 'text-center' },
      { key: 'created_at', label: 'Joined', sortable: true }
    ];
    
    // Fetch employees
    const fetchEmployees = async () => {
      loading.value = true;
      
      try {
        const response = await api.get('/api/employees/all', {
          onError: (err) => {
            console.error('Error fetching employees:', err);
            error.value = 'Failed to load employees. Please try again.';
          }
        });
        
        employees.value = response;
        
        // Calculate stats
        employeeStats.total = employees.value.length;
        employeeStats.active = employees.value.filter(emp => emp.status === 'active').length;
        employeeStats.inactive = employees.value.filter(emp => emp.status === 'inactive').length;
      } catch (err) {
        // Error is already handled by onError callback
      } finally {
        loading.value = false;
      }
    };
    
    // Apply filters
    const applyFilters = async () => {
      loading.value = true;
      
      try {
        // Build query parameters
        const params = {};
        if (roleFilter.value) params.role = roleFilter.value;
        if (statusFilter.value) params.status = statusFilter.value;
        
        const response = await api.get('/api/employees/all', {
          params,
          onError: (err) => {
            console.error('Error applying filters:', err);
            error.value = 'Failed to filter employees. Please try again.';
          }
        });
        
        employees.value = response;
        
        // Update stats based on filtered results
        employeeStats.total = employees.value.length;
        employeeStats.active = employees.value.filter(emp => emp.status === 'active').length;
        employeeStats.inactive = employees.value.filter(emp => emp.status === 'inactive').length;
      } catch (err) {
        // Error is already handled by onError callback
      } finally {
        loading.value = false;
      }
    };
    
    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    };
    
    // Get badge classes
    const getRoleBadgeClass = (role) => {
      const classes = 'badge ';
      switch (role) {
        case 'admin': return classes + 'bg-primary';
        case 'employee': return classes + 'bg-info';
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
      console.log('Opening add employee modal');
      // Implement modal logic here
    };
    
    const editEmployee = (employee) => {
      console.log('Editing employee:', employee);
      // Implement edit logic here
    };
    
    const deleteEmployee = async (employee) => {
      // Replace confirm with custom modal
      modal.confirm(
        'Delete Employee',
        `Are you sure you want to delete ${employee.fullname}?`,
        async () => {
          loading.value = true;
          try {
            await api.delete(`/api/employees/${employee.id}`, {
              onSuccess: () => {
                // Remove from local array
                employees.value = employees.value.filter(e => e.id !== employee.id);
                
                // Update stats
                employeeStats.total = employees.value.length;
                employeeStats.active = employees.value.filter(emp => emp.status === 'active').length;
                employeeStats.inactive = employees.value.filter(emp => emp.status === 'inactive').length;
                
                // Show success message
                modal.success('Success', 'Employee deleted successfully');
              },
              onError: (err) => {
                console.error('Error deleting employee:', err);
                error.value = 'Failed to delete employee. Please try again.';
                
                // Show error message
                modal.danger('Error', 'Failed to delete employee. Please try again.');
              }
            });
          } catch (err) {
            // Error is already handled by onError callback
          } finally {
            loading.value = false;
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
      roleFilter,
      statusFilter,
      formatDate,
      getRoleBadgeClass,
      getStatusBadgeClass,
      openAddModal,
      editEmployee,
      deleteEmployee,
      applyFilters
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