<template>
  <div 
    class="modal fade" 
    :id="modalId" 
    tabindex="-1"
    role="dialog"
    :aria-labelledby="modalId + 'Label'"
    :aria-modal="true"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header theme-header">
          <h5 class="modal-title" :id="modalId + 'Label'" ref="modalTitle">{{ title }}</h5>
          <button 
            type="button" 
            class="btn-close btn-close-white" 
            @click="closeModal"
            aria-label="Close"
            ref="closeButton"
          ></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="handleSubmit">
            <!-- Poultry Selection -->
            <div class="mb-3">
              <label class="form-label">Poultry Type</label>
              <select class="form-select" :value="formData.poultryID" @input="e => handlePoultryChange(e.target.value)" required>                <option value="">Select Poultry</option>
                <option v-for="poultry in poultryTypes" :key="poultry.poultryID" :value="poultry.poultryID">
                  {{ poultry.poultry_name }}
                </option>
              </select>
            </div>

            <!-- Company Location -->
            <div class="mb-3">
              <label class="form-label">Poultry Facility Location</label>
              <select class="form-select" :value="formData.locationID" @input="e => handleLocationChange(e.target.value)" required>                <option value="">Select Storage Location</option>
                <option v-for="location in companyLocations" :key="location.locationID" :value="location.locationID">
                  {{ location.company_address }}
                </option>
              </select>
              <div class="form-text">Select the facility where the poultry is housed and cared for</div>
            </div>

            <!-- Slaughterhouse Location -->
            <div class="mb-3">
              <label class="form-label">Slaughterhouse Location</label>
              <select class="form-select" :value="formData.slaughterhouse_locationID" @input="e => handleSlaughterhouseChange(e.target.value)">                <option value="">Select Slaughterhouse</option>
                <option v-for="location in slaughterhouseLocations" :key="location.locationID" :value="location.locationID">
                  {{ location.company_address }}
                </option>
              </select>
              <div class="form-text">Select where the item will be slaughtered</div>
            </div>

            <!-- Measurement -->
            <div class="row mb-3">
              <div class="col">
                <label class="form-label">Measurement Type</label>
                <select class="form-select" :value="formData.measurement_type" @input="e => handleMeasurementTypeChange(e.target.value)" required>                  <option value="kg">Kilogram (KG)</option>
                  <option value="unit">Unit</option>
                </select>
              </div>
              <div class="col">
                <label class="form-label">
                  {{ formData.measurement_type === 'kg' ? 'Weight per Item (KG)' : 'Size per Item' }}
                </label>
                <!-- For measurement_value input -->
                <input 
                  type="number" 
                  class="form-control" 
                  :value="formData.measurement_value" 
                  @input="e => handleMeasurementValueChange(e.target.value)"
                  required 
                  min="0" 
                  step="0.01"
                >
                <div class="form-text">
                  {{ formData.measurement_type === 'kg' ? 'Weight of each item in kilograms' : 'Size/volume of each item' }}
                </div>
              </div>
            </div>

            <!-- Price and Stock -->
            <div class="row mb-3">
              <div class="col">
                <label class="form-label">Price per Item (RM)</label>
                <input 
                type="number" 
                class="form-control" 
                :value="formData.price" 
                @input="e => handlePriceChange(e.target.value)"
                required 
                min="0" 
                step="0.01"
              >
              <div class="form-text">Price per item in RM</div>

              </div>
              <div class="col">
                <label class="form-label">Quantity in Stock</label>
                <input 
                  type="number" 
                  class="form-control" 
                  :value="formData.stock" 
                  @input="e => handleStockChange(e.target.value)"
                  required 
                  min="0"
                >
                <div class="form-text">Number of items available</div>
              </div>
            </div>

            <!-- Image Upload -->
            <div class="mb-3">
              <label class="form-label">Item Image</label>
              <input type="file" class="form-control" @change="handleImageChange" accept="image/*">
              <div v-if="imagePreview" class="mt-2">
                <img :src="imagePreview" class="img-thumbnail" style="max-height: 200px">
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" @click="handleSubmit" :disabled="loading">
            {{ loading ? 'Saving...' : (isEditing ? 'Update' : 'Save') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, watch, computed, onMounted, onBeforeUnmount } from 'vue';

export default {
  name: 'CustomModal',
  props: {
    modalId: {
      type: String,
      required: true,
      default: 'itemModal'
    },
    isEditing: {
      type: Boolean,
      default: false
    },
    loading: {
      type: Boolean,
      default: false
    },
    poultryTypes: {
      type: Array,
      default: () => []
    },
    companyLocations: {
      type: Array,
      default: () => []
    },
    slaughterhouseLocations: {
      type: Array,
      default: () => []
    },
    initialData: {
      type: Object,
      default: () => ({})
    }
  },
  
  emits: ['submit', 'image-change'],

  setup(props, { emit }) {
    const closeButton = ref(null);
    const modalTitle = ref(null);
    const imagePreview = ref(null);
    const file = ref(null); // Add this line to store the selected file
    
    // Initialize formData with props.initialData if available
    const formData = reactive({
      ...{
        itemID: null,
        poultryID: '',
        locationID: '',
        slaughterhouse_locationID: '',
        measurement_type: 'kg',
        measurement_value: '',
        price: '',
        stock: '',
        item_image: null
      },
      ...props.initialData
    });

    // Add the missing handleImageChange function
    const handleImageChange = (e) => {
      const selectedFile = e.target.files[0];
      if (selectedFile) {
        file.value = selectedFile;
        const reader = new FileReader();
        reader.onload = (e) => {
          imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(selectedFile);
        formData.item_image = selectedFile;
      }
    };

    // Individual field watchers
    const handlePoultryChange = (value) => {
      // console.log('🐔 Poultry changed:', value);
      formData.poultryID = value;
    };

    const handleLocationChange = (value) => {
      // console.log('📍 Location changed:', value);
      formData.locationID = value;
    };

    const handleSlaughterhouseChange = (value) => {
      // console.log('🏢 Slaughterhouse changed:', value);
      formData.slaughterhouse_locationID = value;
    };

    const handleMeasurementTypeChange = (value) => {
      // console.log('📏 Measurement type changed:', value);
      formData.measurement_type = value;
    };

    const handleMeasurementValueChange = (value) => {
      // console.log('⚖️ Measurement value changed:', value);
      formData.measurement_value = Number(value) || null;
    };

    const handlePriceChange = (value) => {
      // console.log('💰 Price changed:', value);
      formData.price = Number(value) || null;
    };

    const handleStockChange = (value) => {
      // console.log('📦 Stock changed:', value);
      formData.stock = Number(value) || null;
    };

    // Watch for changes in initialData
    watch(() => props.initialData, (newVal) => {
      if (newVal) {
        Object.assign(formData, newVal);
      }
    }, { deep: true });

    // Modified handleSubmit
    const handleSubmit = () => {
      // Validate all fields before submission
      if (!formData.poultryID) {
        // console.warn('Poultry type is required');
        return;
      }

      if (!formData.stock || formData.stock < 0) {
        // console.warn('Valid stock quantity is required');
        return;
      }

      // Only handle file operations if a file is selected
      if (file.value) {
        // Update formData with the selected file
        formData.item_image = file.value;

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
          imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file.value);

        // Emit the file
        emit('image-change', file.value);
      }

      // console.log('✅ All validations passed, submitting:', formData);
      emit('submit', formData);
    };

    const closeModal = () => {
      const modalElement = document.getElementById(props.modalId);
      const bsModal = bootstrap.Modal.getInstance(modalElement);
      if (bsModal) {
        bsModal.hide();
      }
    };

    const title = computed(() => props.isEditing ? 'Edit Item' : 'Add New Item');

    // Handle modal focus management
    const handleModalShow = () => {
      if (modalTitle.value) {
        modalTitle.value.focus();
      }
    };

    return {
      closeButton,
      modalTitle,
      closeModal,
      imagePreview,
      handlePoultryChange,
      handleLocationChange,
      handleSlaughterhouseChange,
      handleMeasurementTypeChange,
      handleMeasurementValueChange,
      handlePriceChange,
      handleStockChange,
      formData,
      handleSubmit,
      handleImageChange,
      title
    };
  }
};
</script>

<style scoped>
.theme-header {
  background-color: var(--primary-color);
  color: var(--secondary-color);
  border-bottom: none;
}

.btn-close-white {
  filter: invert(1) grayscale(100%) brightness(200%);
}
</style>