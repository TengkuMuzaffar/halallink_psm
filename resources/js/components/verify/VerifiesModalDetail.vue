<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog" @click.self="closeModal">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content">
        <form @submit.prevent="handleSubmit">
          <div class="modal-header">
            <h5 class="modal-title">Edit Verification (ID: {{ localVerification?.verifyID }})</h5>
            <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="loadingDetails" class="text-center p-4">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading verification details...
            </div>
            <div v-else-if="fetchError" class="alert alert-danger mt-3">
                {{ fetchError }}
            </div>
            <div v-else-if="localVerification">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <strong>Verification ID:</strong>
                    <p>{{ localVerification.verifyID }}</p>
                  </div>
                  <div class="col-md-6 mb-3">
                    <strong>Delivery ID:</strong>
                    <p>{{ localVerification.deliveryID }}</p>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <strong>Checkpoint ID:</strong>
                    <p>{{ localVerification.checkID }}</p>
                  </div>
                </div>
                <hr/>

                <!-- Display Associated Items -->
                <div v-if="localVerification.associated_items && localVerification.associated_items.length > 0" class="mb-3">
                    <strong>Associated Items:</strong>
                    <ul>
                        <li v-for="item in localVerification.associated_items" :key="item.itemID">
                            Item ID: {{ item.itemID }} - {{ item.item_name }} (Qty: {{ item.quantity }})
                        </li>
                    </ul>
                </div>
                 <div v-else class="mb-3">
                    <strong>Associated Items:</strong>
                    <p>No items associated with this verification checkpoint.</p>
                </div>
                <hr/>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="verify_status" class="form-label"><strong>Status:</strong></label>
                    <select class="form-select" id="verify_status" v-model="localVerification.verify_status" required>
                      <option value="pending">Pending</option>
                      <option value="verified">Verified</option>
                      <option value="rejected">Rejected</option>
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-12 mb-3">
                    <label for="verify_comment" class="form-label"><strong>Comment:</strong></label>
                    <textarea class="form-control" id="verify_comment" v-model="localVerification.verify_comment" rows="3"></textarea>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <strong>Created At:</strong>
                    <p>{{ formatDate(localVerification.created_at) }}</p>
                  </div>
                  <div class="col-md-6 mb-3">
                    <strong>Updated At:</strong>
                    <p>{{ formatDate(localVerification.updated_at) }}</p>
                  </div>
                </div>
            </div>
             <div v-if="submissionError" class="alert alert-danger mt-3">
                {{ submissionError }}
            </div>
            <div v-if="submissionSuccess" class="alert alert-success mt-3">
                Verification updated successfully!
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal">Close</button>
            <button type="submit" class="btn btn-primary" :disabled="isSubmitting || loadingDetails || !localVerification">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
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

// Watch for changes in the show prop and verifyId prop
watch(() => [props.show, props.verifyId], async ([newShow, newVerifyId]) => {
  if (newShow && newVerifyId) {
    // Modal is shown and verifyId is available, fetch details
    // Note: The fetchVerificationDetails function currently only uses verifyID.
    // deliveryId, locationId, and token are now available as props if needed for future logic.
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
    // The route is PUT /api/verifications/{verifyID}
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

// No need for statusBadgeClass if status is now an input field, but keeping if you display it elsewhere
// const statusBadgeClass = (status) => {
//   if (status === 'verified') return 'badge bg-success';
//   if (status === 'pending') return 'badge bg-warning text-dark';
//   if (status === 'rejected') return 'badge bg-danger';
//   return 'badge bg-secondary';
// };
</script>

<style scoped>
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
.modal-body p {
  margin-bottom: 0.5rem;
}
.form-label {
    font-weight: 500;
}
/* Add any additional styling for form elements if needed */
</style>