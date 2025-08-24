<template>
  <div class="admin-page">
    <div class="stats-grid">
      <div class="stat-card">
        <h3>Tổng số hồ sơ</h3>
        <div class="stat-number">1,234</div>
        <p class="stat-change positive">+12% so với tháng trước</p>
      </div>
      
      <div class="stat-card">
        <h3>Hồ sơ đang xử lý</h3>
        <div class="stat-number">89</div>
        <p class="stat-change neutral">Cần xử lý</p>
      </div>
      
      <div class="stat-card">
        <h3>Hồ sơ hoàn thành</h3>
        <div class="stat-number">1,145</div>
        <p class="stat-change positive">+8% so với tháng trước</p>
      </div>
      
      <div class="stat-card">
        <h3>Người dùng mới</h3>
        <div class="stat-number">45</div>
        <p class="stat-change positive">+15% so với tháng trước</p>
      </div>
    </div>
    
    <div class="actions">
      <h2>Thao tác nhanh</h2>
      <div class="action-buttons">
        <button class="btn btn-primary">Quản lý hồ sơ</button>
        <button class="btn btn-secondary">Quản lý người dùng</button>
        <button class="btn btn-success">Báo cáo</button>
        <button class="btn btn-warning">Cài đặt hệ thống</button>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin'
})
</script>

<style scoped>
.admin-page {
  max-width: 1200px;
  margin: 0 auto;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
}

.stat-card h3 {
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.stat-number {
  font-size: 2rem;
  font-weight: bold;
  color: #dc2626;
  margin-bottom: 0.5rem;
}

.stat-change {
  font-size: 0.875rem;
  margin: 0;
}

.stat-change.positive {
  color: #059669;
}

.stat-change.negative {
  color: #dc2626;
}

.stat-change.neutral {
  color: #6b7280;
}

.actions {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  padding: 2rem;
  border: 1px solid #e5e7eb;
}

.actions h2 {
  color: #374151;
  margin-bottom: 1.5rem;
}

.action-buttons {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 0.375rem;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  display: inline-block;
  text-align: center;
}

.btn-primary {
  background-color: #dc2626;
  color: white;
}

.btn-primary:hover {
  background-color: #b91c1c;
}

.btn-secondary {
  background-color: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background-color: #4b5563;
}

.btn-success {
  background-color: #059669;
  color: white;
}

.btn-success:hover {
  background-color: #047857;
}

.btn-warning {
  background-color: #d97706;
  color: white;
}

.btn-warning:hover {
  background-color: #b45309;
}
</style>
