<template>
  <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center mb-0">
      <!-- Previous page button -->
      <li class="page-item" :class="{ disabled: pagination.current_page <= 1 }">
        <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">
          <i class="fas fa-chevron-left"></i>
        </a>
      </li>
      
      <!-- Page numbers -->
      <li 
        v-for="page in displayedPages" 
        :key="page" 
        class="page-item"
        :class="{ active: pagination.current_page === page }"
      >
        <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
      </li>
      
      <!-- Next page button -->
      <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.last_page }">
        <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">
          <i class="fas fa-chevron-right"></i>
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
        total: 0,
        from: 1,
        to: 0
      })
    },
    maxVisiblePages: {
      type: Number,
      default: 5
    }
  },
  computed: {
    displayedPages() {
      if (!this.pagination.last_page) return [];
      
      const current = this.pagination.current_page;
      const last = this.pagination.last_page;
      const delta = Math.floor(this.maxVisiblePages / 2);
      
      let start = current - delta;
      let end = current + delta;
      
      if (start <= 1) {
        start = 1;
        end = Math.min(this.maxVisiblePages, last);
      }
      
      if (end >= last) {
        end = last;
        start = Math.max(last - this.maxVisiblePages + 1, 1);
      }
      
      const pages = [];
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      
      return pages;
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
  padding: 0.5rem 0.75rem;
  color: #6c757d;
  background-color: #fff;
  border: 1px solid #dee2e6;
  cursor: pointer;
}
.page-item.active .page-link {
  color: #fff;
  background-color: #6c757d;
  border-color: #6c757d;
}
.page-item.disabled .page-link {
  color: #6c757d;
  pointer-events: none;
  background-color: #fff;
  border-color: #dee2e6;
}
.page-link:hover {
  color: #6c757d;
  background-color: #e9ecef;
  border-color: #dee2e6;
}
.page-item.active .page-link:hover {
  color: #fff;
  background-color: #5a6268;
  border-color: #545b62;
}
</style>