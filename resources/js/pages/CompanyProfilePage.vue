<template>
  <div class="company-profile-page">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4">
      <h1 class="mb-3 mb-sm-0">{{ company ? company.company_name : 'Company Profile' }}</h1>
      <button class="btn theme-btn" @click="goBack">
        <i class="fas fa-arrow-left me-2"></i> Back
      </button>
    </div>

    <!-- Loading State - Updated with LoadingSpinner -->
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
          <div class="card h-100">
            <div class="card-header theme-header">
              <h5 class="mb-0">Company Information</h5>
            </div>
            <div class="card-body">
              <div class="row">
                <!-- Company Image -->
                <div class="col-md-3 text-center mb-3 mb-md-0">
                  <img 
                    :src="company.company_image || '/images/blank.jpg'" 
                    alt="Company Logo" 
                    class="img-fluid rounded company-image"
                  >
                </div>
                <!-- Company Details -->
                <div class="col-md-9">
                  <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Company Name:</div>
                    <div class="col-md-8">{{ company.company_name }}</div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Company Type:</div>
                    <div class="col-md-8">
                      <span :class="getTypeBadgeClass(company.company_type)">{{ company.company_type }}</span>
                    </div>
                  </div>
                  <div class="row mb-3" v-if="company.admin">
                    <div class="col-md-4 fw-bold">Admin Email:</div>
                    <div class="col-md-8">{{ company.admin.email }}</div>
                  </div>
                  <div class="row mb-3" v-if="company.admin">
                    <div class="col-md-4 fw-bold">Admin Phone:</div>
                    <div class="col-md-8">{{ company.admin.tel_number }}</div>
                  </div>
                  <div class="row mb-3" v-if="company.admin">
                    <div class="col-md-4 fw-bold">Status:</div>
                    <div class="col-md-8">
                      <span :class="getStatusBadgeClass(company.admin.status)">{{ company.admin.status }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
    
        <!-- Certifications Card -->
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-header theme-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Company Certifications</h5>
            </div>
            <div class="card-body">
              <!-- Updated with LoadingSpinner -->
              <LoadingSpinner v-if="certLoading" size="sm" message="Loading certifications..." />
              
              <div v-else-if="certError" class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ certError }}
              </div>
              <div v-else-if="certifications.length === 0" class="text-center py-3">
                <i class="fas fa-certificate text-muted mb-2" style="font-size: 2rem;"></i>
                <p class="text-muted">No certifications found</p>
              </div>
              <div v-else class="row">
                <div v-for="cert in certifications" :key="cert.certID" class="col-md-6 col-lg-6 mb-3">
                  <div class="cert-card border rounded p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <h6 class="mb-0">{{ cert.cert_type }}</h6>
                      <span class="badge bg-info">ID: {{ cert.certID }}</span>
                    </div>
                    <div class="cert-file-container">
                      <a 
                        v-if="cert.cert_file" 
                        :href="cert.cert_file" 
                        target="_blank" 
                        class="btn btn-sm btn-outline-primary w-100"
                      >
                        <i class="fas fa-file-pdf me-1"></i> View Certificate
                      </a>
                      <div v-else class="text-muted small">No file attached</div>
                    </div>
                    <div class="text-muted small mt-2">
                      Added: {{ formatDate(cert.created_at) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Location Filter and Company Type Specific Content in one row -->
      <div class="row mb-4">
        <!-- Location Filter - Now styled as a filter indicator -->
        <div v-if="company.locations && company.locations.length > 0 && company.company_type !== 'broiler'" class="col-lg-3 mb-4 mb-lg-0">
          <div class="card h-100 filter-card">
            <div class="card-header filter-header">
              <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i> Location Filter
              </h5>
            </div>
            <div class="card-body">
              <div class="location-selector">
                <div class="location-info mb-3">
                  <h6 class="mb-1">Current Location:</h6>
                  <div class="current-location-card p-2 border rounded">
                    <div class="fw-bold">{{ currentLocationName }}</div>
                    <div class="text-muted small">
                      Location ID: {{ company.locations[currentLocationIndex]?.locationID || 'N/A' }}
                    </div>
                  </div>
                </div>
                <div class="location-controls">
                  <label for="locationSelect" class="form-label">Select Location:</label>
                  <div class="d-flex">
                    <select 
                      id="locationSelect" 
                      class="form-select me-2" 
                      v-model="currentLocationIndex"
                      @change="fetchTypeSpecificData()"
                      :disabled="!company.locations || company.locations.length <= 1"
                    >
                      <option 
                        v-for="(location, index) in company.locations" 
                        :key="location.locationID" 
                        :value="index"
                      >
                        {{ location.company_address || 'Location ' + (index + 1) }}
                      </option>
                    </select>
                  </div>
                  <div class="btn-group w-100 mt-3">
                    <button 
                      class="btn btn-sm btn-outline-primary" 
                      @click="navigateLocation('prev')"
                      :disabled="currentLocationIndex === 0 || !company.locations || company.locations.length <= 1"
                      title="Previous Location"
                    >
                      <i class="fas fa-chevron-left"></i>
                    </button>
                    <button 
                      class="btn btn-sm btn-outline-primary" 
                      @click="navigateLocation('next')"
                      :disabled="currentLocationIndex >= company.locations.length - 1 || !company.locations || company.locations.length <= 1"
                      title="Next Location"
                    >
                      <i class="fas fa-chevron-right"></i>
                    </button>
                  </div>
                  <div class="text-muted small mt-2 text-center">
                    Location {{ currentLocationIndex + 1 }} of {{ company.locations.length }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Company Type Specific Content -->
        <div :class="{'col-lg-9': company.locations && company.locations.length > 0 && company.company_type !== 'broiler', 'col-lg-12': !company.locations || company.locations.length === 0 || company.company_type === 'broiler'}">
          <div v-if="company.company_type === 'broiler'" class="card h-100">
            <div class="card-header theme-header">
              <h5 class="mb-0">Ordered Items Log</h5>
            </div>
            <div class="card-body">
              <!-- Table view for medium and large screens -->
              <div class="d-none d-md-block">
                <ResponsiveTable
                  :columns="broilerColumns"
                  :items="typeSpecificData"
                  :loading="typeDataLoading"
                  item-key="cartID"
                  :has-actions="false"
                >
                  <!-- Custom column slots -->
                  <template #item_image="{ item }">
                    <img 
                      :src="item.item.item_image || '/images/blank.jpg'" 
                      alt="Item Image" 
                      class="img-thumbnail" 
                      style="width: 50px; height: 50px; object-fit: cover;"
                    >
                  </template>
                  <template #price="{ item }">
                    {{ formatCurrency(item.item.price) }}
                  </template>
                  <template #total="{ item }">
                    {{ formatCurrency(item.item.price * item.quantity) }}
                  </template>
                  <template #order_status="{ item }">
                    <span :class="getOrderStatusBadgeClass(item.order.order_status)">
                      {{ item.order.order_status }}
                    </span>
                  </template>
                  <template #order_date="{ item }">
                    {{ formatDate(item.order.order_timestamp) }}
                  </template>
                  <!-- Empty state slot -->
                  <template #empty>
                    <div class="text-center py-4">
                      <i class="fas fa-box text-muted mb-2" style="font-size: 2rem;"></i>
                      <p class="text-muted">No ordered items found</p>
                    </div>
                  </template>
                </ResponsiveTable>
              </div>
              
              <!-- Card grid view for small screens -->
              <div class="d-md-none">
                <div v-if="typeDataLoading" class="text-center py-3">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-2">Loading items...</p>
                </div>
                <div v-else-if="typeSpecificData.length === 0" class="text-center py-4">
                  <i class="fas fa-box text-muted mb-2" style="font-size: 2rem;"></i>
                  <p class="text-muted">No ordered items found</p>
                </div>
                <div v-else class="row">
                  <div v-for="item in typeSpecificData" :key="item.cartID" class="col-12 col-sm-6 mb-3">
                    <div class="broiler-item-card border rounded p-3 h-100">
                      <div class="d-flex mb-3">
                        <div class="me-3">
                          <img 
                            :src="item.item.item_image || '/images/blank.jpg'" 
                            alt="Item Image" 
                            class="img-thumbnail" 
                            style="width: 60px; height: 60px; object-fit: cover;"
                          >
                        </div>
                        <div>
                          <h6 class="mb-1">{{ item.item.poultry ? item.item.poultry.poultry_name : 'Unknown' }}</h6>
                          <span :class="getOrderStatusBadgeClass(item.order.order_status)">
                            {{ item.order.order_status }}
                          </span>
                        </div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-6 text-muted">Quantity:</div>
                        <div class="col-6 text-end fw-bold">{{ item.quantity }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-6 text-muted">Price:</div>
                        <div class="col-6 text-end">{{ formatCurrency(item.item.price) }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-6 text-muted">Total:</div>
                        <div class="col-6 text-end fw-bold">{{ formatCurrency(item.item.price * item.quantity) }}</div>
                      </div>
                      <div class="row">
                        <div class="col-6 text-muted">Order Date:</div>
                        <div class="col-6 text-end">{{ formatDate(item.order.order_timestamp) }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="company.company_type === 'sme'" class="card h-100">
            <div class="card-header theme-header">
              <h5 class="mb-0">Order History</h5>
            </div>
            <div class="card-body">
              <!-- Table view for large screens -->
              <div class="d-none d-lg-block">
                <ResponsiveTable
                  :columns="smeColumns"
                  :items="typeSpecificData"
                  :loading="typeDataLoading"
                  item-key="orderID"
                  :show-pagination="true"
                >
                  <!-- Custom column slots -->
                  <template #order_status="{ item }">
                    <span :class="getOrderStatusBadgeClass(item.order_status)">
                      {{ item.order_status }}
                    </span>
                  </template>
                  <template #order_date="{ item }">
                    {{ formatDate(item.order_timestamp) }}
                  </template>
                  <template #payment.payment_amount="{ item }">
                    {{ formatCurrency(item.payment ? item.payment.payment_amount : 0) }}
                  </template>
                  <template #actions="{ item }">
                    <button class="btn btn-primary btn-sm" @click.stop="viewOrderDetails(item.orderID)" title="View Order Details">
                      <i class="fas fa-eye"></i>
                    </button>
                  </template>
                  <!-- Empty state slot -->
                  <template #empty>
                    <div class="text-center py-4">
                      <i class="fas fa-shopping-cart text-muted mb-2" style="font-size: 2rem;"></i>
                      <p class="text-muted">No order history found</p>
                    </div>
                  </template>
                </ResponsiveTable>
              </div>
              
              <!-- Card grid view for small/medium screens -->
              <div class="d-lg-none">
                <LoadingSpinner 
                  v-if="typeDataLoading" 
                  size="lg" 
                  message="Loading orders..." 
                />
                <div v-else-if="typeSpecificData.length === 0" class="text-center py-4">
                  <i class="fas fa-shopping-cart text-muted mb-2" style="font-size: 2rem;"></i>
                  <p class="text-muted">No order history found</p>
                </div>
                <div v-else class="row">
                  <div v-for="item in typeSpecificData" :key="item.orderID" class="col-12 col-md-6 mb-3">
                    <div class="order-card border rounded p-3 h-100 position-relative">
                      <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                          <h6 class="mb-1">Order #{{ item.orderID }}</h6>
                          <span :class="getOrderStatusBadgeClass(item.order_status)">
                            {{ item.order_status }}
                          </span>
                        </div>
                        <button class="btn btn-primary btn-sm" @click.stop="viewOrderDetails(item.orderID)" title="View Order Details">
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                      <div class="row mb-2">
                        <div class="col-6 text-muted">Order Date:</div>
                        <div class="col-6 text-end">{{ formatDate(item.order_timestamp) }}</div>
                      </div>
                      <div class="row mb-2">
                        <div class="col-6 text-muted">Total Amount:</div>
                        <div class="col-6 text-end fw-bold">{{ formatCurrency(item.payment ? item.payment.payment_amount : 0) }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="company.company_type === 'slaughterhouse'" class="card h-100">
            <div class="card-header theme-header">
              <h5 class="mb-0">Location Tasks</h5>
            </div>
            <div class="card-body">
              <ResponsiveTable
                :columns="slaughterhouseColumns"
                :items="typeSpecificData"  
                :loading="typeDataLoading" 
                :has-actions="false"
              >
                <template #custom_status="{ item }">
                  <span :class="getTaskStatusBadgeClass(item.custom_status)">
                    {{ item.custom_status }}
                  </span>
                </template>
              </ResponsiveTable>
            </div>
          </div>

          <div v-else-if="company.company_type === 'logistic'" class="card h-100">
            <div class="card-header theme-header">
              <h5 class="mb-0">Deliveries</h5>
            </div>
            <div class="card-body">
              <ResponsiveTable
                :columns="logisticColumns"
                :items="typeSpecificData"
                :loading="typeDataLoading"
                item-key="deliveryID"
                :has-actions="false"
              >
                <!-- Custom column slots -->
                <template #scheduled_date="{ item }">
                  {{ formatDate(item.scheduled_date) }}
                </template>
             
                <template #status="{ item }">
                  <span :class="getDeliveryStatusBadgeClass(item)">
                    {{ getDeliveryStatus(item) }}
                  </span>
                </template>
                <!-- Empty state slot -->
                <template #empty>
                  <div class="text-center py-4">
                    <i class="fas fa-truck text-muted mb-2" style="font-size: 2rem;"></i>
                    <p class="text-muted">No deliveries found</p>
                  </div>
                </template>
              </ResponsiveTable>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header theme-header">
          <h5 class="modal-title" id="orderDetailsModalLabel">Order Details #{{ selectedOrder?.orderID }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body position-relative">
          <LoadingSpinner 
            v-if="orderDetailsLoading" 
            size="lg" 
            message="Loading order details..." 
            overlay
          />
          <div v-else-if="orderDetailsError" class="alert alert-danger">
            {{ orderDetailsError }}
          </div>
          <div v-else-if="!orderDetails || orderDetails.items?.length === 0" class="text-center py-4">
            <i class="fas fa-box text-muted mb-2" style="font-size: 2rem;"></i>
            <p class="text-muted">No items found for this order</p>
          </div>
          <div v-else>
            <!-- Order Summary -->
            <div class="card mb-4">
              <div class="card-header bg-light">
                <h6 class="mb-0">Order Summary</h6>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <p><strong>Order ID:</strong> {{ orderDetails.orderID }}</p>
                    <p><strong>Order Date:</strong> {{ formatDate(orderDetails.order_timestamp) }}</p>
                    <p><strong>Status:</strong> 
                      <span :class="getOrderStatusBadgeClass(orderDetails.order_status)">
                        {{ orderDetails.order_status }}
                      </span>
                    </p>
                  </div>
                  <div class="col-md-6">
                    <p><strong>Payment ID:</strong> {{ orderDetails.payment?.paymentID || 'N/A' }}</p>
                    <p><strong>Payment Amount:</strong> {{ formatCurrency(orderDetails.payment?.payment_amount || 0) }}</p>
                    <p><strong>Payment Status:</strong> {{ orderDetails.payment?.payment_status || 'N/A' }}</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Order Items -->
            <h6 class="mb-3">Order Items</h6>
            <div class="table-responsive">
              <table class="table table-bordered table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Image</th>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in orderDetails.items" :key="item.cartID">
                    <td class="text-center">
                      <img 
                        :src="item.item.item_image || '/images/blank.jpg'" 
                        alt="Item Image" 
                        class="img-thumbnail" 
                        style="width: 50px; height: 50px; object-fit: cover;"
                      >
                    </td>
                    <td>
                      <div>{{ item.item.poultry?.poultry_name || 'Unknown' }}</div>
                      <small class="text-muted">Item #{{ item.item.itemID }}</small>
                    </td>
                    <td>{{ item.quantity }}</td>
                    <td>{{ formatCurrency(item.price_at_purchase || item.item.price) }}</td>
                    <td>{{ formatCurrency((item.price_at_purchase || item.item.price) * item.quantity) }}</td>
                  </tr>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                    <td>{{ formatCurrency(orderDetails.payment?.payment_amount || 0) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ResponsiveTable from '../components/ui/ResponsiveTable.vue';
import LoadingSpinner from '../components/ui/LoadingSpinner.vue'; // Add this import
import companyService from '../services/companyService';
import modal from '../utils/modal';

export default {
  name: 'CompanyProfilePage',
  components: {
    ResponsiveTable,
    LoadingSpinner // Add this component
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
    // Function to view order details
    const viewOrderDetails = async (orderID) => {
      selectedOrder.value = { orderID };
      // orderDetailsLoading.value = true;
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
    // Function to navigate between locations
    const navigateLocation = async (direction) => {
      if (!company.value?.locations || company.value.locations.length <= 1) return;
      
      if (direction === 'next' && currentLocationIndex.value < company.value.locations.length - 1) {
        currentLocationIndex.value++;
      } else if (direction === 'prev' && currentLocationIndex.value > 0) {
        currentLocationIndex.value--;
      }
      
      // Reload data for the new location
      await fetchTypeSpecificData();
    };
    
    // Column definitions for different company types
    const broilerColumns = [
      { key: 'item_image', label: 'Image', sortable: false },
      { key: 'item.poultry.poultry_name', label: 'Poultry', sortable: true },
      { key: 'quantity', label: 'Quantity', sortable: true },
      { key: 'price', label: 'Price', sortable: true },
      { key: 'total', label: 'Total', sortable: true },
      { key: 'order_status', label: 'Status', sortable: true },
      { key: 'order_date', label: 'Order Date', sortable: true },
    ];
    
    const smeColumns = [
      { key: 'orderID', label: 'Order ID', sortable: true },
      { key: 'order_date', label: 'Order Date', sortable: true },
      { key: 'payment.payment_amount', label: 'Total Amount', sortable: true },
      { key: 'order_status', label: 'Status', sortable: true },
    ];
    
    const slaughterhouseColumns = [
      { key: 'custom_status', label: 'Status', sortable: true },
      { key: 'task.task_type', label: 'Task Type', sortable: true },

      { key: 'item_record', label: 'Item Details', sortable: false },
    ];
    
    const logisticColumns = [
      { key: 'deliveryID', label: 'Delivery ID', sortable: true },
      { key: 'scheduled_date', label: 'Scheduled Date', sortable: true },
      { key: 'status', label: 'Status', sortable: true },
    ];
    
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
        console.log('Certifications API response:', response);
        console.log('Certifications data structure:', JSON.stringify(response.data, null, 2));
        certifications.value = response.data || [];
        console.log('Certifications length:', certifications.value.length);
      } catch (err) {
        console.error('Error fetching certifications:', err);
        console.error('Error details:', err.response ? err.response.data : 'No response data');
        certError.value = 'Failed to load certification data';
      } finally {
        certLoading.value = false;
      }
    };
    
    // Fetch data specific to company type
    const fetchTypeSpecificData = async () => {
      if (!company.value) return;
      
      typeDataLoading.value = true;
      console.log(`Fetching data for company type: ${company.value.company_type}`);
      
      // Get current location ID if available and not a broiler company
      const locationId = (company.value.locations && company.value.locations.length > 0 && company.value.company_type !== 'broiler') ? 
        company.value.locations[currentLocationIndex.value].locationID : null;
      console.log('Current location ID:', locationId);
      
      try {
        switch (company.value.company_type) {
          case 'broiler':
            // For broiler, get ordered items without location filter
            console.log('Fetching broiler orders for company ID:', companyId);
            const broilerResponse = await companyService.getCompanyOrders(companyId, {});
            console.log('Broiler API response:', broilerResponse);
            typeSpecificData.value = broilerResponse.data || [];
            break;
            
          case 'sme':
            // For SME, get order history with location filter if available
            console.log('Fetching SME orders for company ID:', companyId);
            const smeResponse = await companyService.getCompanyOrders(companyId, 
              locationId ? { locationID: locationId } : {});
            console.log('SME API response:', smeResponse);
            typeSpecificData.value = smeResponse.data || [];
            break;
            
          case 'slaughterhouse':
            // For slaughterhouse, get tasks for their location
            console.log('Checking slaughterhouse locations:', company.value.locations);
            if (locationId) {
              console.log('Using slaughterhouse locationID:', locationId);
              const slaughterhouseResponse = await companyService.getSlaughterhouseTask(locationId);
              console.log('Slaughterhouse API response:', slaughterhouseResponse);
              typeSpecificData.value = slaughterhouseResponse.data || [];
            } else {
              console.log('No locations found for slaughterhouse company');
              typeSpecificData.value = [];
            }
            break;
            
          case 'logistic':
            // For logistics, get deliveries with location filter if available
            console.log('Fetching logistic deliveries for company ID:', companyId);
            const logisticResponse = await companyService.getCompanyDeliveries(companyId, 
              locationId ? { locationID: locationId } : {});
            console.log('Logistic API response:', logisticResponse);
            typeSpecificData.value = logisticResponse.data || [];
            break;
            
          default:
            console.log('Unknown company type:', company.value.company_type);
            typeSpecificData.value = [];
        }
        
        // Log final data that will be displayed in the table
        console.log('Final typeSpecificData:', typeSpecificData.value);
        
      } catch (err) {
        console.error('Error fetching type-specific data:', err);
        console.error('Error details:', err.response ? err.response.data : 'No response data');
        modal.danger('Error', 'Failed to load company-specific data. Please try again.');
        typeSpecificData.value = [];
      } finally {
        typeDataLoading.value = false;
      }
    };
    
    // Helper functions
    const formatCurrency = (value) => {
      if (!value) return 'RM 0.00';
      return new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' }).format(value);
    };
    
    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-MY');
    };
    
    const formatDateTime = (dateTimeString) => {
      if (!dateTimeString) return 'N/A';
      const date = new Date(dateTimeString);
      return date.toLocaleDateString('en-MY') + ' ' + date.toLocaleTimeString('en-MY');
    };
    
    const getTypeBadgeClass = (type) => {
      const classes = 'badge ';
      switch (type) {
        case 'broiler': return classes + 'bg-primary';
        case 'slaughterhouse': return classes + 'bg-danger';
        case 'sme': return classes + 'bg-success';
        case 'logistic': return classes + 'bg-warning';
        default: return classes + 'bg-secondary';
      }
    };
    
    const getStatusBadgeClass = (status) => {
      const classes = 'badge ';
      switch (status) {
        case 'active': return classes + 'bg-success';
        case 'inactive': return classes + 'bg-danger';
        default: return classes + 'bg-secondary';
      }
    };
    
    const getOrderStatusBadgeClass = (status) => {
      const classes = 'badge ';
      switch (status) {
        case 'paid': return classes + 'bg-success';
        case 'pending': return classes + 'bg-warning';
        case 'cancelled': return classes + 'bg-danger';
        case 'delivered': return classes + 'bg-info';
        default: return classes + 'bg-secondary';
      }
    };
    
    const getTaskStatusBadgeClass = (status) => {
      const classes = 'badge ';
      switch (status) {
        case 'not delivered': return classes + 'bg-secondary';
        case 'in progress': return classes + 'bg-warning';
        case 'delivered': return classes + 'bg-info';
        case 'task started': return classes + 'bg-primary';
        case 'task complete': return classes + 'bg-success';
        default: return classes + 'bg-secondary';
      }
    };
    
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
    
    const goBack = () => {
      router.back();
    };
    
    onMounted(() => {
      fetchCompanyData();
    });
    
    return {
      loading,
      error,
      company,
      typeSpecificData,
      typeDataLoading,
      currentLocationIndex,
      currentLocationName,
      navigateLocation,
      broilerColumns,
      smeColumns,
      slaughterhouseColumns,
      logisticColumns,
      formatCurrency,
      formatDate,
      formatDateTime,
      getTypeBadgeClass,
      getStatusBadgeClass,
      getOrderStatusBadgeClass,
      getTaskStatusBadgeClass,
      getDeliveryStatus,
      getDeliveryStatusBadgeClass,
      goBack,
      certifications,
      certLoading,
      certError,
      fetchCompanyCertifications,
      fetchTypeSpecificData,
      // Add these new properties
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

.company-image {
  max-height: 150px;
  object-fit: contain;
}

.badge {
  font-size: 0.8rem;
  padding: 0.35em 0.65em;
}
.order-card {
  transition: all 0.2s ease;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.order-card:hover {
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.modal-header.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

.btn-close-white {
  filter: invert(1) grayscale(100%) brightness(200%);
}
@media (max-width: 768px) {
  .company-profile-page h1 {
    font-size: 1.75rem;
  }
}

/* New filter card styling */
.filter-card {
  border-left: 4px solid var(--primary-color);
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  transition: all 0.3s ease;
}

.filter-card:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.filter-header {
  background-color: rgba(var(--primary-color-rgb), 0.1);
  color: var(--primary-color);
  border-bottom: 1px solid rgba(var(--primary-color-rgb), 0.2);
}

.location-selector {
  background-color: transparent;
  border-radius: 0.25rem;
  padding: 0;
  margin-bottom: 0;
}

.current-location-card {
  background-color: white;
  border-color: var(--primary-color) !important;
  border-left-width: 3px !important;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
}

.location-controls .btn-group .btn {
  border-color: var(--primary-color);
  color: var(--primary-color);
  flex: 1;
}

.location-controls .btn-group .btn:hover {
  background-color: var(--primary-color);
  color: white;
}

.location-controls .form-select {
  border-color: rgba(var(--primary-color-rgb), 0.3);
}

.location-controls .form-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.25rem rgba(var(--primary-color-rgb), 0.25);
}

.cert-card {
  transition: all 0.2s ease;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.cert-card:hover {
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.cert-file-container {
  margin-top: 10px;
}

/* Responsive adjustments */
@media (max-width: 992px) {
  .filter-card {
    margin-bottom: 1.5rem;
  }
}
</style>
