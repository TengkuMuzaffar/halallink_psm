<template>
  <div class="verify-delivery-container">
    <div class="page-card">
      <div class="card-header">
        <h2>Verify Delivery</h2>
        <p v-if="deliveryID && locationID">
          Delivery ID: {{ deliveryID }} | Location ID: {{ locationID }}
        </p>
      </div>

      <div class="card-body">
        <!-- Loading State -->
        <LoadingSpinner v-if="loading" message="Fetching verification data..." overlay size="lg" />

        <!-- Error Message Display -->
        <div v-if="!loading && error" class="alert alert-danger">
          <i class="fas fa-exclamation-circle me-2"></i>
          {{ error }}
        </div>

        <!-- Verifications Table -->
        <div v-if="!loading && !error && verifications.length > 0">
          <ResponsiveTable
            :columns="tableColumns"
            :items="verifications"
            :loading="loading"
            item-key="verifyID"
            :has-actions="true"
            :show-filters="false"
            :show-pagination="true"
          >
            <template #actions="{ item }">
              <button class="btn btn-sm btn-primary" @click="openVerifiesModal(item)">
                <i class="fas fa-edit"></i> Edit
              </button>
            </template>
          </ResponsiveTable>
        </div>

        <div v-if="!loading && !error && verifications.length === 0 && deliveryID && locationID">
            <p>No verifications found for this delivery at this location.</p>
        </div>

      </div>
    </div>

    <!-- Verification Details Modal -->
    <VerifiesModalDetail
      :show="showDetailModal"
      :verify-id="selectedVerification ? selectedVerification.verifyID : null"
      :delivery-id="deliveryID" 
      :location-id="locationID"
      :token="token"
      @close="closeVerifiesModal"
      @updated="handleVerificationUpdated"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import verifyDeliveryService from '../services/verifyDeliveryService'; // Adjusted path
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';
import LoadingSpinner from '../components/ui/LoadingSpinner.vue';
import VerifiesModalDetail from '../components/verify/VerifiesModalDetail.vue'; // New modal

const route = useRoute();
const loading = ref(true);
const error = ref(null);
const deliveryID = ref(null);
const locationID = ref(null);
const token = ref(null); // Add token ref
const verifications = ref([]);

const selectedVerification = ref(null);
const showDetailModal = ref(false);

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleDateString(undefined, options);
};

const tableColumns = ref([
  { key: 'verifyID', label: 'Verification ID', sortable: true },
  { key: 'verify_status', label: 'Status', sortable: true },
  // Removed other columns as per request
]);

const fetchVerifications = async () => {
  // Check for all required parameters including token
  if (!deliveryID.value || !locationID.value || !token.value) {
    error.value = "Delivery ID, Location ID, and Token are required parameters.";
    loading.value = false;
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    // Pass the token to the service function
    const response = await verifyDeliveryService.getVerifications(deliveryID.value, locationID.value, token.value);
    if (response.status === 'error') {
        throw new Error(response.message);
    }
    // Assuming the response directly contains the array of verifications or is nested under a 'data' property
    verifications.value = response.data || response;
    console.log('Fetched verifications:', verifications.value);
  } catch (err) {
    console.error('Error fetching verifications:', err);
    error.value = err.message || 'Failed to fetch verification data. Please try again.';
    verifications.value = []; // Clear verifications on error
  } finally {
    loading.value = false;
  }
};

const openVerifiesModal = (item) => {
  selectedVerification.value = item; // Keep this for now if needed elsewhere, but the modal will use the ID
  showDetailModal.value = true;
};

const closeVerifiesModal = () => {
  showDetailModal.value = false;
  selectedVerification.value = null; // Clear selected item when modal closes
};

const handleVerificationUpdated = (updatedVerification) => {
  const index = verifications.value.findIndex(v => v.verifyID === updatedVerification.verifyID);
  if (index !== -1) {
    // Replace the old item with the updated one to maintain reactivity
    verifications.value[index] = updatedVerification;
  }
  // The modal will close itself after a successful update as per VerifiesModalDetail.vue
};

onMounted(() => {
  deliveryID.value = route.query.deliveryID;
  locationID.value = route.query.locationID;
  token.value = route.query.token; // Read token from route

  // Check for all required parameters
  if (!deliveryID.value || !locationID.value || !token.value) {
    error.value = "Missing Delivery ID, Location ID, or Token in the URL.";
    loading.value = false;
    // Optionally, redirect or show a more prominent error
    // For example: router.push({ name: 'SomeErrorPage' });
  } else {
    fetchVerifications();
  }
});

</script>

<style scoped>
.verify-delivery-container {
  min-height: 100vh;
  display: flex;
  align-items: flex-start; /* Align to top */
  justify-content: center;
  background-color: #f5f5f5;
  padding: 20px;
}

.page-card {
  width: 100%;
  max-width: 900px; /* Adjust as needed */
  background: #fff;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  margin-top: 20px; /* Add some margin at the top */
}

.card-header {
  background-color: #123524; /* Dark green */
  color: #EFE3C2; /* Cream */
  padding: 20px 30px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.card-header h2 {
  margin: 0;
  font-size: 24px;
}
.card-header p {
  margin: 5px 0 0;
  font-size: 14px;
  opacity: 0.8;
}

.card-body {
  padding: 30px;
}

.loading-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  color: #123524;
}

.alert {
  border-radius: 8px;
  padding: 15px;
}

.alert-danger {
  background-color: #f8d7da;
  border-color: #f5c6cb;
  color: #721c24;
}

/* Placeholder for table styling if you add one */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}
th, td {
  border: 1px solid #ddd;
  padding: 8px;
  text-align: left;
}
th {
  background-color: #f2f2f2;
}
</style>