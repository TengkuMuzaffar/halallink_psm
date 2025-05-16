<template>
  <div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content theme-modal">
        <div class="modal-header theme-header">
          <h5 class="modal-title" id="qrScannerModalLabel">
            <i class="fas fa-qrcode me-2"></i>Scan QR Code
          </h5>
          <button type="button" class="btn-close theme-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body theme-body p-0">
          <div v-if="loading" class="text-center p-5">
            <div class="spinner-border theme-spinner" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 theme-text fw-bold">Initializing camera...</p>
          </div>
          <div v-else>
            <div class="scanner-header text-center p-3">
              <p class="mb-0"><i class="fas fa-crosshairs me-2"></i>Position QR code within the scanner frame</p>
            </div>
            
            <div class="qr-scanner-container">
              <!-- Remove the scanner-overlay since the library provides its own -->
              <div id="reader" class="qr-reader"></div>
            </div>
            
            <div v-if="error" class="alert alert-danger m-3">
              <i class="fas fa-exclamation-triangle me-2"></i>{{ error }}
            </div>
            
            <div v-if="scanResult" class="alert alert-success m-3">
              <i class="fas fa-check-circle me-2"></i>QR Code detected! Redirecting...
            </div>
            
            <!-- Add debug info -->
            <div v-if="!scanResult && !error" class="alert alert-info m-3">
              <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Troubleshooting</h6>
              <p class="mb-1">If you can't see the video feed, try:</p>
              <ul class="mb-1">
                <li>Checking browser permissions</li>
                <li>Refreshing the page</li>
                <li><strong>HTTP Solution:</strong> For Chrome, run with this flag:
                  <div class="code-block mt-1">
                    <code>chrome --unsafely-treat-insecure-origin-as-secure={{ httpOrigin }} --user-data-dir=C:\ChromeDevSession</code>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <div class="modal-footer theme-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">
            <i class="fas fa-times me-1"></i>Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { post } from '../../utils/api';

export default {
  name: 'QRScannerModal',
  props: {
    deliveryID: {
      type: [Number, String],
      required: true
    },
    locationID: {
      type: [Number, String],
      required: true
    },
    checkpoints: {
      type: Array,
      required: true
    }
  },
  emits: ['scanner-closed'],
  setup(props, { emit }) {
    const router = useRouter();
    const loading = ref(true);
    const error = ref(null);
    const scanResult = ref(null);
    const modal = ref(null);
    const httpOrigin = ref('');
    let html5QrcodeScanner = null;
    let lastMessage = null;

    // Function to check if DOM is ready
    function docReady(fn) {
      if (document.readyState === "complete" || document.readyState === "interactive") {
        setTimeout(fn, 1);
      } else {
        document.addEventListener("DOMContentLoaded", fn);
      }
    }

    // Success callback when QR code is scanned
    function onScanSuccess(decodedText, decodedResult) {
      if (lastMessage !== decodedText) {
        lastMessage = decodedText;
        scanResult.value = decodedText;
        
        // Process the QR code URL
        try {
          // Validate the URL
          let url = decodedText;
          if (!url.startsWith('http')) {
            url = window.location.origin + (url.startsWith('/') ? url : '/' + url);
          }
          
          // Check if the URL is from the same domain for security
          const urlObj = new URL(url);
          const currentDomain = window.location.hostname;
          
          if (urlObj.hostname !== currentDomain) {
            error.value = 'Security Error: QR code URL is from a different domain. Only URLs from the current domain are allowed.';
            scanResult.value = null;
            return;
          }
          
          // Extract location ID and company ID from URL if possible
          let urlLocationID = props.locationID;
          let companyID = null;
          
          try {
            // Try to extract IDs from URL path segments
            const pathSegments = urlObj.pathname.split('/').filter(segment => segment);
            
            // The URL format is typically /api/qrcode/process/{locationID}/{companyID}
            // Find numeric segments that might be the IDs
            const numericSegments = pathSegments.filter(segment => !isNaN(segment));
            
            if (numericSegments.length >= 2) {
              urlLocationID = parseInt(numericSegments[0]);
              companyID = parseInt(numericSegments[1]);
              console.log('Extracted from URL - Location ID:', urlLocationID, 'Company ID:', companyID);
            }
          } catch (err) {
            console.warn('Could not extract IDs from URL, using props value:', err);
          }
          
          // Prepare data to post
          const postData = {
            deliveryID: props.deliveryID,
            locationID: urlLocationID,
            checkpoints: props.checkpoints,
            companyID: companyID // Add company ID if available
          };
          
          // Log the data being sent
          console.log('Sending data to server:', {
            url: url,
            postData: postData
          });
          
          // Show success message
          scanResult.value = 'QR code detected! Processing...';
          
          // Use the API utility instead of fetch
          post(url, postData, {
            onSuccess: (data) => {
              if (data.status === 'success') {
                // Close modal and redirect to verification page
                setTimeout(() => {
                  closeModal();
                  
                  // Redirect to the verification page with proper parameter formatting
                  const verifyUrl = `/verify/${urlLocationID}/${props.deliveryID}`;
                  console.log('Navigating to:', verifyUrl);
                  
                  // Use router.push with named route and params object instead of path string
                  router.push({
                    name: 'VerifyDelivery',
                    params: {
                      locationID: urlLocationID.toString(),
                      deliveryID: props.deliveryID.toString()
                    }
                  }).catch(err => {
                    console.error('Navigation error:', err);
                    // Fallback to window.location if router navigation fails
                    window.location.href = verifyUrl;
                  });
                }, 1000);
              } else {
                // Show error message
                error.value = data.message || 'Error processing QR code';
                scanResult.value = null;
              }
            },
            onError: (err) => {
              console.error('API Error Details:', err);
              
              // Check if there's a response with error details
              if (err.response && err.response.data) {
                error.value = `Error processing QR code: ${err.response.data.message || err.message}`;
                console.error('Server error details:', err.response.data);
              } else {
                error.value = `Error processing QR code: ${err.message || 'Unknown error'}`;
              }
              
              scanResult.value = null;
            }
          });
        } catch (err) {
          error.value = `Error processing QR code: ${err.message || 'Unknown error'}`;
          scanResult.value = null;
        }
      }
    }

    // Function to calculate QR box size based on viewport
    function qrboxFunction(viewfinderWidth, viewfinderHeight) {
      // Square QR Box, with size = 75% of the min edge.
      const minEdgeSizeThreshold = 250;
      const edgeSizePercentage = 0.75;
      const minEdgeSize = (viewfinderWidth > viewfinderHeight) ?
          viewfinderHeight : viewfinderWidth;
      const qrboxEdgeSize = Math.floor(minEdgeSize * edgeSizePercentage);
      
      if (qrboxEdgeSize < minEdgeSizeThreshold) {
        if (minEdgeSize < minEdgeSizeThreshold) {
          return {width: minEdgeSize, height: minEdgeSize};
        } else {
          return {
            width: minEdgeSizeThreshold,
            height: minEdgeSizeThreshold
          };
        }
      }
      return {width: qrboxEdgeSize, height: qrboxEdgeSize};
    }

    onMounted(() => {
      modal.value = new Modal(document.getElementById('qrScannerModal'));
      
      // Store HTTP origin for Chrome flag
      httpOrigin.value = window.location.origin;
      
      // Load the HTML5 QR code scanner library dynamically
      const script = document.createElement('script');
      script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
      script.async = true;
      script.onload = () => {
        loading.value = false;
        
        // Initialize scanner when script is loaded
        docReady(() => {
          try {
            html5QrcodeScanner = new Html5QrcodeScanner(
              "reader",
              {
                fps: 10,
                qrbox: qrboxFunction,
                experimentalFeatures: {
                  useBarCodeDetectorIfSupported: true
                },
                rememberLastUsedCamera: true,
                showTorchButtonIfSupported: true,
                // Hide the UI elements that are distracting
                formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ],
                // Hide the select element
                hideSelectScanType: true,
                // Hide the camera selection dropdown
                showVideoSourcesSelect: false
              }
            );
            
            html5QrcodeScanner.render(onScanSuccess);
            console.log('QR scanner initialized successfully');
            
            // Apply custom styling to the scanner UI after rendering
            setTimeout(() => {
              // Hide the built-in camera selection UI
              const selectElement = document.querySelector('#reader select');
              if (selectElement) {
                selectElement.style.display = 'none';
              }
              
              // Hide any other UI elements that might be distracting
              const scanTypeSelector = document.querySelector('#reader__scan_type_change_button');
              if (scanTypeSelector) {
                scanTypeSelector.style.display = 'none';
              }
              
              // Add custom styling to the stop button if it exists
              const stopButton = document.querySelector('#reader__dashboard_section_swaplink');
              if (stopButton) {
                stopButton.className = 'btn btn-sm btn-danger mt-2';
                stopButton.innerHTML = '<i class="fas fa-stop me-1"></i>Stop Scanning';
              }
            }, 500);
          } catch (err) {
            console.error('Error initializing QR scanner:', err);
            error.value = `Failed to initialize scanner: ${err.message || 'Unknown error'}`;
          }
        });
      };
      
      script.onerror = () => {
        error.value = 'Failed to load QR scanner library. Please try again.';
        loading.value = false;
      };
      
      document.head.appendChild(script);
    });

    onBeforeUnmount(() => {
      // Clean up scanner if it exists
      if (html5QrcodeScanner) {
        try {
          html5QrcodeScanner.clear();
          console.log('QR scanner cleared successfully');
        } catch (err) {
          console.error('Error clearing QR scanner:', err);
        }
      }
    });

    const showModal = () => {
      if (modal.value) {
        modal.value.show();
      }
    };

    const closeModal = () => {
      if (modal.value) {
        modal.value.hide();
        
        // Emit an event to notify parent that QR scanner is closed
        emit('scanner-closed');
      }
    };

    return {
      loading,
      error,
      scanResult,
      httpOrigin,
      showModal,
      closeModal
    };
  }
};
</script>

<style scoped>
.qr-scanner-container {
  width: 100%;
  max-width: 500px;
  margin: 0 auto;
  border: none;
  overflow: hidden;
  position: relative;
  background-color: transparent;
}

.qr-reader {
  width: 100%;
  position: relative;
  background-color: transparent;
}

/* Override the library's default styles */
:deep(#reader) {
  border: none !important;
  box-shadow: none !important;
  width: 100% !important;
}

:deep(#reader__scan_region) {
  background-color: transparent !important;
  border: none !important;
}

:deep(#reader__dashboard) {
  padding: 10px !important;
}

:deep(#reader__dashboard_section) {
  margin-top: 10px !important;
}

:deep(#reader__dashboard_section_swaplink) {
  background-color: var(--accent-color) !important;
  color: white !important;
  border: none !important;
  border-radius: 5px !important;
  padding: 8px 16px !important;
  font-size: 14px !important;
  cursor: pointer !important;
  text-decoration: none !important;
}

:deep(#reader__dashboard_section_swaplink:hover) {
  background-color: #2d5a1c !important;
}

:deep(#reader__filescan_input) {
  display: none !important;
}

:deep(#reader__camera_selection) {
  display: none !important;
}

:deep(#reader__scan_type_change_button) {
  display: none !important;
}

.scanner-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  font-weight: 500;
}

/* Code block styling */
.code-block {
  background-color: #f8f9fa;
  border-radius: 4px;
  padding: 8px;
  overflow-x: auto;
  border-left: 3px solid var(--accent-color);
}

/* Theme styling */
.theme-modal {
  --primary-color: #123524;
  --secondary-color: #EFE3C2;
  --accent-color: #3E7B27;
  --text-color: #333;
  --light-text: #666;
  --border-color: rgba(18, 53, 36, 0.2);
  --light-bg: rgba(239, 227, 194, 0.2);
  --lighter-bg: rgba(239, 227, 194, 0.1);
  
  border: none;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  background-color: #fff;
  border-radius: 10px;
  overflow: hidden;
}

.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
  padding: 15px 20px;
}

.theme-close {
  filter: invert(1) brightness(1.5);
}

.theme-body {
  background-color: #fff;
  color: var(--text-color);
}

.theme-footer {
  background-color: var(--light-bg);
  border-top: 1px solid var(--border-color);
  padding: 15px 20px;
}

.theme-btn-primary {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: var(--secondary-color);
  border-radius: 5px;
  padding: 8px 16px;
  transition: all 0.2s ease;
}

.theme-btn-primary:hover {
  background-color: #0a1f16;
  border-color: #0a1f16;
  transform: translateY(-2px);
}

.theme-spinner {
  color: var(--accent-color);
  width: 3rem;
  height: 3rem;
}

/* Improve button styling */
.btn {
  border-radius: 5px;
  padding: 8px 16px;
  transition: all 0.2s ease;
}

.btn:hover {
  transform: translateY(-2px);
}

.btn-secondary {
  background-color: #6c757d;
  border-color: #6c757d;
}

.btn-secondary:hover {
  background-color: #5a6268;
  border-color: #545b62;
}

/* Add animation for scanner */
@keyframes scan {
  0% {
    box-shadow: 0 0 0 0 rgba(62, 123, 39, 0.4);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(62, 123, 39, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(62, 123, 39, 0);
  }
}

.scanner-frame {
  animation: scan 2s infinite;
}
</style>