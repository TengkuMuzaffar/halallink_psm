<template>
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
              Location ID: {{ locations[currentLocationIndex]?.locationID || 'N/A' }}
            </div>
          </div>
        </div>
        <div class="location-controls">
          <label for="locationSelect" class="form-label">Select Location:</label>
          <div class="d-flex">
            <select 
              id="locationSelect" 
              class="form-select me-2" 
              v-model="localCurrentLocationIndex"
              @change="onLocationChange"
              :disabled="!locations || locations.length <= 1"
            >
              <option 
                v-for="(location, index) in locations" 
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
              @click="navigate('prev')"
              :disabled="currentLocationIndex === 0 || !locations || locations.length <= 1"
              title="Previous Location"
            >
              <i class="fas fa-chevron-left"></i>
            </button>
            <button 
              class="btn btn-sm btn-outline-primary" 
              @click="navigate('next')"
              :disabled="currentLocationIndex >= locations.length - 1 || !locations || locations.length <= 1"
              title="Next Location"
            >
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
          <div class="text-muted small mt-2 text-center">
            Location {{ currentLocationIndex + 1 }} of {{ locations.length }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LocationFilter',
  props: {
    locations: {
      type: Array,
      required: true
    },
    currentLocationIndex: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      localCurrentLocationIndex: this.currentLocationIndex
    };
  },
  watch: {
    currentLocationIndex(newVal) {
      this.localCurrentLocationIndex = newVal;
    }
  },
  computed: {
    currentLocationName() {
      if (!this.locations || this.locations.length === 0) return 'N/A';
      return this.locations[this.currentLocationIndex]?.company_address || 'Unknown';
    }
  },
  methods: {
    onLocationChange() {
      this.$emit('location-change', this.localCurrentLocationIndex);
    },
    navigate(direction) {
      if (!this.locations || this.locations.length <= 1) return;
      
      if (direction === 'next' && this.currentLocationIndex < this.locations.length - 1) {
        this.$emit('location-change', this.currentLocationIndex + 1);
      } else if (direction === 'prev' && this.currentLocationIndex > 0) {
        this.$emit('location-change', this.currentLocationIndex - 1);
      }
    }
  }
};
</script>

<style scoped>
/* Filter card styling */
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
</style>