<template>
  <div class="categories-page">
    <div class="page-header">
      <h1>Danh mục sản phẩm</h1>
      <el-button type="primary" @click="openCreateForm">+ Thêm danh mục</el-button>
    </div>

    <el-alert v-if="loadError" :title="loadError" type="error" show-icon class="page-alert" />

    <el-table :data="categories" v-loading="loading" stripe style="width: 100%">
      <el-table-column prop="name" label="Tên" />
      <el-table-column prop="slug" label="Slug" />
      <el-table-column label="Trạng thái" width="120">
        <template #default="{ row }">
          <el-tag :type="(row as Category).is_active ? 'success' : 'info'">{{ (row as Category).is_active ? 'Hoạt động' : 'Ẩn' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="" width="160">
        <template #default="{ row }">
          <el-button link type="primary" @click="openEditForm(row as Category)">Sửa</el-button>
          <el-button link type="danger" @click="removeCategory(row as Category)">Xóa</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="showForm" :title="editingId ? 'Sửa danh mục' : 'Thêm danh mục'" width="480px">
      <el-alert v-if="formError" :title="formError" type="error" show-icon class="page-alert" />
      <el-form label-position="top">
        <el-form-item label="Tên danh mục" :error="formErrors.name" required>
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="Mô tả (SEO)">
          <el-input v-model="form.description" type="textarea" :rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="closeForm">Hủy</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">Lưu</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

definePageMeta({ layout: 'admin' })

interface Category {
  id: number
  name: string
  slug: string
  description: string | null
  is_active: boolean
}

const api = useApiClient()

const categories = ref<Category[]>([])
const loading = ref(true)
const loadError = ref('')
const showForm = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const formError = ref('')
const formErrors = reactive({ name: '' })

const form = reactive({
  name: '',
  description: ''
})

const loadCategories = async () => {
  loading.value = true
  loadError.value = ''
  try {
    categories.value = await api.get<Category[]>('/admin/categories')
  } catch (err: any) {
    loadError.value = err.message ?? 'Không thể tải danh mục.'
  } finally {
    loading.value = false
  }
}

const openCreateForm = () => {
  editingId.value = null
  form.name = ''
  form.description = ''
  formErrors.name = ''
  formError.value = ''
  showForm.value = true
}

const openEditForm = (category: Category) => {
  editingId.value = category.id
  form.name = category.name
  form.description = category.description ?? ''
  formErrors.name = ''
  formError.value = ''
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
}

const submitForm = async () => {
  saving.value = true
  formError.value = ''
  formErrors.name = ''

  try {
    if (editingId.value) {
      await api.put(`/admin/categories/${editingId.value}`, { ...form })
    } else {
      await api.post('/admin/categories', { ...form })
    }
    showForm.value = false
    ElMessage.success('Đã lưu danh mục')
    await loadCategories()
  } catch (err: any) {
    formErrors.name = err.errors?.name?.[0] ?? ''
    formError.value = err.message ?? 'Lưu danh mục thất bại.'
  } finally {
    saving.value = false
  }
}

const removeCategory = async (category: Category) => {
  try {
    await ElMessageBox.confirm(`Xóa danh mục "${category.name}"?`, 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await api.del(`/admin/categories/${category.id}`)
    ElMessage.success('Đã xóa danh mục')
    await loadCategories()
  } catch (err: any) {
    loadError.value = err.message ?? 'Xóa danh mục thất bại.'
  }
}

onMounted(loadCategories)
</script>

<style scoped lang="scss">
.categories-page {
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
</style>
