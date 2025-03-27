import api from '../utils/api';

// Handle CSRF token
const getCsrfToken = async () => {
  await api.get('/sanctum/csrf-cookie');
};

export const login = async (credentials) => {
  try {
    await getCsrfToken();
    return await api.post('/api/login', credentials, {
      onError: (error) => {
        console.error('Login API error:', error.response?.data || error.message);
      }
    });
  } catch (error) {
    throw error;
  }
};

export const register = async (userData) => {
  try {
    await getCsrfToken();
    
    // Check if userData is FormData
    if (userData instanceof FormData) {
      // Log FormData entries for debugging
      console.log('Sending FormData to server:');
      for (let pair of userData.entries()) {
        console.log(pair[0] + ': ' + (pair[1] instanceof File ? `File: ${pair[1].name} (${pair[1].type})` : pair[1]));
      }
      
      // Use axios directly for FormData
      const response = await api.client.post('/api/register', userData, {
        headers: {
          'Content-Type': 'multipart/form-data',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });
      return response.data;
    }
    
    // Regular JSON data
    return await api.post('/api/register', userData);
  } catch (error) {
    console.error('Registration error:', error.response?.data || error.message);
    throw error;
  }
};

export const logout = async () => {
  return await api.post('/api/logout');
};

export const getUser = async () => {
  try {
    return await api.get('/api/user');
  } catch (error) {
    throw error;
  }
};

// Add this function to your authService.js file
export const registerEmployee = async (formData) => {
  try {
    await getCsrfToken();
    return await api.post('/api/register-employee', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onError: (error) => {
        console.error('Register employee API error:', error.response?.data || error.message);
      }
    });
  } catch (error) {
    throw error;
  }
};

export default {
  login,
  register,
  logout,
  getUser
};