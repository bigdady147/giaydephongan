<template>
  <div class="banners-page">
    <div class="page-header">
      <h1>Quản lý Banners</h1>
      <el-button type="primary" @click="openCreate">+ Thêm Banner</el-button>
    </div>

    <el-alert v-if="error" :title="error" type="error" show-icon class="page-alert" />

    <el-table :data="banners" v-loading="loading" stripe style="width: 100%">
      <el-table-column label="Hình ảnh" width="180">
        <template #default="{ row }">
          <img :src="(row as Banner).image" class="banner-thumbnail" alt="Banner">
        </template>
      </el-table-column>
      <el-table-column label="Vị trí" width="180">
        <template #default="{ row }">
          <el-tag :type="(row as Banner).position === 'homepage_hero' ? 'primary' : 'success'">
            {{ (row as Banner).position === 'homepage_hero' ? 'Hero trang chủ' : 'Khuyến mãi trang chủ' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="link" label="Đường dẫn liên kết" />
      <el-table-column prop="sort_order" label="Thứ tự" width="90" align="center" />
      <el-table-column label="Thời gian hoạt động" width="220">
        <template #default="{ row }">
          <div class="date-range">
            <div>Bắt đầu: {{ formatDate((row as Banner).starts_at) }}</div>
            <div>Kết thúc: {{ formatDate((row as Banner).ends_at) }}</div>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="Trạng thái" width="110" align="center">
        <template #default="{ row }">
          <el-tag :type="(row as Banner).is_active ? 'success' : 'info'">
            {{ (row as Banner).is_active ? 'Bật' : 'Tắt' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="" width="150" align="center">
        <template #default="{ row }">
          <el-button link type="primary" @click="openEdit(row as Banner)">Sửa</el-button>
          <el-button link type="danger" @click="removeBanner(row as Banner)">Xóa</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="showDialog" :title="editingId ? 'Sửa Banner' : 'Thêm Banner'" width="500px">
      <el-alert v-if="dialogError" :title="dialogError" type="error" show-icon class="page-alert" />

      <el-form label-position="top">
        <el-form-item label="Hình ảnh Banner" required>
          <el-upload
            class="banner-uploader"
            action="#"
            :show-file-list="false"
            :auto-upload="false"
            accept="image/*"
            :on-change="handleImageChange"
          >
            <img v-if="imagePreview" :src="imagePreview" class="uploaded-preview" alt="Preview">
            <div v-else class="uploader-placeholder">
              <el-icon class="uploader-icon"><Plus /></el-icon>
              <span>Chọn ảnh</span>
            </div>
          </el-upload>
        </el-form-item>

        <el-form-item label="Vị trí hiển thị" required>
          <el-select v-model="form.position" placeholder="Chọn vị trí" style="width: 100%">
            <el-option label="Hero trang chủ (1200x500)" value="homepage_hero" />
            <el-option label="Khuyến mãi trang chủ (590x200)" value="homepage_promo" />
          </el-select>
        </el-form-item>

        <el-form-item label="Liên kết (link)">
          <el-input v-model="form.link" placeholder="Ví dụ: /danh-muc/giay-tay" />
        </el-form-item>

        <el-form-item label="Thứ tự sắp xếp">
          <el-input-number v-model="form.sort_order" :min="0" style="width: 100%" />
        </el-form-item>

        <el-form-item label="Thời gian bắt đầu">
          <el-date-picker v-model="form.starts_at" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" placeholder="Chọn thời gian" style="width: 100%" />
        </el-form-item>

        <el-form-item label="Thời gian kết thúc">
          <el-date-picker v-model="form.ends_at" type="datetime" value-format="YYYY-MM-DD HH:mm:ss" placeholder="Chọn thời gian" style="width: 100%" />
        </el-form-item>

        <el-form-item label="Trạng thái">
          <el-switch v-model="form.is_active" active-text="Hoạt động" inactive-text="Tạm khóa" />
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
import { Plus } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { UploadFile } from 'element-plus'

definePageMeta({ layout: 'admin' })

interface Banner {
  id: number
  image: string
  link: string | null
  position: 'homepage_hero' | 'homepage_promo'
  sort_order: number
  starts_at: string | null
  ends_at: string | null
  is_active: boolean
}

const api = useApiClient()

const banners = ref<Banner[]>([])
const loading = ref(true)
const error = ref('')
const showDialog = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const dialogError = ref('')

const imageFile = ref<File | null>(null)
const imagePreview = ref('')

const form = reactive({
  position: 'homepage_hero' as Banner['position'],
  link: '',
  sort_order: 0,
  starts_at: null as string | null,
  ends_at: null as string | null,
  is_active: true
})

const loadAll = async () => {
  loading.value = true
  error.value = ''
  try {
    banners.value = await api.get<Banner[]>('/admin/banners')
  } catch (err: any) {
    error.value = err.message ?? 'Không thể tải danh sách banner.'
  } finally {
    loading.value = false
  }
}

const formatDate = (val: string | null) => {
  if (!val) return 'Vô hạn'
  return new Date(val).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' })
}

const handleImageChange = (uploadFile: UploadFile) => {
  if (uploadFile.raw) {
    imageFile.value = uploadFile.raw
    imagePreview.value = URL.createObjectURL(uploadFile.raw)
  }
}

const openCreate = () => {
  editingId.value = null
  imageFile.value = null
  imagePreview.value = ''
  dialogError.value = ''
  Object.assign(form, {
    position: 'homepage_hero',
    link: '',
    sort_order: 0,
    starts_at: null,
    ends_at: null,
    is_active: true
  })
  showDialog.value = true
}

const openEdit = (banner: Banner) => {
  editingId.value = banner.id
  imageFile.value = null
  imagePreview.value = banner.image
  dialogError.value = ''
  Object.assign(form, {
    position: banner.position,
    link: banner.link ?? '',
    sort_order: banner.sort_order,
    starts_at: banner.starts_at,
    ends_at: banner.ends_at,
    is_active: banner.is_active
  })
  showDialog.value = true
}

const submitForm = async () => {
  if (!editingId.value && !imageFile.value) {
    dialogError.value = 'Vui lòng chọn hình ảnh cho banner.'
    return
  }

  saving.value = true
  dialogError.value = ''

  try {
    const body = new FormData()
    body.append('position', form.position)
    body.append('link', form.link)
    body.append('sort_order', String(form.sort_order))
    body.append('is_active', form.is_active ? '1' : '0')
    if (form.starts_at) body.append('starts_at', form.starts_at)
    if (form.ends_at) body.append('ends_at', form.ends_at)

    if (imageFile.value) {
      body.append('image', imageFile.value)
    }

    if (editingId.value) {
      // Use POST with _method = PUT to allow multipart uploads in update
      body.append('_method', 'PUT')
      await api.post(`/admin/banners/${editingId.value}`, body)
    } else {
      await api.post('/admin/banners', body)
    }

    ElMessage.success('Đã lưu banner')
    showDialog.value = false
    await loadAll()
  } catch (err: any) {
    dialogError.value = err.message ?? 'Lưu banner thất bại.'
  } finally {
    saving.value = false
  }
}

const removeBanner = async (banner: Banner) => {
  try {
    await ElMessageBox.confirm('Xóa banner này?', 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await api.del(`/admin/banners/${banner.id}`)
    ElMessage.success('Đã xóa banner')
    await loadAll()
  } catch (err: any) {
    error.value = err.message ?? 'Xóa banner thất bại.'
  }
}

onMounted(loadAll)
</script>

<style scoped lang="scss">
.banners-page {
  max-width: 1000px;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.page-alert {
  margin-bottom: 16px;
}
.banner-thumbnail {
  width: 120px;
  height: 50px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #e5e7eb;
}
.date-range {
  font-size: 12px;
  color: #6b7280;
}
.banner-uploader {
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  width: 240px;
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  &:hover {
    border-color: var(--el-color-primary);
  }
}
.uploader-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #8c939d;
  font-size: 13px;
}
.uploader-icon {
  font-size: 20px;
  margin-bottom: 4px;
}
.uploaded-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
