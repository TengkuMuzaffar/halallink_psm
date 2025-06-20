<template>
  <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header theme-header">
          <h5 class="modal-title" id="orderDetailsModalLabel">Order Details #{{ selectedOrder?.orderID }}</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body position-relative">
          <LoadingSpinner 
            v-if="loading" 
            size="lg" 
            message="Loading order details..." 
            overlay
          />
          <div v-else-if="error" class="alert alert-danger">
            {{ error }}
          </div>
          <div v-else-if="!orderDetails || orderDetails.items?.length === 0" class="text-center py-4">
            <i class="fas fa-box text-muted mb-2" style="font-size: 2rem;"></i>
            <p class="text-muted">No items found for this order</p>
          </div>
          <div v-else>
            <!-- Order Summary -->
            <div class="card mb-4">
              <div class="card-header bg-light">
                <h6 class="mb-0">Order Summary</h6>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <p><strong>Order ID:</strong> {{ orderDetails.orderID }}</p>
                    <p><strong>Order Date:</strong> {{ formatDate(orderDetails.order_timestamp) }}</p>
                    <p><strong>Status:</strong> 
                      <span :class="getOrderStatusBadgeClass(orderDetails.order_status)">
                        {{ orderDetails.order_status }}
                      </span>
                    </p>
                  </div>
                  <div class="col-md-6">
                    <p><strong>Payment ID:</strong> {{ orderDetails.payment?.paymentID || 'N/A' }}</p>
                    <p><strong>Payment Amount:</strong> {{ formatCurrency(orderDetails.payment?.payment_amount || 0) }}</p>
                    <p><strong>Payment Status:</strong> {{ orderDetails.payment?.payment_status || 'N/A' }}</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Order Items -->
            <h6 class="mb-3">Order Items</h6>
            <div class="table-responsive">
              <table class="table table-bordered table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Image</th>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in orderDetails.items" :key="item.cartID">
                    <td class="text-center">
                      <img 
                        :src="item.item.item_image || '/images/blank.jpg'" 
                        alt="Item Image" 
                        class="img-thumbnail" 
                        style="width: 50px; height: 50px; object-fit: cover;"
                      >
                    </td>
                    <td>
                      <div>{{ item.item.poultry?.poultry_name || 'Unknown' }}</div>
                      <small class="text-muted">Item #{{ item.item.itemID }}</small>
                    </td>
                    <td>{{ item.quantity }}</td>
                    <td>{{ formatCurrency(item.price_at_purchase || item.item.price) }}</td>
                    <td>{{ formatCurrency((item.price_at_purchase || item.item.price) * item.quantity) }}</td>
                  </tr>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                    <td>{{ formatCurrency(orderDetails.payment?.payment_amount || 0) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import LoadingSpinner from '../ui/LoadingSpinner.vue';

export default {
  name: 'OrderDetailsModal',
  components: {
    LoadingSpinner
  },
  props: {
    selectedOrder: {
      type: Object,
      default: null
    },
    orderDetails: {
      type: Object,
      default: null
    },
    loading: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: null
    }
  },
  methods: {
    formatCurrency(value) {
      if (!value) return 'RM 0.00';
      return new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' }).format(value);
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-MY');
    },
    getOrderStatusBadgeClass(status) {
      const classes = 'badge ';
      switch (status) {
        case 'paid': return classes + 'bg-success';
        case 'pending': return classes + 'bg-warning';
        case 'cancelled': return classes + 'bg-danger';
        case 'delivered': return classes + 'bg-info';
        default: return classes + 'bg-secondary';
      }
    }
  }
};
</script>

<style scoped>
.modal-header.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

.btn-close-white {
  filter: invert(1) grayscale(100%) brightness(200%);
}

.badge {
  font-size: 0.8rem;
  padding: 0.35em 0.65em;
}
</style>