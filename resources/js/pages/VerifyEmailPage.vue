<template>
  <div class="verify-email-page">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
          <div class="card mt-5">
            <div class="card-header bg-primary text-white">
              <h4 class="mb-0">Email Verification</h4>
            </div>
            <div class="card-body">
              <div v-if="loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Verifying your email...</p>
              </div>
              
              <div v-else>
                <div v-if="verified" class="text-center">
                  <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                  <h4 class="mt-3">Email Verified Successfully!</h4>
                  <p>Your email has been verified. You can now access all features of the application.</p>
                  <router-link to="/" class="btn btn-primary mt-3">Go to Dashboard</router-link>
                </div>
                
                <div v-else class="text-center">
                  <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                  <h4 class="mt-3">Verification Failed</h4>
                  <p>{{ errorMessage || 'We could not verify your email. The link may be invalid or expired.' }}</p>
                  <router-link to="/profile" class="btn btn-primary mt-3">Go to Profile</router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../utils/api';

export default {
  name: 'VerifyEmailPage',
  setup() {
    const route = useRoute();
    const router = useRouter();
    
    const loading = ref(true);
    const verified = ref(false);
    const errorMessage = ref('');
    
    onMounted(async () => {
      const token = route.query.token;
      const email = route.query.email;
      
      if (!token || !email) {
        errorMessage.value = 'Invalid verification link. Missing token or email.';
        loading.value = false;
        return;
      }
      
      try {
        const response = await api.post('/api/email/verify', { token, email });
        verified.value = true;
        loading.value = false;
      } catch (error) {
        console.error('Email verification error:', error);
        errorMessage.value = error.response?.data?.message || 'Verification failed. Please try again.';
        loading.value = false;
      }
    });
    
    return {
      loading,
      verified,
      errorMessage
    };
  }
};
</script>