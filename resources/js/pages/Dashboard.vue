<template>
  <div class="dashboard">
    <div class="card">
      <div class="card-header">
        <h4 class="mb-0">Dashboard</h4>
      </div>
      <div class="card-body">
        <p>Welcome to HalalLink Dashboard!</p>
        <p>You are logged in as: {{ user ? user.fullname : 'User' }}</p>
        <p>Role: {{ user ? user.role : 'N/A' }}</p>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
  name: 'Dashboard',
  computed: {
    ...mapGetters(['user'])
  }
}
</script>