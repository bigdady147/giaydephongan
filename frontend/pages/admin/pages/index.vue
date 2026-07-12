<template>
  <div class="pages-admin-page">
    <div class="page-header">
      <h1>Quản lý Trang tĩnh (CMS)</h1>
      <el-button type="primary" @click="openCreate">+ Thêm Trang</el-button>
    </div>

    <el-alert v-if="error" :title="error" type="error" show-icon class="page-alert" />

    <el-table :data="pages" v-loading="loading" stripe style="width: 100%">
      <el-table-column prop="title" label="Tiêu đề" />
      <el-table-column prop="slug" label="Đường dẫn (Slug)" width="220" />
      <el-table-column label="SEO Meta" width="220">
        <template #default="{ row }">
          <div class="seo-meta-info">
            <div>Title: {{ (row as Page).seo_title || 'Mặc định' }}</div>
            <div class="seo-desc">Desc: {{ (row as Page).seo_description || 'Không có' }}</div>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="Trạng thái" width="110" align="center">
        <template #default="{ row }">
          <el-tag :type="(row as Page).is_active ? 'success' : 'info'">
            {{ (row as Page).is_active ? 'Hiển thị' : 'Ẩn' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="" width="150" align="center">
        <template #default="{ row }">
          <el-button link type="primary" @click="openEdit(row as Page)">Sửa</el-button>
          <el-button link type="danger" @click="removePage(row as Page)" :disabled="(row as Page).slug === 'huong-dan-chon-size'">Xóa</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="showDialog" :title="editingId ? 'Sửa trang nội dung' : 'Thêm trang mới'" width="720px">
      <el-alert v-if="dialogError" :title="dialogError" type="error" show-icon class="page-alert" />

      <el-form label-position="top">
        <el-form-item label="Tiêu đề trang" required>
          <el-input v-model="form.title" placeholder="Ví dụ: Chính sách bảo mật" />
        </el-form-item>

        <el-form-item label="Đường dẫn tĩnh (Slug) - để trống tự phát sinh">
          <el-input v-model="form.slug" placeholder="Ví dụ: chinh-sach-bao-mat" :disabled="form.slug === 'huong-dan-chon-size' && !!editingId" />
        </el-form-item>

        <el-form-item label="Nội dung trang (hỗ trợ HTML)" required>
          <el-input v-model="form.content" type="textarea" :rows="12" placeholder="Nhập nội dung HTML..." />
        </el-form-item>

        <el-divider>Cấu hình SEO Meta</el-divider>

        <el-form-item label="Tiêu đề SEO (SEO Title)">
          <el-input v-model="form.seo_title" placeholder="Nhập tiêu đề trang hiển thị trên Google..." />
        </el-form-item>

        <el-form-item label="Mô tả SEO (SEO Description)">
          <el-input v-model="form.seo_description" type="textarea" :rows="3" placeholder="Nhập mô tả tóm tắt nội dung..." />
        </el-form-item>

        <el-form-item label="Trạng thái hiển thị">
          <el-switch v-model="form.is_active" active-text="Hiển thị" inactive-text="Tạm khóa" />
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

definePageMeta({ layout: 'admin' })

interface Page {
  id: number
  title: string
  slug: string
  content: string
  seo_title: string | null
  seo_description: string | null
  is_active: boolean
}

const api = useApiClient()

const pages = ref<Page[]>([])
const loading = ref(true)
const error = ref('')
const showDialog = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const dialogError = ref('')

const form = reactive({
  title: '',
  slug: '',
  content: '',
  seo_title: '',
  seo_description: '',
  is_active: true
})

const loadAll = async () => {
  loading.value = true
  error.value = ''
  try {
    pages.value = await api.get<Page[]>('/admin/pages')
  } catch (err: any) {
    error.value = err.message ?? 'Không thể tải danh sách trang.'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingId.value = null
  dialogError.value = ''
  Object.assign(form, {
    title: '',
    slug: '',
    content: '',
    seo_title: '',
    seo_description: '',
    is_active: true
  })
  showDialog.value = true
}

const openEdit = (page: Page) => {
  editingId.value = page.id
  dialogError.value = ''
  Object.assign(form, {
    title: page.title,
    slug: page.slug,
    content: page.content,
    seo_title: page.seo_title ?? '',
    seo_description: page.seo_description ?? '',
    is_active: page.is_active
  })
  showDialog.value = true
}

const submitForm = async () => {
  if (!form.title || !form.content) {
    dialogError.value = 'Tiêu đề và nội dung là bắt buộc.'
    return
  }

  saving.value = true
  dialogError.value = ''

  try {
    const payload = {
      title: form.title,
      slug: form.slug.trim() || null,
      content: form.content,
      seo_title: form.seo_title.trim() || null,
      seo_description: form.seo_description.trim() || null,
      is_active: form.is_active
    }

    if (editingId.value) {
      await api.put(`/admin/pages/${editingId.value}`, payload)
    } else {
      await api.post('/admin/pages', payload)
    }

    ElMessage.success('Đã lưu trang')
    showDialog.value = false
    await loadAll()
  } catch (err: any) {
    dialogError.value = err.message ?? 'Lưu trang thất bại.'
  } finally {
    saving.value = false
  }
}

const removePage = async (page: Page) => {
  try {
    await ElMessageBox.confirm(`Xóa trang "${page.title}"?`, 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await api.del(`/admin/pages/${page.id}`)
    ElMessage.success('Đã xóa trang')
    await loadAll()
  } catch (err: any) {
    error.value = err.message ?? 'Xóa trang thất bại.'
  }
}

onMounted(loadAll)
</script>

<style scoped lang="scss">
.pages-admin-page {
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
.seo-meta-info {
  font-size: 12px;
  color: #6b7280;
}
.seo-desc {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px;
}
</style>
