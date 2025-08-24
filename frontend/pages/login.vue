<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-card">
        <div class="login-header">
          <h1>Đăng nhập</h1>
          <p>Vui lòng đăng nhập để tiếp tục</p>
        </div>
        
        <form @submit.prevent="handleLogin" class="login-form">
          <div class="form-group">
            <label>Email</label>
            <input 
              v-model="form.email" 
              type="email" 
              placeholder="Nhập email của bạn"
              required
            >
          </div>
          
          <div class="form-group">
            <label>Mật khẩu</label>
            <input 
              v-model="form.password" 
              type="password" 
              placeholder="Nhập mật khẩu"
              required
            >
          </div>
          
          <div class="form-options">
            <label class="checkbox">
              <input type="checkbox" v-model="form.remember">
              <span>Ghi nhớ đăng nhập</span>
            </label>
            <NuxtLink to="/forgot-password" class="forgot-link">
              Quên mật khẩu?
            </NuxtLink>
          </div>
          
          <button type="submit" class="btn-login" :disabled="loading">
            {{ loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
          </button>
        </form>
        
        <div class="login-footer">
          <p>Chưa có tài khoản? <NuxtLink to="/register">Đăng ký ngay</NuxtLink></p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const router = useRouter()
const loading = ref(false)

const form = ref({
  email: '',
  password: '',
  remember: false
})

const handleLogin = async () => {
  loading.value = true
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Mock authentication logic
    if (form.value.email === 'admin@example.com' && form.value.password === 'password') {
      // Set authentication state (in real app, save token to localStorage/cookie)
      localStorage.setItem('isAuthenticated', 'true')
      localStorage.setItem('userRole', 'admin')
      
      // Redirect to admin dashboard
      await router.push('/admin')
    } else if (form.value.email === 'user@example.com' && form.value.password === 'password') {
      // Set authentication state
      localStorage.setItem('isAuthenticated', 'true')
      localStorage.setItem('userRole', 'user')
      
      // Redirect to client dashboard
      await router.push('/client')
    } else {
      alert('Email hoặc mật khẩu không đúng!')
    }
  } catch (error) {
    console.error('Login error:', error)
    alert('Có lỗi xảy ra khi đăng nhập!')
  } finally {
    loading.value = false
  }
}
</script>


