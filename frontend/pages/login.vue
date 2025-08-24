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

<style scoped>
.login-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.login-container {
  width: 100%;
  max-width: 400px;
}

.login-card {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  padding: 2rem;
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.login-header h1 {
  color: #374151;
  font-size: 1.875rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.login-header p {
  color: #6b7280;
}

.login-form {
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #374151;
  font-weight: 500;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-group input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 0.875rem;
}

.checkbox input {
  width: auto;
}

.forgot-link {
  color: #667eea;
  text-decoration: none;
  font-size: 0.875rem;
}

.forgot-link:hover {
  text-decoration: underline;
}

.btn-login {
  width: 100%;
  background: #667eea;
  color: white;
  padding: 0.75rem;
  border: none;
  border-radius: 0.5rem;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-login:hover:not(:disabled) {
  background: #5a67d8;
}

.btn-login:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.login-footer {
  text-align: center;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.login-footer p {
  color: #6b7280;
  margin: 0;
}

.login-footer a {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
}

.login-footer a:hover {
  text-decoration: underline;
}
</style>
