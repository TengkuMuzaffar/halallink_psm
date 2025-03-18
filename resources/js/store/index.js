import { createStore } from 'vuex';
import api from '../utils/api';

export default createStore({
  state: {
    user: null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: false
  },
  getters: {
    user: state => state.user,
    isAuthenticated: state => !!state.token,
    userCompany: state => state.user?.company || null,
    token: state => state.token
  },
  mutations: {
    SET_USER(state, user) {
      state.user = user;
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
    logout(state) {
      state.user = null;
      state.token = null;
      state.isAuthenticated = false;
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
    }
  }
});
