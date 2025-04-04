<template>
  <div class="card h-100 product-card">
    <div class="product-image-container">
      <img 
        :src="product.image || '/images/no-image.jpg'" 
        :alt="product.name"
        class="product-image"
      >
    </div>
    <div class="card-body d-flex flex-column">
      <h5 class="card-title product-title" :title="product.name">
        {{ product.name }}
      </h5>
      <p class="card-text product-price mb-2">
        RM {{ formatPrice(product.price) }}
      </p>
      <p class="card-text product-quantity mb-2">
        {{ product.quantity }} {{ product.unit }}
      </p>
      <p class="card-text product-seller mb-2" :title="product.seller">
        <i class="fas fa-store me-1"></i>{{ product.seller }}
      </p>
      <p class="card-text product-location mb-3" :title="product.location">
        <i class="fas fa-map-marker-alt me-1"></i>{{ product.location }}
      </p>
      <div class="mt-auto">
        <button class="btn btn-primary w-100 mb-2" @click="$emit('view-details', product)">
          View Details
        </button>
        <button class="btn btn-outline-primary w-100" @click="$emit('add-to-cart', product)">
          <i class="fas fa-cart-plus me-1"></i> Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProductCard',
  props: {
    product: {
      type: Object,
      required: true
    }
  },
  methods: {
    formatPrice(price) {
      return parseFloat(price).toFixed(2);
    }
  }
}
</script>

<style scoped>
.product-card {
  transition: transform 0.2s, box-shadow 0.2s;
  border-color: #e0e0e0;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.product-image-container {
  height: 200px;
  overflow: hidden;
}

.product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-title {
  font-size: 1.1rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #123524;
}

.product-price {
  font-size: 1.2rem;
  font-weight: 700;
  color: #123524;
}

.product-seller, .product-location {
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #555;
}

.btn-primary {
  background-color: #123524;
  border-color: #123524;
}

.btn-primary:hover, .btn-primary:focus {
  background-color: #0d2a1c;
  border-color: #0d2a1c;
}

.btn-outline-primary {
  color: #123524;
  border-color: #123524;
}

.btn-outline-primary:hover, .btn-outline-primary:focus {
  background-color: #123524;
  border-color: #123524;
  color: white;
}
</style>