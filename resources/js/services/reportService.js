import api from '../utils/api';
import store from '../store';

// Helper function to determine the correct endpoint prefix
const getReportEndpoint = () => {
  const user = store.state.user;
  if (user && user.company) {
    return `/api/reports/${user.company.company_type}`;
  }
  return '/api/reports/admin'; // Default fallback
};

/**
 * Get all report validities with pagination and filtering
 */
export const getReportValidities = async (params = {}) => {
  try {
    const endpoint = getReportEndpoint();
    return await api.get(endpoint, { params });
  } catch (error) {
    console.error('Error fetching report validities:', error);
    throw error;
  }
};

/**
 * Get a specific report validity with its related companies
 */
export const getReportValidity = async (reportValidityID) => {
  try {
    const endpoint = getReportEndpoint();
    return await api.get(`${endpoint}/${reportValidityID}`);
  } catch (error) {
    console.error(`Error fetching report validity ${reportValidityID}:`, error);
    throw error;
  }
};

/**
 * Create a new report validity with associated companies
 */
export const createReportValidity = async (reportData) => {
  try {
    const endpoint = getReportEndpoint();
    return await api.post(endpoint, reportData);
  } catch (error) {
    console.error('Error creating report validity:', error);
    throw error;
  }
};

/**
 * Update a report validity (only approval and company_ids can be updated)
 */
export const updateReportValidity = async (reportValidityID, updateData) => {
  try {
    const endpoint = getReportEndpoint();
    return await api.put(`${endpoint}/${reportValidityID}`, updateData);
  } catch (error) {
    console.error(`Error updating report validity ${reportValidityID}:`, error);
    throw error;
  }
};

/**
 * Delete a report validity and its associated reports
 */
export const deleteReportValidity = async (reportValidityID) => {
  try {
    const endpoint = getReportEndpoint();
    return await api.delete(`${endpoint}/${reportValidityID}`);
  } catch (error) {
    console.error(`Error deleting report validity ${reportValidityID}:`, error);
    throw error;
  }
};

/**
 * Get all SME companies for report selection
 */
export const getSmeCompanies = async (searchTerm = '') => {
  try {
    return await api.get('/api/companies', { 
      params: { 
        company_type: 'sme',
        search: searchTerm
      } 
    });
  } catch (error) {
    console.error('Error fetching SME companies:', error);
    throw error;
  }
};

/**
 * Download a PDF report for a specific report validity
 */
export const downloadReportPdf = (reportValidityID) => {
  // Create a temporary anchor element to trigger the download
  const link = document.createElement('a');
  link.href = `/report-pdf/${reportValidityID}`;
  link.target = '_blank';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

export default {
  getReportValidities,
  getReportValidity,
  createReportValidity,
  updateReportValidity,
  deleteReportValidity,
  getSmeCompanies,
  downloadReportPdf
};