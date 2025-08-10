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
 * Get all report validities with pagination and filtering (generic)
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
 * Get all report validities for admin with pagination and filtering
 */
export const getAdminReportValidities = async (params = {}) => {
  try {
    return await api.get('/api/reports/admin', { params });
  } catch (error) {
    console.error('Error fetching admin report validities:', error);
    throw error;
  }
};

/**
 * Get all report validities for SME with pagination and filtering
 */
export const getSmeReportValidities = async (params = {}) => {
  try {
    return await api.get('/api/reports/sme', { params });
  } catch (error) {
    console.error('Error fetching SME report validities:', error);
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

/**
 * Download a QR code for a specific report validity
 */
export const downloadReportQrCode = async (reportValidityID) => {
  try {
    // Create a URL for the QR code to point to (same as backend implementation)
    const url = `${window.location.origin}/report-pdf/${reportValidityID}`;
    
    // Create a temporary canvas element to generate the QR code
    const canvas = document.createElement('canvas');
    canvas.width = 300; // Match the backend size
    canvas.height = 300;
    document.body.appendChild(canvas);
    
    // Generate QR code using the QRCode library
    return new Promise((resolve, reject) => {
      QRCode.toCanvas(canvas, url, {
        width: 300,
        margin: 4,
        color: {
          dark: '#000000', // Black
          light: '#FFFFFF'  // White
        },
        errorCorrectionLevel: 'M' // Medium error correction is sufficient without logo
      }, function(error) {
        if (error) {
          console.error('Error generating QR code:', error);
          document.body.removeChild(canvas);
          reject(error);
          return;
        }
        
        // Convert canvas to blob and download
        canvas.toBlob(function(blob) {
          const url = window.URL.createObjectURL(blob);
          
          // Create a temporary link to download the file
          const link = document.createElement('a');
          link.href = url;
          link.download = `sme-report-qr-${reportValidityID}.png`;
          document.body.appendChild(link);
          link.click();
          
          // Clean up
          window.URL.revokeObjectURL(url);
          document.body.removeChild(link);
          document.body.removeChild(canvas);
          
          resolve(true);
        }, 'image/png');
      });
    });
  } catch (error) {
    console.error(`Error generating QR code for report ${reportValidityID}:`, error);
    throw error;
  }
};

export default {
  getReportValidities,
  getAdminReportValidities,
  getSmeReportValidities,
  getReportValidity,
  createReportValidity,
  updateReportValidity,
  deleteReportValidity,
  getSmeCompanies,
  downloadReportPdf,
  downloadReportQrCode
};