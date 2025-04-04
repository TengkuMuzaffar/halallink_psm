<template>
  <div>
    <!-- Products Grid -->
    <div v-if="!loading && !error" class="row g-4">
      <div v-for="product in products" 
           :key="product.id" 
           class="col-sm-6 col-md-4 col-xl-3">
        <ProductCard 
          :product="product"
          @view-details="$emit('view-details', product)"
          @add-to-cart="$emit('add-to-cart', product)"
        />
      </div>
    </div>

    <!-- No Results -->
    <div v-if="!loading && !error && products.length === 0" class="text-center py-5">
      <div class="alert alert-info">
        No products found. Try adjusting your search criteria.
      </div>
    </div>
  </div>
</template>

<script>
import ProductCard from './ProductCard.vue';

export default {
  name: 'ProductGrid',
  components: {
    ProductCard
  },
  props: {
    products: {
      type: Array,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    },
    error: {
      type: [String, null],
      default: null
    }
  }
}
</script>