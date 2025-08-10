<template>
  <div class="company-search-selection">
    <div class="mb-3">
      <label for="companySearch" class="form-label">{{ searchLabel }}</label>
      <input 
        type="text" 
        class="form-control" 
        id="companySearch" 
        v-model="searchQuery"
        :placeholder="searchPlaceholder"
      >
    </div>
    
    <div class="mt-3 mb-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">{{ selectedLabel }}</h6>
        <span class="badge bg-primary">{{ selectedCompanies.length }}</span>
      </div>
      <div class="list-group selected-companies-list">
        <div v-if="selectedCompanies.length === 0" class="text-muted small">
          No companies selected
        </div>
        <div 
          v-for="company in selectedCompanies" 
          :key="company.companyID"
          class="list-group-item d-flex justify-content-between align-items-center"
        >
          <span>{{ company.company_name }}</span>
          <button 
            type="button" 
            class="btn btn-sm btn-outline-danger"
            @click="removeCompany(company)"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    </div>
    
    <div class="mt-3">
      <h6>{{ resultsLabel }}</h6>
      <div class="list-group search-results">
        <div v-if="loading" class="text-center">
          <div class="spinner-border spinner-border-sm" role="status"></div> Searching...
        </div>
        <div v-else-if="searchResults.length === 0 && searchQuery.length < 2" class="text-muted small">
          Enter at least 2 characters to search
        </div>
        <div v-else-if="searchResults.length === 0" class="text-muted small">
          No companies found
        </div>
        <div 
          v-for="company in searchResults" 
          :key="company.companyID"
          class="list-group-item d-flex justify-content-between align-items-center"
        >
          <span>{{ company.company_name }}</span>
          <button 
            type="button" 
            class="btn btn-sm" 
            :class="isCompanySelected(company) ? 'btn-success disabled' : 'btn-outline-primary'"
            @click="addCompany(company)"
            :disabled="isCompanySelected(company)"
          >
            {{ isCompanySelected(company) ? 'Added' : 'Add' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch, computed } from 'vue';
import reportService from '../../../services/reportService';

export default {
  name: 'CompanySearchAndSelection',
  props: {
    searchLabel: {
      type: String,
      default: 'Search SME Companies'
    },
    searchPlaceholder: {
      type: String,
      default: 'Search by name...'
    },
    selectedLabel: {
      type: String,
      default: 'Selected Companies'
    },
    resultsLabel: {
      type: String,
      default: 'Search Results'
    },
    initialSelectedCompanies: {
      type: Array,
      default: () => []
    }
  },
  emits: ['update:selectedCompanies'],
  setup(props, { emit }) {
    const searchQuery = ref('');
    const searchResults = ref([]);
    const selectedCompanies = ref([...props.initialSelectedCompanies]);
    const loading = ref(false);
    
    // Watch for changes in selected companies and emit the updated list
    watch(selectedCompanies, (newValue) => {
      emit('update:selectedCompanies', newValue);
    }, { deep: true });
    
    // Check if a company is already selected
    const isCompanySelected = (company) => {
      return selectedCompanies.value.some(c => c.companyID === company.companyID);
    };
    
    // Add a company to the selected list
    const addCompany = (company) => {
      if (!isCompanySelected(company)) {
        selectedCompanies.value.push(company);
      }
    };
    
    // Remove a company from the selected list
    const removeCompany = (company) => {
      selectedCompanies.value = selectedCompanies.value.filter(
        c => c.companyID !== company.companyID
      );
    };
    
    // Search companies with debounce
    let searchTimeout;
    watch(searchQuery, (newQuery) => {
      clearTimeout(searchTimeout);
      
      if (newQuery.length < 2) {
        searchResults.value = [];
        return;
      }
      
      loading.value = true;
      searchTimeout = setTimeout(async () => {
        try {
          const response = await reportService.getSmeCompanies(newQuery);
          searchResults.value = response.data;
        } catch (error) {
          console.error('Error searching companies:', error);
          searchResults.value = [];
        } finally {
          loading.value = false;
        }
      }, 500);
    });
    
    // Reset the component
    const reset = () => {
      searchQuery.value = '';
      searchResults.value = [];
      selectedCompanies.value = [];
    };
    
    return {
      searchQuery,
      searchResults,
      selectedCompanies,
      loading,
      isCompanySelected,
      addCompany,
      removeCompany,
      reset
    };
  }
};
</script>

<style scoped>
.selected-companies-list {
  max-height: 200px;
  overflow-y: auto;
}

.search-results {
  max-height: 200px;
  overflow-y: auto;
}
</style>