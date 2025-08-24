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


