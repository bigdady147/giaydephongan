<template>
  <div class="services-page">
    <div class="container">
      <div class="header">
        <h1>Dịch vụ</h1>
        <p>Đăng ký và quản lý các dịch vụ</p>
      </div>
      
      <div class="content">
        <div class="services-grid">
          <div v-for="service in services" :key="service.id" class="service-card">
            <div class="service-icon">{{ service.icon }}</div>
            <h3>{{ service.name }}</h3>
            <p>{{ service.description }}</p>
            <div class="service-price">{{ service.price }}</div>
            <div class="service-status" :class="service.status">
              {{ getStatusText(service.status) }}
            </div>
            <button 
              @click="registerService(service.id)" 
              class="btn btn-primary"
              :disabled="service.status === 'registered'"
            >
              {{ service.status === 'registered' ? 'Đã đăng ký' : 'Đăng ký' }}
            </button>
          </div>
        </div>
        
        <div class="my-services">
          <h2>Dịch vụ của tôi</h2>
          <div class="service-list">
            <div v-for="myService in myServices" :key="myService.id" class="my-service-item">
              <div class="service-info">
                <h4>{{ myService.name }}</h4>
                <p>Đăng ký: {{ formatDate(myService.registeredAt) }}</p>
              </div>
              <div class="service-progress">
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: myService.progress + '%' }"></div>
                </div>
                <span class="progress-text">{{ myService.progress }}% hoàn thành</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'client'
})

// Mock services data
const services = ref([
  {
    id: 1,
    name: 'Dịch vụ A',
    description: 'Mô tả chi tiết về dịch vụ A',
    price: '500,000 VNĐ',
    icon: '🔧',
    status: 'available'
  },
  {
    id: 2,
    name: 'Dịch vụ B',
    description: 'Mô tả chi tiết về dịch vụ B',
    price: '750,000 VNĐ',
    icon: '📋',
    status: 'registered'
  },
  {
    id: 3,
    name: 'Dịch vụ C',
    description: 'Mô tả chi tiết về dịch vụ C',
    price: '1,000,000 VNĐ',
    icon: '⚡',
    status: 'available'
  }
])

const myServices = ref([
  {
    id: 2,
    name: 'Dịch vụ B',
    registeredAt: '2024-01-15',
    progress: 75
  }
])

const getStatusText = (status) => {
  const statusMap = {
    available: 'Có sẵn',
    registered: 'Đã đăng ký',
    completed: 'Hoàn thành'
  }
  return statusMap[status] || status
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('vi-VN')
}

const registerService = (serviceId) => {
  const service = services.value.find(s => s.id === serviceId)
  if (service) {
    service.status = 'registered'
    alert(`Đã đăng ký thành công dịch vụ: ${service.name}`)
  }
}
</script>

<style scoped>
.services-page {
  padding: 2rem;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  text-align: center;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2rem;
  font-weight: bold;
  color: #1e40af;
  margin-bottom: 0.5rem;
}

.header p {
  color: #6b7280;
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.service-card {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
  text-align: center;
}

.service-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.service-card h3 {
  color: #1e40af;
  margin-bottom: 0.5rem;
}

.service-card p {
  color: #6b7280;
  margin-bottom: 1rem;
}

.service-price {
  font-size: 1.25rem;
  font-weight: bold;
  color: #059669;
  margin-bottom: 0.5rem;
}

.service-status {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 1rem;
}

.service-status.available {
  background: #dbeafe;
  color: #1e40af;
}

.service-status.registered {
  background: #dcfce7;
  color: #059669;
}

.btn {
  background: #1e40af;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn:hover:not(:disabled) {
  background: #1e3a8a;
}

.btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.my-services {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  padding: 2rem;
  border: 1px solid #e5e7eb;
}

.my-services h2 {
  color: #374151;
  margin-bottom: 1.5rem;
}

.service-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.my-service-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 0.375rem;
  border: 1px solid #e2e8f0;
}

.service-info h4 {
  color: #1e40af;
  margin-bottom: 0.25rem;
}

.service-info p {
  color: #6b7280;
  font-size: 0.875rem;
}

.service-progress {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.progress-bar {
  width: 200px;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #059669;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.875rem;
  color: #6b7280;
}
</style>
