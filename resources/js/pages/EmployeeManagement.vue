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
import axios from 'axios';
import EmployeeStats from '../components/employee/EmployeeStats.vue';
import EmployeeTable from '../components/employee/EmployeeTable.vue';

export default {
  name: 'EmployeeManagement',
  components: {
    EmployeeStats,
    EmployeeTable
  },
  setup() {
    const loading = ref(true);
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
        const response = await axios.get('/api/employees/all');
        employees.value = response.data;
        
        // Calculate stats
        employeeStats.total = employees.value.length;
        employeeStats.active = employees.value.filter(emp => emp.status === 'active').length;
        employeeStats.inactive = employees.value.filter(emp => emp.status === 'inactive').length;
      } catch (error) {
        console.error('Error fetching employees:', error);
      } finally {
        loading.value = false;
      }
    };
    
    // Apply filters
    const applyFilters = () => {
      // This would typically make an API call with filters
      // For now, we'll just log the filters
      console.log('Applying filters:', { role: roleFilter.value, status: statusFilter.value });
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
    
    const deleteEmployee = (employee) => {
      console.log('Deleting employee:', employee);
      // Implement delete logic here
      if (confirm(`Are you sure you want to delete ${employee.fullname}?`)) {
        // Call API to delete employee
      }
    };
    
    onMounted(() => {
      fetchEmployees();
    });
    
    return {
      loading,
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