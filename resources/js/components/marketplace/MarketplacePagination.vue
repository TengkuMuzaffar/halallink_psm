<template>
  <div class="d-flex justify-content-between align-items-center mt-4">
    <div>
      <span class="text-muted">Showing {{ from || 1 }}-{{ to || itemCount }} of {{ total || itemCount }}</span>
    </div>
    <nav aria-label="Product pagination" v-if="lastPage > 1">
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
            :class="{ active: page === currentPage, disabled: loading }"
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
      const maxVisiblePages = 5;
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
  }
}
</script>

<style scoped>
.pagination {
  margin-bottom: 0;
}

.page-link {
  color: #123524;
}

.page-item.active .page-link {
  background-color: #123524;
  border-color: #123524;
  color: #fff;
}
</style>