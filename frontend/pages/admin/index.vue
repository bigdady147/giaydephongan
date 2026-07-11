<template>
  <div class="dashboard-page">
    <h1 class="page-title">Tổng quan</h1>

    <el-alert v-if="loadError" :title="loadError" type="error" show-icon class="dashboard-alert" />

    <el-row :gutter="20" v-loading="loading">
      <el-col :span="6">
        <el-card shadow="hover">
          <el-statistic title="Sản phẩm" :value="stats.products_count" />
          <p class="stat-sub">{{ stats.products_published_count }} đã đăng · {{ stats.products_draft_count }} nháp</p>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <el-statistic title="Danh mục" :value="stats.categories_count" />
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <el-statistic title="Thương hiệu" :value="stats.brands_count" />
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover">
          <el-statistic title="Biến thể sắp hết hàng" :value="stats.low_stock_variants_count" />
          <p class="stat-sub">Trong tổng {{ stats.variants_count }} biến thể (dưới 5 đôi)</p>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="quick-links">
      <el-col :span="8">
        <el-card shadow="never">
          <template #header>Thao tác nhanh</template>
          <div class="quick-actions">
            <el-button type="primary" @click="router.push('/admin/products')">+ Thêm sản phẩm</el-button>
            <el-button @click="router.push('/admin/categories')">Quản lý danh mục</el-button>
            <el-button @click="router.push('/admin/brands')">Quản lý thương hiệu</el-button>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

definePageMeta({ layout: 'admin' })

interface DashboardStats {
  categories_count: number
  brands_count: number
  products_count: number
  products_published_count: number
  products_draft_count: number
  products_archived_count: number
  variants_count: number
  low_stock_variants_count: number
}

const router = useRouter()
const api = useApiClient()

const loading = ref(true)
const loadError = ref('')
const stats = reactive<DashboardStats>({
  categories_count: 0,
  brands_count: 0,
  products_count: 0,
  products_published_count: 0,
  products_draft_count: 0,
  products_archived_count: 0,
  variants_count: 0,
  low_stock_variants_count: 0
})

const loadStats = async () => {
  loading.value = true
  loadError.value = ''
  try {
    Object.assign(stats, await api.get<DashboardStats>('/admin/dashboard/stats'))
  } catch (err: any) {
    loadError.value = err.message ?? 'Không thể tải thống kê.'
  } finally {
    loading.value = false
  }
}

onMounted(loadStats)
</script>

<style scoped lang="scss">
.dashboard-page {
  max-width: 1200px;
}

.page-title {
  margin-bottom: 20px;
}

.stat-sub {
  margin-top: 8px;
  font-size: 12px;
  color: #6b7280;
}

.quick-links {
  margin-top: 20px;
}

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.dashboard-alert {
  margin-bottom: 16px;
}
</style>
