<template>
  <div class="employee-table">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ title }}</h5>
        <button 
          v-if="showAddButton" 
          class="btn btn-primary" 
          @click="$emit('add')"
          :disabled="loading"
        >
          <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-copy'"></i>
          <span class="ms-1 d-none d-md-inline">{{ loading ? 'Loading...' : 'Copy Registration Link' }}</span>
        </button>
      </div>
      <div class="card-body">
        <ResponsiveTable
          :columns="columns"
          :items="employees"
          :loading="loading"
          :has-actions="true"
          item-key="userID"
          @sort="$emit('sort', $event)"
          @page-change="$emit('page-change', $event)"
          @search="$emit('search', $event)"
        >
          <!-- Pass through all slots -->
          <template v-for="(_, name) in $slots" #[name]="slotData">
            <slot :name="name" v-bind="slotData"></slot>
          </template>
        </ResponsiveTable>
      </div>
    </div>
  </div>
</template>

<script>
import ResponsiveTable from '../ui/ResponsiveTable.vue';

export default {
  name: 'EmployeeTable',
  components: {
    ResponsiveTable
  },
  props: {
    title: {
      type: String,
      default: 'Employees'
    },
    employees: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    columns: {
      type: Array,
      required: true
    },
    showAddButton: {
      type: Boolean,
      default: true
    }
  },
  emits: ['add', 'sort', 'page-change', 'search']
}
</script>