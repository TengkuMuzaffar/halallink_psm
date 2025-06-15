<template>
  <div class="responsive-table">
    <!-- Table filters -->
    <div class="table-filters mb-3 d-flex flex-wrap justify-content-between align-items-center" v-if="showFilters">
      <!-- Search input -->
      <div class="search-wrapper mb-2 mb-md-0">
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-search"></i></span>
          <input 
            type="text" 
            class="form-control" 
            :placeholder="searchPlaceholder" 
            v-model="searchQuery"
            @input="onSearchInput"
          >
        </div>
      </div>
      
      <!-- Min-Max filters -->
      <div class="min-max-filters d-flex" v-if="showMinMaxFilters">
        <div class="input-group me-2">
          <span class="input-group-text">{{ minLabel }}</span>
          <input 
            type="number" 
            class="form-control" 
            :placeholder="minPlaceholder" 
            v-model.number="minValue"
            @change="onMinMaxChange"
          >
        </div>
        <div class="input-group">
          <span class="input-group-text">{{ maxLabel }}</span>
          <input 
            type="number" 
            class="form-control" 
            :placeholder="maxPlaceholder" 
            v-model.number="maxValue"
            @change="onMinMaxChange"
          >
        </div>
      </div>
      
      <!-- Custom filters slot -->
      <div class="custom-filters">
        <slot name="filters"></slot>
      </div>
    </div>
    
    <div class="table-responsive position-relative">
      <!-- Custom LoadingSpinner component -->
      <LoadingSpinner 
        v-if="loading"  
        size="md" 
        message="Loading data..." 
      />
      
      <table class="table table-hover">
        <thead>
          <tr>
            <th 
              v-for="column in columns" 
              :key="column.key" 
              :class="[column.class, column.sortable ? 'sortable' : '']"
              @click="column.sortable ? sortBy(column.key) : null"
            >
              {{ column.label }}
              <span v-if="column.sortable" class="sort-icon">
                <i 
                  class="fas" 
                  :class="getSortIconClass(column.key)"
                ></i>
              </span>
            </th>
            <th v-if="hasActions" class="actions-column">Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Removed the inline loading row and replaced with LoadingSpinner above -->
          <tr v-if="paginatedItems.length === 0 && !loading">
            <td :colspan="hasActions ? columns.length + 1 : columns.length" class="text-center py-4">
              <slot name="empty">
                <div class="text-muted">No data available</div>
              </slot>
            </td>
          </tr>
          <tr v-for="(item, index) in paginatedItems" :key="getItemKey(item, index)" v-else>
            <td v-for="column in columns" :key="column.key" :class="column.class">
              <slot :name="column.key" :item="item" :index="index">
                {{ getItemValue(item, column.key) }}
              </slot>
            </td>
            <td v-if="hasActions" class="actions-column">
              <slot name="actions" :item="item" :index="index"></slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-3" v-if="showPagination && filteredItems.length > 0">
      <div>
        <span class="text-muted">Showing {{ paginationStart + 1 }} to {{ Math.min(paginationStart + perPage, filteredItems.length) }} of {{ filteredItems.length }} entries</span>
      </div>
      <nav aria-label="Table pagination">
        <ul class="pagination mb-0">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">
              <i class="fas fa-chevron-left"></i>
            </a>
          </li>
          <li 
            v-for="page in pageNumbers" 
            :key="page" 
            class="page-item"
            :class="{ active: currentPage === page }"
          >
            <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
          </li>
          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
            <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">
              <i class="fas fa-chevron-right"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue';
import LoadingSpinner from './LoadingSpinner.vue';

export default {
  name: 'ResponsiveTable',
  components: {
    LoadingSpinner
  },
  props: {
    columns: {
      type: Array,
      required: true
      // Each column should have: { key, label, sortable, class }
    },
    items: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    hasActions: {
      type: Boolean,
      default: true
    },
    itemKey: {
      type: String,
      default: 'id'
    },
    showFilters: {
      type: Boolean,
      default: true
    },
    showPagination: {
      type: Boolean,
      default: true
    },
    perPage: {
      type: Number,
      default: 10
    },
    // Add new prop for server-side sorting
    serverSide: {
      type: Boolean,
      default: false
    },
    // New props for min-max filtering
    showMinMaxFilters: {
      type: Boolean,
      default: false
    },
    minLabel: {
      type: String,
      default: 'Min'
    },
    maxLabel: {
      type: String,
      default: 'Max'
    },
    minPlaceholder: {
      type: String,
      default: 'Min'
    },
    maxPlaceholder: {
      type: String,
      default: 'Max'
    },
    searchPlaceholder: {
      type: String,
      default: 'Search...'
    },
    minMaxField: {
      type: String,
      default: ''
    }
  },
  setup(props, { emit }) {
    // Search and filtering
    const searchQuery = ref('');
    
    // Min-Max filtering
    const minValue = ref('');
    const maxValue = ref('');
    
    // Add debounce functionality for search
    let searchTimeout = null;
    const onSearchInput = () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        emit('search', searchQuery.value);
      }, 500); // 500ms debounce
    };
    
    // Handle min-max filter changes
    const onMinMaxChange = () => {
      emit('min-max-change', {
        min: minValue.value === '' ? null : minValue.value,
        max: maxValue.value === '' ? null : maxValue.value,
        field: props.minMaxField
      });
    };
    
    // Sorting
    const sortKey = ref('');
    const sortDirection = ref('asc');
    
    // Pagination
    const currentPage = ref(1);
    
    // Reset pagination when items or search changes
    watch([() => props.items, searchQuery], () => {
      currentPage.value = 1;
    });
    
    // Modified to handle server-side filtering
    const filteredItems = computed(() => {
      // If server-side is enabled, don't filter locally
      if (props.serverSide) return props.items;
      
      let result = [...props.items];
      
      // Apply search filter if query exists
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(item => {
          return props.columns.some(column => {
            const value = getItemValue(item, column.key);
            return value && String(value).toLowerCase().includes(query);
          });
        });
      }
      
      // Apply sorting if sort key exists
      if (sortKey.value) {
        result.sort((a, b) => {
          const valueA = getItemValue(a, sortKey.value);
          const valueB = getItemValue(b, sortKey.value);
          
          // Handle different data types
          if (typeof valueA === 'string' && typeof valueB === 'string') {
            return sortDirection.value === 'asc' 
              ? valueA.localeCompare(valueB) 
              : valueB.localeCompare(valueA);
          } else {
            return sortDirection.value === 'asc' 
              ? valueA - valueB 
              : valueB - valueA;
          }
        });
      }
      
      return result;
    });
    
    // Add the missing helper functions
    // Helper function to get value from an item using a key (supports nested properties)
    const getItemValue = (item, key) => {
      // Handle nested properties with dot notation (e.g., 'company.name')
      if (key.includes('.')) {
        return key.split('.').reduce((obj, prop) => obj && obj[prop], item);
      }
      return item[key];
    };
    
    // Helper function to get a unique key for each item
    const getItemKey = (item, index) => {
      return item[props.itemKey] || index;
    };
    
    // Modified to handle server-side pagination
    const paginatedItems = computed(() => {
      // If server-side or pagination is disabled, return all filtered items
      if (props.serverSide || !props.showPagination) return filteredItems.value;
      
      return filteredItems.value.slice(
        paginationStart.value,
        paginationStart.value + props.perPage
      );
    });
    
    // Pagination calculations
    const totalPages = computed(() => {
      return Math.ceil(filteredItems.value.length / props.perPage);
    });
    
    const paginationStart = computed(() => {
      return (currentPage.value - 1) * props.perPage;
    });
    
    // Add the missing pageNumbers computed property
    const pageNumbers = computed(() => {
      const pages = [];
      const maxVisiblePages = 5;
      
      if (totalPages.value <= maxVisiblePages) {
        // Show all pages if total is less than max visible
        for (let i = 1; i <= totalPages.value; i++) {
          pages.push(i);
        }
      } else {
        // Show limited pages with current page in the middle
        let startPage = Math.max(1, currentPage.value - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages.value, startPage + maxVisiblePages - 1);
        
        // Adjust if we're near the end
        if (endPage === totalPages.value) {
          startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
          pages.push(i);
        }
      }
      
      return pages;
    });
    
    // Modified sortBy to emit event for server-side sorting
    const sortBy = (key) => {
      if (sortKey.value === key) {
        // Toggle direction if already sorting by this key
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
      } else {
        // Set new sort key and default to ascending
        sortKey.value = key;
        sortDirection.value = 'asc';
      }
      
      emit('sort', { key, direction: sortDirection.value });
    };
    
    // Fix the getSortIconClass function
    const getSortIconClass = (key) => {
      if (sortKey.value !== key) return 'fa-sort';
      return sortDirection.value === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    };
    
    const changePage = (page) => {
      if (page < 1 || page > totalPages.value) return;
      currentPage.value = page;
      emit('page-change', page);
    };
    
    return {
      searchQuery,
      minValue,
      maxValue,
      sortKey,
      sortDirection,
      currentPage,
      filteredItems,
      paginatedItems,
      totalPages,
      paginationStart,
      pageNumbers,
      getItemValue,
      getItemKey,
      sortBy,
      getSortIconClass,
      changePage,
      onSearchInput,
      onMinMaxChange // Add the new method
    };
  }
};
</script>

<style scoped>
.responsive-table {
  margin-bottom: 1.5rem;
}

.table-filters {
  margin-bottom: 1rem;
}

.search-wrapper {
  max-width: 300px;
}

.min-max-filters {
  flex-grow: 1;
  justify-content: flex-end;
  max-width: 500px;
}

.sortable {
  cursor: pointer;
}

.sort-icon {
  margin-left: 0.25rem;
}

.actions-column {
  width: 120px;
  text-align: right;
}

/* Added to ensure proper positioning of the loading spinner */
.table-responsive {
  min-height: 200px;
}

/* Pagination styling to match MarketplacePage */
.pagination {
  margin-bottom: 0;
}

.page-link {
  color: #123524;
  border-color: #dee2e6;
}

.page-item.active .page-link {
  background-color: #123524;
  border-color: #123524;
  color: #fff;
}

.page-item.disabled .page-link {
  color: #6c757d;
  pointer-events: none;
  background-color: #fff;
  border-color: #dee2e6;
}

.page-link:hover {
  color: #0a1f15;
  background-color: #e9ecef;
  border-color: #dee2e6;
}

.page-link:focus {
  box-shadow: 0 0 0 0.25rem rgba(18, 53, 36, 0.25);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .table-filters {
    flex-direction: column;
  }
  
  .search-wrapper {
    max-width: 100%;
    margin-bottom: 1rem;
  }
  
  .custom-filters {
    width: 100%;
  }
}
</style>