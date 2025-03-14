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
            placeholder="Search..." 
            v-model="searchQuery"
          >
        </div>
      </div>
      
      <!-- Custom filters slot -->
      <div class="custom-filters">
        <slot name="filters"></slot>
      </div>
    </div>
    
    <div class="table-responsive">
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
          <tr v-if="loading">
            <td :colspan="hasActions ? columns.length + 1 : columns.length" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </td>
          </tr>
          <tr v-else-if="filteredItems.length === 0">
            <td :colspan="hasActions ? columns.length + 1 : columns.length" class="text-center py-4">
              <slot name="empty">
                <div class="text-muted">No data available</div>
              </slot>
            </td>
          </tr>
          <tr v-for="(item, index) in paginatedItems" :key="getItemKey(item, index)">
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
      <div class="pagination-info">
        Showing {{ paginationStart + 1 }} to {{ Math.min(paginationStart + perPage, filteredItems.length) }} of {{ filteredItems.length }} entries
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

export default {
  name: 'ResponsiveTable',
  props: {
    columns: {
      type: Array,
      required: true,
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
    }
  },
  setup(props, { emit }) {
    // Search and filtering
    const searchQuery = ref('');
    
    // Sorting
    const sortKey = ref('');
    const sortDirection = ref('asc');
    
    // Pagination
    const currentPage = ref(1);
    
    // Reset pagination when items or search changes
    watch([() => props.items, searchQuery], () => {
      currentPage.value = 1;
    });
    
    // Filter items based on search query
    const filteredItems = computed(() => {
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
    
    // Pagination calculations
    const totalPages = computed(() => {
      return Math.ceil(filteredItems.value.length / props.perPage);
    });
    
    const paginationStart = computed(() => {
      return (currentPage.value - 1) * props.perPage;
    });
    
    const paginatedItems = computed(() => {
      if (!props.showPagination) return filteredItems.value;
      
      return filteredItems.value.slice(
        paginationStart.value,
        paginationStart.value + props.perPage
      );
    });
    
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
    
    // Helper functions
    const getItemValue = (item, key) => {
      // Handle nested properties with dot notation (e.g., 'company.name')
      if (key.includes('.')) {
        return key.split('.').reduce((obj, prop) => obj && obj[prop], item);
      }
      return item[key];
    };
    
    const getItemKey = (item, index) => {
      return item[props.itemKey] || index;
    };
    
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
      changePage
    };
  }
};
</script>

<style scoped>
.responsive-table {
  width: 100%;
}

.table-responsive {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sort-icon {
  margin-left: 5px;
}

.actions-column {
  width: 120px;
  text-align: right;
}

.pagination {
  margin-bottom: 0;
}

.page-link {
  padding: 0.375rem 0.75rem;
}

@media (max-width: 768px) {
  .table th, .table td {
    white-space: nowrap;
  }
  
  .pagination-info {
    font-size: 0.875rem;
  }
}
</style>