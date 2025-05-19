import { fetchData } from '../utils/api';

export const taskService = {
  /**
   * Fetch tasks with optional filters and pagination
   * @param {Object} params - Query parameters
   * @returns {Promise} - Promise with response data
   */
  fetchTasks(params = {}) {
    return fetchData('/api/tasks', { params });
  },
  
  /**
   * Fetch task statistics
   * @returns {Promise} - Promise with task stats
   */
  fetchTaskStats() {
    return fetchData('/api/tasks/stats');
  },
  
  /**
   * Get task by ID
   * @param {number} id - Task ID
   * @returns {Promise} - Promise with task data
   */
  getTaskById(id) {
    return fetchData(`/api/tasks/${id}`);
  },
  
  /**
   * Update an existing task
   * @param {number} id - Task ID
   * @param {Object} taskData - Updated task data
   * @returns {Promise} - Promise with updated task
   */
  updateTask(id, taskData) {
    return fetchData(`/api/tasks/${id}`, {
      method: 'put',
      data: taskData
    });
  }
};

export default taskService;