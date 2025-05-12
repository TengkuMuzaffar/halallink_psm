<template>
  <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
      <!-- Previous page button -->
      <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
        <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">
          <span aria-hidden="true">&laquo;</span>
        </a>
      </li>
      
      <!-- First page -->
      <li v-if="showFirstPage" class="page-item" :class="{ active: pagination.current_page === 1 }">
        <a class="page-link" href="#" @click.prevent="changePage(1)">1</a>
      </li>
      
      <!-- Ellipsis after first page -->
      <li v-if="showFirstEllipsis" class="page-item disabled">
        <span class="page-link">...</span>
      </li>
      
      <!-- Page numbers -->
      <li v-for="page in displayedPages" :key="page" class="page-item" :class="{ active: pagination.current_page === page }">
        <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
      </li>
      
      <!-- Ellipsis before last page -->
      <li v-if="showLastEllipsis" class="page-item disabled">
        <span class="page-link">...</span>
      </li>
      
      <!-- Last page -->
      <li v-if="showLastPage" class="page-item" :class="{ active: pagination.current_page === pagination.last_page }">
        <a class="page-link" href="#" @click.prevent="changePage(pagination.last_page)">{{ pagination.last_page }}</a>
      </li>
      
      <!-- Next page button -->
      <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
        <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">
          <span aria-hidden="true">&raquo;</span>
        </a>
      </li>
    </ul>
  </nav>
</template>

<script>
export default {
  name: 'Pagination',
  props: {
    pagination: {
      type: Object,
      required: true,
      default: () => ({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
      })
    },
    maxVisiblePages: {
      type: Number,
      default: 5
    }
  },
  computed: {
    // Calculate which page numbers to display
    displayedPages() {
      if (!this.pagination.last_page) return [];
      
      const current = this.pagination.current_page;
      const last = this.pagination.last_page;
      const delta = Math.floor(this.maxVisiblePages / 2);
      
      let start = current - delta;
      let end = current + delta;
      
      if (start <= 1) {
        start = 2;
        end = Math.min(start + this.maxVisiblePages - 2, last - 1);
      }
      
      if (end >= last) {
        end = last - 1;
        start = Math.max(end - (this.maxVisiblePages - 2), 2);
      }
      
      // Ensure we don't go below 2 (since 1 is shown separately)
      start = Math.max(start, 2);
      
      // Ensure we don't go above last_page-1 (since last_page is shown separately)
      end = Math.min(end, last - 1);
      
      // Generate the array of page numbers
      const pages = [];
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      
      return pages;
    },
    
    // Determine if we should show the first page
    showFirstPage() {
      return this.pagination.last_page > 1;
    },
    
    // Determine if we should show the last page
    showLastPage() {
      return this.pagination.last_page > 1 && this.pagination.last_page !== 1;
    },
    
    // Determine if we should show ellipsis after first page
    showFirstEllipsis() {
      return this.displayedPages.length > 0 && this.displayedPages[0] > 2;
    },
    
    // Determine if we should show ellipsis before last page
    showLastEllipsis() {
      return this.displayedPages.length > 0 && 
             this.displayedPages[this.displayedPages.length - 1] < this.pagination.last_page - 1;
    }
  },
  methods: {
    changePage(page) {
      if (page < 1 || page > this.pagination.last_page) return;
      this.$emit('page-changed', page);
    }
  }
}
</script>

<style scoped>
.pagination {
  margin-bottom: 0;
}
.page-link {
  color: #007bff;
  cursor: pointer;
}
.page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}
</style>