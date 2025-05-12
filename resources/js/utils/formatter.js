/**
 * Utility functions for formatting different types of data
 */

/**
 * Format large numbers with K, M, B suffixes
 * @param {number|string} value - The number to format
 * @param {number} decimals - Number of decimal places (default: 1)
 * @param {boolean} removeTrailingZeros - Whether to remove trailing zeros (default: true)
 * @returns {string} Formatted number with appropriate suffix
 */
export const formatLargeNumber = (value, decimals = 1, removeTrailingZeros = true) => {
  if (typeof value !== 'number') {
    // Try to convert to number if it's not already
    value = Number(value);
    if (isNaN(value)) return '0';
  }
  
  // Format with appropriate suffix
  let formattedValue, suffix = '';
  
  if (value >= 1000000000) {
    formattedValue = value / 1000000000;
    suffix = 'B';
  } else if (value >= 1000000) {
    formattedValue = value / 1000000;
    suffix = 'M';
  } else if (value >= 1000) {
    formattedValue = value / 1000;
    suffix = 'K';
  } else {
    return value.toFixed(0);
  }
  
  // Format with specified decimal places
  let result = formattedValue.toFixed(decimals);
  
  // Remove trailing zeros if requested
  if (removeTrailingZeros) {
    result = result.replace(/\.0+$/, '');
  }
  
  return result + suffix;
};

/**
 * Format currency values
 * @param {number|string} value - The value to format
 * @param {string} currency - Currency code (default: 'MYR')
 * @param {string} locale - Locale for formatting (default: 'en-MY')
 * @param {boolean} shorten - Whether to shorten large numbers (default: false)
 * @returns {string} Formatted currency value
 */
export const formatCurrency = (value, currency = 'MYR', locale = 'en-MY', shorten = false) => {
  if (typeof value !== 'number') {
    // Try to convert to number if it's not already
    value = Number(value);
    if (isNaN(value)) return currency === 'MYR' ? 'RM 0' : '$0';
  }
  
  // If shorten is true, format large numbers with suffixes
  if (shorten) {
    let prefix = currency === 'MYR' ? 'RM ' : '$';
    return prefix + formatLargeNumber(value);
  }
  
  // Otherwise use standard formatting
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: currency,
    currencyDisplay: 'narrowSymbol'
  }).format(value);
};

/**
 * Format date values
 * @param {string|Date} dateValue - The date to format
 * @param {string} format - Format type: 'short', 'medium', 'long' (default: 'medium')
 * @param {string} locale - Locale for formatting (default: 'en-MY')
 * @returns {string} Formatted date
 */
export const formatDate = (dateValue, format = 'medium', locale = 'en-MY') => {
  if (!dateValue) return '';
  
  const date = dateValue instanceof Date ? dateValue : new Date(dateValue);
  
  if (isNaN(date.getTime())) return '';
  
  const options = {
    short: { year: 'numeric', month: 'numeric', day: 'numeric' },
    medium: { year: 'numeric', month: 'short', day: 'numeric' },
    long: { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' },
    time: { hour: '2-digit', minute: '2-digit' },
    datetime: { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }
  };
  
  return date.toLocaleDateString(locale, options[format] || options.medium);
};

/**
 * Truncate text with ellipsis
 * @param {string} text - The text to truncate
 * @param {number} maxLength - Maximum length before truncation (default: 30)
 * @returns {string} Truncated text with ellipsis if needed
 */
export const truncateText = (text, maxLength = 30) => {
  if (!text || typeof text !== 'string') return '';
  
  if (text.length <= maxLength) return text;
  
  return text.substring(0, maxLength) + '...';
};

/**
 * Format a person's name (e.g., for display in UI)
 * @param {string} fullName - The full name
 * @param {string} format - Format type: 'initial', 'first', 'short', 'full' (default: 'full')
 * @returns {string} Formatted name
 */
export const formatName = (fullName, format = 'full') => {
  if (!fullName || typeof fullName !== 'string') return '';
  
  const nameParts = fullName.trim().split(' ');
  
  switch (format) {
    case 'initial':
      // Return initials (e.g., "John Doe" -> "JD")
      return nameParts.map(part => part.charAt(0).toUpperCase()).join('');
      
    case 'first':
      // Return only first name
      return nameParts[0];
      
    case 'short':
      // Return first name and last initial (e.g., "John D.")
      if (nameParts.length === 1) return nameParts[0];
      return `${nameParts[0]} ${nameParts[nameParts.length - 1].charAt(0)}.`;
      
    case 'full':
    default:
      // Return full name
      return fullName;
  }
};

/**
 * Format file size
 * @param {number} bytes - Size in bytes
 * @param {number} decimals - Number of decimal places (default: 2)
 * @returns {string} Formatted file size with appropriate unit
 */
export const formatFileSize = (bytes, decimals = 2) => {
  if (bytes === 0) return '0 Bytes';
  
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(decimals)) + ' ' + sizes[i];
};

// Export a default object with all formatters
export default {
  formatLargeNumber,
  formatCurrency,
  formatDate,
  truncateText,
  formatName,
  formatFileSize
};

// Add the missing capitalizeFirstLetter function
export function capitalizeFirstLetter(string) {
  if (!string) return '';
  return string.charAt(0).toUpperCase() + string.toLowerCase().slice(1);
}


export function formatDateTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString('en-MY', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Add any other formatter functions you need