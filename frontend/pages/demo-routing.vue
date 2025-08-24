<template>
  <div class="demo-page">
    <div class="container">
      <div class="header">
        <h1>Demo Nuxt Routing</h1>
        <p>Minh họa các tính năng routing trong Nuxt 4</p>
      </div>
      
      <div class="content">
        <!-- Basic Navigation -->
        <div class="section">
          <h2>1. Basic Navigation</h2>
          <div class="nav-demo">
            <NuxtLink to="/" class="nav-link">Trang chủ</NuxtLink>
            <NuxtLink to="/client" class="nav-link">Client Portal</NuxtLink>
            <NuxtLink to="/admin" class="nav-link">Admin Portal</NuxtLink>
            <NuxtLink to="/login" class="nav-link">Đăng nhập</NuxtLink>
          </div>
        </div>
        
        <!-- Programmatic Navigation -->
        <div class="section">
          <h2>2. Programmatic Navigation</h2>
          <div class="nav-demo">
            <button @click="goToHome" class="btn">Trang chủ</button>
            <button @click="goToClient" class="btn">Client Portal</button>
            <button @click="goToAdmin" class="btn">Admin Portal</button>
            <button @click="goBack" class="btn">Quay lại</button>
          </div>
        </div>
        
        <!-- Route Parameters -->
        <div class="section">
          <h2>3. Route Parameters</h2>
          <div class="param-demo">
            <input v-model="userId" type="number" placeholder="User ID" class="input">
            <button @click="goToUser" class="btn">Xem User</button>
            <p class="note">Sẽ navigate đến /users/[id] (nếu có route này)</p>
          </div>
        </div>
        
        <!-- Query Parameters -->
        <div class="section">
          <h2>4. Query Parameters</h2>
          <div class="query-demo">
            <input v-model="searchQuery" type="text" placeholder="Tìm kiếm..." class="input">
            <button @click="search" class="btn">Tìm kiếm</button>
            <p class="note">Sẽ thêm ?q=searchQuery vào URL</p>
          </div>
        </div>
        
        <!-- Current Route Info -->
        <div class="section">
          <h2>5. Current Route Information</h2>
          <div class="route-info">
            <div class="info-item">
              <strong>Path:</strong> {{ route.path }}
            </div>
            <div class="info-item">
              <strong>Name:</strong> {{ route.name }}
            </div>
            <div class="info-item">
              <strong>Params:</strong> {{ JSON.stringify(route.params) }}
            </div>
            <div class="info-item">
              <strong>Query:</strong> {{ JSON.stringify(route.query) }}
            </div>
          </div>
        </div>
        
        <!-- Layout Demo -->
        <div class="section">
          <h2>6. Layout Demo</h2>
          <div class="layout-demo">
            <p>Trang hiện tại sử dụng layout: <strong>{{ currentLayout }}</strong></p>
            <div class="layout-buttons">
              <button @click="changeLayout('default')" class="btn">Default Layout</button>
              <button @click="changeLayout('client')" class="btn">Client Layout</button>
              <button @click="changeLayout('admin')" class="btn">Admin Layout</button>
            </div>
          </div>
        </div>
        
        <!-- Authentication Demo -->
        <div class="section">
          <h2>7. Authentication Demo</h2>
          <div class="auth-demo">
            <div class="auth-status">
              <p>Trạng thái: <span :class="authStatus.class">{{ authStatus.text }}</span></p>
              <p>Vai trò: <strong>{{ userRole || 'Chưa đăng nhập' }}</strong></p>
            </div>
            <div class="auth-buttons">
              <button @click="loginAsUser" class="btn">Đăng nhập User</button>
              <button @click="loginAsAdmin" class="btn">Đăng nhập Admin</button>
              <button @click="logout" class="btn">Đăng xuất</button>
            </div>
          </div>
        </div>
        
        <!-- Error Demo -->
        <div class="section">
          <h2>8. Error Handling Demo</h2>
          <div class="error-demo">
            <button @click="triggerError" class="btn btn-danger">Tạo lỗi 404</button>
            <button @click="triggerServerError" class="btn btn-danger">Tạo lỗi 500</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const router = useRouter()
const route = useRoute()

// Route parameters
const userId = ref(1)
const searchQuery = ref('')
const currentLayout = ref('default')

// Authentication state
const userRole = ref('')
const isAuthenticated = ref(false)

// Check authentication on mount
onMounted(() => {
  if (process.client) {
    isAuthenticated.value = localStorage.getItem('isAuthenticated') === 'true'
    userRole.value = localStorage.getItem('userRole') || ''
  }
})

// Computed properties
const authStatus = computed(() => {
  if (isAuthenticated.value) {
    return {
      text: 'Đã đăng nhập',
      class: 'status-success'
    }
  }
  return {
    text: 'Chưa đăng nhập',
    class: 'status-error'
  }
})

// Navigation functions
const goToHome = () => {
  router.push('/')
}

const goToClient = () => {
  router.push('/client')
}

const goToAdmin = () => {
  router.push('/admin')
}

const goBack = () => {
  router.back()
}

const goToUser = () => {
  router.push(`/users/${userId.value}`)
}

const search = () => {
  router.push({
    query: { q: searchQuery.value }
  })
}

const changeLayout = (layout) => {
  currentLayout.value = layout
  // In real app, you would set this in page meta
  console.log(`Layout changed to: ${layout}`)
}

// Authentication functions
const loginAsUser = () => {
  if (process.client) {
    localStorage.setItem('isAuthenticated', 'true')
    localStorage.setItem('userRole', 'user')
    isAuthenticated.value = true
    userRole.value = 'user'
    alert('Đã đăng nhập với vai trò User')
  }
}

const loginAsAdmin = () => {
  if (process.client) {
    localStorage.setItem('isAuthenticated', 'true')
    localStorage.setItem('userRole', 'admin')
    isAuthenticated.value = true
    userRole.value = 'admin'
    alert('Đã đăng nhập với vai trò Admin')
  }
}

const logout = () => {
  if (process.client) {
    localStorage.removeItem('isAuthenticated')
    localStorage.removeItem('userRole')
    isAuthenticated.value = false
    userRole.value = ''
    alert('Đã đăng xuất')
  }
}

// Error functions
const triggerError = () => {
  throw createError({
    statusCode: 404,
    message: 'Trang không tồn tại'
  })
}

const triggerServerError = () => {
  throw createError({
    statusCode: 500,
    message: 'Lỗi server'
  })
}
</script>

<style scoped>
.demo-page {
  padding: 2rem;
  background: #f8fafc;
  min-height: 100vh;
}

.container {
  max-width: 1000px;
  margin: 0 auto;
}

.header {
  text-align: center;
  margin-bottom: 3rem;
}

.header h1 {
  color: #1e40af;
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
}

.header p {
  color: #6b7280;
  font-size: 1.125rem;
}

.section {
  background: white;
  border-radius: 0.5rem;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.section h2 {
  color: #374151;
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
}

.nav-demo {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.nav-link {
  background: #3b82f6;
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 0.375rem;
  text-decoration: none;
  font-weight: 500;
  transition: background-color 0.2s;
}

.nav-link:hover {
  background: #2563eb;
}

.btn {
  background: #10b981;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn:hover {
  background: #059669;
}

.btn-danger {
  background: #ef4444;
}

.btn-danger:hover {
  background: #dc2626;
}

.param-demo, .query-demo {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 1rem;
  min-width: 200px;
}

.note {
  color: #6b7280;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.route-info {
  background: #f8fafc;
  padding: 1.5rem;
  border-radius: 0.375rem;
  border: 1px solid #e2e8f0;
}

.info-item {
  margin-bottom: 0.75rem;
}

.info-item:last-child {
  margin-bottom: 0;
}

.layout-demo, .auth-demo {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.layout-buttons, .auth-buttons {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.auth-status {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 0.375rem;
  border: 1px solid #e2e8f0;
}

.status-success {
  color: #059669;
  font-weight: 500;
}

.status-error {
  color: #dc2626;
  font-weight: 500;
}

.error-demo {
  display: flex;
  gap: 1rem;
}
</style>
