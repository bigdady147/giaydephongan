<template>
  <div class="client-page">
    <div class="container">
      <div class="header">
        <h1>Client Portal</h1>
        <p>Phần dành cho khách hàng</p>
      </div>
      
      <div class="content">
        <div class="card">
          <h2>Chào mừng đến với Client Portal</h2>
          <p>Đây là phần dành cho khách hàng sử dụng hệ thống</p>
          
          <div class="features">
            <div class="feature-item">
              <h3>Quản lý hồ sơ</h3>
              <p>Xem và cập nhật thông tin cá nhân</p>
            </div>
            <div class="feature-item">
              <h3>Đăng ký dịch vụ</h3>
              <p>Đăng ký các dịch vụ mới</p>
            </div>
            <div class="feature-item">
              <h3>Theo dõi tiến độ</h3>
              <p>Kiểm tra trạng thái hồ sơ</p>
            </div>
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
</script>

<style scoped>
.client-page {
  padding: 2rem;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  text-align: center;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2.5rem;
  font-weight: bold;
  color: #1e40af;
  margin-bottom: 0.5rem;
}

.header p {
  font-size: 1.125rem;
  color: #6b7280;
}

.content {
  max-width: 800px;
  margin: 0 auto;
}

.card {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  padding: 2rem;
  border: 1px solid #e5e7eb;
}

.card h2 {
  color: #1e40af;
  margin-bottom: 1rem;
}

.features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}

.feature-item {
  padding: 1.5rem;
  background: #f8fafc;
  border-radius: 0.5rem;
  border: 1px solid #e2e8f0;
}

.feature-item h3 {
  color: #1e40af;
  margin-bottom: 0.5rem;
}

.feature-item p {
  color: #6b7280;
}
</style>
