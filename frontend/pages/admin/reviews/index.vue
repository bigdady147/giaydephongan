<template>
  <div class="reviews-admin-page">
    <div class="page-header">
      <h1>Duyệt đánh giá</h1>
    </div>

    <el-alert v-if="error" :title="error" type="error" show-icon class="page-alert" />

    <el-radio-group v-model="filterStatus" @change="loadAll">
      <el-radio-button label="">Tất cả</el-radio-button>
      <el-radio-button label="pending">Chờ duyệt</el-radio-button>
      <el-radio-button label="approved">Đã duyệt</el-radio-button>
      <el-radio-button label="rejected">Đã từ chối</el-radio-button>
    </el-radio-group>

    <el-table :data="reviews" v-loading="loading" stripe style="width: 100%; margin-top: 16px;">
      <el-table-column label="Sản phẩm" width="220">
        <template #default="{ row }">{{ (row as Review).product.name }}</template>
      </el-table-column>
      <el-table-column label="Khách hàng" width="160">
        <template #default="{ row }">{{ (row as Review).user.name }}</template>
      </el-table-column>
      <el-table-column label="Đánh giá" width="110">
        <template #default="{ row }">{{ '★'.repeat((row as Review).rating) }}</template>
      </el-table-column>
      <el-table-column prop="comment" label="Nhận xét" />
      <el-table-column label="Trạng thái" width="120" align="center">
        <template #default="{ row }">
          <el-tag :type="statusTagType((row as Review).status)">{{ statusLabel((row as Review).status) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="" width="220" align="center">
        <template #default="{ row }">
          <el-button v-if="(row as Review).status !== 'approved'" link type="success" @click="setStatus(row as Review, 'approved')">Duyệt</el-button>
          <el-button v-if="(row as Review).status !== 'rejected'" link type="warning" @click="setStatus(row as Review, 'rejected')">Từ chối</el-button>
          <el-button link type="danger" @click="removeReview(row as Review)">Xóa</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="pagination-container" style="margin-top: 16px; display: flex; justify-content: flex-end;">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        layout="prev, pager, next"
        :total="totalRows"
        @current-change="loadAll"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

definePageMeta({ layout: 'admin' })

interface Review {
  id: number
  rating: number
  comment: string | null
  status: 'pending' | 'approved' | 'rejected'
  created_at: string
  product: { id: number; name: string; slug: string }
  user: { id: number; name: string }
}

const api = useApiClient()

const reviews = ref<Review[]>([])
const loading = ref(true)
const error = ref('')
const filterStatus = ref('')
const currentPage = ref(1)
const pageSize = ref(15)
const totalRows = ref(0)

const loadAll = async () => {
  loading.value = true
  error.value = ''
  try {
    let url = `/admin/reviews?page=${currentPage.value}`
    if (filterStatus.value) {
      url += `&status=${filterStatus.value}`
    }
    const res = await api.get<{ data: Review[]; total: number; current_page: number }>(url)
    reviews.value = res.data
    totalRows.value = res.total
    currentPage.value = res.current_page
  } catch (err: any) {
    error.value = err.message ?? 'Không thể tải danh sách đánh giá.'
  } finally {
    loading.value = false
  }
}

const statusLabel = (status: Review['status']) => ({
  pending: 'Chờ duyệt',
  approved: 'Đã duyệt',
  rejected: 'Đã từ chối'
})[status]

const statusTagType = (status: Review['status']) => ({
  pending: 'info',
  approved: 'success',
  rejected: 'danger'
})[status] as 'info' | 'success' | 'danger'

const setStatus = async (review: Review, status: 'approved' | 'rejected') => {
  try {
    await api.patch(`/admin/reviews/${review.id}/status`, { status })
    ElMessage.success('Đã cập nhật trạng thái đánh giá')
    await loadAll()
  } catch (err: any) {
    ElMessage.error(err.message ?? 'Cập nhật thất bại.')
  }
}

const removeReview = async (review: Review) => {
  try {
    await ElMessageBox.confirm('Xóa đánh giá này?', 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await api.del(`/admin/reviews/${review.id}`)
    ElMessage.success('Đã xóa đánh giá')
    await loadAll()
  } catch (err: any) {
    ElMessage.error(err.message ?? 'Xóa thất bại.')
  }
}

onMounted(loadAll)
</script>

<style scoped lang="scss">
.reviews-admin-page { max-width: 1200px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-alert { margin-bottom: 16px; }
</style>
