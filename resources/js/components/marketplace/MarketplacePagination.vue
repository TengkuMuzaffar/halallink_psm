<template>
  <div class="pagination-container mt-4">
    <div class="pagination-info">
      <span class="text-muted">Showing {{ from || 1 }}-{{ to || itemCount }} of {{ total || itemCount }}</span>
    </div>
    <nav aria-label="Product pagination" v-if="lastPage > 1" class="pagination-nav">
      <ul class="pagination mb-0">
        <li class="page-item" :class="{ disabled: currentPage <= 1 || loading }">
          <a class="page-link" href="#" @click.prevent="!loading && onPageChange(currentPage - 1)">
            <i class="fas fa-chevron-left"></i>
          </a>
        </li>
        <li 
            v-for="page in paginationRange" 
            :key="page" 
            class="page-item"
            :class="{ active: page === currentPage, disabled: loading, 'page-number': true }"
        >
          <a class="page-link" href="#" @click.prevent="!loading && onPageChange(page)">{{ page }}</a>
        </li>
        <li class="page-item" :class="{ disabled: currentPage >= lastPage || loading }">
          <a class="page-link" href="#" @click.prevent="!loading && onPageChange(currentPage + 1)">
            <i class="fas fa-chevron-right"></i>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script>
export default {
  name: 'MarketplacePagination',
  props: {
    currentPage: {
      type: Number,
      required: true
    },
    lastPage: {
      type: Number,
      required: true
    },
    from: {
      type: Number,
      default: null
    },
    to: {
      type: Number,
      default: null
    },
    total: {
      type: Number,
      default: null
    },
    itemCount: {
      type: Number,
      default: 0
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    paginationRange() {
      // Adjust max visible pages based on screen size
      let maxVisiblePages = 5;
      
      // Reduce visible pages on smaller screens
      if (window.innerWidth < 768) {
        maxVisiblePages = 3;
      } else if (window.innerWidth < 992) {
        maxVisiblePages = 4;
      }
      
      // For very small screens, still show at least 3 pages if possible
      if (window.innerWidth < 400) {
        maxVisiblePages = 3;
      }
      
      let startPage = 1;
      let endPage = this.lastPage;
      
      if (this.lastPage > maxVisiblePages) {
        // Calculate start and end page
        const middlePage = Math.floor(maxVisiblePages / 2);
        
        if (this.currentPage <= middlePage) {
          // Near the start
          endPage = maxVisiblePages;
        } else if (this.currentPage > this.lastPage - middlePage) {
          // Near the end
          startPage = this.lastPage - maxVisiblePages + 1;
        } else {
          // Middle
          startPage = this.currentPage - middlePage;
          endPage = this.currentPage + middlePage;
        }
        
        // Adjust if needed
        startPage = Math.max(1, startPage);
        endPage = Math.min(this.lastPage, endPage);
      }
      
      // Generate range array
      const range = [];
      for (let i = startPage; i <= endPage; i++) {
        range.push(i);
      }
      
      return range;
    }
  },
  methods: {
    onPageChange(page) {
      if (page < 1 || page > this.lastPage || this.loading) return;
      this.$emit('page-change', page);
    }
  },
  mounted() {
    // Add window resize listener to update pagination when screen size changes
    window.addEventListener('resize', this.$forceUpdate);
  },
  beforeUnmount() {
    // Clean up event listener
    window.removeEventListener('resize', this.$forceUpdate);
  }
}
</script>

<style scoped>
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination {
  margin-bottom: 0;
  flex-wrap: wrap;
}

.page-link {
  color: #123524;
}

.page-item.active .page-link {
  background-color: #123524;
  border-color: #123524;
  color: #fff;
}

/* Medium screens */
@media (max-width: 991px) {
  .pagination-container {
    justify-content: center;
  }
  
  .pagination-info {
    width: 100%;
    text-align: center;
    margin-bottom: 0.5rem;
  }
  
  .pagination-nav {
    width: 100%;
    display: flex;
    justify-content: center;
  }
  
  .page-link {
    padding: 0.4rem 0.75rem;
  }
}

/* Small screens */
@media (max-width: 576px) {
  .page-link {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
  }
  
  /* Compact pagination for very small screens */
  @media (max-width: 360px) {
    .pagination {
      gap: 0.1rem;
    }
    
    .page-link {
      padding: 0.2rem 0.4rem;
      font-size: 0.8rem;
    }
  }
}
</style>