import axios from 'axios';

// Configure axios
const apiClient = axios.create({
  baseURL: window.location.origin,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
});

// Add token to requests if available
apiClient.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle CSRF token
const getCsrfToken = async () => {
  await apiClient.get('/sanctum/csrf-cookie');
};

export const login = async (credentials) => {
  try {
    await getCsrfToken();
    const response = await apiClient.post('/api/login', credentials);
    return response.data;
  } catch (error) {
    console.error('Login API error:', error.response?.data || error.message);
    throw error;
  }
};

export const register = async (userData) => {
  await getCsrfToken();
  const response = await apiClient.post('/api/register', userData);
  return response.data;
};

export const logout = async () => {
  const response = await apiClient.post('/api/logout');
  return response.data;
};

export const getUser = async () => {
  const response = await apiClient.get('/api/user');
  return response.data;
};

export default {
  login,
  register,
  logout,
  getUser
};