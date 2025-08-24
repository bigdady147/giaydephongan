<template>
  <div class="app">
    <div class="container">
      <div class="header">
        <h1>Giấy Đề Phòng An</h1>
        <p>Laravel 12 + Nuxt 4 TypeScript</p>
      </div>
      
      <div class="content">
        <div class="card">
          <div v-if="loading" class="loading">
            <div class="spinner"></div>
            <p>Đang kết nối với API...</p>
          </div>
          
          <div v-else-if="error" class="error">
            <h3>Lỗi kết nối</h3>
            <p>{{ error }}</p>
            <button @click="checkApi" class="btn">Thử lại</button>
          </div>
          
          <div v-else class="success">
            <h2>Trạng thái API: {{ apiStatus.status }}</h2>
            <p>{{ apiStatus.message }}</p>
            <small>Cập nhật lúc: {{ formatTime(apiStatus.timestamp) }}</small>
          </div>
        </div>
        
        <div class="links">
          <div class="link-card">
            <h3>Laravel Backend</h3>
            <p>API server chạy trên port 8000</p>
            <a href="http://localhost:8000/api/health" target="_blank" class="btn">Kiểm tra API</a>
          </div>
          
          <div class="link-card">
            <h3>Nuxt Frontend</h3>
            <p>Frontend chạy trên port 3000</p>
            <a href="http://localhost:3000" target="_blank" class="btn">Mở Frontend</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const config = useRuntimeConfig()
const loading = ref(true)
const error = ref('')
const apiStatus = ref({
  status: '',
  message: '',
  timestamp: ''
})

const checkApi = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await $fetch(`${config.public.apiBase}/health`)
    apiStatus.value = response
  } catch (err) {
    error.value = 'Không thể kết nối với API Laravel'
    console.error('API Error:', err)
  } finally {
    loading.value = false
  }
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  return new Date(timestamp).toLocaleString('vi-VN')
}

onMounted(() => {
  checkApi()
})
</script>

<style>
.app {
  min-height: 100vh;
  background-color: #f9fafb;
  font-family: 'Inter', system-ui, sans-serif;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.header {
  text-align: center;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2.5rem;
  font-weight: bold;
  color: #111827;
  margin-bottom: 0.5rem;
}

.header p {
  font-size: 1.125rem;
  color: #6b7280;
}

.content {
  max-width: 800px;
  margin: 0 auto;
}

.card {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
  margin-bottom: 2rem;
}

.loading, .error, .success {
  text-align: center;
  padding: 2rem 0;
}

.spinner {
  width: 2rem;
  height: 2rem;
  border: 2px solid #e5e7eb;
  border-top: 2px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error h3 {
  color: #dc2626;
  margin-bottom: 1rem;
}

.success h2 {
  color: #059669;
  margin-bottom: 1rem;
}

.btn {
  background-color: #3b82f6;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  text-decoration: none;
  display: inline-block;
  margin-top: 1rem;
  border: none;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn:hover {
  background-color: #2563eb;
}

.links {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.link-card {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
}

.link-card h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 0.5rem;
}

.link-card p {
  color: #6b7280;
  margin-bottom: 1rem;
}

small {
  color: #9ca3af;
  font-size: 0.875rem;
}
</style>
