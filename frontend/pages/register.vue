<template>
  <div class="register-page">
    <div class="register-container">
      <div class="header-section">
        <h1>Tạo tài khoản</h1>
        <p>Đăng ký để theo dõi đơn hàng và tích điểm thành viên.</p>
      </div>

      <div v-if="generalError" class="general-error">{{ generalError }}</div>

      <form class="register-form" @submit.prevent="handleRegister">
        <BaseInput v-model="form.name" label="Họ và tên" type="text" required :error="errors.name" />
        <BaseInput v-model="form.username" label="Tên đăng nhập" type="text" required :error="errors.username" />
        <BaseInput v-model="form.email" label="Email" type="email" required :error="errors.email" />
        <BaseInput v-model="form.phone" label="Số điện thoại (không bắt buộc)" type="text" :error="errors.phone" />
        <BaseInput v-model="form.password" label="Mật khẩu" type="password" required :error="errors.password" />
        <BaseInput v-model="form.password_confirmation" label="Xác nhận mật khẩu" type="password" required />

        <BaseButton type="submit" full-width :loading="loading">Đăng ký</BaseButton>
      </form>

      <div class="login-prompt">
        Đã có tài khoản? <NuxtLink to="/login">Đăng nhập</NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'

const router = useRouter()
const auth = useAuth()

const form = reactive({
  name: '',
  username: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: ''
})

const errors = reactive({
  name: '',
  username: '',
  email: '',
  phone: '',
  password: ''
})

const generalError = ref('')
const loading = ref(false)

const resetErrors = () => {
  errors.name = ''
  errors.username = ''
  errors.email = ''
  errors.phone = ''
  errors.password = ''
  generalError.value = ''
}

const handleRegister = async () => {
  loading.value = true
  resetErrors()

  try {
    await auth.register({ ...form })
    await router.push('/')
  } catch (err: any) {
    if (err.errors) {
      for (const field of Object.keys(errors) as Array<keyof typeof errors>) {
        errors[field] = err.errors[field]?.[0] ?? ''
      }
    }
    generalError.value = err.message ?? 'Đăng ký thất bại, vui lòng thử lại.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
.register-page {
  display: flex;
  justify-content: center;
  min-height: 100vh;
  padding: 60px 40px;
  background-color: white;
}

.register-container {
  width: 100%;
  max-width: 420px;
}

.header-section {
  margin-bottom: 32px;

  h1 {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 8px;
  }

  p {
    color: #6b7280;
    font-size: 14px;
  }
}

.general-error {
  background-color: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 12px 16px;
  font-size: 14px;
  margin-bottom: 20px;
}

.register-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.login-prompt {
  text-align: center;
  font-size: 14px;
  color: #6b7280;
  margin-top: 24px;
}
</style>
