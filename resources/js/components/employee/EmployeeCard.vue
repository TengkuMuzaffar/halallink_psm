<template>
  <div class="employee-card-modal ">
    <div class="modal fade" id="employeeCardModal" tabindex="-1" aria-labelledby="employeeCardModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-0 m-0 border-0">
          <div class="d-flex justify-content-end p-2" style="background-color: #123524; border-bottom: 0 !important;">
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div class="employee-id-card">
              <div class="card-header text-center py-3">
                <div class="company-logo mb-2">
                  <img src="/images/HalalLink.png" alt="Company Logo" class="img-fluid" style="max-height: 60px;">
                </div>
                <h4 class="mb-0 fw-bold">{{ employee.company_name || 'HalalLink' }}</h4>
              </div>
              
              <div class="card-body p-2">
                <div class="employee-avatar text-center mb-4">
                  <div class="avatar-container">
                    <div class="avatar-placeholder">
                      <i class="fas fa-user fa-3x"></i>
                    </div>
                    <div class="avatar-badge" :class="getStatusBadgeClass(employee.status, true)">
                      <i class="fas" :class="employee.status === 'active' ? 'fa-check' : 'fa-times'"></i>
                    </div>
                  </div>
                  <h5 class="mt-3 mb-0 fw-bold">{{ employee.fullname }}</h5>
                  <div class="employee-id-badge mt-1">ID: {{ employee.userID }}</div>
                </div>
                
                <div class="employee-details">
                  <div class="detail-row">
                    <div class="detail-label"><i class="fas fa-envelope me-2"></i>Email:</div>
                    <div class="detail-value">{{ employee.email }}</div>
                  </div>
                  
                  <div class="detail-row">
                    <div class="detail-label"><i class="fas fa-phone me-2"></i>Phone:</div>
                    <div class="detail-value">{{ employee.tel_number || 'Not provided' }}</div>
                  </div>
                  
                  <div class="detail-row">
                    <div class="detail-label"><i class="fas fa-calendar me-2"></i>Joined:</div>
                    <div class="detail-value">{{ formatDate(employee.created_at) }}</div>
                  </div>
                  
                  <div class="detail-row">
                    <div class="detail-label"><i class="fas fa-check-circle me-2"></i>Status:</div>
                    <div class="detail-value">
                      <span :class="getStatusBadgeClass(employee.status)">{{ employee.status }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
            
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'EmployeeCard',
  props: {
    employee: {
      type: Object,
      required: true
    }
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    },
    getStatusBadgeClass(status, isIcon = false) {
      if (isIcon) {
        return {
          'status-active': status === 'active',
          'status-inactive': status === 'inactive' || status === 'pending',
          'status-default': !status
        };
      }
      
      const classes = 'badge ';
      switch (status) {
        case 'active': return classes + 'bg-success';
        case 'inactive': return classes + 'bg-danger';
        default: return classes + 'bg-secondary';
      }
    },
  }
});
</script>

<style scoped>
.employee-id-card {
  border: none;
  border-radius: 0;
  margin: 0;
  overflow: hidden;
  box-shadow: none;
  background-color: #fff;
}

.card-header {
  background-color: var(--primary-color, #123524);
  color: white;
  position: relative;
  overflow: hidden;
}

.card-header::after {
  content: '';
  position: absolute;
  bottom: -10px;
  right: -10px;
  width: 100px;
  height: 100px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  pointer-events: none;
}

.avatar-container {
  position: relative;
  width: 110px;
  height: 110px;
  margin: 0 auto;
}

.avatar-placeholder {
  width: 110px;
  height: 110px;
  background-color: #f8f9fa;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  color: #6c757d;
  border: 1px solid #dee2e6;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.avatar-badge {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.status-active {
  background-color: #28a745;
}

.status-inactive {
  background-color: #dc3545;
}

.status-default {
  background-color: #6c757d;
}

.employee-id-badge {
  display: inline-block;
  background-color: #f0f0f0;
  color: #495057;
  font-size: 0.8rem;
  padding: 2px 8px;
  border-radius: 12px;
  font-weight: 500;
}

.detail-row {
  display: flex;
  margin-bottom: 12px;
  border-bottom: 1px solid #f0f0f0;
  padding-bottom: 10px;
  transition: background-color 0.2s ease;
}

.detail-row:hover {
  background-color: #f9f9f9;
}

.detail-label {
  font-weight: 600;
  width: 40%;
  color: #495057;
}

.detail-value {
  width: 60%;
  color: #212529;
}

.card-footer {
  background-color: #f8f9fa;
  border-top: 1px solid #eee;
}

.qr-code-container {
  display: flex;
  justify-content: center;
}

.qr-code-placeholder {
  width: 90px;
  height: 90px;
  background-color: #fff;
  border: 1px solid #dee2e6;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  color: #6c757d;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.modal-content {
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.btn-primary {
  transition: all 0.2s ease;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

@media (max-width: 576px) {
  .detail-row {
    flex-direction: column;
  }
  
  .detail-label,
  .detail-value {
    width: 100%;
  }
  
  .detail-value {
    margin-top: 4px;
  }
  
  .avatar-placeholder {
    width: 90px;
    height: 90px;
  }
  
  .avatar-container {
    width: 90px;
    height: 90px;
  }
}
</style>