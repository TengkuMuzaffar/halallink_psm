<template>
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-12">
        <div class="card theme-card mb-4">
          <div class="card-header theme-header pb-0">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">
                <i class="fas fa-clipboard-check me-2"></i>Delivery Verification
              </h5>
              <button class="btn btn-sm btn-secondary" @click="goBack">
                <i class="fas fa-arrow-left me-1"></i>Back to Deliveries
              </button>
            </div>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center py-5">
              <div class="spinner-border theme-spinner" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-3">Loading verification data...</p>
            </div>
            
            <div v-else-if="error" class="alert alert-danger">
              <i class="fas fa-exclamation-triangle me-2"></i>{{ error }}
            </div>
            
            <div v-else>
              <div class="delivery-info mb-4">
                <h6 class="text-uppercase text-muted mb-3">Delivery Information</h6>
                <div class="row">
                  <div class="col-md-4">
                    <p class="mb-1"><strong>Delivery ID:</strong> {{ deliveryID }}</p>
                    <p class="mb-1"><strong>Location ID:</strong> {{ locationID }}</p>
                  </div>
                  <div class="col-md-4">
                    <p class="mb-1"><strong>Driver:</strong> {{ deliveryInfo.driver_name || 'N/A' }}</p>
                    <p class="mb-1"><strong>Vehicle:</strong> {{ deliveryInfo.vehicle_plate || 'N/A' }}</p>
                  </div>
                  <div class="col-md-4">
                    <p class="mb-1"><strong>Date:</strong> {{ formatDate(deliveryInfo.scheduled_date) }}</p>
                    <p class="mb-1"><strong>Status:</strong> <span class="badge bg-info">{{ deliveryInfo.delivery_status || 'In Progress' }}</span></p>
                  </div>
                </div>
              </div>
              
              <div class="verification-list">
                <h6 class="text-uppercase text-muted mb-3">Verification Items</h6>
                
                <div v-if="verifications.length === 0" class="alert alert-info">
                  <i class="fas fa-info-circle me-2"></i>No verification items found for this delivery and location.
                </div>
                
                <div v-else class="table-responsive">
                  <table class="table align-items-center mb-0">
                    <thead>
                      <tr>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Checkpoint</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Comments</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="verify in verifications" :key="verify.verifyID">
                        <td>
                          <div class="d-flex px-2 py-1">
                            <div class="d-flex flex-column justify-content-center">
                              <h6 class="mb-0 text-sm">{{ verify.checkpoint?.check_name || `Checkpoint #${verify.checkID}` }}</h6>
                              <p class="text-xs text-secondary mb-0">ID: {{ verify.checkID }}</p>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span :class="getStatusBadgeClass(verify.verify_status)">
                            {{ formatStatus(verify.verify_status) }}
                          </span>
                        </td>
                        <td>
                          <p class="text-xs font-weight-bold mb-0">{{ verify.verify_comment || 'No comments' }}</p>
                        </td>
                        <td>
                          <button 
                            class="btn btn-sm btn-primary" 
                            @click="openVerificationModal(verify)"
                            :disabled="verify.verify_status === 'verified'"
                          >
                            <i class="fas fa-edit me-1"></i>{{ verify.verify_status === 'verified' ? 'View' : 'Update' }}
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              
              <div class="mt-4 text-end">
                <button 
                  class="btn btn-success" 
                  @click="completeVerification"
                  :disabled="!allVerificationsComplete"
                >
                  <i class="fas fa-check-circle me-1"></i>Complete All Verifications
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Verification Modal -->
    <div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content theme-modal">
          <div class="modal-header theme-header">
            <h5 class="modal-title" id="verificationModalLabel">
              <i class="fas fa-clipboard-check me-2"></i>Update Verification
            </h5>
            <button type="button" class="btn-close theme-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body theme-body">
            <form @submit.prevent="submitVerification">
              <div class="mb-3">
                <label for="verifyID" class="form-label">Verification ID</label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="verifyID" 
                  v-model="currentVerify.verifyID" 
                  readonly
                >
              </div>
              
              <div class="mb-3">
                <label for="checkpointName" class="form-label">Checkpoint</label>
                <input 
                  type="text" 
                  class="form-control" 
                  id="checkpointName" 
                  v-model="currentVerify.checkpoint?.check_name" 
                  readonly
                >
              </div>
              
              <div class="mb-3">
                <label for="verify_status" class="form-label">Verification Status</label>
                <select 
                  class="form-select" 
                  id="verify_status" 
                  v-model="currentVerify.verify_status" 
                  required
                >
                  <option value="">Select Status</option>
                  <option value="verified">Verified</option>
                  <option value="rejected">Rejected</option>
                  <option value="pending">Pending</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label for="verify_comment" class="form-label">Comments</label>
                <textarea 
                  class="form-control" 
                  id="verify_comment" 
                  v-model="currentVerify.verify_comment" 
                  rows="3"
                  placeholder="Enter any comments or notes about this verification"
                ></textarea>
              </div>
              
              <div class="modal-footer theme-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-1"></i>Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Modal } from 'bootstrap';
import { fetchData, postData } from '../../utils/api';

export default {
  name: 'VerifyDeliveryPage',
  setup() {
    const route = useRoute();
    const router = useRouter();
    const loading = ref(true);
    const error = ref(null);
    const deliveryID = ref(route.params.deliveryID);
    const locationID = ref(route.params.locationID);
    const verifications = ref([]);
    const deliveryInfo = ref({});
    const currentVerify = ref({});
    const verificationModal = ref(null);
    
    // Computed property to check if all verifications are complete
    const allVerificationsComplete = computed(() => {
      return verifications.value.every(v => v.verify_status === 'verified' || v.verify_status === 'rejected');
    });
    
    // Format date
    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    };
    
    // Format status
    const formatStatus = (status) => {
      if (!status) return 'Pending';
      return status.charAt(0).toUpperCase() + status.slice(1);
    };
    
    // Get status badge class
    const getStatusBadgeClass = (status) => {
      const classes = 'badge ';
      switch (status) {
        case 'verified':
          return classes + 'bg-success';
        case 'rejected':
          return classes + 'bg-danger';
        case 'pending':
        default:
          return classes + 'bg-warning';
      }
    };
    
    // Load verification data
    const loadVerificationData = async () => {
      try {
        loading.value = true;
        
        // Fetch delivery info
        const deliveryResponse = await fetchData(`/api/deliveries/${deliveryID.value}`);
        if (deliveryResponse.status === 'success') {
          deliveryInfo.value = deliveryResponse.data;
        }
        
        // Fetch verifications
        const response = await fetchData(`/api/verifications?deliveryID=${deliveryID.value}&locationID=${locationID.value}`);
        if (response.status === 'success') {
          verifications.value = response.data;
          
          // Fetch checkpoint details for each verification
          for (const verify of verifications.value) {
            const checkpointResponse = await fetchData(`/api/checkpoints/${verify.checkID}`);
            if (checkpointResponse.status === 'success') {
              verify.checkpoint = checkpointResponse.data;
            }
          }
        } else {
          error.value = response.message || 'Failed to load verification data';
        }
      } catch (err) {
        error.value = `Error loading verification data: ${err.message || 'Unknown error'}`;
      } finally {
        loading.value = false;
      }
    };
    
    // Open verification modal
    const openVerificationModal = (verify) => {
      currentVerify.value = { ...verify };
      verificationModal.value.show();
    };
    
    // Submit verification update
    const submitVerification = async () => {
      try {
        const response = await postData(`/api/verifications/${currentVerify.value.verifyID}`, {
          verify_status: currentVerify.value.verify_status,
          verify_comment: currentVerify.value.verify_comment
        });
        
        if (response.status === 'success') {
          // Update the verification in the list
          const index = verifications.value.findIndex(v => v.verifyID === currentVerify.value.verifyID);
          if (index !== -1) {
            verifications.value[index] = { ...verifications.value[index], ...currentVerify.value };
          }
          
          // Close the modal
          verificationModal.value.hide();
        } else {
          alert(response.message || 'Failed to update verification');
        }
      } catch (err) {
        alert(`Error updating verification: ${err.message || 'Unknown error'}`);
      }
    };
    
    // Complete all verifications
    const completeVerification = async () => {
      try {
        if (!allVerificationsComplete.value) {
          alert('Please complete all verifications before proceeding');
          return;
        }
        
        const response = await postData(`/api/deliveries/${deliveryID.value}/complete-verification`, {
          locationID: locationID.value
        });
        
        if (response.status === 'success') {
          alert('All verifications completed successfully');
          router.push('/deliveries');
        } else {
          alert(response.message || 'Failed to complete verifications');
        }
      } catch (err) {
        alert(`Error completing verifications: ${err.message || 'Unknown error'}`);
      }
    };
    
    // Go back to deliveries page
    const goBack = () => {
      router.push('/deliveries');
    };
    
    onMounted(() => {
      // Initialize the modal
      verificationModal.value = new Modal(document.getElementById('verificationModal'));
      
      // Load verification data
      loadVerificationData();
    });
    
    return {
      loading,
      error,
      deliveryID,
      locationID,
      verifications,
      deliveryInfo,
      currentVerify,
      allVerificationsComplete,
      formatDate,
      formatStatus,
      getStatusBadgeClass,
      openVerificationModal,
      submitVerification,
      completeVerification,
      goBack
    };
  }
};
</script>

<style scoped>
.theme-card {
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.theme-header {
  background-color: #123524;
  color: #EFE3C2;
  padding: 15px 20px;
}

.theme-body {
  padding: 20px;
}

.theme-spinner {
  color: #3E7B27;
  width: 3rem;
  height: 3rem;
}

.theme-close {
  filter: invert(1) brightness(1.5);
}

.theme-modal {
  border: none;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  border-radius: 10px;
}

.theme-footer {
  background-color: rgba(239, 227, 194, 0.2);
  border-top: 1px solid rgba(18, 53, 36, 0.2);
}

.delivery-info, .verification-list {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
}

.table th {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 12px 15px;
}

.table td {
  padding: 12px 15px;
  vertical-align: middle;
}

.btn {
  border-radius: 5px;
  padding: 8px 16px;
  transition: all 0.2s ease;
}

.btn:hover {
  transform: translateY(-2px);
}

.btn-primary {
  background-color: #3E7B27;
  border-color: #3E7B27;
}

.btn-primary:hover {
  background-color: #2d5a1c;
  border-color: #2d5a1c;
}

.btn-success {
  background-color: #198754;
  border-color: #198754;
}

.btn-success:hover {
  background-color: #157347;
  border-color: #146c43;
}
</style>