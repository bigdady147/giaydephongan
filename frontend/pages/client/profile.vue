<template>
  <div class="profile-page">
    <div class="container">
      <div class="header">
        <h1>Hồ sơ cá nhân</h1>
        <p>Quản lý thông tin cá nhân của bạn</p>
      </div>
      
      <div class="content">
        <div class="profile-card">
          <div class="profile-header">
            <div class="avatar">
              <span>{{ userInitials }}</span>
            </div>
            <div class="user-info">
              <h2>{{ user.name }}</h2>
              <p>{{ user.email }}</p>
              <span class="status active">Đang hoạt động</span>
            </div>
          </div>
          
          <div class="profile-form">
            <h3>Thông tin cá nhân</h3>
            <form @submit.prevent="updateProfile">
              <div class="form-group">
                <label>Họ và tên</label>
                <input v-model="form.name" type="text" placeholder="Nhập họ và tên">
              </div>
              
              <div class="form-group">
                <label>Email</label>
                <input v-model="form.email" type="email" placeholder="Nhập email">
              </div>
              
              <div class="form-group">
                <label>Số điện thoại</label>
                <input v-model="form.phone" type="tel" placeholder="Nhập số điện thoại">
              </div>
              
              <div class="form-group">
                <label>Địa chỉ</label>
                <textarea v-model="form.address" placeholder="Nhập địa chỉ"></textarea>
              </div>
              
              <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'client'
})

// Mock user data
const user = ref({
  name: 'Nguyễn Văn A',
  email: 'nguyenvana@example.com',
  phone: '0123456789',
  address: '123 Đường ABC, Quận 1, TP.HCM'
})

const form = ref({
  name: user.value.name,
  email: user.value.email,
  phone: user.value.phone,
  address: user.value.address
})

const userInitials = computed(() => {
  return user.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
})

const updateProfile = () => {
  // Simulate API call
  console.log('Updating profile:', form.value)
  alert('Cập nhật thông tin thành công!')
}
</script>

<style scoped>
.profile-page {
  padding: 2rem;
}

.container {
  max-width: 800px;
  margin: 0 auto;
}

.header {
  text-align: center;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2rem;
  font-weight: bold;
  color: #1e40af;
  margin-bottom: 0.5rem;
}

.header p {
  color: #6b7280;
}

.profile-card {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.profile-header {
  background: #1e40af;
  color: white;
  padding: 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.avatar {
  width: 80px;
  height: 80px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: bold;
}

.user-info h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
}

.user-info p {
  margin: 0 0 0.5rem 0;
  opacity: 0.9;
}

.status {
  background: #059669;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.profile-form {
  padding: 2rem;
}

.profile-form h3 {
  color: #374151;
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

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 1rem;
}

.form-group textarea {
  height: 100px;
  resize: vertical;
}

.btn {
  background: #1e40af;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn:hover {
  background: #1e3a8a;
}
</style>
