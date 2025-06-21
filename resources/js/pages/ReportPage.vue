<template>
  <div class="report-page">
    <h1 class="mb-4">Reports</h1>
    
    <!-- Conditionally render admin or SME report table based on company type -->
    <AdminReportTable v-if="isAdmin" />
    <SmeReportTable v-else />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import AdminReportTable from '../components/report/admin/ReportTable.vue';
import SmeReportTable from '../components/report/sme/ReportTable.vue';

export default {
  name: 'ReportPage',
  components: {
    AdminReportTable,
    SmeReportTable
  },
  setup() {
    const store = useStore();
    
    // Determine if user is admin based on company type
    const isAdmin = computed(() => {
      const user = store.state.user; // Changed from store.state.auth.user
      console.log(user);
      return user && user.company && user.company.company_type === 'admin';
    });
    
    return {
      isAdmin
    };
  }
};
</script>

