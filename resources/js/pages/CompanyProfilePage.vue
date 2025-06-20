<template>
  <div class="company-profile-page">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4">
      <h1 class="mb-3 mb-sm-0">{{ company ? company.company_name : 'Company Profile' }}</h1>
      <button class="btn theme-btn" @click="goBack">
        <i class="fas fa-arrow-left me-2"></i> Back
      </button>
    </div>

    <!-- Loading State -->
    <LoadingSpinner v-if="loading" message="Loading company information..." />

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i>
      {{ error }}
    </div>

    <!-- Content when loaded -->
    <div v-else-if="company">
      <!-- Wrap company info and certifications in a row for large screens -->
      <div class="row mb-4">
        <!-- Company Info Card -->
        <div class="col-lg-6 mb-4 mb-lg-0">
          <CompanyInfoCard :company="company" />
        </div>
    
        <!-- Certifications Card -->
        <div class="col-lg-6">
          <CompanyCertificationsCard 
            :certifications="certifications" 
            :loading="certLoading" 
            :error="certError" 
          />
        </div>
      </div>

      <!-- Location Filter and Company Type Specific Content in one row -->
      <div class="row mb-4">
        <!-- Location Filter -->
        <div v-if="company.locations && company.locations.length > 0 && company.company_type !== 'broiler'" class="col-lg-3 mb-4 mb-lg-0">
          <LocationFilter 
            :locations="company.locations" 
            :current-location-index="currentLocationIndex" 
            @location-change="handleLocationChange" 
          />
        </div>

        <!-- Company Type Specific Content -->
        <div :class="{'col-lg-9': company.locations && company.locations.length > 0 && company.company_type !== 'broiler', 'col-lg-12': !company.locations || company.locations.length === 0 || company.company_type === 'broiler'}">
          <BroilerContent 
            v-if="company.company_type === 'broiler'" 
            :items="typeSpecificData" 
            :loading="typeDataLoading" 
          />
          
          <SmeContent 
            v-else-if="company.company_type === 'sme'" 
            :items="typeSpecificData" 
            :loading="typeDataLoading" 
            @view-order-details="viewOrderDetails" 
          />
          
          <SlaughterhouseContent 
            v-else-if="company.company_type === 'slaughterhouse'" 
            :items="typeSpecificData" 
            :loading="typeDataLoading" 
          />
          
          <LogisticContent 
            v-else-if="company.company_type === 'logistic'" 
            :items="typeSpecificData" 
            :loading="typeDataLoading" 
          />
        </div>
      </div>
    </div>
    
    <!-- Order Details Modal -->
    <OrderDetailsModal 
      :selected-order="selectedOrder" 
      :order-details="orderDetails" 
      :loading="orderDetailsLoading" 
      :error="orderDetailsError" 
    />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import LoadingSpinner from '../components/ui/LoadingSpinner.vue';
import companyService from '../services/companyService';
import modal from '../utils/modal';

// Import modular components
import CompanyInfoCard from '../components/company/CompanyInfoCard.vue';
import CompanyCertificationsCard from '../components/company/CompanyCertificationsCard.vue';
import LocationFilter from '../components/company/LocationFilter.vue';
import OrderDetailsModal from '../components/company/OrderDetailsModal.vue';
import BroilerContent from '../components/company/company-type/BroilerContent.vue';
import SmeContent from '../components/company/company-type/SmeContent.vue';
import SlaughterhouseContent from '../components/company/company-type/SlaughterhouseContent.vue';
import LogisticContent from '../components/company/company-type/LogisticContent.vue';

export default {
  name: 'CompanyProfilePage',
  components: {
    LoadingSpinner,
    CompanyInfoCard,
    CompanyCertificationsCard,
    LocationFilter,
    OrderDetailsModal,
    BroilerContent,
    SmeContent,
    SlaughterhouseContent,
    LogisticContent
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    const companyId = route.params.id;
    
    const loading = ref(true);
    const error = ref(null);
    const company = ref(null);
    const typeSpecificData = ref([]);
    const typeDataLoading = ref(false);
    const currentLocationIndex = ref(0);
    
    // Add these new refs for certifications
    const certifications = ref([]);
    const certLoading = ref(false);
    const certError = ref(null);

    const orderDetailsModal = ref(null);
    const selectedOrder = ref(null);
    const orderDetails = ref(null);
    const orderDetailsLoading = ref(false);
    const orderDetailsError = ref(null);
    
    // Computed property for current location name
    const currentLocationName = computed(() => {
      if (!company.value?.locations || company.value.locations.length === 0) return 'N/A';
      return company.value.locations[currentLocationIndex.value]?.company_address || 'Unknown';
    });
    
    // Function to handle location change from LocationFilter component
    const handleLocationChange = async (newIndex) => {
      currentLocationIndex.value = newIndex;
      await fetchTypeSpecificData();
    };
    
    // Function to view order details
    const viewOrderDetails = async (orderID) => {
      selectedOrder.value = { orderID };
      orderDetailsLoading.value = true;
      orderDetailsError.value = null;
      orderDetails.value = null;
      
      // Initialize modal if not already done
      if (!orderDetailsModal.value) {
        orderDetailsModal.value = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
      }
      
      // Show the modal
      orderDetailsModal.value.show();
      
      try {
        const response = await companyService.getOrderDetails(orderID, { companyId });
        orderDetails.value = response.data;
        console.log(orderDetails.value);
      } catch (err) {
        console.error('Error fetching order details:', err);
        orderDetailsError.value = 'Failed to load order details. Please try again.';
      } finally {
        orderDetailsLoading.value = false;
      }
    };
    
    // Fetch company data
    const fetchCompanyData = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await companyService.getCompany(companyId);
        company.value = response;
        
        // After getting company data, fetch certifications and type-specific data
        await Promise.all([
          fetchCompanyCertifications(),
          fetchTypeSpecificData()
        ]);
      } catch (err) {
        console.error('Error fetching company:', err);
        error.value = err.message || 'Failed to load company data. Please try again.';
      } finally {
        loading.value = false;
      }
    };
    
    // Add this new function to fetch certifications
    const fetchCompanyCertifications = async () => {
      certLoading.value = true;
      certError.value = null;
      
      try {
        console.log('Fetching certifications for company ID:', companyId);
        const response = await companyService.getCompanyCertifications(companyId);
        certifications.value = response.data || [];
      } catch (err) {
        console.error('Error fetching certifications:', err);
        certError.value = 'Failed to load certification data';
      } finally {
        certLoading.value = false;
      }
    };
    
    // Fetch data specific to company type
    const fetchTypeSpecificData = async () => {
      if (!company.value) return;
      
      typeDataLoading.value = true;
      
      // Get current location ID if available and not a broiler company
      const locationId = (company.value.locations && company.value.locations.length > 0 && company.value.company_type !== 'broiler') ? 
        company.value.locations[currentLocationIndex.value].locationID : null;
      
      try {
        switch (company.value.company_type) {
          case 'broiler':
            // For broiler, get ordered items without location filter
            const broilerResponse = await companyService.getCompanyOrders(companyId, {});
            typeSpecificData.value = broilerResponse.data || [];
            break;
            
          case 'sme':
            // For SME, get order history with location filter if available
            const smeResponse = await companyService.getCompanyOrders(companyId, 
              locationId ? { locationID: locationId } : {});
            typeSpecificData.value = smeResponse.data || [];
            break;
            
          case 'slaughterhouse':
            // For slaughterhouse, get tasks for their location
            if (locationId) {
              const slaughterhouseResponse = await companyService.getSlaughterhouseTask(locationId);
              typeSpecificData.value = slaughterhouseResponse.data || [];
              console.log("Slaughterhouse Info:"+ JSON.stringify(typeSpecificData.value, null, 2));
            } else {
              typeSpecificData.value = [];
            }
            break;
            
          case 'logistic':
            // For logistics, get deliveries with location filter if available
            const logisticResponse = await companyService.getCompanyDeliveries(companyId, 
              locationId ? { locationID: locationId } : {});
            typeSpecificData.value = logisticResponse.data || [];
            break;
            
          default:
            typeSpecificData.value = [];
        }
      } catch (err) {
        console.error('Error fetching type-specific data:', err);
        modal.danger('Error', 'Failed to load company-specific data. Please try again.');
        typeSpecificData.value = [];
      } finally {
        typeDataLoading.value = false;
      }
    };
    
    const goBack = () => {
      router.back();
    };
    
    onMounted(() => {
      fetchCompanyData();
    });
    
    // Add these helper methods for logistic display
    const getDeliveryStatus = (delivery) => {
      if (delivery.end_timestamp) return 'Completed';
      if (delivery.start_timestamp) return 'In Progress';
      return 'Scheduled';
    };
    
    const getDeliveryStatusBadgeClass = (delivery) => {
      const classes = 'badge ';
      const status = getDeliveryStatus(delivery);
      
      switch (status) {
        case 'Completed': return classes + 'bg-success';
        case 'In Progress': return classes + 'bg-primary';
        case 'Scheduled': return classes + 'bg-warning';
        default: return classes + 'bg-secondary';
      }
    };
    
    return {
      loading,
      error,
      company,
      typeSpecificData,
      typeDataLoading,
      currentLocationIndex,
      currentLocationName,
      handleLocationChange,
      goBack,
      certifications,
      certLoading,
      certError,
      fetchCompanyCertifications,
      fetchTypeSpecificData,
      viewOrderDetails,
      selectedOrder,
      orderDetails,
      orderDetailsLoading,
      orderDetailsError,
    };
  }
};
</script>

<style scoped>
.company-profile-page h1 {
  color: #123524;
}

.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
}

.theme-btn {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border: none;
}

.theme-btn:hover {
  background-color: #0a1f15;
  color: var(--secondary-color);
}

@media (max-width: 768px) {
  .company-profile-page h1 {
    font-size: 1.75rem;
  }
}
</style>