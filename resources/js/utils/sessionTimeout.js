/**
 * Session Timeout Utility
 * 
 * This utility tracks user activity and handles session timeouts.
 * It works in conjunction with the backend SessionTimeout middleware.
 */

import axios from 'axios';
import store from '../store';
import router from '../router';

// Configuration
const SESSION_TIMEOUT = 10 * 1000; // 10 seconds (should match backend)
const CHECK_INTERVAL = 5 * 1000;   // Check every 5 seconds
const ACTIVITY_EVENTS = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];

export default function setupSessionTimeout() {
    let lastActivity = Date.now();
    let timeoutChecker = null;
    
    // Function to update last activity timestamp
    const updateActivity = () => {
        lastActivity = Date.now();
        // Optionally ping the server to update the session
        if (store.getters.isAuthenticated) {
            axios.get('/api/check-session').catch(error => {
                // If we get a 401 with SESSION_TIMEOUT code, the session has expired
                if (error.response?.status === 401 && 
                    error.response?.data?.code === 'SESSION_TIMEOUT') {
                    handleSessionTimeout();
                }
            });
        }
    };
    
    // Function to check if session has timed out
    const checkTimeout = () => {
        const now = Date.now();
        if (now - lastActivity > SESSION_TIMEOUT && store.getters.isAuthenticated) {
            handleSessionTimeout();
        }
    };
    
    // Function to handle session timeout
    const handleSessionTimeout = () => {
        // Clear the interval to stop checking
        if (timeoutChecker) {
            clearInterval(timeoutChecker);
            timeoutChecker = null;
        }
        
        // Remove event listeners
        ACTIVITY_EVENTS.forEach(event => {
            window.removeEventListener(event, updateActivity);
        });
        
        // Log the user out
        store.dispatch('logout');
        
        // Redirect to login with message
        router.push({
            name: 'Login',
            params: { message: 'Your session has expired due to inactivity. Please log in again.' }
        });
    };
    
    // Set up activity tracking
    ACTIVITY_EVENTS.forEach(event => {
        window.addEventListener(event, updateActivity);
    });
    
    // Set up interval to check for timeout
    timeoutChecker = setInterval(checkTimeout, CHECK_INTERVAL);
    
    // Set up beforeunload event to clear session when tab/browser is closed
    window.addEventListener('beforeunload', () => {
        // Make a synchronous request to logout
        if (store.getters.isAuthenticated) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/api/logout', false); // false makes it synchronous
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            // Add authorization header if token exists
            const token = localStorage.getItem('token');
            if (token) {
                xhr.setRequestHeader('Authorization', `Bearer ${token}`);
            }
            
            xhr.send();
        }
    });
    
    // Return a cleanup function
    return () => {
        if (timeoutChecker) {
            clearInterval(timeoutChecker);
        }
        
        ACTIVITY_EVENTS.forEach(event => {
            window.removeEventListener(event, updateActivity);
        });
        
        window.removeEventListener('beforeunload', handleSessionTimeout);
    };
}