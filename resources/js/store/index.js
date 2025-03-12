import { createStore } from 'vuex';
import { getUser, logout as logoutService, login as loginService } from '../services/authService';

const store = createStore({
  state() {
    return {
      user: null,
      token: localStorage.getItem('token') || null,
      isAuthenticated: false
    };
  },
  mutations: {
    setUser(state, user) {
      state.user = user;
    },
    setToken(state, token) {
      state.token = token;
      localStorage.setItem('token', token);
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
    setUser({ commit }, user) {
      commit('setUser', user);
    },
    setToken({ commit }, token) {
      commit('setToken', token);
    },
    setAuthenticated({ commit }, isAuthenticated) {
      commit('setAuthenticated', isAuthenticated);
    },
    // Add the missing login action
    async login({ commit }, credentials) {
      try {
        const data = await loginService(credentials);
        commit('setUser', data.user);
        commit('setToken', data.access_token);
        commit('setAuthenticated', true);
        return Promise.resolve(data);
      } catch (error) {
        console.error('Store login error:', error.response?.status, error.response?.data);
        return Promise.reject(error);
      }
    },
    async fetchUser({ commit, state }) {
      if (!state.token) return Promise.resolve(null);
      
      try {
        const user = await getUser();
        commit('setUser', user);
        commit('setAuthenticated', true);
        return Promise.resolve(user);
      } catch (error) {
        commit('logout');
        return Promise.reject(error);
      }
    },
    async logout({ commit }) {
      try {
        await logoutService();
        commit('logout');
        // Force navigation to login page after logout
        window.location.href = '/';
        return Promise.resolve();
      } catch (error) {
        commit('logout');
        // Force navigation to login page even if logout API fails
        window.location.href = '/';
        return Promise.reject(error);
      }
    }
  },
  getters: {
    isAuthenticated: state => state.isAuthenticated,
    user: state => state.user,
    token: state => state.token
  }
});
export default store;
