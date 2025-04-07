/**
 * Custom Bootstrap 5.3 Modal Utility
 * Provides easy-to-use modal dialogs for different scenarios
 */

// Import Bootstrap directly to ensure it's available
import * as bootstrap from 'bootstrap';

// Modal types with their corresponding Bootstrap classes and icons
const MODAL_TYPES = {
  info: {
    headerClass: 'bg-info text-white',
    icon: 'bi bi-info-circle-fill',
    color: '#0dcaf0'
  },
  success: {
    headerClass: 'bg-success text-white',
    icon: 'bi bi-check-circle-fill',
    color: '#198754'
  },
  warning: {
    headerClass: 'bg-warning text-dark',
    icon: 'bi bi-exclamation-triangle-fill',
    color: '#ffc107'
  },
  danger: {
    headerClass: 'bg-danger text-white',
    icon: 'bi bi-x-circle-fill',
    color: '#dc3545'
  },
  loading: {
    headerClass: 'bg-primary text-white',
    icon: 'bi bi-arrow-repeat',
    color: '#0d6efd'
  }
};

// Counter to generate unique IDs for modals
let modalCounter = 0;

// Reference to the current loading modal
let currentLoadingModal = null;

// Add the animated modal styles to the document
const addAnimatedModalStyles = () => {
  // Check if styles are already added
  if (document.getElementById('animated-modal-styles')) {
    return;
  }

  const styles = `
    .modal-icon-container {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .modal-icon {
      font-size: 4rem;
      animation: iconBounce 0.6s ease-out;
    }
    
    .modal-icon.loading-icon {
      animation: iconSpin 1.5s linear infinite;
    }
    
    .modal-centered-content {
      text-align: center;
    }
    
    .modal-centered-title {
      margin-bottom: 15px;
      font-weight: 600;
    }
    
    @keyframes iconBounce {
      0% { transform: scale(0); opacity: 0; }
      50% { transform: scale(1.2); }
      100% { transform: scale(1); opacity: 1; }
    }
    
    @keyframes iconSpin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `;

  const styleElement = document.createElement('style');
  styleElement.id = 'animated-modal-styles';
  styleElement.textContent = styles;
  document.head.appendChild(styleElement);
};

/**
 * Creates and shows a modal dialog
 * @param {Object} options - Modal configuration options
 * @param {string} options.type - Modal type: 'info', 'success', 'warning', 'danger'
 * @param {string} options.title - Modal title
 * @param {string} options.message - Modal message content
 * @param {boolean} options.showClose - Whether to show close button in header (default: true)
 * @param {Array} options.buttons - Array of button configurations
 * @param {Function} options.onShown - Callback when modal is shown
 * @param {Function} options.onHidden - Callback when modal is hidden
 * @returns {Object} - Modal instance with control methods
 */
export const showModal = (options) => {
  // Check if bootstrap is available
  if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
    console.error('Bootstrap is not available. Make sure it is properly imported.');
    return null;
  }

  // Add animated styles
  addAnimatedModalStyles();

  const {
    type = 'info',
    title = '',
    message = '',
    showClose = true,
    buttons = [{ label: 'Close', type: 'secondary', dismiss: true }],
    onShown = null,
    onHidden = null,
    customModalBody = null
  } = options;

  // Generate unique ID for this modal
  const modalId = `modal-${++modalCounter}`;
  
  // Get modal type configuration
  const modalType = MODAL_TYPES[type] || MODAL_TYPES.info;
  
  // Create modal body content
  const modalBody = customModalBody 
    ? customModalBody(modalType, message)
    : `
      <div class="modal-body modal-centered-content">
        <div class="modal-icon-container">
          <i class="${modalType.icon} modal-icon${type === 'loading' ? ' loading-icon' : ''}" style="color: ${modalType.color};"></i>
        </div>
        <div class="modal-message">
          ${message}
        </div>
      </div>
    `;
  
  // Create modal HTML with centered content and animated icon
  const modalHTML = `
    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}-label">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header ${modalType.headerClass} justify-content-center">
            <h5 class="modal-title" id="${modalId}-label">
              ${title}
            </h5>
            ${showClose ? '<button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 mt-2 me-2" data-bs-dismiss="modal" aria-label="Close"></button>' : ''}
          </div>
          ${modalBody}
          ${buttons.length > 0 ? `
            <div class="modal-footer justify-content-center">
              ${buttons.map(btn => `
                <button type="button" class="btn btn-${btn.type || 'primary'}" 
                  ${btn.id ? `id="${btn.id}"` : ''} 
                  ${btn.dismiss ? 'data-bs-dismiss="modal"' : ''}>
                  ${btn.label}
                </button>
              `).join('')}
            </div>
          ` : ''}
        </div>
      </div>
    </div>
  `;
  
  // Add modal to DOM
  document.body.insertAdjacentHTML('beforeend', modalHTML);
  
  // Get the modal element
  const modalElement = document.getElementById(modalId);
  
  // Initialize Bootstrap modal
  const modalInstance = new bootstrap.Modal(modalElement);
  
  // Add event listeners for buttons
  buttons.forEach((btn, index) => {
    if (btn.onClick) {
      const buttonElement = modalElement.querySelectorAll('.modal-footer button')[index];
      buttonElement.addEventListener('click', (event) => {
        btn.onClick(event, modalInstance);
      });
    }
  });
  
  // Add event listeners for modal events
  if (onShown) {
    modalElement.addEventListener('shown.bs.modal', onShown);
  }
  
  if (onHidden) {
    modalElement.addEventListener('hidden.bs.modal', onHidden);
  }
  
  // Fix for aria-hidden accessibility issue
  modalElement.addEventListener('hide.bs.modal', () => {
    // Find all focusable elements in the modal
    const focusableElements = modalElement.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    // Remove focus from any elements inside the modal before it's hidden
    if (focusableElements.length > 0) {
      // Check if any element inside the modal has focus
      const activeElement = document.activeElement;
      if (modalElement.contains(activeElement) && activeElement instanceof HTMLElement) {
        // Store reference to the focused element
        activeElement.dataset.lastFocused = 'true';
        activeElement.blur();
        
        // Move focus to the body explicitly
        document.body.focus();
        
        // Temporarily disable focus for all focusable elements
        focusableElements.forEach(el => {
          if (el instanceof HTMLElement) {
            el.dataset.originalTabindex = el.tabIndex;
            el.tabIndex = -1;
          }
        });
      }
    }
  });

  // Restore original state when modal is fully hidden
  modalElement.addEventListener('hidden.bs.modal', () => {
    const focusableElements = modalElement.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]'
    );
    
    // Restore original tabindex values
    focusableElements.forEach(el => {
      if (el.dataset.originalTabindex) {
        el.tabIndex = Number(el.dataset.originalTabindex);
        delete el.dataset.originalTabindex;
      }
    });
    
    // Remove temporary focus marker
    const lastFocused = document.querySelector('[data-last-focused]');
    if (lastFocused) {
      delete lastFocused.dataset.lastFocused;
    }
  });
  
  // Add listener to remove modal from DOM after it's hidden
  modalElement.addEventListener('hidden.bs.modal', () => {
    modalElement.remove();
  });
  
  // Show the modal
  modalInstance.show();
  
  // Return modal control object
  return {
    hide: () => modalInstance.hide(),
    element: modalElement,
    instance: modalInstance
  };
};

/**
 * Shows an info modal
 * @param {string} title - Modal title
 * @param {string} message - Modal message
 * @param {Object} options - Additional modal options
 * @returns {Object} - Modal instance
 */
export const showInfo = (title, message, options = {}) => {
  return showModal({
    type: 'info',
    title,
    message,
    ...options
  });
};

/**
 * Shows a success modal
 * @param {string} title - Modal title
 * @param {string} message - Modal message
 * @param {Object} options - Additional modal options
 * @returns {Object} - Modal instance
 */
export const showSuccess = (title, message, options = {}) => {
  return showModal({
    type: 'success',
    title,
    message,
    ...options
  });
};

/**
 * Shows a warning modal
 * @param {string} title - Modal title
 * @param {string} message - Modal message
 * @param {Object} options - Additional modal options
 * @returns {Object} - Modal instance
 */
export const showWarning = (title, message, options = {}) => {
  return showModal({
    type: 'warning',
    title,
    message,
    ...options
  });
};

/**
 * Shows a danger/error modal
 * @param {string} title - Modal title
 * @param {string} message - Modal message
 * @param {Object} options - Additional modal options
 * @returns {Object} - Modal instance
 */
export const showDanger = (title, message, options = {}) => {
  return showModal({
    type: 'danger',
    title,
    message,
    ...options
  });
};

/**
 * Shows a confirmation modal
 * @param {string} title - Modal title
 * @param {string} message - Modal message
 * @param {Function} onConfirm - Callback when confirmed
 * @param {Function} onCancel - Callback when canceled
 * @param {Object} options - Additional modal options
 * @returns {Object} - Modal instance
 */
export const showConfirm = (title, message, onConfirm, onCancel = null, options = {}) => {
  return showModal({
    type: options.type || 'warning',
    title,
    message,
    buttons: [
      {
        label: options.cancelLabel || 'Cancel',
        type: 'secondary',
        dismiss: true,
        onClick: (_, modal) => {
          if (onCancel) onCancel();
        }
      },
      {
        label: options.confirmLabel || 'Confirm',
        type: options.confirmType || 'primary',
        onClick: (_, modal) => {
          onConfirm();
          modal.hide();
        }
      }
    ],
    ...options
  });
};

/**
 * Shows a loading modal
 * @param {string} title - Modal title
 * @param {string} message - Modal message
 * @param {Object} options - Additional modal options
 * @returns {Object} - Modal instance
 */
export const showLoading = (title, message, options = {}) => {
  // Close any existing loading modal
  if (currentLoadingModal) {
    currentLoadingModal.hide();
  }
  
  // Create new loading modal with no close button and no footer buttons
  currentLoadingModal = showModal({
    type: 'loading',
    title,
    message,
    showClose: false,
    buttons: [],
    ...options
  });
  
  return currentLoadingModal;
};

/**
 * Closes the current loading modal if it exists
 */
export const closeLoading = () => {
  if (currentLoadingModal) {
    currentLoadingModal.hide();
    currentLoadingModal = null;
  }
};

// Export default object with all methods
export default {
  show: showModal,
  info: showInfo,
  success: showSuccess,
  warning: showWarning,
  danger: showDanger,
  confirm: showConfirm,
  loading: showLoading,
  close: closeLoading
};