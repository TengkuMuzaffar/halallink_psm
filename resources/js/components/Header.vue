<template>
  <header class="bg-primary text-white py-3">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center">
        <h1 class="h4 mb-0">HalalLink</h1>
        <div v-if="isAuthenticated">
          <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              {{ user ? user.fullname : 'User' }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="#" @click.prevent="handleLogout">Logout</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script>
import { mapGetters, mapActions } from 'vuex';

export default {
  name: 'Header',
  computed: {
    ...mapGetters(['isAuthenticated', 'user'])
  },
  methods: {
    ...mapActions(['logout']),
    async handleLogout() {
      try {
        await this.logout();
        this.$router.push({ name: 'login' });
      } catch (error) {
        console.error('Logout error:', error);
        this.$router.push({ name: 'login' });
      }
    }
  }
}
</script>