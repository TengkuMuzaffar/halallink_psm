import { createStore } from 'vuex';
import api from '../utils/api';

export default createStore({
  state: {
    user: null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: false,
    emailVerified: false
  },
  getters: {
    user: state => state.user,
    isAuthenticated: state => !!state.token,
    userCompany: state => state.user?.company || null,
    token: state => state.token,
    emailVerified: state => state.emailVerified
  },
  mutations: {
    SET_USER(state, user) {
      // Preserve existing company data when updating user
      const existingCompany = state.user?.company;
      state.user = user ? { ...user, company: user.company || existingCompany } : null;
      state.emailVerified = !!user?.email_verified_at;
    },
    SET_TOKEN(state, token) {
      state.token = token;
      if (token) {
        localStorage.setItem('token', token);
      } else {
        localStorage.removeItem('token');
      }
    },
    setAuthenticated(state, isAuthenticated) {
      state.isAuthenticated = isAuthenticated;
    },
    SET_EMAIL_VERIFIED(state, verified) {
      state.emailVerified = verified;
    },
    logout(state) {
      state.user = null;
      state.token = null;
      state.isAuthenticated = false;
      state.emailVerified = false;
      localStorage.removeItem('token');
    }
  },
  actions: {
    async fetchUser({ commit }) {
      try {
        // Use the enhanced API
        const userData = await api.get('/api/user');
        if (userData) {
          commit('SET_USER', userData);
          commit('setAuthenticated', true);
          return userData;
        } else {
          throw new Error('No user data returned from API');
        }
      } catch (error) {
        console.error('Error fetching user data:', error);
        throw error;
      }
    },
    
    async login({ commit }, credentials) {
      try {
        const data = await api.post('/api/login', credentials);
        
        const { user, access_token } = data;
        commit('SET_USER', user);
        commit('SET_TOKEN', access_token);
        
        return user;
      } catch (error) {
        throw error;
      }
    },
    
    async logout({ commit }, { silent = false } = {}) {
      try {
        if (!silent) {
          await api.post('/api/logout');
        }
        commit('logout');
        return Promise.resolve();
      } catch (error) {
        commit('logout');
        return Promise.reject(error);
      }
    },
    
    async register({ commit }, userData) {
      try {
        const data = await api.post('/api/register', userData);
        
        const { user, access_token } = data;
        commit('SET_USER', user);
        commit('SET_TOKEN', access_token);
        
        return user;
      } catch (error) {
        throw error;
      }
    },
    
    async sendVerificationEmail() {
      try {
        const response = await api.post('/api/email/send-verification');
        return response;
      } catch (error) {
        console.error('Error sending verification email:', error);
        throw error;
      }
    },
    
    async verifyEmail({ commit }, { token, email }) {
      try {
        const response = await api.post('/api/email/verify', { token, email });
        if (response.verified) {
          // Update the user data to reflect verified status
          const userData = await api.get('/api/user');
          commit('SET_USER', userData);
        }
        return response;
      } catch (error) {
        console.error('Error verifying email:', error);
        throw error;
      }
    },
    
    async checkEmailVerification({ commit }) {
      try {
        const response = await api.get('/api/email/verification-status');
        commit('SET_EMAIL_VERIFIED', response.verified);
        return response.verified;
      } catch (error) {
        console.error('Error checking email verification status:', error);
        return false;
      }
    }
  }
});
