import store from '../store';

export const authMiddleware = async (to, from, next) => {
  // If route requires authentication
  if (to.matched.some(record => record.meta.requiresAuth)) {
    // Check if user is authenticated
    if (!store.getters.isAuthenticated) {
      try {
        // Try to fetch user data if token exists
        if (store.state.token) {
          await store.dispatch('fetchUser');
          
          // If authentication was successful, proceed
          if (store.getters.isAuthenticated) {
            return next();
          }
        }
        
        // If not authenticated, redirect to login
        return next({ name: 'login' });
      } catch (error) {
        console.error('Authentication error:', error);
        return next({ name: 'login' });
      }
    } else {
      // User is already authenticated, proceed
      return next();
    }
  } 
  // If route requires guest (non-authenticated user)
  else if (to.matched.some(record => record.meta.requiresGuest)) {
    if (store.getters.isAuthenticated) {
      return next({ name: 'dashboard' });
    } else {
      return next();
    }
  } else {
    // Route doesn't require auth or guest, proceed
    return next();
  }
};

export default authMiddleware;