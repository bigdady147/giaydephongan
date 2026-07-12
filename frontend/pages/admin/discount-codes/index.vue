<template>
  <div class="discount-admin-page">
    <div class="page-header">
      <h1>Mã giảm giá</h1>
      <el-button type="primary" @click="openCreate">+ Thêm mã</el-button>
    </div>

    <el-alert v-if="error" :title="error" type="error" show-icon class="page-alert" />

    <el-table :data="codes" v-loading="loading" stripe style="width: 100%">
      <el-table-column prop="code" label="Mã" width="140" />
      <el-table-column label="Loại" width="110">
        <template #default="{ row }">
          {{ (row as DiscountCode).type === 'percent' ? 'Phần trăm' : 'Số tiền cố định' }}
        </template>
      </el-table-column>
      <el-table-column label="Giá trị" width="140">
        <template #default="{ row }">
          {{ (row as DiscountCode).type === 'percent' ? `${(row as DiscountCode).value}%` : formatVnd((row as DiscountCode).value) }}
        </template>
      </el-table-column>
      <el-table-column label="Đơn tối thiểu" width="140">
        <template #default="{ row }">
          {{ (row as DiscountCode).min_order_value ? formatVnd((row as DiscountCode).min_order_value!) : '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Đã dùng / Giới hạn" width="150">
        <template #default="{ row }">
          {{ (row as DiscountCode).used_count }} / {{ (row as DiscountCode).usage_limit ?? '∞' }}
        </template>
      </el-table-column>
      <el-table-column label="Hiệu lực" width="200">
        <template #default="{ row }">
          {{ formatDate((row as DiscountCode).starts_at) }} → {{ formatDate((row as DiscountCode).expires_at) }}
        </template>
      </el-table-column>
      <el-table-column label="Trạng thái" width="110" align="center">
        <template #default="{ row }">
          <el-tag :type="(row as DiscountCode).is_active ? 'success' : 'info'">
            {{ (row as DiscountCode).is_active ? 'Hoạt động' : 'Tắt' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="" width="150" align="center">
        <template #default="{ row }">
          <el-button link type="primary" @click="openEdit(row as DiscountCode)">Sửa</el-button>
          <el-button link type="danger" @click="removeCode(row as DiscountCode)">Xóa</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="showDialog" :title="editingId ? 'Sửa mã giảm giá' : 'Thêm mã giảm giá'" width="520px">
      <el-alert v-if="dialogError" :title="dialogError" type="error" show-icon class="page-alert" />

      <el-form label-position="top">
        <el-form-item label="Mã (không phân biệt hoa/thường)" required>
          <el-input v-model="form.code" placeholder="Ví dụ: SALE10" />
        </el-form-item>

        <el-form-item label="Loại giảm giá" required>
          <el-select v-model="form.type" style="width: 100%">
            <el-option label="Phần trăm (%)" value="percent" />
            <el-option label="Số tiền cố định (VNĐ)" value="fixed" />
          </el-select>
        </el-form-item>

        <el-form-item :label="form.type === 'percent' ? 'Giá trị (%)' : 'Giá trị (VNĐ)'" required>
          <el-input-number v-model="form.value" :min="0" style="width: 100%" />
        </el-form-item>

        <el-form-item label="Giá trị đơn hàng tối thiểu (không bắt buộc)">
          <el-input-number v-model="form.min_order_value" :min="0" :step="50000" style="width: 100%" />
        </el-form-item>

        <el-form-item label="Giới hạn số lần dùng (không bắt buộc)">
          <el-input-number v-model="form.usage_limit" :min="1" style="width: 100%" />
        </el-form-item>

        <el-form-item label="Thời gian hiệu lực (không bắt buộc)">
          <el-date-picker
            v-model="dateRange"
            type="datetimerange"
            start-placeholder="Bắt đầu"
            end-placeholder="Kết thúc"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>

        <el-form-item label="Trạng thái">
          <el-switch v-model="form.is_active" active-text="Hoạt động" inactive-text="Tắt" />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="showDialog = false">Hủy</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">Lưu</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { formatVnd } from '~/utils/format'

definePageMeta({ layout: 'admin' })

interface DiscountCode {
  id: number
  code: string
  type: 'percent' | 'fixed'
  value: number
  min_order_value: number | null
  usage_limit: number | null
  used_count: number
  starts_at: string | null
  expires_at: string | null
  is_active: boolean
}

const api = useApiClient()

const codes = ref<DiscountCode[]>([])
const loading = ref(true)
const error = ref('')
const showDialog = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const dialogError = ref('')
const dateRange = ref<[string, string] | null>(null)

const form = reactive({
  code: '',
  type: 'percent' as 'percent' | 'fixed',
  value: 0,
  min_order_value: null as number | null,
  usage_limit: null as number | null,
  is_active: true
})

const formatDate = (val: string | null) => {
  if (!val) return 'Vô hạn'
  return new Date(val).toLocaleDateString('vi-VN')
}

const loadAll = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await api.get<{ data: DiscountCode[] }>('/admin/discount-codes')
    codes.value = response.data
  } catch (err: any) {
    error.value = err.message ?? 'Không thể tải danh sách mã giảm giá.'
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  Object.assign(form, { code: '', type: 'percent', value: 0, min_order_value: null, usage_limit: null, is_active: true })
  dateRange.value = null
  dialogError.value = ''
}

const openCreate = () => {
  editingId.value = null
  resetForm()
  showDialog.value = true
}

const openEdit = (code: DiscountCode) => {
  editingId.value = code.id
  resetForm()
  Object.assign(form, {
    code: code.code,
    type: code.type,
    value: code.value,
    min_order_value: code.min_order_value,
    usage_limit: code.usage_limit,
    is_active: code.is_active
  })
  dateRange.value = code.starts_at && code.expires_at ? [code.starts_at, code.expires_at] : null
  showDialog.value = true
}

const submitForm = async () => {
  saving.value = true
  dialogError.value = ''

  try {
    const payload = {
      code: form.code,
      type: form.type,
      value: form.value,
      min_order_value: form.min_order_value,
      usage_limit: form.usage_limit,
      is_active: form.is_active,
      starts_at: dateRange.value?.[0] ?? null,
      expires_at: dateRange.value?.[1] ?? null
    }

    if (editingId.value) {
      await api.put(`/admin/discount-codes/${editingId.value}`, payload)
    } else {
      await api.post('/admin/discount-codes', payload)
    }

    ElMessage.success('Đã lưu mã giảm giá')
    showDialog.value = false
    await loadAll()
  } catch (err: any) {
    dialogError.value = err.errors?.code?.[0] ?? err.message ?? 'Lưu mã giảm giá thất bại.'
  } finally {
    saving.value = false
  }
}

const removeCode = async (code: DiscountCode) => {
  try {
    await ElMessageBox.confirm(`Xóa mã "${code.code}"?`, 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await api.del(`/admin/discount-codes/${code.id}`)
    ElMessage.success('Đã xóa mã giảm giá')
    await loadAll()
  } catch (err: any) {
    error.value = err.message ?? 'Xóa mã giảm giá thất bại.'
  }
}

onMounted(loadAll)
</script>

<style scoped lang="scss">
.discount-admin-page { max-width: 1100px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-alert { margin-bottom: 16px; }
</style>
