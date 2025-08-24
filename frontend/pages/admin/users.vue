<template>
  <div class="users-page">
    <div class="header">
      <h1>Quản lý người dùng</h1>
      <button @click="showAddUserModal = true" class="btn btn-primary">
        + Thêm người dùng mới
      </button>
    </div>
    
    <div class="content">
      <div class="filters">
        <div class="search-box">
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Tìm kiếm người dùng..."
            @input="filterUsers"
          >
        </div>
        <div class="filter-options">
          <select v-model="statusFilter" @change="filterUsers">
            <option value="">Tất cả trạng thái</option>
            <option value="active">Đang hoạt động</option>
            <option value="inactive">Không hoạt động</option>
            <option value="pending">Chờ xác nhận</option>
          </select>
        </div>
      </div>
      
      <div class="users-table">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Họ tên</th>
              <th>Email</th>
              <th>Số điện thoại</th>
              <th>Vai trò</th>
              <th>Trạng thái</th>
              <th>Ngày tạo</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in filteredUsers" :key="user.id">
              <td>{{ user.id }}</td>
              <td>
                <div class="user-info">
                  <div class="avatar">{{ getUserInitials(user.name) }}</div>
                  <span>{{ user.name }}</span>
                </div>
              </td>
              <td>{{ user.email }}</td>
              <td>{{ user.phone }}</td>
              <td>
                <span class="role-badge" :class="user.role">
                  {{ getRoleText(user.role) }}
                </span>
              </td>
              <td>
                <span class="status-badge" :class="user.status">
                  {{ getStatusText(user.status) }}
                </span>
              </td>
              <td>{{ formatDate(user.createdAt) }}</td>
              <td>
                <div class="actions">
                  <button @click="editUser(user)" class="btn-icon edit">✏️</button>
                  <button @click="deleteUser(user.id)" class="btn-icon delete">🗑️</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Add User Modal -->
    <div v-if="showAddUserModal" class="modal-overlay" @click="showAddUserModal = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>Thêm người dùng mới</h3>
          <button @click="showAddUserModal = false" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="addUser">
            <div class="form-group">
              <label>Họ tên</label>
              <input v-model="newUser.name" type="text" required>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input v-model="newUser.email" type="email" required>
            </div>
            <div class="form-group">
              <label>Số điện thoại</label>
              <input v-model="newUser.phone" type="tel">
            </div>
            <div class="form-group">
              <label>Vai trò</label>
              <select v-model="newUser.role" required>
                <option value="user">Người dùng</option>
                <option value="admin">Admin</option>
                <option value="moderator">Moderator</option>
              </select>
            </div>
            <div class="form-actions">
              <button type="button" @click="showAddUserModal = false" class="btn btn-secondary">
                Hủy
              </button>
              <button type="submit" class="btn btn-primary">
                Thêm người dùng
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin'
})

// Mock users data
const users = ref([
  {
    id: 1,
    name: 'Nguyễn Văn A',
    email: 'nguyenvana@example.com',
    phone: '0123456789',
    role: 'admin',
    status: 'active',
    createdAt: '2024-01-01'
  },
  {
    id: 2,
    name: 'Trần Thị B',
    email: 'tranthib@example.com',
    phone: '0987654321',
    role: 'user',
    status: 'active',
    createdAt: '2024-01-05'
  },
  {
    id: 3,
    name: 'Lê Văn C',
    email: 'levanc@example.com',
    phone: '0555666777',
    role: 'moderator',
    status: 'pending',
    createdAt: '2024-01-10'
  }
])

const searchQuery = ref('')
const statusFilter = ref('')
const filteredUsers = ref([...users.value])
const showAddUserModal = ref(false)

const newUser = ref({
  name: '',
  email: '',
  phone: '',
  role: 'user'
})

const getUserInitials = (name) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
}

const getRoleText = (role) => {
  const roleMap = {
    admin: 'Admin',
    user: 'Người dùng',
    moderator: 'Moderator'
  }
  return roleMap[role] || role
}

const getStatusText = (status) => {
  const statusMap = {
    active: 'Hoạt động',
    inactive: 'Không hoạt động',
    pending: 'Chờ xác nhận'
  }
  return statusMap[status] || status
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('vi-VN')
}

const filterUsers = () => {
  let filtered = [...users.value]
  
  if (searchQuery.value) {
    filtered = filtered.filter(user => 
      user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      user.email.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }
  
  if (statusFilter.value) {
    filtered = filtered.filter(user => user.status === statusFilter.value)
  }
  
  filteredUsers.value = filtered
}

const addUser = () => {
  const user = {
    id: users.value.length + 1,
    ...newUser.value,
    status: 'active',
    createdAt: new Date().toISOString().split('T')[0]
  }
  
  users.value.push(user)
  filteredUsers.value = [...users.value]
  showAddUserModal.value = false
  
  // Reset form
  newUser.value = {
    name: '',
    email: '',
    phone: '',
    role: 'user'
  }
  
  alert('Thêm người dùng thành công!')
}

const editUser = (user) => {
  alert(`Chỉnh sửa người dùng: ${user.name}`)
}

const deleteUser = (userId) => {
  if (confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
    users.value = users.value.filter(user => user.id !== userId)
    filteredUsers.value = [...users.value]
    alert('Xóa người dùng thành công!')
  }
}
</script>

<style scoped>
.users-page {
  padding: 2rem;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header h1 {
  color: #374151;
  margin: 0;
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.search-box input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  width: 300px;
}

.filter-options select {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  background: white;
}

.users-table {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

th {
  background: #f8fafc;
  font-weight: 600;
  color: #374151;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.avatar {
  width: 2rem;
  height: 2rem;
  background: #dc2626;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: bold;
}

.role-badge, .status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.role-badge.admin {
  background: #dc2626;
  color: white;
}

.role-badge.user {
  background: #3b82f6;
  color: white;
}

.role-badge.moderator {
  background: #f59e0b;
  color: white;
}

.status-badge.active {
  background: #dcfce7;
  color: #059669;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #dc2626;
}

.status-badge.pending {
  background: #fef3c7;
  color: #d97706;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 0.25rem;
  transition: background-color 0.2s;
}

.btn-icon:hover {
  background: #f3f4f6;
}

.btn-icon.edit:hover {
  background: #dbeafe;
}

.btn-icon.delete:hover {
  background: #fee2e2;
}

.btn {
  background: #dc2626;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn:hover {
  background: #b91c1c;
}

.btn-secondary {
  background: #6b7280;
}

.btn-secondary:hover {
  background: #4b5563;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 0.5rem;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  color: #374151;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #374151;
  font-weight: 500;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}
</style>
