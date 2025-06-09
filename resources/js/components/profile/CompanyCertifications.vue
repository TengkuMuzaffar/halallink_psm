<template>
  <div class="card mb-4 theme-card">
    <div class="card-header d-flex justify-content-between align-items-center theme-header">
      <h5 class="mb-0">Certifications</h5>
      <button 
        class="btn btn-primary theme-btn-primary" 
        @click="$emit('toggle-edit')"
        v-if="!editMode"
      >
        <i class="bi bi-pencil-fill me-1"></i> 
        <span class="d-none d-md-inline">Manage Certifications</span>
      </button>
      <div v-else>
        <button class="btn btn-success theme-btn-primary me-2" @click="saveCertifications">
          <i class="bi bi-check-lg me-1"></i> 
          <span class="d-none d-md-inline">Save</span>
        </button>
        <button class="btn btn-secondary theme-btn-secondary" @click="cancelEdit">
          <i class="bi bi-x-lg me-1"></i> 
          <span class="d-none d-md-inline">Cancel</span>
        </button>
      </div>
    </div>
    <div class="card-body theme-body">
      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <div v-else>
        <!-- View Mode -->
        <div v-if="!editMode">
          <div v-if="certifications.length === 0" class="text-center py-3">
            <i class="fas fa-certificate text-muted mb-2" style="font-size: 2rem;"></i>
            <p class="text-muted">No certifications added yet</p>
          </div>
          <div v-else class="certification-list">
            <div v-for="(cert, index) in certifications" :key="cert.certID || index" class="certification-item mb-3 p-3 border rounded">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-bold">{{ cert.cert_type }}</div>
                  <div class="text-muted small">
                    <a :href="getFileUrl(cert.cert_file)" target="_blank" class="text-primary">
                      <i class="fas fa-file-pdf me-1"></i> View Certificate
                    </a>
                  </div>
                </div>
                <div class="ms-auto">
                  <span class="badge bg-success">Active</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Edit Mode -->
        <div v-else>
          <form @submit.prevent="saveCertifications">
            <div v-for="(cert, index) in editableCertifications" :key="index" class="certification-item mb-3 p-3 border rounded">
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label">Certificate Type</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="cert.cert_type" 
                    required
                    minlength="1"
                    placeholder="e.g., Halal Certification"
                    @blur="cert.cert_type = cert.cert_type.trim()"
                  >
                </div>
                <div class="col-md-4">
                  <label class="form-label">Certificate File</label>
                  <input 
                    type="file" 
                    class="form-control" 
                    @change="handleFileChange($event, index)" 
                    accept=".pdf,.jpg,.jpeg,.png"
                    :required="!cert.cert_file"
                  >
                  <div v-if="cert.cert_file && !cert.newFile" class="form-text">
                    <a :href="getFileUrl(cert.cert_file)" target="_blank" class="text-primary">
                      <i class="fas fa-file me-1"></i> Current file
                    </a>
                  </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button 
                    type="button" 
                    class="btn btn-danger btn-sm w-100" 
                    @click="confirmDeleteCertification(index, cert)"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
            
            <div class="mb-3">
              <button 
                type="button" 
                class="btn btn-outline-primary btn-sm" 
                @click="addNewCertification"
              >
                <i class="fas fa-plus me-1"></i> Add Certification
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue';
import api from '../../utils/api';
import modal from '../../utils/modal';
import certificationService from '../../services/certificationService';

export default {
  name: 'CompanyCertifications',
  props: {
    certifications: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    editMode: {
      type: Boolean,
      default: false
    }
  },
  emits: ['toggle-edit', 'loading', 'update:certifications', 'refresh-data'],
  setup(props, { emit }) {
    const saving = ref(false);
    const editableCertifications = ref([]);
    
    // Initialize editable certifications when edit mode changes or certifications prop changes
    const initEditableCertifications = () => {
      editableCertifications.value = props.certifications.map(cert => ({
        ...cert,
        newFile: null
      }));
      
      // If no certifications, add an empty one
      if (editableCertifications.value.length === 0) {
        addNewCertification();
      }
    };
    
    // Watch for changes in edit mode or certifications
    watch(
      [() => props.editMode, () => props.certifications],
      ([newEditMode]) => {
        if (newEditMode) {
          initEditableCertifications();
        }
      },
      { immediate: true }
    );
    
    // Add a new empty certification
    const addNewCertification = () => {
      editableCertifications.value.push({
        cert_type: '',
        cert_file: null,
        newFile: null
      });
    };
    
    // Confirm delete certification with modal (only in view mode)
    const confirmDeleteCertification = async (index, cert) => {
      // If in edit mode, delete directly without confirmation
      if (props.editMode) {
        removeCertification(index);
        return;
      }
      
      // If not in edit mode, show confirmation modal
      const certType = cert.cert_type || 'this certification';
      
      try {
        modal.confirm(
          'Delete Certification',
          `Are you sure you want to delete "${certType}"? This action cannot be undone and the certificate file will be permanently removed.`,
          () => {
            // This function will be called when the user confirms
            removeCertification(index);
          },
          null, // onCancel (optional)
          {
            confirmLabel: 'Yes, Delete',
            cancelLabel: 'Cancel',
            confirmType: 'danger'
          }
        );
      } catch (error) {
        // User cancelled or modal error
        // console.log('Delete cancelled');
      }
    };
    
    // Remove a certification
    const removeCertification = async (index) => {
      const cert = editableCertifications.value[index];
      
      // If the certification exists in the database (has certID), delete it from backend
      if (cert.certID) {
        try {
          await api.delete(`/api/profile/certifications/${cert.certID}`);
          // console.log('Certification deleted from server');
        } catch (error) {
          console.error('Error deleting certification from server:', error);
          modal.danger('Error', 'Failed to delete certification from server');
          return; // Don't remove from local array if server deletion failed
        }
      }
      
      // Remove from local array
      editableCertifications.value.splice(index, 1);
      
      // If no certifications left, add an empty one
      if (editableCertifications.value.length === 0) {
        addNewCertification();
      }
    };
    
    // Handle file change
    const handleFileChange = (event, index) => {
      const file = event.target.files[0];
      if (!file) return;
      
      // Check file type
      const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
      if (!allowedTypes.includes(file.type)) {
        modal.danger('Invalid File', 'Please select a PDF or image file');
        event.target.value = null;
        return;
      }
      
      // Check file size (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        modal.danger('File Too Large', 'File size should not exceed 5MB');
        event.target.value = null;
        return;
      }
      
      // Store the file
      editableCertifications.value[index].newFile = file;
    };
    
    // Get file URL
    const getFileUrl = (filePath) => {
      if (!filePath) return '';
      return filePath.startsWith('http') ? filePath : `/storage/${filePath}`;
    };
    
    // Save certifications
    const saveCertifications = async () => {
      try {
        emit('loading', true);
        saving.value = true;
        
        const formData = new FormData();
        
        // Filter out empty certifications and validate
        const validCertifications = editableCertifications.value.filter(cert => 
          cert.cert_type && (cert.cert_file || cert.newFile)
        );
        
        if (validCertifications.length === 0) {
          modal.danger('Error', 'Please add at least one certification with both type and file.');
          return;
        }
        
        // Append certifications data
        validCertifications.forEach((cert, index) => {
          formData.append(`certifications[${index}][cert_type]`, cert.cert_type);
          if (cert.newFile) {
            formData.append(`certifications[${index}][cert_file]`, cert.newFile);
          } else if (cert.cert_file) {
            formData.append(`certifications[${index}][existing_file]`, cert.cert_file);
          }
        });
        
        const response = await certificationService.updateCertifications(formData);
        
        modal.success('Success', 'Certifications updated successfully');
        
        // Update certifications data
        emit('update:certifications', response.certifications);
        
        // Exit edit mode
        emit('toggle-edit');
        
        // Emit refresh event to parent
        emit('refresh-data');
        
      } catch (error) {
        console.error('Error updating certifications:', error);
        modal.danger('Error', 'Failed to update certifications. Please try again.');
      } finally {
        emit('loading', false);
        saving.value = false;
      }
    };
    
    // Add this method to handle cancel button click
    const cancelEdit = () => {
      emit('toggle-edit'); // Toggle edit mode off
      emit('refresh-data'); // Refresh profile data
    };
    
    // Make sure to return the cancelEdit function in the setup return statement
    return {
      saving,
      editableCertifications,
      addNewCertification,
      confirmDeleteCertification,
      removeCertification,
      handleFileChange,
      getFileUrl,
      saveCertifications,
      cancelEdit // Add this to the return object
    };
  }
};
</script>

<style scoped>
.theme-card {
  --primary-color: #123524;
  --secondary-color: #f5f5f5;
  --accent-color: #3E7B27;
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: box-shadow 0.3s ease;
}

.theme-card:hover {
  box-shadow: 0 4px 12px rgba(18, 53, 36, 0.15);
}

.theme-header {
  background-color: var(--primary-color);
  color: white;
  border-bottom: none;
}

.theme-body {
  background-color: var(--secondary-color);
}

.theme-btn-primary {
    background-color: var(--accent-color);
  border-color: var(--accent-color);
  color: var(--secondary-color);
}

.theme-btn-primary:hover {
  background-color: var(--accent-color);
  border-color: var(--accent-color);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(18, 53, 36, 0.3);
}

.theme-btn-secondary {
  transition: all 0.3s ease;
}

.theme-btn-secondary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn-outline-danger {
  transition: all 0.3s ease;
}

.btn-outline-danger:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
}

.btn-outline-primary {
  color: var(--primary-color);
  border-color: var(--primary-color);
  transition: all 0.3s ease;
}

.btn-outline-primary:hover {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(18, 53, 36, 0.3);
}

.certification-item {
  transition: all 0.3s ease;
  border: 1px solid #e9ecef;
  background-color: white;
}

.certification-item:hover {
  border-color: var(--primary-color);
  box-shadow: 0 2px 8px rgba(18, 53, 36, 0.1);
  transform: translateY(-1px);
}

.form-control:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(18, 53, 36, 0.25);
}

.form-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(18, 53, 36, 0.25);
}

.badge.bg-success {
  background-color: var(--primary-color) !important;
}

.text-primary {
  color: var(--primary-color) !important;
}

.text-primary:hover {
  color: var(--accent-color) !important;
}
</style>