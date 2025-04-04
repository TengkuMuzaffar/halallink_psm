/**
 * Custom Cart Modal Utility
 * Provides specialized modal dialogs for cart operations
 */

// Import Bootstrap directly to ensure it's available
import * as bootstrap from 'bootstrap';
import api from '../../utils/api';

// Counter to generate unique IDs for modals
let cartModalCounter = 0;

// Add the cart modal styles to the document
const addCartModalStyles = () => {
  // Check if styles are already added
  if (document.getElementById('cart-modal-styles')) {
    return;
  }

  const styles = `
    .cart-modal .modal-dialog {
      max-width: 500px;
    }
    
    .cart-modal .modal-content {
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .cart-modal .modal-header {
      background-color: #123524;
      color: white;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;
      padding: 15px 20px;
    }
    
    .cart-modal .modal-body {
      padding: 20px;
    }
    
    .cart-modal .modal-footer {
      border-top: 1px solid #eee;
      padding: 15px 20px;
    }
    
    .cart-item-image {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 5px;
    }
    
    .cart-item-details {
      padding-left: 15px;
    }
    
    .cart-item-title {
      font-weight: 600;
      margin-bottom: 5px;
    }
    
    .cart-item-price {
      font-weight: 700;
      color: #123524;
      margin-bottom: 5px;
    }
    
    .cart-item-seller {
      font-size: 0.85rem;
      color: #666;
    }
    
    .cart-item-quantity {
      width: 80px;
    }
    
    .cart-item {
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;
    }
    
    .cart-item:last-child {
      margin-bottom: 0;
      padding-bottom: 0;
      border-bottom: none;
    }
    
    .cart-total {
      font-size: 1.2rem;
      font-weight: 700;
      color: #123524;
      text-align: right;
      margin-top: 15px;
    }
    
    .quantity-input {
      width: 120px;
      text-align: center;
      margin: 0 auto;
    }
    
    .quantity-input .form-control {
      text-align: center;
    }
    
    .quantity-input .btn {
      padding: 0.375rem 0.5rem;
    }
    
    .add-to-cart-item {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .add-to-cart-image {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 5px;
      margin-right: 15px;
    }
    
    .add-to-cart-details {
      flex: 1;
    }
    
    .add-to-cart-title {
      font-weight: 600;
      font-size: 1.1rem;
      margin-bottom: 5px;
    }
    
    .add-to-cart-price {
      font-weight: 700;
      color: #123524;
      margin-bottom: 10px;
    }
    
    .add-to-cart-seller {
      font-size: 0.85rem;
      color: #666;
      margin-bottom: 5px;
    }
    
    .add-to-cart-location {
      font-size: 0.85rem;
      color: #666;
    }
    
    .cart-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background-color: #dc3545;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      font-size: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .cart-button {
      position: relative;
    }
    
    @keyframes cartShake {
      0% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      50% { transform: translateX(5px); }
      75% { transform: translateX(-5px); }
      100% { transform: translateX(0); }
    }
    
    .cart-shake {
      animation: cartShake 0.5s ease-in-out;
    }
    
    .checkout-btn {
      background-color: #123524;
      border-color: #123524;
    }
    
    .checkout-btn:hover {
      background-color: #0d2a1c;
      border-color: #0d2a1c;
    }
  `;

  const styleElement = document.createElement('style');
  styleElement.id = 'cart-modal-styles';
  styleElement.textContent = styles;
  document.head.appendChild(styleElement);
};

/**
 * Format price to display with 2 decimal places
 * @param {Number|String} price - Price to format
 * @returns {String} - Formatted price
 */
const formatPrice = (price) => {
  return parseFloat(price).toFixed(2);
};

/**
 * Shows a modal for adding an item to the cart
 * @param {Object} product - Product to add to cart
 * @returns {Promise} - Promise that resolves with the modal result
 */
const showAddToCartModal = (product) => {
  return new Promise((resolve) => {
    // Check if bootstrap is available
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
      console.error('Bootstrap is not available. Make sure it is properly imported.');
      resolve({ confirmed: false });
      return;
    }

    // Add cart modal styles
    addCartModalStyles();

    // Generate unique ID for this modal
    const modalId = `cart-modal-${++cartModalCounter}`;
    
    // Create modal HTML
    const modalHTML = `
      <div class="modal fade cart-modal" id="${modalId}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Add to Cart</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="add-to-cart-item">
                <img 
                  src="${product.item_image || '/images/no-image.jpg'}" 
                  alt="${product.name}"
                  class="add-to-cart-image"
                >
                <div class="add-to-cart-details">
                  <h5 class="add-to-cart-title">${product.name}</h5>
                  <p class="add-to-cart-price">RM ${formatPrice(product.price)}</p>
                  <p class="add-to-cart-seller">
                    <i class="bi bi-shop me-1"></i>${product.seller || 'Unknown Seller'}
                  </p>
                  <p class="add-to-cart-location">
                    <i class="bi bi-geo-alt me-1"></i>${product.location || 'Unknown Location'}
                  </p>
                </div>
              </div>
              
              <div class="text-center mt-4">
                <label for="quantity-input" class="form-label">Quantity:</label>
                <div class="input-group quantity-input">
                  <button class="btn btn-outline-secondary" type="button" id="${modalId}-decrease">-</button>
                  <input type="number" class="form-control" id="${modalId}-quantity" value="1" min="1" max="99">
                  <button class="btn btn-outline-secondary" type="button" id="${modalId}-increase">+</button>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary" id="${modalId}-add">Add to Cart</button>
            </div>
          </div>
        </div>
      </div>
    `;
    
    // Add modal to document
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Get modal element
    const modalEl = document.getElementById(modalId);
    
    // Initialize modal
    const modalInstance = new bootstrap.Modal(modalEl);
    
    // Get quantity input and buttons
    const quantityInput = document.getElementById(`${modalId}-quantity`);
    const decreaseBtn = document.getElementById(`${modalId}-decrease`);
    const increaseBtn = document.getElementById(`${modalId}-increase`);
    const addBtn = document.getElementById(`${modalId}-add`);
    
    // Add event listeners for quantity buttons
    decreaseBtn.addEventListener('click', () => {
      const currentValue = parseInt(quantityInput.value) || 1;
      if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
      }
    });
    
    increaseBtn.addEventListener('click', () => {
      const currentValue = parseInt(quantityInput.value) || 1;
      if (currentValue < 99) {
        quantityInput.value = currentValue + 1;
      }
    });
    
    // Add event listener for add button
    addBtn.addEventListener('click', () => {
      const quantity = parseInt(quantityInput.value) || 1;
      modalInstance.hide();
      resolve({ confirmed: true, quantity });
    });
    
    // Add event listener for modal hidden event
    modalEl.addEventListener('hidden.bs.modal', () => {
      // Remove modal from DOM after it's hidden
      modalEl.remove();
      
      // Resolve with cancelled if not already resolved
      resolve({ confirmed: false });
    });
    
    // Show modal
    modalInstance.show();
  });
};

/**
 * Shows a modal with cart items
 * @param {Object} cartData - Cart data from API
 * @returns {Promise} - Promise that resolves with the modal result
 */
const showCartModal = (cartData) => {
  return new Promise((resolve) => {
    // Check if bootstrap is available
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
      console.error('Bootstrap is not available. Make sure it is properly imported.');
      resolve({ confirmed: false });
      return;
    }

    // Add cart modal styles
    addCartModalStyles();

    // Generate unique ID for this modal
    const modalId = `cart-modal-${++cartModalCounter}`;
    
    // Get cart items and total
    const cartItems = cartData.cart_items || [];
    const cartTotal = cartData.cart_total || 0;
    const cartCount = cartData.cart_count || 0;
    
    // Create cart items HTML
    let cartItemsHTML = '';
    
    if (cartItems.length === 0) {
      cartItemsHTML = `
        <div class="text-center py-4">
          <i class="bi bi-cart3" style="font-size: 3rem; color: #ccc;"></i>
          <p class="mt-3">Your cart is empty</p>
        </div>
      `;
    } else {
      cartItems.forEach(item => {
        const itemData = item.item || {};
        cartItemsHTML += `
          <div class="cart-item" data-cart-id="${item.cartID}">
            <div class="d-flex">
              <img 
                src="${itemData.item_image || '/images/no-image.jpg'}" 
                alt="${itemData.name || 'Product'}"
                class="cart-item-image"
              >
              <div class="cart-item-details flex-grow-1">
                <h6 class="cart-item-title">${itemData.name || 'Product'}</h6>
                <p class="cart-item-price">RM ${formatPrice(item.price_at_purchase)}</p>
                <p class="cart-item-seller">
                  <i class="bi bi-shop me-1"></i>${itemData.user?.name || 'Unknown Seller'}
                </p>
              </div>
              <div class="d-flex flex-column align-items-end">
                <div class="input-group cart-item-quantity mb-2">
                  <input 
                    type="number" 
                    class="form-control form-control-sm quantity-input" 
                    value="${item.quantity}" 
                    min="1" 
                    max="99"
                    data-cart-id="${item.cartID}"
                  >
                </div>
                <button 
                  class="btn btn-sm btn-outline-danger remove-item-btn"
                  data-cart-id="${item.cartID}"
                >
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
        `;
      });
      console.log("Testing Info: "+item);

      // Add cart total
      cartItemsHTML += `
        <div class="cart-total">
          Total: RM ${formatPrice(cartTotal)}
        </div>
      `;
    }
    
    // Create modal HTML
    const modalHTML = `
      <div class="modal fade cart-modal" id="${modalId}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Your Cart (${cartCount} items)</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              ${cartItemsHTML}
            </div>
            <div class="modal-footer">
              ${cartItems.length > 0 ? 
                `<button type="button" class="btn btn-primary checkout-btn" id="${modalId}-checkout">Proceed to Checkout</button>` : 
                ''}
            </div>
          </div>
        </div>
      </div>
    `;
    
    // Add modal to document
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Get modal element
    const modalEl = document.getElementById(modalId);
    
    // Initialize modal
    const modalInstance = new bootstrap.Modal(modalEl);
    
    // Add event listeners for quantity inputs
    const quantityInputs = modalEl.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
      let timeout;
      input.addEventListener('input', (e) => {
        clearTimeout(timeout);
        timeout = setTimeout(async () => {
          const cartID = e.target.dataset.cartId;
          const quantity = parseInt(e.target.value) || 1;
          
          if (quantity < 1) {
            e.target.value = 1;
            return;
          }
          
          if (quantity > 99) {
            e.target.value = 99;
            return;
          }
          
          try {
            // Update cart item
            await api.put('/api/cart/update', {
              cartID: cartID,
              quantity: quantity
            });
            
            // Refresh cart modal
            modalInstance.hide();
            const newCartData = await api.get('/api/cart/items');
            showCartModal(newCartData.data);
          } catch (error) {
            console.error('Error updating cart item:', error);
          }
        }, 500);
      });
    });
    
    // Add event listeners for remove buttons
    const removeButtons = modalEl.querySelectorAll('.remove-item-btn');
    removeButtons.forEach(button => {
      button.addEventListener('click', async () => {
        const cartID = button.dataset.cartId;
        
        try {
          // Remove cart item
          await api.delete(`/api/cart/remove/${cartID}`);
          
          // Refresh cart modal
          modalInstance.hide();
          const newCartData = await api.get('/api/cart/items');
          showCartModal(newCartData.data);
        } catch (error) {
          console.error('Error removing cart item:', error);
        }
      });
    });
    
    // Add event listener for checkout button
    const checkoutBtn = document.getElementById(`${modalId}-checkout`);
    if (checkoutBtn) {
      checkoutBtn.addEventListener('click', () => {
        modalInstance.hide();
        resolve({ action: 'checkout' });
      });
    }
    
    // Add event listener for modal hidden event
    modalEl.addEventListener('hidden.bs.modal', () => {
      // Remove modal from DOM after it's hidden
      modalEl.remove();
      
      // Resolve with cancelled if not already resolved
      resolve({ action: 'close' });
    });
    
    // Show modal
    modalInstance.show();
  });
};

export default {
  showAddToCartModal,
  showCartModal
};