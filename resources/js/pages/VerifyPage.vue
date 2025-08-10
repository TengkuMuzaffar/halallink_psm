<template>
  <div class="verify-page">
    <div class="container py-4">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card theme-card">
            <div class="card-header theme-header">
              <h5 class="mb-0">Delivery Verification</h5>
            </div>
            <div class="card-body">
              <div v-if="loading" class="text-center p-4">
                <div class="spinner-border theme-spinner" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Processing verification...</p>
              </div>
              
              <div v-else-if="error" class="alert alert-danger">
                {{ error }}
                <div class="mt-3">
                  <button class="btn theme-btn-primary" @click="goBack">Go Back</button>
                </div>
              </div>
              
              <div v-else>
                <div class="alert" :class="statusClass" v-if="verificationStatus">
                  <strong>{{ statusMessage }}</strong>
                </div>
                
                <form @submit.prevent="submitVerification" v-if="!verificationComplete">
                  <div class="mb-3">
                    <label for="deliveryID" class="form-label">Delivery ID</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      id="deliveryID" 
                      v-model="formData.deliveryID" 
                      readonly
                    >
                  </div>
                  
                  <div class="mb-3">
                    <label for="checkID" class="form-label">Checkpoint ID</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      id="checkID" 
                      v-model="formData.checkID" 
                      readonly
                    >
                  </div>
                  
                  <div class="mb-3">
                    <label for="verify_status" class="form-label">Verification Status</label>
                    <select 
                      class="form-select" 
                      id="verify_status" 
                      v-model="formData.verify_status" 
                      required
                    >
                      <option value="">Select Status</option>
                      <option value="verified">Verified</option>
                      <option value="rejected">Rejected</option>
                    </select>
                  </div>
                  
                  <div class="mb-3">
                    <label for="verify_comment" class="form-label">Comments</label>
                    <textarea 
                      class="form-control" 
                      id="verify_comment" 
                      v-model="formData.verify_comment" 
                      rows="3"
                      placeholder="Add any comments about this verification"
                    ></textarea>
                  </div>
                  
                  <div class="d-grid gap-2">
                    <button 
                      type="submit" 
                      class="btn theme-btn-primary" 
                      :disabled="submitting"
                    >
                      <span v-if="submitting">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Processing...
                      </span>
                      <span v-else>Submit Verification</span>
                    </button>
                    <button 
                      type="button" 
                      class="btn theme-btn-secondary" 
                      @click="goBack"
                    >
                      Cancel
                    </button>
                  </div>
                </form>
                
                <div v-else class="text-center">
                  <p class="mb-4">Verification has been successfully processed.</p>
                  <button class="btn theme-btn-primary" @click="goBack">Return to Delivery</button>
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
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'VerifyPage',
  
  setup() {
    const route = useRoute();
    const router = useRouter();
    
    const loading = ref(true);
    const error = ref(null);
    const submitting = ref(false);
    const verificationComplete = ref(false);
    const verificationStatus = ref(null);
    
    const formData = reactive({
      deliveryID: '',
      checkID: '',
      verify_status: '',
      verify_comment: ''
    });
    
    const statusClass = computed(() => {
      if (!verificationStatus.value) return '';
      
      switch (verificationStatus.value) {
        case 'success':
          return 'alert-success';
        case 'error':
          return 'alert-danger';
        case 'warning':
          return 'alert-warning';
        default:
          return 'alert-info';
      }
    });
    
    const statusMessage = computed(() => {
      if (!verificationStatus.value) return '';
      
      switch (verificationStatus.value) {
        case 'success':
          return 'Verification successful!';
        case 'error':
          return 'Verification failed. Please try again.';
        case 'warning':
          return 'Verification pending. Please complete the form.';
        default:
          return 'Processing verification...';
      }
    });
    
    onMounted(async () => {
      try {
        // Get parameters from the URL
        const deliveryID = route.query.deliveryID;
        const locationID = route.query.locationID;
        const qrUrl = route.query.url;
        
        if (!deliveryID || !locationID || !qrUrl) {
          error.value = 'Missing required parameters. Please scan the QR code again.';
          loading.value = false;
          return;
        }
        
        // Extract orderID and checkpointID from the QR URL
        const urlParts = qrUrl.split('/');
        const orderID = urlParts[urlParts.length - 2];
        const checkpointLocationID = urlParts[urlParts.length - 1];
        
        // Validate that the locationID matches
        if (checkpointLocationID !== locationID) {
          error.value = 'Location ID mismatch. Please scan the correct QR code for this location.';
          loading.value = false;
          return;
        }
        
        // Fetch checkpoint data from the API
        const response = await axios.get(`/api/qrcode/process/${orderID}/${locationID}`);
        
        if (response.data.success) {
          // Set form data
          formData.deliveryID = deliveryID;
          formData.checkID = response.data.checkpoint?.checkID || '';
          
          // Set verification status
          verificationStatus.value = 'warning';
          
          // Check if already verified
          if (response.data.verified) {
            verificationStatus.value = 'success';
            verificationComplete.value = true;
          }
        } else {
          error.value = response.data.message || 'Failed to fetch verification data';
        }
      } catch (err) {
        error.value = err.response?.data?.message || 'An error occurred while processing the verification';
      } finally {
        loading.value = false;
      }
    });
    
    const submitVerification = async () => {
      if (!formData.verify_status) {
        verificationStatus.value = 'error';
        return;
      }
      
      submitting.value = true;
      
      try {
        const response = await axios.post('/api/qrcode/process', formData);
        
        if (response.data.success) {
          verificationStatus.value = 'success';
          verificationComplete.value = true;
        } else {
          verificationStatus.value = 'error';
          error.value = response.data.message || 'Verification submission failed';
        }
      } catch (err) {
        verificationStatus.value = 'error';
        error.value = err.response?.data?.message || 'An error occurred while submitting the verification';
      } finally {
        submitting.value = false;
      }
    };
    
    const goBack = () => {
      router.push('/deliveries');
    };
    
    return {
      loading,
      error,
      formData,
      submitting,
      verificationComplete,
      verificationStatus,
      statusClass,
      statusMessage,
      submitVerification,
      goBack
    };
  }
};
</script>

<style scoped>
.verify-page {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.theme-card {
  border-color: rgba(18, 53, 36, 0.2);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.theme-header {
  background-color: #123524;
  color: #EFE3C2;
  border-bottom: none;
}

.theme-spinner {
  color: #3E7B27;
}

.theme-btn-primary {
  background-color: #123524;
  border-color: #123524;
  color: #EFE3C2;
  transition: all 0.3s ease;
}

.theme-btn-primary:hover {
  background-color: #0a1f16;
  border-color: #0a1f16;
}

.theme-btn-secondary {
  background-color: #6c757d;
  border-color: #6c757d;
  color: #fff;
}

.theme-btn-secondary:hover {
  background-color: #5a6268;
  border-color: #545b62;
}
</style>