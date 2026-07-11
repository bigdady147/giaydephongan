<template>
  <div class="login-page">
    <!-- Left Section: Login Form -->
    <div class="login-left">
      <div class="login-container">
        <div class="logo-section">
          <div class="logo-icon">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="32" height="32">
              <rect width="24" height="24" rx="6" fill="#FF5C00"/>
              <path d="M6 12L10 16L18 8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="logo-text">SOLESPHERE</span>
        </div>

        <div class="header-section">
          <h1>Welcome Back</h1>
          <p>Step into your style. Log in to your account.</p>
        </div>

        <form class="login-form" @submit.prevent="handleLogin">
          <BaseInput
            v-model="loginForm.login"
            label="Email Address or Username"
            type="text"
            placeholder="name@example.com"
            has-icon
            required
            :error="errors.login"
          >
            <template #icon>
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            </template>
          </BaseInput>

          <div class="password-field">
            <BaseInput
              v-model="loginForm.password"
              label="Password"
              type="password"
              placeholder="••••••••"
              has-icon
              required
              :error="errors.password"
            >
              <template #icon>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              </template>
            </BaseInput>
          </div>

          <div class="form-utils">
            <label class="remember-me">
              <input type="checkbox" v-model="loginForm.remember">
              <span>Remember me</span>
            </label>
            <a href="#" class="forgot-password">Forgot password?</a>
          </div>

          <BaseButton type="submit" full-width :loading="loading">
            Sign In
          </BaseButton>
        </form>

        <div class="divider">
          <span>OR CONTINUE WITH</span>
        </div>

        <div class="social-logins">
          <SocialLoginButton provider="google" label="Google" @click="socialLogin('google')" />
          <SocialLoginButton provider="apple" label="Apple" @click="socialLogin('apple')" />
        </div>

        <div class="register-prompt">
          Don't have an account? <a href="#" class="join-link">Join the club</a>
        </div>
      </div>
    </div>

    <!-- Right Section: Branding/Image -->
    <div class="login-right">
      <div class="brand-content">
        <div class="new-collection-badge">NEW COLLECTION</div>
        <h2>UNLEASH THE ENERGY</h2>
        <p>Discover the exclusive SoleSphere Drop. Limited edition sneakers crafted for the urban explorer.</p>
        
        <div class="stats-card">
          <div class="avatars">
            <img src="https://i.pravatar.cc/150?u=1" alt="user">
            <img src="https://i.pravatar.cc/150?u=2" alt="user">
            <img src="https://i.pravatar.cc/150?u=3" alt="user">
          </div>
          <div class="stats-text">
            <strong>12k+</strong>
            <span>Community members</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

const loginForm = reactive({
  login: '',
  password: '',
  remember: false
})

const errors = reactive({
  login: '',
  password: ''
})

const loading = ref(false)

const handleLogin = async () => {
  loading.value = true
  errors.login = ''
  errors.password = ''
  
  try {
    // Integrate with Laravel Auth API later
    console.log('Logging in with:', loginForm)
    // await $fetch('/api/login', { method: 'POST', body: loginForm })
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500))
    alert('Logged in successfully (simulated)')
  } catch (err: any) {
    console.error(err)
    if (err.data?.errors) {
      Object.assign(errors, err.data.errors)
    }
  } finally {
    loading.value = false
  }
}

const socialLogin = (provider: string) => {
  console.log(`Logging in with ${provider}`)
}
</script>

<style scoped lang="scss">
.login-page {
  display: flex;
  min-height: 100vh;
  font-family: 'Inter', sans-serif;
}

.login-left {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: white;
  padding: 40px;
}

.login-container {
  width: 100%;
  max-width: 400px;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 40px;

  .logo-text {
    font-weight: 800;
    font-size: 20px;
    letter-spacing: 1px;
    color: #111827;
  }
}

.header-section {
  margin-bottom: 32px;

  h1 {
    font-size: 32px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 8px;
  }

  p {
    color: #6b7280;
    font-size: 14px;
  }
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-utils {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;

  .remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: #374151;
  }

  .forgot-password {
    color: #FF5C00;
    font-weight: 600;
    text-decoration: none;
    
    &:hover {
      text-decoration: underline;
    }
  }
}

.divider {
  position: relative;
  text-align: center;
  margin: 32px 0;

  &::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background-color: #f3f4f6;
  }

  span {
    position: relative;
    background-color: white;
    padding: 0 16px;
    font-size: 11px;
    font-weight: 600;
    color: #9ca3af;
    letter-spacing: 0.5px;
  }
}

.social-logins {
  display: flex;
  gap: 16px;
  margin-bottom: 32px;
}

.register-prompt {
  text-align: center;
  font-size: 14px;
  color: #6b7280;

  .join-link {
    color: #FF5C00;
    font-weight: 600;
    text-decoration: none;
    
    &:hover {
      text-decoration: underline;
    }
  }
}

.login-right {
  flex: 1;
  background-image: linear-gradient(rgba(0,0,0,0.1), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1542291026-7eec264c27ff?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80');
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: flex-end;
  padding: 60px;
  color: white;

  .brand-content {
    max-width: 480px;

    .new-collection-badge {
      display: inline-block;
      background-color: #FF5C00;
      color: white;
      padding: 4px 12px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 16px;
    }

    h2 {
      font-size: 48px;
      font-weight: 900;
      line-height: 1.1;
      margin-bottom: 20px;
    }

    p {
      font-size: 16px;
      opacity: 0.9;
      margin-bottom: 40px;
      line-height: 1.6;
    }
  }
}

.stats-card {
  display: flex;
  align-items: center;
  gap: 16px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  padding: 16px;
  border-radius: 12px;
  width: fit-content;

  .avatars {
    display: flex;
    margin-right: -8px;

    img {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,0.2);
      margin-left: -8px;

      &:first-child {
        margin-left: 0;
      }
    }
  }

  .stats-text {
    display: flex;
    flex-direction: column;

    strong {
      font-size: 16px;
    }

    span {
      font-size: 12px;
      opacity: 0.8;
    }
  }
}

/* Responsive */
@media (max-width: 1024px) {
  .login-right {
    display: none;
  }
}
</style>
