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
          {{ $t('navigation.dashboard') }}
        </NuxtLink>
        <NuxtLink to="/admin/categories" class="nav-item">
          <span class="nav-icon">🗂️</span>
          Danh mục
        </NuxtLink>
        <NuxtLink to="/admin/brands" class="nav-item">
          <span class="nav-icon">🏷️</span>
          Thương hiệu
        </NuxtLink>
        <NuxtLink to="/admin/products" class="nav-item">
          <span class="nav-icon">👞</span>
          Sản phẩm
        </NuxtLink>
        <NuxtLink to="/admin/users" class="nav-item">
          <span class="nav-icon">👥</span>
          {{ $t('navigation.users') }}
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
const router = useRouter()
const { t } = useI18n()
const auth = useAuth()

const handleLogout = async () => {
  if (confirm(t('auth.logoutConfirm'))) {
    try {
      await auth.logout()
    } catch {
      // Local session is already cleared by logout()'s finally block;
      // a failed server call must not strand the user on the admin page.
    }
    router.push('/login')
  }
}
</script>


