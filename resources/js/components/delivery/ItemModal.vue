<template>
  <div class="modal fade" :id="modalId" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header theme-header">
          <h5 class="modal-title" id="itemModalLabel">
            <i class="fas fa-box me-2"></i>Delivery Items
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-3">
          <!-- Summary Section -->
          <div class="delivery-summary mb-4">
            <div class="total-items">
              <span class="label">Total Items:</span>
              <span class="value">{{ items.length }}</span>
            </div>
            <div class="total-value">
              <span class="label">Total Value:</span>
              <span class="value">RM {{ typeof totalValue === 'number' ? totalValue.toFixed(2) : '0.00' }}</span>
            </div>
          </div>

          <!-- Items Grid -->
          <div class="items-grid">
            <div v-for="item in items" :key="item.itemID" class="item-card">
              <div class="card h-100">
                <div class="card-header item-header">
                  <span class="item-id">#{{ item.itemID }}</span>
                </div>
                <div class="card-body">
                  <h6 class="card-title d-flex align-items-center">
                    <i class="fas fa-cube me-2"></i>{{ item.name }}
                  </h6>
                  <div class="item-details">
                    <div class="detail-row">
                      <i class="fas fa-weight-hanging text-muted me-2"></i>
                      <span class="detail-label">Quantity:</span>
                      <span class="detail-value">{{ item.measurement_value }} {{ item.measurement_type }}</span>
                    </div>
                    <div class="detail-row">
                      <i class="fas fa-tag text-muted me-2"></i>
                      <span class="detail-label">Price:</span>
                      <span class="detail-value price">RM {{ item.price.toFixed(2) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ItemModal',
  props: {
    modalId: {
      type: String,
      required: true
    },
    items: {
      type: Array,
      required: true
    }
  },
  computed: {
    totalValue() {
      return this.items.reduce((sum, item) => {
        // Make sure item.price is a number before adding
        const itemPrice = parseFloat(item.price) || 0;
        return sum + itemPrice;
      }, 0);
    }
  }
};
</script>

<style scoped>
.modal-content {
  --primary-color: #123524;
  --secondary-color: #EFE3C2;
  --accent-color: #3E7B27;
  --text-color: #333;
  --light-text: #666;
  --border-color: rgba(18, 53, 36, 0.2);
  --light-bg: rgba(239, 227, 194, 0.2);
  border-color: var(--border-color);
}

.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
}

.delivery-summary {
  background-color: var(--light-bg);
  border-radius: 0.5rem;
  padding: 1rem;
  display: flex;
  justify-content: space-around;
  align-items: center;
  border: 1px solid var(--border-color);
}

.delivery-summary .label {
  color: var(--light-text);
  font-size: 0.9rem;
  margin-right: 0.5rem;
}

.delivery-summary .value {
  color: var(--primary-color);
  font-weight: 600;
  font-size: 1.1rem;
}

.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1rem;
}

.item-card {
  transition: transform 0.2s;
}

.item-card:hover {
  transform: translateY(-2px);
}

.card {
  border-color: var(--border-color);
  background-color: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.item-header {
  background-color: var(--light-bg);
  padding: 0.5rem 1rem;
  border-bottom: 1px solid var(--border-color);
}

.item-id {
  color: var(--light-text);
  font-size: 0.9rem;
  font-weight: 500;
}

.card-title {
  color: var(--primary-color);
  font-weight: 600;
  margin-bottom: 1rem;
  font-size: 1.1rem;
}

.item-details .detail-row {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
  color: var(--text-color);
}

.detail-label {
  color: var(--light-text);
  margin-right: 0.5rem;
  font-size: 0.9rem;
}

.detail-value {
  font-weight: 500;
}

.detail-value.price {
  color: var(--accent-color);
  font-weight: 600;
}

@media (max-width: 576px) {
  .delivery-summary {
    flex-direction: column;
    text-align: center;
    gap: 0.5rem;
  }

  .items-grid {
    grid-template-columns: 1fr;
  }
}
</style>