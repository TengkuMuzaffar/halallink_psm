<template>
  <div class="main-layout d-flex flex-column min-vh-100">
    <Header />
    <Navigation />
    <main class="flex-grow-1">
      <Container>
        <router-view></router-view>
      </Container>
    </main>
    <Footer />
  </div>
</template>

<script>
import Header from '../components/Header.vue';
import Navigation from '../components/Navigation.vue';
import Container from '../components/Container.vue';
import Footer from '../components/Footer.vue';

export default {
  name: 'MainLayout',
  components: {
    Header,
    Navigation,
    Container,
    Footer
  }
}
</script>