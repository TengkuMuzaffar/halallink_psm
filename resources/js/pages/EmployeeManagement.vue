<template>
  <div class="employee-management">
    <h1 class="mb-4">Employee Management</h1>
    
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
          <h5 class="card-title">Employees</h5>
          <button class="btn btn-primary" @click="openAddModal">
            <i class="fas fa-plus me-1"></i> Add Employee
          </button>
        </div>
        
        <ResponsiveTable
          :columns="columns"
          :items="employees"
          :hasActions="true"
        >
          <!-- Custom column slots -->
          <template #status="{ item }">
            <span :class="getStatusClass(item.status)">{{ item.status }}</span>
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
          
          <!-- Empty state slot -->
          <template #empty>
            <div class="text-center py-4">
              <i class="fas fa-users text-muted mb-2" style="font-size: 2rem;"></i>
              <p class="text-muted">No employees found</p>
            </div>
          </template>
        </ResponsiveTable>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import ResponsiveTable from '../components/ResponsiveTable.vue';

export default {
  name: 'EmployeeManagement',
  components: {
    ResponsiveTable
  },
  setup() {
    const employees = ref([]);
    const columns = [
      { field: 'name', label: 'Name' },
      { field: 'email', label: 'Email' },
      { field: 'role', label: 'Role' },
      { field: 'department', label: 'Department' },
      { field: 'status', label: 'Status', class: 'text-center' }
    ];
    
    // Your existing methods...
    
    return {
      employees,
      columns,
      // Your existing return values...
    };
  }
}
</script>