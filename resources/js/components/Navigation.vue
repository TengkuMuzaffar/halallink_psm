<template>
  <nav class="bg-light py-2">
    <div class="container">
      <ul class="nav">
        <li class="nav-item">
          <router-link class="nav-link" :to="{ name: 'dashboard' }">Dashboard</router-link>
        </li>
        <!-- Add more navigation items as needed -->
      </ul>
    </div>
  </nav>
</template>

<script>
export default {
  name: 'Navigation'
}
</script>