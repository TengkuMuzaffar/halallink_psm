<template>
  <div class="marketplace-page">
    <h1 class="mb-4">Marketplace</h1>
    
    <!-- Search and Filter Bar -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="input-group">
              <input 
                type="text" 
                class="form-control" 
                placeholder="Search products..." 
                v-model="searchQuery"
              >
              <button class="btn btn-primary" type="button">
                <i class="bi bi-search"></i> Search
              </button>
            </div>
          </div>
          <div class="col-md-6">
            <div class="d-flex gap-2 justify-content-md-end">
              <select class="form-select" v-model="categoryFilter">
                <option value="">All Categories</option>
                <option value="chicken">Chicken</option>
                <option value="duck">Duck</option>
                <option value="turkey">Turkey</option>
              </select>
              <select class="form-select" v-model="sortBy">
                <option value="newest">Newest</option>
                <option value="price_low">Price: Low to High</option>
                <option value="price_high">Price: High to Low</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Products Grid -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
      <div class="col" v-for="(product, index) in mockProducts" :key="index">
        <div class="card h-100 product-card">
          <div class="product-image-container">
            <img :src="product.image" class="card-img-top product-image" :alt="product.name">
          </div>
          <div class="card-body">
            <h5 class="card-title product-title">{{ product.name }}</h5>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="product-price">RM {{ product.price.toFixed(2) }}</span>
              <span class="product-quantity">{{ product.quantity }} {{ product.unit }}</span>
            </div>
            <p class="card-text product-seller">
              <i class="bi bi-shop me-1"></i> {{ product.seller }}
            </p>
            <div class="d-flex justify-content-between align-items-center">
              <span class="product-location">
                <i class="bi bi-geo-alt me-1"></i> {{ product.location }}
              </span>
              <div class="product-rating">
                <i class="bi bi-star-fill text-warning"></i>
                <span class="ms-1">{{ product.rating }}</span>
              </div>
            </div>
          </div>
          <div class="card-footer bg-transparent border-top-0">
            <div class="d-grid gap-2">
              <button class="btn btn-primary">
                <i class="bi bi-cart-plus me-1"></i> Add to Cart
              </button>
              <button class="btn btn-outline-secondary">
                <i class="bi bi-eye me-1"></i> View Details
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Pagination -->
    <nav class="d-flex justify-content-center mt-5">
      <ul class="pagination">
        <li class="page-item disabled">
          <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
        </li>
        <li class="page-item active"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">2</a></li>
        <li class="page-item"><a class="page-link" href="#">3</a></li>
        <li class="page-item">
          <a class="page-link" href="#">Next</a>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script>
export default {
  name: 'MarketplacePage',
  data() {
    return {
      searchQuery: '',
      categoryFilter: '',
      sortBy: 'newest',
      mockProducts: [
        {
          name: 'Fresh Whole Chicken',
          price: 15.90,
          quantity: 1.5,
          unit: 'kg',
          seller: 'Farm Fresh Poultry',
          location: 'Selangor',
          rating: 4.8,
          image: '/images/placeholder-product.jpg'
        },
        {
          name: 'Chicken Breast Fillet',
          price: 12.50,
          quantity: 500,
          unit: 'g',
          seller: 'Organic Farms',
          location: 'Kuala Lumpur',
          rating: 4.5,
          image: '/images/placeholder-product.jpg'
        },
        {
          name: 'Chicken Drumsticks',
          price: 9.90,
          quantity: 6,
          unit: 'pcs',
          seller: 'Happy Poultry',
          location: 'Penang',
          rating: 4.7,
          image: '/images/placeholder-product.jpg'
        },
        {
          name: 'Chicken Wings',
          price: 8.50,
          quantity: 500,
          unit: 'g',
          seller: 'Premium Meats',
          location: 'Johor',
          rating: 4.6,
          image: '/images/placeholder-product.jpg'
        },
        {
          name: 'Duck Breast',
          price: 22.90,
          quantity: 400,
          unit: 'g',
          seller: 'Gourmet Poultry',
          location: 'Melaka',
          rating: 4.9,
          image: '/images/placeholder-product.jpg'
        },
        {
          name: 'Turkey Whole',
          price: 89.90,
          quantity: 4.5,
          unit: 'kg',
          seller: 'Festival Foods',
          location: 'Kuala Lumpur',
          rating: 4.7,
          image: '/images/placeholder-product.jpg'
        },
        {
          name: 'Chicken Eggs (Grade A)',
          price: 12.00,
          quantity: 30,
          unit: 'pcs',
          seller: 'Free Range Farm',
          location: 'Selangor',
          rating: 4.8,
          image: '/images/placeholder-product.jpg'
        },
        {
          name: 'Quail Eggs',
          price: 8.90,
          quantity: 24,
          unit: 'pcs',
          seller: 'Specialty Poultry',
          location: 'Ipoh',
          rating: 4.5,
          image: '/images/placeholder-product.jpg'
        }
      ]
    };
  },
  methods: {
    // Methods will be implemented when connecting to API
  }
};
</script>

<style scoped>
.product-card {
  transition: transform 0.3s, box-shadow 0.3s;
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.product-image-container {
  height: 200px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f8f9fa;
}

.product-image {
  object-fit: cover;
  height: 100%;
  width: 100%;
}

.product-title {
  font-size: 1rem;
  font-weight: 600;
  height: 2.4rem;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.product-price {
  font-size: 1.25rem;
  font-weight: 700;
  color: #123524; /* Using the theme color */
}

.product-quantity {
  font-size: 0.875rem;
  color: #6c757d;
}

.product-seller, .product-location {
  font-size: 0.875rem;
  color: #6c757d;
}

.product-rating {
  font-size: 0.875rem;
  font-weight: 600;
}

.btn-primary {
  background-color: #123524; /* Using the theme color */
  border-color: #123524;
}

.btn-primary:hover {
  background-color: #0d2a1c;
  border-color: #0d2a1c;
}

.btn-outline-secondary:hover {
  background-color: #f8f9fa;
  color: #123524; /* Using the theme color */
}

.pagination .page-item.active .page-link {
  background-color: #123524; /* Using the theme color */
  border-color: #123524;
}

.pagination .page-link {
  color: #123524; /* Using the theme color */
}
</style>