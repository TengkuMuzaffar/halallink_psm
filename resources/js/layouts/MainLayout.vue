<template>
  <div class="main-layout d-flex flex-column min-vh-100">
    <Header />
    <Navigation />
    <main class="flex-grow-1">
      <Container>
        <Suspense>
          <template #default>
            <router-view></router-view>
          </template>
          <template #fallback>
            <div class="d-flex justify-content-center align-items-center" style="height: 300px;">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </template>
        </Suspense>
      </Container>
    </main>
    <Footer />
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import Header from '../components/Header.vue';
import Navigation from '../components/Navigation.vue';
import Container from '../components/Container.vue';
import Footer from '../components/Footer.vue';

export default defineComponent({
  name: 'MainLayout',
  components: {
    Header,
    Navigation,
    Container,
    Footer
  },
  setup() {
    return {};
  }
});
</script>

<style scoped>
.main-layout {
  background-color: #f5f5f5;
}
</style>
