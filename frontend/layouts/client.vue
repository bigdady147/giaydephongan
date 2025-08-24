<template>
  <div class="client-layout">
    <header class="header">
      <div class="container">
        <div class="logo">
          <h1>Giày dép Hồng An</h1>
          <span class="badge">Client Portal</span>
        </div>
        <nav class="nav">
          <NuxtLink to="/client" class="nav-link">Trang chủ</NuxtLink>
          <NuxtLink to="/client/profile" class="nav-link">Hồ sơ</NuxtLink>
          <NuxtLink to="/client/services" class="nav-link">Dịch vụ</NuxtLink>
          <NuxtLink to="/client/tracking" class="nav-link">Theo dõi</NuxtLink>
        </nav>
        <div class="user-menu">
          <button @click="handleLogout" class="btn-logout">Đăng xuất</button>
        </div>
      </div>
    </header>
     123123
    <main class="main">
      <slot />
    </main>
    
    <footer class="footer">
      <div class="container">
        <p>&copy; 2024 Giày dép Hồng An. Client Portal.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
// Client layout logic
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


