<template>
  <div class="left-panel">
    <div class="logo">
      <img src="/images/HalalLink.png" alt="HalalLink" class="logo-img">
    </div>
    <h2 class="d-none d-md-block">Welcome Back!</h2>
    <p class="d-none d-md-block">To stay connected with us<br>please login with your personal info</p>
    
    <animated-text class="d-none d-md-block" :texts="texts" :interval="4000" />
    
    <div class="bottom-links d-none d-md-block">
      <span>CONNECT WITH US</span>
      <div class="social-icons">
        <a href="#" class="social-icon"><i class="fas fa-globe"></i></a>
        <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
      </div>
    </div>
  </div>
</template>

<script>
import AnimatedText from './AnimatedText.vue';

export default {
  name: 'LoginLeftPanel',
  components: {
    AnimatedText
  },
  props: {
    texts: {
      type: Array,
      required: true
    }
  }
}
</script>

<style scoped>
.left-panel {
  width: 40%;
  background-color: #123524;
  color: #EFE3C2;
  padding: 60px 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  position: relative;
  border-top-right-radius: 80px;
  border-bottom-right-radius: 80px;
}

.logo-img {
  width: 180px;
  height: auto;
  margin-bottom: 30px;
  object-fit: contain;
}

h2 {
  margin-bottom: 15px;
  font-size: 28px;
  font-weight: 600;
  color: #EFE3C2;
}

p {
  text-align: center;
  margin-bottom: 20px;
  font-size: 15px;
  line-height: 1.6;
  opacity: 0.9;
  color: #EFE3C2;
}

.bottom-links {
  position: absolute;
  bottom: 30px;
  font-size: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #EFE3C2;
}

.social-icons {
  display: flex;
  margin-top: 10px;
}

.social-icon {
  color: #EFE3C2;
  margin: 0 8px;
  font-size: 18px;
  transition: transform 0.3s ease;
}

.social-icon:hover {
  transform: scale(1.2);
  color: #85A947;
}

@media (min-width: 1200px) {
  .left-panel {
    padding: 80px 40px;
  }
  
  .logo-img {
    width: 220px;
  }
}

@media (max-width: 768px) {
  .left-panel {
    width: 100%;
    padding: 30px;
    border-radius: 0 0 80px 80px;
  }
}
</style>