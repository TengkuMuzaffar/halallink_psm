/**
 * Format a date string or Date object to a human-readable format
 * @param {string|Date} dateString - The date to format
 * @param {boolean} includeTime - Whether to include time in the output
 * @returns {string} - Formatted date string
 */
export const formatDate = (dateString, includeTime = false) => {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return 'Invalid date';
  
  const options = {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  };
  
  if (includeTime) {
    options.hour = '2-digit';
    options.minute = '2-digit';
  }
  
  return date.toLocaleDateString('en-US', options);
};

/**
 * Format a date for input[type="datetime-local"]
 * @param {string|Date} dateString - The date to format
 * @returns {string} - Formatted date string (YYYY-MM-DDThh:mm)
 */
export const formatDateForInput = (dateString) => {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '';
  
  return date.toISOString().slice(0, 16);
};

export default {
  formatDate,
  formatDateForInput
};