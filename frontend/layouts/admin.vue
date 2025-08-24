<template>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>Admin Panel</h2>
        <span class="badge">Administrator</span>
      </div>
      
      <nav class="sidebar-nav">
        <NuxtLink to="/admin" class="nav-item">
          <span class="nav-icon">📊</span>
          Dashboard
        </NuxtLink>
        <NuxtLink to="/admin/users" class="nav-item">
          <span class="nav-icon">👥</span>
          Quản lý người dùng
        </NuxtLink>
        <NuxtLink to="/admin/records" class="nav-item">
          <span class="nav-icon">📋</span>
          Quản lý hồ sơ
        </NuxtLink>
        <NuxtLink to="/admin/services" class="nav-item">
          <span class="nav-icon">🔧</span>
          Quản lý dịch vụ
        </NuxtLink>
        <NuxtLink to="/admin/reports" class="nav-item">
          <span class="nav-icon">📈</span>
          Báo cáo
        </NuxtLink>
        <NuxtLink to="/admin/settings" class="nav-item">
          <span class="nav-icon">⚙️</span>
          Cài đặt
        </NuxtLink>
      </nav>
      
             <div class="sidebar-footer">
         <button @click="handleLogout" class="btn-logout">Đăng xuất</button>
       </div>
    </aside>
    
    <div class="main-content">
      <header class="top-header">
        <div class="header-content">
          <h1>Giày dép Hồng An</h1>
          <div class="user-info">
            <span>Admin User</span>
            <div class="avatar">A</div>
          </div>
        </div>
      </header>
      
      <main class="main">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
// Admin layout logic
const router = useRouter()

const handleLogout = () => {
  if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
    // Clear authentication data
    localStorage.removeItem('isAuthenticated')
    localStorage.removeItem('userRole')
    
    // Redirect to login page
    router.push('/login')
  }
}
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  width: 280px;
  background: #1f2937;
  color: white;
  display: flex;
  flex-direction: column;
  box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
}

.sidebar-header {
  padding: 2rem 1.5rem;
  border-bottom: 1px solid #374151;
}

.sidebar-header h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  font-weight: bold;
}

.badge {
  background: #dc2626;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.sidebar-nav {
  flex: 1;
  padding: 1rem 0;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.5rem;
  color: #d1d5db;
  text-decoration: none;
  transition: all 0.2s;
  border-left: 3px solid transparent;
}

.nav-item:hover {
  background: #374151;
  color: white;
  border-left-color: #dc2626;
}

.nav-item.router-link-active {
  background: #374151;
  color: white;
  border-left-color: #dc2626;
}

.nav-icon {
  font-size: 1.25rem;
  width: 1.5rem;
  text-align: center;
}

.sidebar-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #374151;
}

.btn-logout {
  width: 100%;
  background: #dc2626;
  color: white;
  border: none;
  padding: 0.75rem;
  border-radius: 0.375rem;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-logout:hover {
  background: #b91c1c;
}

.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.top-header {
  background: white;
  border-bottom: 1px solid #e5e7eb;
  padding: 1rem 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto;
}

.header-content h1 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: bold;
  color: #1f2937;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.avatar {
  width: 2.5rem;
  height: 2.5rem;
  background: #dc2626;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.main {
  flex: 1;
  background: #f8fafc;
  padding: 2rem;
}
</style>
