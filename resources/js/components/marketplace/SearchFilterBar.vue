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
    }
  },
  data() {
    return {
      searchText: this.initialSearchQuery,
      selectedType: this.initialPoultryType,
      sortOption: this.initialSortBy
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
        sortBy: this.sortOption
      });
    }
  }
}
</script>

<style scoped>
.search-filter-card {
  border-color: #e0e0e0;
}

.btn-primary {
  background-color: #123524;
  border-color: #123524;
}

.btn-primary:hover, .btn-primary:focus {
  background-color: #0d2a1c;
  border-color: #0d2a1c;
}

.form-control:focus, .form-select:focus {
  border-color: #123524;
  box-shadow: 0 0 0 0.25rem rgba(18, 53, 36, 0.25);
}

.form-select {
  color: #123524;
}
</style>