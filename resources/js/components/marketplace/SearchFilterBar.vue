<template>
  <div class="card mb-4 search-filter-card">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="input-group">
            <input 
              type="text" 
              class="form-control" 
              placeholder="Search products..." 
              v-model="searchText"
              @input="onSearchInput"
            >
            <button class="btn btn-primary" type="button" @click.prevent="onSearchClick">
              <i class="fas fa-search"></i> Search
            </button>
          </div>
        </div>
        <div class="col-md-6">
          <div class="d-flex gap-2 justify-content-md-end">
            <select class="form-select" 
                    v-model="selectedType" 
                    @change="onFilterChange">
              <option value="">All Poultry Types</option>
              <option v-for="type in poultryTypes" 
                      :key="type.poultryID" 
                      :value="type.poultryID">
                {{ type.poultry_name }}
              </option>
            </select>
            <select class="form-select" 
                    v-model="sortOption" 
                    @change="onFilterChange">
              <option value="newest">Newest First</option>
              <option value="price_low">Price: Low to High</option>
              <option value="price_high">Price: High to Low</option>
            </select>
          </div>
        </div>
      </div>
      
      <!-- Improved Price Range Filter -->
      <div class="row mt-3">
        <div class="col-12">
          <div class="price-filter-container">
            <div class="price-filter-header">
              <span class="price-filter-label">Price Range</span>
              <button 
                class="btn btn-sm btn-link text-secondary p-0 ms-2"
                @click="resetPriceFilter"
                title="Reset price filter"
                v-if="minPrice || maxPrice"
              >
                <i class="fas fa-undo-alt"></i> Reset
              </button>
            </div>
            
            <div class="price-range-inputs">
              <div class="price-input-group">
                <div class="price-input-wrapper">
                  <span class="price-currency">$</span>
                  <input 
                    type="number" 
                    class="form-control price-input" 
                    placeholder="Min" 
                    v-model.number="minPrice"
                    min="0"
                    @change="onFilterChange"
                  >
                </div>
              </div>
              
              <div class="price-range-separator">
                <span>-</span>
              </div>
              
              <div class="price-input-group">
                <div class="price-input-wrapper">
                  <span class="price-currency">$</span>
                  <input 
                    type="number" 
                    class="form-control price-input" 
                    placeholder="Max" 
                    v-model.number="maxPrice"
                    min="0"
                    @change="onFilterChange"
                  >
                </div>
              </div>
              
              <button 
                class="btn btn-sm btn-outline-primary apply-price-btn"
                @click="onFilterChange"
              >
                Apply
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SearchFilterBar',
  props: {
    poultryTypes: {
      type: Array,
      default: () => []
    },
    initialSearchQuery: {
      type: String,
      default: ''
    },
    initialPoultryType: {
      type: [String, Number],
      default: ''
    },
    initialSortBy: {
      type: String,
      default: 'newest'
    },
    initialMinPrice: {
      type: [Number, String],
      default: ''
    },
    initialMaxPrice: {
      type: [Number, String],
      default: ''
    }
  },
  data() {
    return {
      searchText: this.initialSearchQuery,
      selectedType: this.initialPoultryType,
      sortOption: this.initialSortBy,
      minPrice: this.initialMinPrice,
      maxPrice: this.initialMaxPrice
    }
  },
  methods: {
    onSearchInput() {
      this.$emit('search-input', this.searchText);
    },
    onSearchClick() {
      this.$emit('search-click', this.searchText);
    },
    onFilterChange() {
      this.$emit('filter-change', {
        poultryType: this.selectedType,
        sortBy: this.sortOption,
        minPrice: this.minPrice,
        maxPrice: this.maxPrice
      });
    },
    resetPriceFilter() {
      this.minPrice = '';
      this.maxPrice = '';
      this.onFilterChange();
    }
  }
}
</script>

<style scoped>
.search-filter-card {
  border-color: #e0e0e0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.btn-primary {
  background-color: #123524;
  border-color: #123524;
}

.btn-primary:hover, .btn-primary:focus {
  background-color: #0d2a1c;
  border-color: #0d2a1c;
}

.btn-outline-primary {
  color: #123524;
  border-color: #123524;
}

.btn-outline-primary:hover, .btn-outline-primary:focus {
  background-color: #123524;
  color: white;
}

.form-control:focus, .form-select:focus {
  border-color: #123524;
  box-shadow: 0 0 0 0.25rem rgba(18, 53, 36, 0.25);
}

.form-select {
  color: #123524;
}

/* Price filter styles */
.price-filter-container {
  background-color: #f8f9fa;
  border-radius: 0.375rem;
  padding: 0.75rem 1rem;
  border: 1px solid #e9ecef;
}

.price-filter-header {
  display: flex;
  align-items: center;
  margin-bottom: 0.75rem;
}

.price-filter-label {
  font-weight: 600;
  color: #495057;
  font-size: 0.9rem;
}

.price-range-inputs {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.price-input-group {
  flex: 1;
  min-width: 100px;
}

.price-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.price-currency {
  position: absolute;
  left: 0.75rem;
  color: #6c757d;
  z-index: 4;
}

.price-input {
  padding-left: 1.5rem;
  height: 38px;
  border-radius: 0.25rem;
}

.price-range-separator {
  color: #6c757d;
  padding: 0 0.25rem;
  font-weight: 500;
}

.apply-price-btn {
  height: 38px;
  white-space: nowrap;
}

@media (max-width: 576px) {
  .price-input-group {
    flex: 1 0 calc(50% - 1.5rem);
  }
  
  .price-range-separator {
    flex: 0 0 1rem;
    text-align: center;
  }
  
  .apply-price-btn {
    flex: 1 0 100%;
    margin-top: 0.5rem;
  }
}
</style>