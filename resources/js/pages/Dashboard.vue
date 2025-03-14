<template>
  <div class="dashboard">
    <h1 class="mb-4">Dashboard</h1>
    
    <!-- Stats Cards Row -->
    <div class="row mb-4">
      <!-- Stats cards remain unchanged -->
    </div>
    
    <!-- Recent Users Table -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Recent Users</h5>
      </div>
      <div class="card-body">
        <ResponsiveTable
          :columns="userColumns"
          :items="users"
          :loading="loading"
          itemKey="id"
        >
          <!-- Custom filters -->
          <template #filters>
            <div class="d-flex gap-2">
              <select class="form-select form-select-sm" v-model="roleFilter">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
                <option value="manager">Manager</option>
              </select>
              
              <select class="form-select form-select-sm" v-model="statusFilter">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </template>
          
          <!-- Rest of the template remains unchanged -->
        </ResponsiveTable>
      </div>
    </div>
    
    <!-- Employees Table remains unchanged -->
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import ResponsiveTable from '../components/ResponsiveTable.vue';

export default {
  name: 'Dashboard',
  components: {
    ResponsiveTable
  },
  setup() {
    const loading = ref(true);
    const stats = ref({
      broiler: 0,
      slaughterhouse: 0,
      sme: 0,
      logistic: 0
    });
    
    // Add missing variables
    const users = ref([]);
    const employees = ref([]);
    const roleFilter = ref('');
    const statusFilter = ref('');
    const companyTypeFilter = ref('');
    
    // Table columns definition
    const userColumns = [
      { key: 'name', label: 'Name', sortable: true },
      { key: 'email', label: 'Email', sortable: true },
      { key: 'role', label: 'Role', sortable: true },
      { key: 'status', label: 'Status', sortable: true, class: 'text-center' },
      { key: 'created_at', label: 'Joined', sortable: true }
    ];
    
    const employeeColumns = [
      { key: 'name', label: 'Name', sortable: true },
      { key: 'email', label: 'Email', sortable: true },
      { key: 'company_name', label: 'Company', sortable: true },
      { key: 'company_type', label: 'Type', sortable: true },
      { key: 'status', label: 'Status', sortable: true, class: 'text-center' },
      { key: 'created_at', label: 'Joined', sortable: true }
    ];
    
    // Fetch dashboard data
    const fetchDashboardData = async () => {
      loading.value = true;
      
      try {
        // Initialize with default values in case of errors
        stats.value = {
          broiler: 0,
          slaughterhouse: 0,
          sme: 0,
          logistic: 0
        };
        employees.value = [];
        users.value = []; // Initialize users array
        
        try {
          const response = await axios.get('/api/dashboard/stats');
          // Check if response is valid JSON
          if (typeof response.data === 'object') {
            stats.value = response.data;
          } else {
            console.error('Invalid stats response format:', response);
          }
        } catch (statsError) {
          console.error('Error fetching stats:', statsError);
        }
        
        try {
          // Fetch all employees
          const employeesResponse = await axios.get('/api/employees/all');
          // Check if response is valid JSON array
          if (Array.isArray(employeesResponse.data)) {
            employees.value = employeesResponse.data;
            // Also use this data for users table
            users.value = employeesResponse.data;
          } else if (employeesResponse.data && Array.isArray(employeesResponse.data.data)) {
            // Handle nested data structure
            employees.value = employeesResponse.data.data;
            users.value = employeesResponse.data.data;
          } else {
            console.error('Invalid employees response format:', employeesResponse);
          }
        } catch (employeesError) {
          console.error('Error fetching employees:', employeesError);
        }
      } catch (error) {
        console.error('Error in fetchDashboardData:', error);
      } finally {
        loading.value = false;
      }
    };
    
    // Helper functions
    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';
      
      try {
        const date = new Date(dateString);
        // Check if date is valid
        if (isNaN(date.getTime())) {
          return 'Invalid date';
        }
        
        return new Intl.DateTimeFormat('en-US', { 
          year: 'numeric', 
          month: 'short', 
          day: 'numeric' 
        }).format(date);
      } catch (error) {
        console.error('Error formatting date:', dateString, error);
        return 'Error';
      }
    };
    
    const getRoleBadgeClass = (role) => {
      const classes = {
        admin: 'bg-primary',
        user: 'bg-secondary',
        manager: 'bg-info'
      };
      return classes[role] || 'bg-secondary';
    };
    
    const getCompanyTypeBadgeClass = (type) => {
      const classes = {
        broiler: 'bg-primary',
        slaughterhouse: 'bg-danger',
        sme: 'bg-success',
        logistic: 'bg-warning'
      };
      return classes[type] || 'bg-secondary';
    };
    
    // Filtered employees based on selected filters
    const filteredEmployees = computed(() => {
      let result = [...employees.value];
      
      if (companyTypeFilter.value) {
        result = result.filter(employee => employee.company_type === companyTypeFilter.value);
      }
      
      return result;
    });
    
    // Filtered users based on selected filters
    const filteredUsers = computed(() => {
      let result = [...users.value];
      
      if (roleFilter.value) {
        result = result.filter(user => user.role === roleFilter.value);
      }
      
      if (statusFilter.value) {
        result = result.filter(user => user.status === statusFilter.value);
      }
      
      return result;
    });
    
    onMounted(() => {
      fetchDashboardData();
    });
    
    return {
      loading,
      stats,
      users,
      employees,
      userColumns,
      employeeColumns,
      roleFilter,
      statusFilter,
      companyTypeFilter,
      filteredUsers,
      filteredEmployees,
      formatDate,
      getRoleBadgeClass,
      getCompanyTypeBadgeClass
    };
  }
};
</script>

<style scoped>
.dashboard h1 {
  color: #123524;
}

.icon-box {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.card {
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  border: none;
}

.card-header {
  background-color: #fff;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  padding: 1rem 1.5rem;
}

@media (max-width: 768px) {
  .icon-box {
    width: 40px;
    height: 40px;
    font-size: 1.2rem;
  }
  
  .card-title {
    font-size: 1.5rem;
  }
}
</style>