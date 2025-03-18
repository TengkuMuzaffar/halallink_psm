/**
 * Custom Bootstrap 5.3 Modal Utility
 * Provides easy-to-use modal dialogs for different scenarios
 */

// Modal types with their corresponding Bootstrap classes and icons
const MODAL_TYPES = {
  info: {
    headerClass: 'bg-info text-white',
    icon: 'bi bi-info-circle-fill'
  },
  success: {
    headerClass: 'bg-success text-white',
    icon: 'bi bi-check-circle-fill'
  },
  warning: {
    headerClass: 'bg-warning text-dark',
    icon: 'bi bi-exclamation-triangle-fill'
  },
  danger: {
    headerClass: 'bg-danger text-white',
    icon: 'bi bi-x-circle-fill'
  }
};

// Counter to generate unique IDs for modals
let modalCounter = 0;

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
  const {
    type = 'info',
    title = '',
    message = '',
    showClose = true,
    buttons = [{ label: 'Close', type: 'secondary', dismiss: true }],
    onShown = null,
    onHidden = null
  } = options;

  // Generate unique ID for this modal
  const modalId = `modal-${++modalCounter}`;
  
  // Get modal type configuration
  const modalType = MODAL_TYPES[type] || MODAL_TYPES.info;
  
  // Create modal HTML
  const modalHTML = `
    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}-label" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header ${modalType.headerClass}">
            <h5 class="modal-title" id="${modalId}-label">
              <i class="${modalType.icon} me-2"></i>
              ${title}
            </h5>
            ${showClose ? '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>' : ''}
          </div>
          <div class="modal-body">
            ${message}
          </div>
          ${buttons.length > 0 ? `
            <div class="modal-footer">
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

// Export default object with all methods
export default {
  show: showModal,
  info: showInfo,
  success: showSuccess,
  warning: showWarning,
  danger: showDanger,
  confirm: showConfirm
};