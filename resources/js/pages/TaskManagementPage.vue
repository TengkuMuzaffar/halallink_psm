<template>
  <div class="task-management-page">
    <h1 class="mb-4">Task Management</h1>
    
    <!-- Stats Cards Row -->
    <div class="row mb-4">
      <div class="col-md-4 mb-3 mb-md-0">
        <StatsCard 
          title="Total Tasks" 
          :count="taskStats.total || 0" 
          icon="fas fa-tasks" 
          bg-color="bg-primary"
        />
      </div>
      <div class="col-md-4 mb-3 mb-md-0">
        <StatsCard 
          title="Completed Tasks" 
          :count="taskStats.completed || 0" 
          icon="fas fa-check-circle" 
          bg-color="bg-success"
        />
      </div>
      <div class="col-md-4">
        <StatsCard 
          title="Pending Tasks" 
          :count="taskStats.pending || 0" 
          icon="fas fa-clock" 
          bg-color="bg-warning"
        />
      </div>
    </div>
    
    <!-- Tasks Table -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-4">Tasks List</h5>
        
        <ResponsiveTable
          :columns="columns"
          :items="tasks"
          :loading="loading"
          @search="handleSearch"
          @sort="handleSort"
          @page-change="handlePageChange"
        >
          <!-- Status column slot - corrected name -->
          <template #task_status="{ item }">
            <span 
              class="badge" 
              :class="getStatusClass(item.task_status)"
            >
              {{ item.task_status }}
            </span>
          </template>
          
         
          
          <!-- Item name slot -->
          <template #item_name="{ item }">
            {{ item.item_name || 'N/A' }}
          </template>
          
          <!-- Actions column slot -->
          <template #actions="{ item }">
            <button 
              class="btn btn-sm btn-primary" 
              @click="viewTaskDetails(item)"
            >
              <i class="fas fa-edit"></i> Edit
            </button>
          </template>
          
          <!-- Empty state slot -->
          <template #empty>
            <div class="text-center py-5">
              <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
              <p>No tasks available</p>
            </div>
          </template>
        </ResponsiveTable>
      </div>
    </div>
    
    <!-- Task Details Modal -->
    <!-- Task Details Modal -->
    <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content theme-modal">
          <div class="modal-header theme-header">
            <h5 class="modal-title">Task Details</h5>
            <button type="button" class="btn-close theme-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body theme-body" v-if="selectedTask">
            <!-- Essential Information - Always Visible -->
            <div class="task-essential-info mb-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <h4 class="mb-1">{{ selectedTask.item_name || 'Unnamed Task' }}</h4>
                  <div class="d-flex align-items-center">
                    <span class="badge me-2" :class="getStatusClass(selectedTask.task_status)">
                      {{ selectedTask.task_status }}
                    </span>
                    <span class="text-muted small">Task ID: {{ selectedTask.taskID }}</span>
                  </div>
                </div>
                <div class="task-timeline">
                  <div class="d-flex align-items-center" v-if="selectedTask.start_timestamp">
                    <i class="fas fa-play-circle text-primary me-1"></i>
                    <span class="small">{{ formatDate(selectedTask.start_timestamp) }}</span>
                  </div>
                  <div class="d-flex align-items-center" v-if="selectedTask.finish_timestamp">
                    <i class="fas fa-check-circle text-success me-1"></i>
                    <span class="small">{{ formatDate(selectedTask.finish_timestamp) }}</span>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="info-item">
                    <span class="info-label">Delivery ID:</span>
                    <span class="info-value">{{ selectedTask.deliveryID || 'N/A' }}</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Order ID:</span>
                    <span class="info-value">{{ selectedTask.orderID || 'N/A' }}</span>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="info-item">
                    <span class="info-label">Quantity:</span>
                    <span class="info-value">{{ selectedTask.item_quantity || 'N/A' }}</span>
                  </div>
                  <div class="info-item" v-if="selectedTask.measurement_value && selectedTask.measurement_type">
                    <span class="info-label">Measurement:</span>
                    <span class="info-value">{{ selectedTask.measurement_value }} {{ selectedTask.measurement_type }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Expandable Sections -->
            <div class="accordion" id="taskDetailsAccordion">
              <!-- Item Details Section -->
              <div class="accordion-item border-0 mb-2">
                <h2 class="accordion-header" id="itemDetailsHeading">
                  <button class="accordion-button collapsed theme-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#itemDetailsCollapse" aria-expanded="false" aria-controls="itemDetailsCollapse">
                    <i class="fas fa-box me-2"></i> Item Details
                  </button>
                </h2>
                <div id="itemDetailsCollapse" class="accordion-collapse collapse" aria-labelledby="itemDetailsHeading" data-bs-parent="#taskDetailsAccordion">
                  <div class="accordion-body theme-accordion-body">
                    <div class="info-item">
                      <span class="info-label">Item Name:</span>
                      <span class="info-value">{{ selectedTask.item_name || 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Quantity:</span>
                      <span class="info-value">{{ selectedTask.item_quantity || 'N/A' }}</span>
                    </div>
                    <div class="info-item" v-if="selectedTask.measurement_value && selectedTask.measurement_type">
                      <span class="info-label">Measurement:</span>
                      <span class="info-value">{{ selectedTask.measurement_value }} {{ selectedTask.measurement_type }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Task Type:</span>
                      <span class="info-value text-capitalize">{{ selectedTask.task_type || 'N/A' }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Supplier Information Section -->
              <div class="accordion-item border-0 mb-2">
                <h2 class="accordion-header" id="supplierHeading">
                  <button class="accordion-button collapsed theme-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#supplierCollapse" aria-expanded="false" aria-controls="supplierCollapse">
                    <i class="fas fa-map-marker-alt me-2"></i> Supplier Information
                  </button>
                </h2>
                <div id="supplierCollapse" class="accordion-collapse collapse" aria-labelledby="supplierHeading" data-bs-parent="#taskDetailsAccordion">
                  <div class="accordion-body theme-accordion-body">
                    <div class="info-item">
                      <span class="info-label">Supplier Address:</span>
                      <span class="info-value">{{ selectedTask.supplier_address || 'No address available' }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Timeline Section -->
              <div class="accordion-item border-0 mb-2">
                <h2 class="accordion-header" id="timelineHeading">
                  <button class="accordion-button collapsed theme-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#timelineCollapse" aria-expanded="false" aria-controls="timelineCollapse">
                    <i class="fas fa-history me-2"></i> Timeline
                  </button>
                </h2>
                <div id="timelineCollapse" class="accordion-collapse collapse" aria-labelledby="timelineHeading" data-bs-parent="#taskDetailsAccordion">
                  <div class="accordion-body theme-accordion-body">
                    <div class="timeline">
                      <div class="timeline-item">
                        <div class="timeline-icon bg-secondary">
                          <i class="fas fa-plus text-white"></i>
                        </div>
                        <div class="timeline-content">
                          <h6 class="mb-0">Created</h6>
                          <p class="small text-muted mb-0">{{ formatDate(selectedTask.created_at) }}</p>
                        </div>
                      </div>
                      
                      <div class="timeline-item" v-if="selectedTask.start_timestamp">
                        <div class="timeline-icon bg-primary">
                          <i class="fas fa-play text-white"></i>
                        </div>
                        <div class="timeline-content">
                          <h6 class="mb-0">Started</h6>
                          <p class="small text-muted mb-0">{{ formatDate(selectedTask.start_timestamp) }}</p>
                        </div>
                      </div>
                      
                      <div class="timeline-item" v-if="selectedTask.finish_timestamp">
                        <div class="timeline-icon bg-success">
                          <i class="fas fa-check text-white"></i>
                        </div>
                        <div class="timeline-content">
                          <h6 class="mb-0">Completed</h6>
                          <p class="small text-muted mb-0">{{ formatDate(selectedTask.finish_timestamp) }}</p>
                        </div>
                      </div>
                      
                      <div class="timeline-item" v-if="selectedTask.updated_at && selectedTask.updated_at !== selectedTask.created_at">
                        <div class="timeline-icon bg-info">
                          <i class="fas fa-edit text-white"></i>
                        </div>
                        <div class="timeline-content">
                          <h6 class="mb-0">Last Updated</h6>
                          <p class="small text-muted mb-0">{{ formatDate(selectedTask.updated_at) }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Notes Section -->
              <div class="accordion-item border-0">
                <h2 class="accordion-header" id="notesHeading">
                  <button class="accordion-button collapsed theme-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#notesCollapse" aria-expanded="false" aria-controls="notesCollapse">
                    <i class="fas fa-sticky-note me-2"></i> Notes
                  </button>
                </h2>
                <div id="notesCollapse" class="accordion-collapse collapse" aria-labelledby="notesHeading" data-bs-parent="#taskDetailsAccordion">
                  <div class="accordion-body theme-accordion-body">
                    <p class="mb-0">{{ selectedTask.notes || 'No notes available' }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer theme-footer">
            <!-- Start Task button - show when both timestamps are null -->
            <button 
              v-if="selectedTask && !selectedTask.start_timestamp && !selectedTask.finish_timestamp"
              type="button" 
              class="btn btn-primary theme-btn-primary me-2" 
              @click="openStartConfirmation()"
            >
              <i class="fas fa-play"></i> Start Task
            </button>
            
            <!-- Complete Task button - show when start_timestamp exists but finish_timestamp is null -->
            <button 
              v-if="selectedTask && selectedTask.start_timestamp && !selectedTask.finish_timestamp"
              type="button" 
              class="btn btn-success me-2" 
              @click="openCompleteConfirmation()"
            >
              <i class="fas fa-check"></i> Complete Task
            </button>
            
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Start Task Confirmation Modal -->
    <div class="modal fade" id="startTaskConfirmModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content theme-modal">
          <div class="modal-header theme-header">
            <h5 class="modal-title">Confirm Start Task</h5>
            <button type="button" class="btn-close theme-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body theme-body">
            <p>Are you sure you want to start this task?</p>
          </div>
          <div class="modal-footer theme-footer">
            <button type="button" class="btn btn-secondary" @click="cancelStartTask()">No</button>
            <button type="button" class="btn btn-primary theme-btn-primary" @click="confirmStartTask()">Yes</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Complete Task Confirmation Modal -->
    <div class="modal fade" id="completeTaskConfirmModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content theme-modal">
          <div class="modal-header theme-header">
            <h5 class="modal-title">Confirm Complete Task</h5>
            <button type="button" class="btn-close theme-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body theme-body">
            <p>Are you sure you want to complete this task?</p>
          </div>
          <div class="modal-footer theme-footer">
            <button type="button" class="btn btn-secondary" @click="cancelCompleteTask()">No</button>
            <button type="button" class="btn btn-success" @click="confirmCompleteTask()">Yes</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue';
import StatsCard from '../components/ui/StatsCard.vue';
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';
import LoadingSpinner from '../components/ui/LoadingSpinner.vue';
import taskService from '../services/taskService';
import * as bootstrap from 'bootstrap';

export default {
  name: 'TaskManagement',
  components: {
    StatsCard,
    ResponsiveTable,
    LoadingSpinner
  },
  setup() {
    // State variables
    const loading = ref(true);
    const error = ref(null);
    const tasks = ref([]);
    const taskStats = ref({
      total: 0,
      completed: 0,
      pending: 0
    });
    const selectedTask = ref(null);
    
    // Table columns configuration
    const columns = [
      { key: 'deliveryID', label: 'Delivery ID', sortable: true },
      { key: 'orderID', label: 'Order ID', sortable: true },
      { key: 'item_name', label: 'Item Name', sortable: true },
      { key: 'task_status', label: 'Status', sortable: true },
    ];
    
    // Pagination state
    const pagination = ref({
      current_page: 1,
      per_page: 10,
      total: 0
    });
    
    // Search and sort state
    const searchQuery = ref('');
    const sortBy = ref('start_timestamp');
    const sortDirection = ref('asc');
    
    // Modal references
    let taskDetailsModal = null;
    let startTaskConfirmModal = null;
    let completeTaskConfirmModal = null;
    
    // Fetch tasks with current filters and pagination
    const fetchTasks = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const params = {
          page: pagination.value.current_page,
          search: searchQuery.value,
          sort_by: sortBy.value,
          sort_direction: sortDirection.value
        };
        
        const response = await taskService.fetchTasks(params);
        console.log('Response:', JSON.stringify(response, null, 2)); // Log the response to the console  
        tasks.value = response.data;
        
        // Update pagination from response
        pagination.value = {
          current_page: response.current_page,
          per_page: response.per_page,
          total: response.total
        };
        
        // Calculate stats
        calculateStats();
      } catch (err) {
        console.error('Error fetching tasks:', err);
        error.value = 'Failed to load tasks. Please try again.';
      } finally {
        loading.value = false;
      }
    };
    
    // Calculate task statistics
    const calculateStats = () => {
      const completed = tasks.value.filter(task => task.task_status === 'completed').length;
      const pending = tasks.value.filter(task => task.task_status !== 'completed').length;
      
      taskStats.value = {
        total: tasks.value.length,
        completed,
        pending
      };
    };
    
    // Format date for display
    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';
      
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    };
    
    // Get CSS class for status badge
    const getStatusClass = (status) => {
      switch (status) {
        case 'completed':
          return 'bg-success';
        case 'in_progress':
          return 'bg-primary';
        case 'pending':
          return 'bg-warning';
        default:
          return 'bg-secondary';
      }
    };
    
    // Handle search input
    const handleSearch = (query) => {
      searchQuery.value = query;
      pagination.value.current_page = 1; // Reset to first page
      fetchTasks();
    };
    
    // Handle sort change
    const handleSort = ({ key, direction }) => {
      sortBy.value = key;
      sortDirection.value = direction;
      fetchTasks();
    };
    
    // Handle page change
    const handlePageChange = (page) => {
      pagination.value.current_page = page;
      fetchTasks();
    };
    
    // View task details
    const viewTaskDetails = (task) => {
      selectedTask.value = task;
      
      // Initialize modal if not already done
      if (!taskDetailsModal) {
        taskDetailsModal = new bootstrap.Modal(document.getElementById('taskDetailsModal'));
      }
      
      taskDetailsModal.show();
    };
    
    // Open start task confirmation modal
    const openStartConfirmation = () => {
      // Hide the task details modal
      taskDetailsModal.hide();
      
      // Initialize and show the start confirmation modal
      if (!startTaskConfirmModal) {
        startTaskConfirmModal = new bootstrap.Modal(document.getElementById('startTaskConfirmModal'));
      }
      
      startTaskConfirmModal.show();
    };
    
    // Open complete task confirmation modal
    const openCompleteConfirmation = () => {
      // Hide the task details modal
      taskDetailsModal.hide();
      
      // Initialize and show the complete confirmation modal
      if (!completeTaskConfirmModal) {
        completeTaskConfirmModal = new bootstrap.Modal(document.getElementById('completeTaskConfirmModal'));
      }
      
      completeTaskConfirmModal.show();
    };
    
    // Cancel start task
    const cancelStartTask = () => {
      // Hide the confirmation modal
      startTaskConfirmModal.hide();
      
      // Show the task details modal again
      setTimeout(() => {
        taskDetailsModal.show();
      }, 500); // Small delay to allow the first modal to close
    };
    
    // Cancel complete task
    const cancelCompleteTask = () => {
      // Hide the confirmation modal
      completeTaskConfirmModal.hide();
      
      // Show the task details modal again
      setTimeout(() => {
        taskDetailsModal.show();
      }, 500); // Small delay to allow the first modal to close
    };
    
    // Confirm start task
    const confirmStartTask = async () => {
      try {
        const response = await taskService.updateTask(selectedTask.value.taskID, {
          task_status: 'in_progress',
          start_timestamp: new Date().toISOString()
        });
        
        // Hide the confirmation modal
        startTaskConfirmModal.hide();
        
        // Refresh the tasks data
        await fetchTasks();
        
        // Show the task details modal again with refreshed data
        setTimeout(() => {
          // Find the updated task in the refreshed data
          const updatedTask = tasks.value.find(t => t.taskID === selectedTask.value.taskID);
          if (updatedTask) {
            selectedTask.value = updatedTask;
          }
          taskDetailsModal.show();
        }, 500); // Small delay to allow the first modal to close
      } catch (err) {
        console.error('Error starting task:', err);
        alert('Failed to start task. Please try again.');
        
        // Hide the confirmation modal
        startTaskConfirmModal.hide();
      }
    };
    
    // Confirm complete task
    const confirmCompleteTask = async () => {
      try {
        const response = await taskService.updateTask(selectedTask.value.taskID, {
          task_status: 'completed',
          finish_timestamp: new Date().toISOString()
        });
        
        // Hide the confirmation modal
        completeTaskConfirmModal.hide();
        
        // Refresh the tasks data
        await fetchTasks();
        
        // Show the task details modal again with refreshed data
        setTimeout(() => {
          // Find the updated task in the refreshed data
          const updatedTask = tasks.value.find(t => t.taskID === selectedTask.value.taskID);
          if (updatedTask) {
            selectedTask.value = updatedTask;
          }
          taskDetailsModal.show();
        }, 500); // Small delay to allow the first modal to close
      } catch (err) {
        console.error('Error completing task:', err);
        alert('Failed to complete task. Please try again.');
        
        // Hide the confirmation modal
        completeTaskConfirmModal.hide();
      }
    };
    
    // Initialize component
    onMounted(() => {
      fetchTasks();
    });
    
    return {
      loading,
      error,
      tasks,
      taskStats,
      columns,
      pagination,
      selectedTask,
      formatDate,
      getStatusClass,
      handleSearch,
      handleSort,
      handlePageChange,
      viewTaskDetails,
      openStartConfirmation,
      openCompleteConfirmation,
      cancelStartTask,
      cancelCompleteTask,
      confirmStartTask,
      confirmCompleteTask
    };
  }
};
</script>

<style scoped>
.task-management-page {
  padding-bottom: 2rem;
}

.badge {
  font-size: 0.85rem;
  padding: 0.35em 0.65em;
}

/* Theme styling */
.theme-modal {
  --primary-color: #123524;
  --secondary-color: #EFE3C2;
  --accent-color: #3E7B27;
  --text-color: #333;
  --light-text: #666;
  --border-color: rgba(18, 53, 36, 0.2);
  --light-bg: rgba(239, 227, 194, 0.2);
  --lighter-bg: rgba(239, 227, 194, 0.1);
  
  border: none;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  background-color: #fff;
  border-radius: 10px;
  overflow: hidden;
}

.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
  padding: 15px 20px;
}

.theme-close {
  filter: invert(1) brightness(1.5);
}

.theme-body {
  background-color: #fff;
  color: var(--text-color);
}

.theme-footer {
  background-color: var(--light-bg);
  border-top: 1px solid var(--border-color);
  padding: 15px 20px;
}

.theme-btn-primary {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
  border-radius: 5px;
  padding: 8px 16px;
  transition: all 0.2s ease;
}

.theme-btn-primary:hover {
  background-color: #0a1f16;
  border-color: #0a1f16;
  transform: translateY(-2px);
}

/* Additional styles for enhanced task details modal */
.timeline-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.card-header {
  background-color: var(--lighter-bg) !important;
  border-bottom: 1px solid var(--border-color);
}

.shadow-sm {
  box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important;
}

.theme-btn-primary {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
}

.theme-btn-primary:hover {
  background-color: #0a1f16;
  border-color: #0a1f16;
}

/* Additional styles for task details modal */
.task-essential-info {
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 1rem;
}

.info-item {
  margin-bottom: 0.5rem;
  display: flex;
}

.info-label {
  font-weight: 600;
  min-width: 120px;
  color: var(--light-text);
}

.info-value {
  color: var(--text-color);
}

.theme-accordion-button {
  background-color: var(--lighter-bg);
  color: var(--primary-color);
  font-weight: 600;
  padding: 0.75rem 1rem;
  border: 1px solid var(--border-color);
  border-radius: 6px !important;
  box-shadow: none;
}

.theme-accordion-button:not(.collapsed) {
  background-color: var(--light-bg);
  color: var(--primary-color);
}

.theme-accordion-button:focus {
  box-shadow: none;
  border-color: var(--primary-color);
}

.theme-accordion-body {
  padding: 1rem;
  background-color: #fff;
  border: 1px solid var(--border-color);
  border-top: none;
  border-radius: 0 0 6px 6px;
}

.timeline {
  position: relative;
  padding-left: 1.5rem;
}

.timeline-item {
  position: relative;
  padding-bottom: 1.5rem;
}

.timeline-item:last-child {
  padding-bottom: 0;
}

.timeline-item:not(:last-child)::before {
  content: '';
  position: absolute;
  left: 15px;
  top: 30px;
  height: calc(100% - 30px);
  width: 2px;
  background-color: var(--border-color);
}

.timeline-icon {
  position: absolute;
  left: -1.5rem;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.timeline-content {
  padding-left: 0.5rem;
}

.task-timeline {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.task-timeline > div {
  margin-bottom: 0.25rem;
}

.task-timeline > div:last-child {
  margin-bottom: 0;
}
</style>