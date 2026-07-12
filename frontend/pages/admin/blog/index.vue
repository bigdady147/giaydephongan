<template>
  <div class="blog-admin-page">
    <div class="page-header">
      <h1>Quản lý Blog</h1>
      <el-button type="primary" @click="openCreate">+ Thêm bài viết</el-button>
    </div>

    <el-alert v-if="error" :title="error" type="error" show-icon class="page-alert" />

    <div class="filters">
      <el-select v-model="statusFilter" placeholder="Trạng thái" clearable style="width: 160px" @change="loadAll">
        <el-option label="Nháp" value="draft" />
        <el-option label="Đã đăng" value="published" />
      </el-select>
      <el-select v-model="pillarFilter" placeholder="Chủ đề" clearable style="width: 220px" @change="loadAll">
        <el-option v-for="pillar in pillars" :key="pillar.value" :label="pillar.label" :value="pillar.value" />
      </el-select>
    </div>

    <el-table :data="posts" v-loading="loading" stripe style="width: 100%">
      <el-table-column label="Ảnh bìa" width="110">
        <template #default="{ row }">
          <img v-if="(row as BlogPost).thumbnail" :src="(row as BlogPost).thumbnail!" class="post-thumbnail" alt="">
        </template>
      </el-table-column>
      <el-table-column prop="title" label="Tiêu đề" />
      <el-table-column label="Chủ đề" width="180">
        <template #default="{ row }">
          <el-tag v-if="(row as BlogPost).pillar" type="info">{{ pillarLabel((row as BlogPost).pillar) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Trạng thái" width="110" align="center">
        <template #default="{ row }">
          <el-tag :type="(row as BlogPost).status === 'published' ? 'success' : 'info'">
            {{ (row as BlogPost).status === 'published' ? 'Đã đăng' : 'Nháp' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="" width="150" align="center">
        <template #default="{ row }">
          <el-button link type="primary" @click="openEdit(row as BlogPost)">Sửa</el-button>
          <el-button link type="danger" @click="removePost(row as BlogPost)">Xóa</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="showDialog" :title="editingId ? 'Sửa bài viết' : 'Thêm bài viết'" width="720px">
      <el-alert v-if="dialogError" :title="dialogError" type="error" show-icon class="page-alert" />

      <el-form label-position="top">
        <el-form-item label="Tiêu đề" required>
          <el-input v-model="form.title" />
        </el-form-item>

        <el-form-item label="Đường dẫn (Slug) - để trống tự phát sinh">
          <el-input v-model="form.slug" />
        </el-form-item>

        <el-form-item label="Ảnh bìa">
          <el-upload
            class="thumb-uploader"
            action="#"
            :show-file-list="false"
            :auto-upload="false"
            accept="image/*"
            :on-change="handleThumbnailChange"
          >
            <img v-if="thumbnailPreview" :src="thumbnailPreview" class="uploaded-preview" alt="Preview">
            <div v-else class="uploader-placeholder">
              <el-icon class="uploader-icon"><Plus /></el-icon>
              <span>Chọn ảnh</span>
            </div>
          </el-upload>
        </el-form-item>

        <el-form-item label="Tóm tắt (excerpt)">
          <el-input v-model="form.excerpt" type="textarea" :rows="2" />
        </el-form-item>

        <el-form-item label="Nội dung (hỗ trợ HTML)" required>
          <el-input v-model="form.content" type="textarea" :rows="10" />
        </el-form-item>

        <el-form-item label="Chủ đề (pillar)">
          <el-select v-model="form.pillar" clearable placeholder="Không chọn" style="width: 100%">
            <el-option v-for="pillar in pillars" :key="pillar.value" :label="pillar.label" :value="pillar.value" />
          </el-select>
        </el-form-item>

        <el-divider>Cấu hình SEO</el-divider>
        <el-form-item label="SEO title">
          <el-input v-model="form.seo_title" />
        </el-form-item>
        <el-form-item label="SEO description">
          <el-input v-model="form.seo_description" type="textarea" :rows="2" />
        </el-form-item>

        <el-form-item label="Trạng thái">
          <el-select v-model="form.status" style="width: 100%">
            <el-option label="Nháp" value="draft" />
            <el-option label="Đã đăng" value="published" />
          </el-select>
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

interface BlogPost {
  id: number
  title: string
  slug: string
  excerpt: string | null
  content: string
  thumbnail: string | null
  pillar: string | null
  seo_title: string | null
  seo_description: string | null
  status: 'draft' | 'published'
}

const pillars = [
  { value: 'cam-nang-chon-giay', label: 'Cẩm nang chọn giày' },
  { value: 'bao-quan-giay-da', label: 'Bảo quản giày da' },
  { value: 'giay-theo-dip', label: 'Giày theo dịp' }
]

const pillarLabel = (value: string | null) => pillars.find(p => p.value === value)?.label ?? value ?? ''

const api = useApiClient()

const posts = ref<BlogPost[]>([])
const loading = ref(true)
const error = ref('')
const showDialog = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const dialogError = ref('')
const statusFilter = ref('')
const pillarFilter = ref('')

const thumbnailFile = ref<File | null>(null)
const thumbnailPreview = ref('')

const form = reactive({
  title: '',
  slug: '',
  excerpt: '',
  content: '',
  pillar: '',
  seo_title: '',
  seo_description: '',
  status: 'draft' as 'draft' | 'published'
})

const loadAll = async () => {
  loading.value = true
  error.value = ''
  try {
    const params = new URLSearchParams()
    if (statusFilter.value) params.set('status', statusFilter.value)
    if (pillarFilter.value) params.set('pillar', pillarFilter.value)
    const query = params.toString() ? `?${params.toString()}` : ''
    const response = await api.get<{ data: BlogPost[] }>(`/admin/blog${query}`)
    posts.value = response.data
  } catch (err: any) {
    error.value = err.message ?? 'Không thể tải danh sách bài viết.'
  } finally {
    loading.value = false
  }
}

const handleThumbnailChange = (uploadFile: UploadFile) => {
  if (uploadFile.raw) {
    thumbnailFile.value = uploadFile.raw
    thumbnailPreview.value = URL.createObjectURL(uploadFile.raw)
  }
}

const resetForm = () => {
  Object.assign(form, {
    title: '', slug: '', excerpt: '', content: '', pillar: '',
    seo_title: '', seo_description: '', status: 'draft'
  })
  thumbnailFile.value = null
  thumbnailPreview.value = ''
  dialogError.value = ''
}

const openCreate = () => {
  editingId.value = null
  resetForm()
  showDialog.value = true
}

const openEdit = (post: BlogPost) => {
  editingId.value = post.id
  resetForm()
  Object.assign(form, {
    title: post.title,
    slug: post.slug,
    excerpt: post.excerpt ?? '',
    content: post.content,
    pillar: post.pillar ?? '',
    seo_title: post.seo_title ?? '',
    seo_description: post.seo_description ?? '',
    status: post.status
  })
  thumbnailPreview.value = post.thumbnail ?? ''
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
    const body = new FormData()
    body.append('title', form.title)
    if (form.slug.trim()) body.append('slug', form.slug.trim())
    if (form.excerpt) body.append('excerpt', form.excerpt)
    body.append('content', form.content)
    if (form.pillar) body.append('pillar', form.pillar)
    if (form.seo_title) body.append('seo_title', form.seo_title)
    if (form.seo_description) body.append('seo_description', form.seo_description)
    body.append('status', form.status)
    if (thumbnailFile.value) body.append('thumbnail', thumbnailFile.value)

    if (editingId.value) {
      body.append('_method', 'PUT')
      await api.post(`/admin/blog/${editingId.value}`, body)
    } else {
      await api.post('/admin/blog', body)
    }

    ElMessage.success('Đã lưu bài viết')
    showDialog.value = false
    await loadAll()
  } catch (err: any) {
    dialogError.value = err.message ?? 'Lưu bài viết thất bại.'
  } finally {
    saving.value = false
  }
}

const removePost = async (post: BlogPost) => {
  try {
    await ElMessageBox.confirm(`Xóa bài viết "${post.title}"?`, 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await api.del(`/admin/blog/${post.id}`)
    ElMessage.success('Đã xóa bài viết')
    await loadAll()
  } catch (err: any) {
    error.value = err.message ?? 'Xóa bài viết thất bại.'
  }
}

onMounted(loadAll)
</script>

<style scoped lang="scss">
.blog-admin-page { max-width: 1100px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-alert { margin-bottom: 16px; }
.filters { display: flex; gap: 12px; margin-bottom: 16px; }
.post-thumbnail { width: 80px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; }
.thumb-uploader {
  border: 1px dashed #d9d9d9; border-radius: 6px; cursor: pointer; position: relative;
  overflow: hidden; width: 200px; height: 100px; display: flex; align-items: center; justify-content: center;
  &:hover { border-color: var(--el-color-primary); }
}
.uploader-placeholder { display: flex; flex-direction: column; align-items: center; color: #8c939d; font-size: 13px; }
.uploader-icon { font-size: 20px; margin-bottom: 4px; }
.uploaded-preview { width: 100%; height: 100%; object-fit: cover; }
</style>
