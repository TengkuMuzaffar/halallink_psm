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
    return await api.post('/api/register', userData);
  } catch (error) {
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

export default {
  login,
  register,
  logout,
  getUser
};