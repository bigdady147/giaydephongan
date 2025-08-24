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
          <button class="btn-logout">Đăng xuất</button>
        </div>
      </div>
    </header>
    
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
</script>

<style scoped>
.client-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.header {
  background: #1e40af;
  color: white;
  padding: 1rem 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.logo {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo h1 {
  font-size: 1.5rem;
  font-weight: bold;
  margin: 0;
}

.badge {
  background: #3b82f6;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.nav {
  display: flex;
  gap: 2rem;
}

.nav-link {
  color: white;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s;
}

.nav-link:hover {
  color: #93c5fd;
}

.btn-logout {
  background: #dc2626;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-logout:hover {
  background: #b91c1c;
}

.main {
  flex: 1;
  background: #f0f9ff;
}

.footer {
  background: #1e40af;
  color: white;
  padding: 1rem 0;
  text-align: center;
}

.footer p {
  margin: 0;
  font-size: 0.875rem;
}
</style>
