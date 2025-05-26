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
  },
  /**
   * Search for slaughterers (employees) by name
   * @param {string} search - Search term
   * @param {number} companyId - Optional company ID
   * @returns {Promise} - Promise with search results
   */
  searchSlaughterers(search = '', companyId = null) {
    const params = {};
    // Only add search param if it's not empty
    if (search) params.search = search;
    if (companyId) params.company_id = companyId;
    
    return fetchData('/api/slaughterers/search', { params });
  },
  /**
   * Verify if a task can be assigned to a user
   * @param {number} taskId - Task ID to verify
   * @returns {Promise} - Promise with verification result
   */
  verifyTaskUser(taskId) {
    return fetchData(`/api/tasks/${taskId}/verify-user`);
  },
};

export default taskService;
