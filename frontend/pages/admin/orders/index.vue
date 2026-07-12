<template>
  <div class="orders-admin-page">
    <div class="page-header">
      <h1>Quản lý Đơn hàng</h1>
    </div>

    <el-alert v-if="error" :title="error" type="error" show-icon class="page-alert" />

    <div class="filter-bar">
      <el-radio-group v-model="filterStatus" @change="loadAll">
        <el-radio-button label="">Tất cả</el-radio-button>
        <el-radio-button label="pending">Chờ xác nhận</el-radio-button>
        <el-radio-button label="confirmed">Đã xác nhận</el-radio-button>
        <el-radio-button label="shipping">Đang giao</el-radio-button>
        <el-radio-button label="delivered">Đã giao</el-radio-button>
        <el-radio-button label="cancelled">Đã hủy</el-radio-button>
      </el-radio-group>

      <el-input
        v-model="searchQuery"
        placeholder="Tìm mã đơn, tên hoặc số điện thoại..."
        clearable
        style="width: 300px"
        @clear="loadAll"
        @keyup.enter="loadAll"
      >
        <template #append>
          <el-button @click="loadAll">🔍</el-button>
        </template>
      </el-input>
    </div>

    <el-table :data="orders" v-loading="loading" stripe style="width: 100%; margin-top: 16px;">
      <el-table-column prop="order_code" label="Mã đơn hàng" width="160" />
      <el-table-column label="Khách hàng">
        <template #default="{ row }">
          <div v-if="(row as Order).user">
            <strong>{{ (row as Order).user?.name }}</strong> (Thành viên)
          </div>
          <div v-else>
            <strong>{{ (row as Order).guest_name }}</strong> (Khách vãng lai)
            <div class="phone-sub">{{ (row as Order).guest_phone }}</div>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="Ngày đặt" width="160">
        <template #default="{ row }">
          {{ formatDate((row as Order).created_at) }}
        </template>
      </el-table-column>
      <el-table-column label="Tổng tiền" width="140">
        <template #default="{ row }">
          {{ (row as Order).total.toLocaleString('vi-VN') }}đ
        </template>
      </el-table-column>
      <el-table-column label="Trạng thái" width="130" align="center">
        <template #default="{ row }">
          <el-tag :type="statusTagType((row as Order).status)">
            {{ statusLabel((row as Order).status) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="" width="120" align="center">
        <template #default="{ row }">
          <el-button link type="primary" @click="viewDetails(row as Order)">Xem chi tiết</el-button>
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

    <!-- Details Drawer -->
    <el-drawer v-model="drawerVisible" title="Chi tiết đơn hàng" size="550px" destroy-on-close>
      <div v-if="selectedOrder" class="details-container">
        <div class="details-section">
          <h3>Thông tin khách hàng</h3>
          <table class="info-table">
            <tr>
              <td>Họ và tên:</td>
              <td>
                <strong v-if="selectedOrder.user">{{ selectedOrder.user.name }} (Thành viên)</strong>
                <strong v-else>{{ selectedOrder.guest_name }}</strong>
              </td>
            </tr>
            <tr v-if="selectedOrder.guest_email || selectedOrder.user?.email">
              <td>Email:</td>
              <td>{{ selectedOrder.user?.email ?? selectedOrder.guest_email }}</td>
            </tr>
            <tr>
              <td>Số điện thoại:</td>
              <td>{{ selectedOrder.user?.phone ?? selectedOrder.guest_phone ?? 'Không có' }}</td>
            </tr>
            <tr>
              <td>Địa chỉ giao hàng:</td>
              <td>{{ selectedOrder.shipping_address }}</td>
            </tr>
            <tr v-if="selectedOrder.note">
              <td>Ghi chú khách:</td>
              <td>{{ selectedOrder.note }}</td>
            </tr>
          </table>
        </div>

        <el-divider />

        <div class="details-section">
          <h3>Sản phẩm đặt mua</h3>
          <el-table :data="selectedOrder.items" border size="small" style="width: 100%">
            <el-table-column prop="product_name_snapshot" label="Sản phẩm" />
            <el-table-column prop="variant_snapshot" label="Phân loại" width="130" />
            <el-table-column label="Đơn giá" width="100">
              <template #default="{ row }">
                {{ (row as any).price.toLocaleString('vi-VN') }}đ
              </template>
            </el-table-column>
            <el-table-column prop="quantity" label="SL" width="60" align="center" />
            <el-table-column label="Thành tiền" width="110" align="right">
              <template #default="{ row }">
                {{ ((row as any).price * (row as any).quantity).toLocaleString('vi-VN') }}đ
              </template>
            </el-table-column>
          </el-table>

          <div class="summary-details">
            <div class="summary-row">
              <span>Tạm tính:</span>
              <span>{{ selectedOrder.subtotal.toLocaleString('vi-VN') }}đ</span>
            </div>
            <div class="summary-row">
              <span>Phí vận chuyển:</span>
              <span>{{ selectedOrder.shipping_fee === 0 ? 'Miễn phí' : selectedOrder.shipping_fee.toLocaleString('vi-VN') + 'đ' }}</span>
            </div>
            <div v-if="selectedOrder.discount_amount > 0" class="summary-row discount">
              <span>Giảm giá:</span>
              <span>–{{ selectedOrder.discount_amount.toLocaleString('vi-VN') }}đ</span>
            </div>
            <div class="summary-row total">
              <span>Tổng thanh toán:</span>
              <strong>{{ selectedOrder.total.toLocaleString('vi-VN') }}đ</strong>
            </div>
          </div>
        </div>

        <el-divider />

        <div class="details-section">
          <h3>Quy trình xử lý đơn hàng</h3>
          <div class="workflow-actions" v-if="selectedOrder.status !== 'delivered' && selectedOrder.status !== 'cancelled'">
            <el-button v-if="selectedOrder.status === 'pending'" type="primary" @click="updateStatus('confirmed')">
              Xác nhận đơn hàng
            </el-button>
            <el-button v-if="selectedOrder.status === 'confirmed'" type="primary" @click="updateStatus('shipping')">
              Giao hàng
            </el-button>
            <el-button v-if="selectedOrder.status === 'shipping'" type="success" @click="updateStatus('delivered')">
              Hoàn thành đơn hàng
            </el-button>
            <el-button type="danger" plain @click="updateStatus('cancelled')">
              Hủy đơn
            </el-button>
          </div>
          <div v-else class="workflow-final">
            Đơn hàng đã hoàn thành vòng đời ở trạng thái: 
            <el-tag :type="statusTagType(selectedOrder.status)">{{ statusLabel(selectedOrder.status) }}</el-tag>
          </div>
        </div>
      </div>
    </el-drawer>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

definePageMeta({ layout: 'admin' })

interface User {
  id: number
  name: string
  email: string
  phone?: string
}

interface OrderItem {
  id: number
  product_name_snapshot: string
  variant_snapshot: string
  price: number
  quantity: number
}

interface Order {
  id: number
  order_code: string
  guest_name: string | null
  guest_phone: string | null
  guest_email: string | null
  shipping_address: string
  status: 'pending' | 'confirmed' | 'shipping' | 'delivered' | 'cancelled'
  payment_method: string
  subtotal: number
  shipping_fee: number
  discount_amount: number
  total: number
  note: string | null
  created_at: string
  user: User | null
  items: OrderItem[]
}

const api = useApiClient()

const orders = ref<Order[]>([])
const loading = ref(true)
const error = ref('')

const filterStatus = ref('')
const searchQuery = ref('')
const currentPage = ref(1)
const pageSize = ref(15)
const totalRows = ref(0)

const drawerVisible = ref(false)
const selectedOrder = ref<Order | null>(null)

const loadAll = async () => {
  loading.value = true
  error.value = ''
  try {
    let url = `/admin/orders?page=${currentPage.value}`
    if (filterStatus.value) {
      url += `&status=${filterStatus.value}`
    }
    if (searchQuery.value) {
      url += `&search=${encodeURIComponent(searchQuery.value.trim())}`
    }

    const res = await api.get<{ data: Order[]; total: number; current_page: number }>(url)
    orders.value = res.data
    totalRows.value = res.total
    currentPage.value = res.current_page
  } catch (err: any) {
    error.value = err.message ?? 'Không thể tải danh sách đơn hàng.'
  } finally {
    loading.value = false
  }
}

const formatDate = (val: string) => {
  return new Date(val).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' })
}

const statusLabel = (status: Order['status']) => ({
  pending: 'Chờ xác nhận',
  confirmed: 'Đã xác nhận',
  shipping: 'Đang giao',
  delivered: 'Đã giao',
  cancelled: 'Đã hủy'
})[status]

const statusTagType = (status: Order['status']) => ({
  pending: 'info',
  confirmed: 'primary',
  shipping: 'warning',
  delivered: 'success',
  cancelled: 'danger'
})[status] as 'info' | 'primary' | 'warning' | 'success' | 'danger'

const viewDetails = async (order: Order) => {
  try {
    const res = await api.get<Order>(`/admin/orders/${order.id}`)
    selectedOrder.value = res
    drawerVisible.value = true
  } catch (err: any) {
    ElMessage.error(err.message ?? 'Không thể tải chi tiết đơn hàng.')
  }
}

const updateStatus = async (nextStatus: Order['status']) => {
  if (!selectedOrder.value) return

  const actionText = {
    confirmed: 'xác nhận đơn hàng này',
    shipping: 'bắt đầu giao hàng cho đơn này',
    delivered: 'hoàn thành đơn hàng này',
    cancelled: 'HỦY đơn hàng này'
  }[nextStatus]

  try {
    await ElMessageBox.confirm(`Bạn có chắc muốn ${actionText}?`, 'Xác nhận thay đổi', {
      confirmButtonText: 'Đồng ý',
      cancelButtonText: 'Hủy',
      type: nextStatus === 'cancelled' ? 'warning' : 'info'
    })
  } catch {
    return
  }

  try {
    const res = await api.patch<Order>(`/admin/orders/${selectedOrder.value.id}/status`, {
      status: nextStatus
    })
    ElMessage.success('Đã cập nhật trạng thái đơn hàng.')
    selectedOrder.value = res
    await loadAll()
  } catch (err: any) {
    ElMessage.error(err.message ?? 'Cập nhật trạng thái thất bại.')
  }
}

onMounted(loadAll)
</script>

<style scoped lang="scss">
.orders-admin-page {
  max-width: 1100px;
}
.page-header {
  margin-bottom: 20px;
}
.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}
.phone-sub {
  font-size: 12px;
  color: #6b7280;
}
.details-container {
  padding: 0 10px;
}
.details-section {
  h3 { font-size: 15px; font-weight: 700; margin-bottom: 12px; }
}
.info-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
  td { padding: 6px 0; vertical-align: top; }
  td:first-child { width: 140px; color: #6b7280; }
}
.summary-details {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
  .summary-row {
    display: flex; width: 260px; justify-content: space-between; font-size: 13px;
    &.discount { color: #ef4444; }
    &.total { font-weight: 700; font-size: 14px; border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 4px; strong { color: #8b5e34; font-size: 16px; } }
  }
}
.workflow-actions {
  display: flex; gap: 8px; margin-top: 12px;
}
.workflow-final {
  font-size: 14px; color: #6b7280; margin-top: 8px;
}
</style>
