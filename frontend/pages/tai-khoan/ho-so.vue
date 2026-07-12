<template>
  <div class="profile-page">
    <h2>Hồ sơ cá nhân</h2>

    <div v-if="successMsg" class="alert alert-success">{{ successMsg }}</div>
    <div v-if="errorMsg" class="alert alert-danger">{{ errorMsg }}</div>

    <form class="profile-form" @submit.prevent="saveProfile">
      <div class="form-group">
        <label for="name">Họ và tên *</label>
        <input id="name" v-model="form.name" type="text" required>
      </div>

      <div class="form-group-row">
        <div class="form-group">
          <label for="phone">Số điện thoại</label>
          <input id="phone" v-model="form.phone" type="tel">
        </div>
        <div class="form-group">
          <label for="gender">Giới tính</label>
          <select id="gender" v-model="form.gender">
            <option value="">Chọn giới tính</option>
            <option value="male">Nam</option>
            <option value="female">Nữ</option>
            <option value="other">Khác</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="birthday">Ngày sinh</label>
        <input id="birthday" v-model="form.birthday" type="date">
      </div>

      <div class="form-group">
        <label for="address">Địa chỉ giao hàng mặc định</label>
        <textarea id="address" v-model="form.address" rows="3" placeholder="Số nhà, tên đường, phường/xã..."></textarea>
      </div>

      <button type="submit" class="btn-save" :disabled="submitting">
        {{ submitting ? 'Đang lưu...' : 'Lưu thay đổi' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

definePageMeta({ layout: 'account', middleware: 'auth' })

const api = useApiClient()
const { user, fetchUser } = useAuth()

const form = reactive({
  name: '',
  phone: '',
  gender: '',
  birthday: '',
  address: ''
})

const submitting = ref(false)
const successMsg = ref('')
const errorMsg = ref('')

const initForm = () => {
  if (user.value) {
    form.name = user.value.name || ''
    form.phone = user.value.phone || ''
    form.gender = user.value.gender || ''
    form.address = user.value.address || ''
    if (user.value.birthday) {
      // Format YYYY-MM-DD
      form.birthday = user.value.birthday.split('T')[0]
    }
  }
}

const saveProfile = async () => {
  submitting.value = true
  successMsg.value = ''
  errorMsg.value = ''
  try {
    const payload = {
      name: form.name,
      phone: form.phone || null,
      gender: form.gender || null,
      address: form.address || null,
      birthday: form.birthday || null
    }
    await api.put('/user/profile', payload)
    // Refresh user state in useAuth
    await fetchUser()
    successMsg.value = 'Đã cập nhật hồ sơ cá nhân thành công.'
  } catch (err: any) {
    errorMsg.value = err.message ?? 'Cập nhật thất bại. Vui lòng thử lại.'
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  initForm()
})
</script>

<style scoped lang="scss">
.profile-page {
  h2 { font-size: 20px; font-weight: 700; margin-bottom: 20px; letter-spacing: -0.01em; }
}
.profile-form {
  background: #fff; border: 1px solid $sf-color-border; border-radius: $sf-radius; padding: 24px; max-width: 600px;
}
.form-group-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group {
  margin-bottom: 18px;
  label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
  input, textarea, select {
    width: 100%; border: 1px solid $sf-color-border; border-radius: 8px; padding: 10px 12px;
    font-size: 14px; outline: none; &:focus { border-color: $sf-color-accent; }
  }
}
.btn-save {
  background: $sf-color-accent; color: #fff; border: 0; font-size: 14px; font-weight: 700;
  padding: 12px 24px; border-radius: 8px; cursor: pointer; transition: background 0.2s;
  &:hover:not(:disabled) { background: $sf-color-accent-dark; }
  &:disabled { opacity: 0.5; }
}
.alert {
  padding: 12px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; margin-bottom: 20px;
  &.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
  &.alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
}
</style>
