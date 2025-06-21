<template>
  <div class="payment-status-container">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <div v-if="loading" class="my-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-3">Processing your payment...</p>
        </div>
        
        <div v-else-if="success" class="my-5">
          <div class="success-icon mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
          </div>
          <h2 class="mb-3">Payment Successful!</h2>
          <p class="mb-4">Your order has been placed successfully.</p>
          <p class="mb-2"><strong>Order Reference:</strong> {{ orderReference }}</p>
          <p class="mb-2"><strong>Transaction ID:</strong> {{ transactionId }}</p>
          <p class="mb-4"><strong>Amount:</strong> {{ amount }}</p>
          
          <div class="d-grid gap-2 col-md-6 mx-auto">
            <button @click="goToMarketplace" class="btn btn-primary">
              Continue Shopping
            </button>
            <button @click="viewOrders" class="btn btn-outline-primary">
              View My Orders
            </button>
          </div>
        </div>
        
        <div v-else class="my-5">
          <div class="error-icon mb-4">
            <i class="bi bi-exclamation-circle-fill text-danger" style="font-size: 4rem;"></i>
          </div>
          <h2 class="mb-3">Payment Failed</h2>
          <p class="mb-4">{{ errorMessage }}</p>
          
          <div class="d-grid gap-2 col-md-6 mx-auto">
            <button @click="goToMarketplace" class="btn btn-primary">
              Return to Marketplace
            </button>
            <button @click="tryAgain" class="btn btn-outline-primary">
              Try Again
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import marketplaceService from '../services/marketplaceService';

export default {
  name: 'PaymentStatusPage',
  setup() {
    const router = useRouter();
    const route = useRoute();
    const loading = ref(true);
    const success = ref(false);
    const errorMessage = ref('');
    const orderReference = ref('');
    const transactionId = ref('');
    const amount = ref('');
    
    onMounted(async () => {
      try {
        // Get query parameters from URL
        const urlParams = route.query;
        // console.log('Payment status URL params:', urlParams);
        
        // Check if we have billcode and order_id for verification
        if (urlParams.billcode && urlParams.order_id) {
          // This is a direct callback from ToyyibPay - verify the payment
          await marketplaceService.verifyPayment({
            billcode: urlParams.billcode,
            order_id: urlParams.order_id,
            status_id: urlParams.status_id,
            transaction_id: urlParams.transaction_id || ''
          });
        }
        
        // Process the status parameters
        const isSuccess = urlParams.success === 'true';
        const message = urlParams.message || '';
        const orderId = urlParams.order_id || '';
        const reference = urlParams.reference || '';
        const txnId = urlParams.transaction_id || '';
        const amountValue = urlParams.amount || '';
        
        // Store values
        success.value = isSuccess;
        orderReference.value = orderId || reference || 'N/A';
        transactionId.value = txnId || 'N/A';
        amount.value = amountValue || 'N/A';
        
        if (!isSuccess) {
          errorMessage.value = message || 'Payment was not successful. Please try again.';
        }
      } catch (error) {
        // console.error('Error processing payment status:', error);
        success.value = false;
        errorMessage.value = 'An error occurred while processing your payment. Please contact support.';
      } finally {
        loading.value = false;
      }
    });
    
    const goToMarketplace = () => {
      router.push({ name: 'Marketplace' });
    };
    
    const viewOrders = () => {
      // Navigate to OrderManagement page
      router.push({ name: 'OrderManagement' });
    };
    
    const tryAgain = () => {
      router.push({ name: 'Marketplace' });
    };
    
    return {
      loading,
      success,
      errorMessage,
      orderReference,
      transactionId,
      amount,
      goToMarketplace,
      viewOrders,
      tryAgain
    };
  }
}
</script>

<style scoped>
.payment-status-container {
  max-width: 600px;
  margin: 5rem auto;
  padding: 0 1rem;
}

.success-icon, .error-icon {
  margin-bottom: 1.5rem;
}

.card {
  border-radius: 10px;
  overflow: hidden;
}
</style>