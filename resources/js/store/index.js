import { createStore } from 'vuex';
import axios from 'axios';

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
    // Fix the action names to match what's being called
    async fetchUser({ commit }) {
      try {
        // Use the authService instead of direct axios call
        const response = await axios.get('/api/user');
        // Make sure we're setting the user data correctly
        if (response.data) {
          commit('SET_USER', response.data);
          commit('setAuthenticated', true);
          return response.data;
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
        const response = await axios.post('/api/login', credentials);
        const { user, access_token } = response.data;
        
        commit('SET_USER', user);
        commit('SET_TOKEN', access_token);
        
        return user;
      } catch (error) {
        throw error;
      }
    },
    
    async logout({ commit }) {
      try {
        await axios.post('/api/logout');
        commit('logout');
        return Promise.resolve();
      } catch (error) {
        commit('logout');
        return Promise.reject(error);
      }
    },  // Added missing comma here
    
    async register({ commit }, userData) {
      try {
        const response = await axios.post('/api/register', userData);
        const { user, access_token } = response.data;
        
        commit('SET_USER', user);
        commit('SET_TOKEN', access_token);
        
        return user;
      } catch (error) {
        throw error;
      }
    }
  }
});
