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
        <div class="modal-header">
          <h5 class="modal-title" :id="modalId + 'Label'" ref="modalTitle">{{ title }}</h5>
          <button 
            type="button" 
            class="btn-close" 
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
              <select class="form-select" v-model="formData.poultryID" required>
                <option value="">Select Poultry</option>
                <option v-for="poultry in poultryTypes" :key="poultry.poultryID" :value="poultry.poultryID">
                  {{ poultry.poultry_name }}
                </option>
              </select>
            </div>

            <!-- Company Location -->
            <div class="mb-3">
              <label class="form-label">Storage Location</label>
              <select class="form-select" v-model="formData.locationID" required>
                <option value="">Select Storage Location</option>
                <option v-for="location in companyLocations" :key="location.locationID" :value="location.locationID">
                  {{ location.company_address }}
                </option>
              </select>
              <div class="form-text">Select where the item will be stored</div>
            </div>

            <!-- Slaughterhouse Location -->
            <div class="mb-3">
              <label class="form-label">Slaughterhouse Location</label>
              <select class="form-select" v-model="formData.slaughterhouse_locationID">
                <option value="">Select Slaughterhouse</option>
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
                <select class="form-select" v-model="formData.measurement_type" required>
                  <option value="kg">Kilogram (KG)</option>
                  <option value="unit">Unit</option>
                </select>
              </div>
              <div class="col">
                <label class="form-label">
                  {{ formData.measurement_type === 'kg' ? 'Weight per Item (KG)' : 'Size per Item' }}
                </label>
                <input 
                  type="number" 
                  class="form-control" 
                  v-model="formData.measurement_value" 
                  required 
                  min="0" 
                  step="0.01"
                  :placeholder="formData.measurement_type === 'kg' ? 'Enter weight in KG' : 'Enter size'"
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
                  v-model="formData.price" 
                  required 
                  min="0" 
                  step="0.01"
                  placeholder="Enter price"
                >
              </div>
              <div class="col">
                <label class="form-label">Quantity in Stock</label>
                <input 
                  type="number" 
                  class="form-control" 
                  v-model="formData.stock" 
                  required 
                  min="0"
                  placeholder="Enter quantity"
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
    
    const formData = reactive({
      itemID: null,
      poultryID: '',
      locationID: '',
      slaughterhouse_locationID: '',
      measurement_type: 'kg',
      measurement_value: '',
      price: '',
      stock: '',
      item_image: null
    });

    // Watch for changes in initialData and update formData
    watch(() => props.initialData, (newVal) => {
      Object.assign(formData, newVal);
    }, { deep: true });

    const handleSubmit = () => {
      emit('submit', formData);
    };

    const handleImageChange = (event) => {
      const file = event.target.files[0];
      if (!file) return;

      // Create preview
      const reader = new FileReader();
      reader.onload = (e) => {
        imagePreview.value = e.target.result;
      };
      reader.readAsDataURL(file);

      // Emit the file
      emit('image-change', file);
    };

    const title = computed(() => props.isEditing ? 'Edit Item' : 'Add New Item');

    // Handle modal focus management
    const handleModalShow = () => {
      if (modalTitle.value) {
        modalTitle.value.focus();
      }
    };

    const closeModal = () => {
      const modalElement = document.getElementById(props.modalId);
      const bsModal = bootstrap.Modal.getInstance(modalElement);
      if (bsModal) {
        bsModal.hide();
      }
    };

    onMounted(() => {
      const modalElement = document.getElementById(props.modalId);
      if (modalElement) {
        modalElement.addEventListener('shown.bs.modal', handleModalShow);
      }
    });

    onBeforeUnmount(() => {
      const modalElement = document.getElementById(props.modalId);
      if (modalElement) {
        modalElement.removeEventListener('shown.bs.modal', handleModalShow);
      }
    });

    return {
      closeButton,
      modalTitle,
      closeModal,
      imagePreview,
      formData,
      handleSubmit,
      handleImageChange,
      title
    };
  }
};
</script>