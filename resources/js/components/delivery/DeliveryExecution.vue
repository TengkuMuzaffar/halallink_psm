<template>
  <div>
    <div class="card mb-4">
      <div class="card-header bg-light">
        <h5 class="mb-0">Execute Deliveries</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          This section allows drivers to manage and execute assigned deliveries.
        </div>
        
        <!-- Driver selection -->
        <div class="mb-4">
          <label for="driverFilter" class="form-label">Select Driver:</label>
          <select id="driverFilter" class="form-select" v-model="selectedDriverID">
            <option value="">-- Select Driver --</option>
            <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
              {{ driver.name }}
            </option>
          </select>
        </div>
        
        <!-- Assigned deliveries will be shown here -->
        <div v-if="loading" class="text-center my-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        
        <div v-else-if="!selectedDriverID" class="text-center py-5">
          <i class="fas fa-truck-loading fa-3x mb-3 text-muted"></i>
          <p class="lead">Please select a driver to view assigned deliveries</p>
        </div>
        
        <div v-else-if="assignedDeliveries.length === 0" class="text-center py-5">
          <i class="fas fa-clipboard-check fa-3x mb-3 text-muted"></i>
          <p class="lead">No deliveries assigned to this driver</p>
        </div>
        
        <div v-else>
          <!-- This will be implemented with actual data -->
          <p class="text-center">Delivery execution interface will be implemented here</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import deliveryService from '../../services/deliveryService';

export default {
  name: 'DeliveryExecution',
  data() {
    return {
      loading: false,
      selectedDriverID: '',
      drivers: [],
      assignedDeliveries: []
    };
  },
  mounted() {
    this.fetchDrivers();
  },
  watch: {
    selectedDriverID(newVal) {
      if (newVal) {
        this.fetchAssignedDeliveries(newVal);
      } else {
        this.assignedDeliveries = [];
      }
    }
  },
  methods: {
    async fetchDrivers() {
      try {
        const response = await deliveryService.getDrivers();
        if (response.success) {
          this.drivers = response.data;
        }
      } catch (error) {
        console.error('Error fetching drivers:', error);
      }
    },
    async fetchAssignedDeliveries(driverID) {
      this.loading = true;
      try {
        // This will be implemented with actual API
        // const response = await deliveryService.getAssignedDeliveries(driverID);
        // if (response.success) {
        //   this.assignedDeliveries = response.data;
        // }
        
        // Placeholder for now
        this.assignedDeliveries = [];
        
      } catch (error) {
        console.error('Error fetching assigned deliveries:', error);
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>