<template>
    <div class="app-container" :class="customClass">
      <slot></slot>
    </div>
  </template>
  
  <script>
  export default {
    name: 'Container',
    props: {
      customClass: {
        type: String,
        default: 'py-4'
      }
    }
  }
  </script>
  
  <style scoped>
  .app-container {
    width: 100%;
    padding-right: 15px;
    padding-left: 15px;
    margin-right: auto;
    margin-left: auto;
  }
  
  @media (min-width: 576px) {
    .app-container {
      max-width: 540px;
    }
  }
  
  @media (min-width: 768px) {
    .app-container {
      max-width: 720px;
    }
  }
  
  @media (min-width: 992px) {
    .app-container {
      max-width: 960px;
    }
  }
  
  @media (min-width: 1200px) {
    .app-container {
      max-width: 1140px;
    }
  }
  </style>