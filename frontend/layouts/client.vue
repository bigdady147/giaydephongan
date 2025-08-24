<template>
  <div class="client-layout">
    <header class="header">
      <div class="container">
        <div class="logo">
          <h1>Giày dép Hồng An</h1>
          <span class="badge">Client Portal</span>
        </div>
        <nav class="nav">
          <NuxtLink to="/client" class="nav-link">{{ $t('navigation.home') }}</NuxtLink>
          <NuxtLink to="/client/profile" class="nav-link">{{ $t('navigation.profile') }}</NuxtLink>
          <NuxtLink to="/client/services" class="nav-link">{{ $t('navigation.services') }}</NuxtLink>
          <NuxtLink to="/client/tracking" class="nav-link">{{ $t('navigation.tracking') }}</NuxtLink>
        </nav>
        <div class="user-menu">
                      <button @click="handleLogout" class="btn-logout">{{ $t('navigation.logout') }}</button>
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
    if (confirm($t('auth.logoutConfirm'))) {
      // Clear authentication data
      localStorage.removeItem('isAuthenticated')
      localStorage.removeItem('userRole')
      
      // Redirect to login page
      router.push('/login')
    }
  }
</script>


