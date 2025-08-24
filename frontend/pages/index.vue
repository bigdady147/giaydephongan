<template>
      <div class="app">
      <div class="container">
        <div class="header">
          <h1>{{ $t('home.title') }}</h1>
          <p>{{ $t('home.subtitle') }}</p>
          <LanguageSwitcher />
        </div>
      
      <div class="content">
        <div class="card">
          <div v-if="loading" class="loading">
            <div class="spinner"></div>
            <p>{{ $t('home.apiConnecting') }}</p>
          </div>
          
          <div v-else-if="error" class="error">
            <h3>{{ $t('home.apiError') }}</h3>
            <p>{{ error }}</p>
            <button @click="checkApi" class="btn">{{ $t('home.apiRetry') }}</button>
          </div>
          
          <div v-else class="success">
            <h2>{{ $t('home.apiStatus') }}: {{ apiStatus.status }}</h2>
            <p>{{ apiStatus.message }}</p>
            <small>{{ $t('home.lastUpdated') }}: {{ formatTime(apiStatus.timestamp) }}</small>
          </div>
        </div>
        
        <div class="portals">
          <div class="portal-card client">
            <div class="portal-icon">👤</div>
            <h3>{{ $t('home.clientPortal.title') }}</h3>
            <p>{{ $t('home.clientPortal.subtitle') }}</p>
            <div class="portal-features">
              <!-- <span v-for="(feature, index) in $t('home.clientPortal.features')" :key="feature">• {{ feature }}</span> -->
            </div>
            <NuxtLink to="/client" class="btn btn-primary">{{ $t('home.clientPortal.button') }}</NuxtLink>
          </div>
          
          <div class="portal-card admin">
            <div class="portal-icon">⚙️</div>
            <h3>{{ $t('home.adminPortal.title') }}</h3>
            <p>{{ $t('home.adminPortal.subtitle') }}</p>
            <div class="portal-features">
              <!-- <span v-for="feature in $t('home.adminPortal.features')" :key="feature">• {{ feature }}</span> -->
            </div>
            <NuxtLink to="/admin" class="btn btn-secondary">{{ $t('home.adminPortal.button') }}</NuxtLink>
          </div>
        </div>
        
        <div class="links">
          <div class="link-card">
            <h3>{{ $t('home.links.laravelBackend.title') }}</h3>
            <p>{{ $t('home.links.laravelBackend.description') }}</p>
            <a href="http://localhost:8000/api/health" target="_blank" class="btn">{{ $t('home.links.laravelBackend.button') }}</a>
          </div>
          
          <div class="link-card">
            <h3>{{ $t('home.links.nuxtFrontend.title') }}</h3>
            <p>{{ $t('home.links.nuxtFrontend.description') }}</p>
            <a href="http://localhost:3000" target="_blank" class="btn">{{ $t('home.links.nuxtFrontend.button') }}</a>
          </div>
          
          <div class="link-card">
            <h3>{{ $t('home.links.demoRouting.title') }}</h3>
            <p>{{ $t('home.links.demoRouting.description') }}</p>
            <NuxtLink to="/demo-routing" class="btn">{{ $t('home.links.demoRouting.button') }}</NuxtLink>
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

<style scoped>
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
  max-width: 1200px;
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

.portals {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.portal-card {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  padding: 2rem;
  border: 1px solid #e5e7eb;
  text-align: center;
  transition: transform 0.2s;
}

.portal-card:hover {
  transform: translateY(-2px);
}

.portal-card.client {
  border-top: 4px solid #1e40af;
}

.portal-card.admin {
  border-top: 4px solid #dc2626;
}

.portal-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.portal-card h3 {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.portal-card.client h3 {
  color: #1e40af;
}

.portal-card.admin h3 {
  color: #dc2626;
}

.portal-card p {
  color: #6b7280;
  margin-bottom: 1.5rem;
}

.portal-features {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  text-align: left;
}

.portal-features span {
  color: #374151;
  font-size: 0.875rem;
}

.btn {
  background-color: #3b82f6;
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 0.375rem;
  text-decoration: none;
  display: inline-block;
  border: none;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn:hover {
  background-color: #2563eb;
}

.btn-primary {
  background-color: #1e40af;
}

.btn-primary:hover {
  background-color: #1e3a8a;
}

.btn-secondary {
  background-color: #dc2626;
}

.btn-secondary:hover {
  background-color: #b91c1c;
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
