<template>
  <el-container class="admin-layout">
    <el-aside width="220px" class="admin-sidebar">
      <div class="sidebar-brand">
        <span class="brand-icon">👞</span>
        <span class="brand-name">Hồng An Admin</span>
      </div>
      <el-menu :default-active="route.path" router class="sidebar-menu" background-color="#1f2937" text-color="#d1d5db" active-text-color="#ffffff">
        <el-menu-item index="/admin">
          <el-icon><Odometer /></el-icon>
          <span>{{ $t('navigation.dashboard') }}</span>
        </el-menu-item>
        <el-menu-item index="/admin/categories">
          <el-icon><Collection /></el-icon>
          <span>Danh mục</span>
        </el-menu-item>
        <el-menu-item index="/admin/brands">
          <el-icon><PriceTag /></el-icon>
          <span>Thương hiệu</span>
        </el-menu-item>
        <el-menu-item index="/admin/products">
          <el-icon><Goods /></el-icon>
          <span>Sản phẩm</span>
        </el-menu-item>
        <el-menu-item index="/admin/banners">
          <el-icon><Picture /></el-icon>
          <span>Banners</span>
        </el-menu-item>
        <el-menu-item index="/admin/pages">
          <el-icon><Document /></el-icon>
          <span>Trang tĩnh</span>
        </el-menu-item>
        <el-menu-item index="/admin/settings">
          <el-icon><Setting /></el-icon>
          <span>Cài đặt shop</span>
        </el-menu-item>
        <el-menu-item v-if="isAdmin" index="/admin/users">
          <el-icon><User /></el-icon>
          <span>{{ $t('navigation.users') }}</span>
        </el-menu-item>
      </el-menu>
    </el-aside>

    <el-container>
      <el-header class="admin-header">
        <span class="header-title">Giày dép Hồng An</span>
        <el-dropdown @command="handleCommand">
          <span class="header-user">
            <el-avatar :size="32">{{ userInitial }}</el-avatar>
            <span class="header-username">{{ user?.name ?? 'Admin' }}</span>
            <el-icon><ArrowDown /></el-icon>
          </span>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="logout">Đăng xuất</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </el-header>

      <el-main class="admin-main">
        <slot />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Odometer, Collection, PriceTag, Goods, User, ArrowDown, Picture, Document, Setting } from '@element-plus/icons-vue'
import { ElMessageBox } from 'element-plus'

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const { user, isAdmin, logout } = useAuth()

const userInitial = computed(() => (user.value?.name ?? 'A').charAt(0).toUpperCase())

const handleCommand = async (command: string) => {
  if (command !== 'logout') return

  try {
    await ElMessageBox.confirm(t('auth.logoutConfirm'), 'Xác nhận', {
      confirmButtonText: 'Đăng xuất',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await logout()
  } catch {
    // Local session is already cleared by logout()'s finally block.
  }
  router.push('/login')
}
</script>

<style scoped lang="scss">
.admin-layout {
  min-height: 100vh;
}

.admin-sidebar {
  background-color: #1f2937;
  display: flex;
  flex-direction: column;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 20px 16px;
  color: white;
  font-weight: 700;
  font-size: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar-menu {
  border-right: none;
  flex: 1;
}

.admin-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #e5e7eb;
  background-color: white;
}

.header-title {
  font-weight: 600;
  font-size: 16px;
}

.header-user {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.header-username {
  font-size: 14px;
  color: #374151;
}

.admin-main {
  background-color: #f3f4f6;
  padding: 24px;
}
</style>
