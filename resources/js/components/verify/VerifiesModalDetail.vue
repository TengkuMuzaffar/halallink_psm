<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog" @click.self="closeModal">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content">
        <form @submit.prevent="handleSubmit">
          <div class="modal-header theme-header">
            <h5 class="modal-title">Verification Form #{{ localVerification?.verifyID }}</h5>
            <button type="button" class="btn-close theme-close" @click="closeModal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Loading State -->
            <div v-if="loadingDetails" class="text-center p-4">
                <div class="spinner-border theme-spinner" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading verification details...</p>
            </div>
            
            <!-- Error State -->
            <div v-else-if="fetchError" class="alert alert-danger mt-3">
                {{ fetchError }}
            </div>
            
            <!-- Form Content -->
            <div v-else-if="localVerification" class="verification-form">
              <!-- Basic Info & Status Badge -->
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center">
                  <h6 class="mb-0 me-2">Delivery ID: {{ localVerification.deliveryID }}</h6>
                  <span class="badge bg-secondary me-2">Checkpoint: {{ localVerification.checkID }}</span>
                </div>
                <span class="badge" :class="getStatusBadgeClass(localVerification.verify_status)">
                  {{ localVerification.verify_status || 'Pending' }}
                </span>
              </div>
              
              <!-- Associated Items Section (Always Visible) -->
              <div class="form-section mb-4">
                <h6 class="section-title">Items to Verify</h6>
                
                <div class="items-section">
                  <div v-if="localVerification.associated_items && localVerification.associated_items.length > 0">
                    <div class="table-responsive">
                      <table class="table table-sm table-hover mb-0">
                        <thead class="table-header">
                          <tr>
                            <th scope="col" class="text-center">#</th>
                            <th scope="col">Name</th>
                            <th scope="col" class="text-center">Quantity</th>
                            <th scope="col" class="text-center">Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <template v-for="(items, orderID) in groupItemsByOrder()">
                            <!-- Order Header Row -->
                            <tr class="order-header">
                              <td colspan="4" class="order-header-cell">
                                <div class="d-flex align-items-center">
                                  <span class="order-badge me-2">Order #{{ orderID }}</span>
                                  <span class="order-item-count">{{ items.length }} item(s)</span>
                                </div>
                              </td>
                            </tr>
                            <!-- Item Rows -->
                            <tr v-for="(item, index) in items" :key="item.itemID" class="align-middle item-row">
                              <td class="text-center">{{ index + 1 }}</td>
                              <td>{{ item.item_name || 'Unknown Item' }}</td>
                              <td class="text-center">
                                <span class="quantity-badge">{{ item.quantity || 1 }}</span>
                              </td>
                              <td class="text-center">
                                <span class="badge" :class="getStatusBadgeClass(localVerification.verify_status)">
                                  {{ localVerification.verify_status || 'Pending' }}
                                </span>
                              </td>
                            </tr>
                          </template>
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div v-else class="text-center text-muted py-3">
                    <p class="mb-0">No items associated with this verification checkpoint.</p>
                  </div>
                </div>
              </div>
              
              <!-- Update Form Section -->
              <div class="form-section">
                <h6 class="section-title">Update Verification Status</h6>
                
                <!-- Status Selection -->
                <div class="mb-4">
                  <label for="verify_status" class="form-label">Status:</label>
                  <div class="status-options">
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="verify_status" id="status-pending" 
                        value="pending" v-model="localVerification.verify_status">
                      <label class="form-check-label" for="status-pending">
                        <span class="status-badge theme-badge-warning">Pending</span>
                      </label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="verify_status" id="status-complete" 
                        value="complete" v-model="localVerification.verify_status">
                      <label class="form-check-label" for="status-complete">
                        <span class="status-badge theme-badge-success">Complete</span>
                      </label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="verify_status" id="status-reject" 
                        value="reject" v-model="localVerification.verify_status">
                      <label class="form-check-label" for="status-reject">
                        <span class="status-badge theme-badge-danger">Reject</span>
                      </label>
                    </div>
                  </div>
                </div>
                
                <!-- Comment Section -->
                <div class="mb-3">
                  <label for="verify_comment" class="form-label">Verification Notes:</label>
                  <textarea 
                    class="form-control" 
                    id="verify_comment" 
                    v-model="localVerification.verify_comment" 
                    rows="4"
                    placeholder="Enter any notes or comments about this verification..."
                  ></textarea>
                </div>
              </div>
            </div>
            
            <!-- Submission Feedback -->
            <div v-if="submissionError" class="alert alert-danger mt-3">
                {{ submissionError }}
            </div>
            <div v-if="submissionSuccess" class="alert alert-success mt-3">
                <i class="fas fa-check-circle me-2"></i> Verification updated successfully!
            </div>
          </div>
          
          <!-- Modal Footer -->
          <div class="modal-footer theme-footer">
            <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
            <button 
              type="submit" 
              class="btn theme-btn-primary" 
              :disabled="isSubmitting || loadingDetails || !localVerification"
            >
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
              {{ isSubmitting ? 'Saving...' : 'Save Verification' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div v-if="show" class="modal-backdrop fade show"></div>
</template>

<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import verifyDeliveryService from '../../services/verifyDeliveryService';

const props = defineProps({
  show: {
    type: Boolean,
    required: true,
  },
  verifyId: {
    type: [Number, String],
    default: null,
  },
  deliveryId: { // New prop
    type: [Number, String],
    default: null,
  },
  locationId: { // New prop
    type: [Number, String],
    default: null,
  },
  token: { // New prop
    type: String,
    default: null,
  },
});

const emit = defineEmits(['close', 'updated']);

const localVerification = ref(null);
const isSubmitting = ref(false);
const submissionError = ref(null);
const submissionSuccess = ref(false);
const loadingDetails = ref(false);
const fetchError = ref(null);
const showItemsSection = ref(false);

// Watch for changes in the show prop and verifyId prop
watch(() => [props.show, props.verifyId], async ([newShow, newVerifyId]) => {
  if (newShow && newVerifyId) {
    // Modal is shown and verifyId is available, fetch details
    await fetchVerificationDetails(newVerifyId);
  } else {
    // Modal is hidden or verifyId is null, reset states
    localVerification.value = null;
    submissionError.value = null;
    submissionSuccess.value = false;
    fetchError.value = null;
  }
}, { immediate: true });

const fetchVerificationDetails = async (verifyID) => {
    loadingDetails.value = true;
    fetchError.value = null;
    try {
        // Pass deliveryId, locationId, and token props to the service function
        const response = await verifyDeliveryService.getVerificationById(
            verifyID,
            props.deliveryId,
            props.locationId,
            props.token
        );
        console.log('Response from fetchVerificationDetails:', JSON.stringify(response, null, 2));
        if (response.status === 'success') {
            localVerification.value = { ...response.data, associated_items: response.data.associated_items || [] };
        } else {
            fetchError.value = response.message || 'Failed to load verification details.';
        }
    } catch (error) {
        console.error('Error fetching verification details:', error);
        fetchError.value = error.message || 'An unexpected error occurred while fetching details.';
    } finally {
        loadingDetails.value = false;
    }
};

const toggleItemsSection = () => {
  showItemsSection.value = !showItemsSection.value;
};

const closeModal = () => {
  emit('close');
};

const handleSubmit = async () => {
  if (!localVerification.value || !localVerification.value.verifyID) return;

  isSubmitting.value = true;
  submissionError.value = null;
  submissionSuccess.value = false;

  try {
    const payload = {
      verify_status: localVerification.value.verify_status,
      verify_comment: localVerification.value.verify_comment,
    };
    // Assuming verifyDeliveryService has an updateVerification method
    const response = await verifyDeliveryService.updateVerification(localVerification.value.verifyID, payload);

    if (response.status === 'success') {
      submissionSuccess.value = true;
      emit('updated', response.data); // Emit event with updated data
      setTimeout(() => { // Optionally close modal after a delay
         closeModal();
      }, 1500);
    } else {
      submissionError.value = response.message || 'Failed to update verification.';
      if (response.errors) {
        submissionError.value += " Details: " + JSON.stringify(response.errors);
      }
    }
  } catch (error) {
    console.error('Error updating verification:', error);
    submissionError.value = error.response?.data?.message || error.message || 'An unexpected error occurred.';
  } finally {
    isSubmitting.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};

const getStatusBadgeClass = (status) => {
  if (status === 'complete') return 'theme-badge-success';
  if (status === 'pending') return 'theme-badge-warning';
  if (status === 'reject') return 'theme-badge-danger';
  return 'theme-badge-secondary';
};

const groupItemsByOrder = () => {
  if (!localVerification.value || !localVerification.value.associated_items) {
    return {};
  }
  
  // Group items by orderID
  return localVerification.value.associated_items.reduce((groups, item) => {
    const orderID = item.orderID || 'Unknown';
    if (!groups[orderID]) {
      groups[orderID] = [];
    }
    groups[orderID].push(item);
    return groups;
  }, {});
};
</script>

<style scoped>
/* Theme colors */
:root {
  --primary-color: #123524;
  --secondary-color: #EFE3C2;
  --accent-color: #3E7B27;
  --text-color: #333;
  --light-text: #666;
  --border-color: rgba(18, 53, 36, 0.2);
  --light-bg: rgba(239, 227, 194, 0.2);
}

/* Modal styling */
.modal.show {
  display: block;
  background-color: rgba(0,0,0,0.5);
}

.modal-dialog {
  z-index: 1050;
}

.modal-backdrop.show {
  opacity: 0.5;
  z-index: 1040;
}

/* Theme header */
.theme-header {
  background-color: #123524;
  color: #EFE3C2;
  border-bottom: none;
}

.theme-close {
  filter: invert(1) brightness(200%);
}

/* Theme footer */
.theme-footer {
  background-color: #f8f9fa;
  border-top: 1px solid #dee2e6;
}

/* Form styling */
.verification-form {
  max-width: 100%;
}

.section-title {
  color: #123524;
  font-weight: 600;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid rgba(18, 53, 36, 0.2);
}

.form-section {
  background-color: #fff;
  border-radius: 0.5rem;
  padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-bottom: 1rem;
}

.info-summary {
  background-color: rgba(239, 227, 194, 0.15);
  border-radius: 0.5rem;
  border-left: 4px solid #123524;
}

.info-item {
  display: flex;
  flex-direction: column;
}

.info-label {
  font-weight: 600;
  color: #123524;
  font-size: 0.875rem;
}

.info-value {
  font-size: 1rem;
}

/* Status options styling */
.status-options {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
}

.status-badge {
  display: inline-block;
  padding: 0.35rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.875rem;
}

.form-check-input:checked + .form-check-label .status-badge {
  box-shadow: 0 0 0 2px rgba(18, 53, 36, 0.5);
}

/* Items section */
.items-section {
  background-color: #f8f9fa;
  border-radius: 0.5rem;
  padding: 0.75rem;
  margin-top: 0.5rem;
}

.cursor-pointer {
  cursor: pointer;
}

/* Theme buttons */
.theme-btn-primary {
  background-color: #123524;
  border-color: #123524;
  color: #EFE3C2;
}

.theme-btn-primary:hover {
  background-color: #0a1f16;
  border-color: #0a1f16;
}

.theme-btn-primary:disabled {
  background-color: #123524;
  border-color: #123524;
  opacity: 0.65;
}

/* Badge styles */
.theme-badge-success {
  background-color: #3E7B27;
  color: white;
}

.theme-badge-warning {
  background-color: #e0a800;
  color: #212529;
}

.theme-badge-danger {
  background-color: #dc3545;
  color: white;
}

.theme-badge-secondary {
  background-color: #6c757d;
  color: white;
}

/* Enhanced Table Styling */
.table-header {
  background-color: rgba(18, 53, 36, 0.1);
}

.table-header th {
  font-weight: 600;
  color: #123524;
  border-bottom: 2px solid rgba(18, 53, 36, 0.2);
}

/* Order grouping styles */
.order-header {
  background-color: rgba(18, 53, 36, 0.05);
}

.order-header-cell {
  padding: 0.5rem 0.75rem !important;
  border-top: 2px solid rgba(18, 53, 36, 0.2);
  border-bottom: 1px solid rgba(18, 53, 36, 0.2);
}

.order-badge {
  display: inline-block;
  background-color: #123524;
  color: #EFE3C2;
  padding: 0.2rem 0.5rem;
  border-radius: 0.25rem;
  font-weight: 600;
  font-size: 0.875rem;
}

.order-item-count {
  font-size: 0.8rem;
  color: #666;
  font-weight: 500;
}

.item-row td {
  border-top: none;
}

.quantity-badge {
  display: inline-block;
  background-color: rgba(18, 53, 36, 0.1);
  color: #123524;
  padding: 0.2rem 0.6rem;
  border-radius: 50%;
  font-weight: 600;
  min-width: 1.8rem;
  text-align: center;
}

.table-hover tbody tr {
  transition: background-color 0.15s ease-in-out;
}

.table-hover tbody tr:hover {
  background-color: rgba(239, 227, 194, 0.3);
}

.table td, .table th {
  padding: 0.75rem;
  vertical-align: middle;
}

/* Ensure consistent badge sizes */
.badge {
  min-width: 5rem;
  text-align: center;
}

.theme-badge-warning {
  background-color: #e0a800;
  color: #212529;
}

.theme-badge-danger {
  background-color: #dc3545;
  color: white;
}

.theme-badge-secondary {
  background-color: #6c757d;
  color: white;
}

/* Spinner */
.theme-spinner {
  color: #3E7B27;
}

/* Form controls */
.form-control:focus, .form-check-input:focus {
  border-color: rgba(18, 53, 36, 0.5);
  box-shadow: 0 0 0 0.25rem rgba(18, 53, 36, 0.25);
}

.form-check-input:checked {
  background-color: #123524;
  border-color: #123524;
}

/* Table styling */
.table-hover tbody tr:hover {
  background-color: rgba(239, 227, 194, 0.2);
}

@media (max-width: 768px) {
  .modal-dialog {
    margin: 0.5rem;
  }
  
  .status-options {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .form-check-inline {
    display: block;
    margin-right: 0;
  }
}
</style>