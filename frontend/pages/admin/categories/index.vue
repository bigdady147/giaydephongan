<template>
  <div class="categories-page">
    <div class="header">
      <h1>Danh mục sản phẩm</h1>
      <BaseButton @click="openCreateForm">+ Thêm danh mục</BaseButton>
    </div>

    <div v-if="loadError" class="general-error">{{ loadError }}</div>

    <table class="data-table">
      <thead>
        <tr>
          <th>Tên</th>
          <th>Slug</th>
          <th>Trạng thái</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="category in categories" :key="category.id">
          <td>{{ category.name }}</td>
          <td>{{ category.slug }}</td>
          <td>{{ category.is_active ? 'Hoạt động' : 'Ẩn' }}</td>
          <td class="row-actions">
            <button @click="openEditForm(category)">Sửa</button>
            <button @click="removeCategory(category)">Xóa</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showForm" class="form-panel">
      <h2>{{ editingId ? 'Sửa danh mục' : 'Thêm danh mục' }}</h2>
      <div v-if="formError" class="general-error">{{ formError }}</div>
      <form @submit.prevent="submitForm">
        <BaseInput v-model="form.name" label="Tên danh mục" required :error="formErrors.name" />
        <BaseInput v-model="form.description" label="Mô tả (SEO)" />
        <div class="form-actions">
          <BaseButton type="submit" :loading="saving">Lưu</BaseButton>
          <button type="button" @click="closeForm">Hủy</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

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
  loadError.value = ''
  try {
    categories.value = await api.get<Category[]>('/admin/categories')
  } catch (err: any) {
    loadError.value = err.message ?? 'Không thể tải danh mục.'
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
    await loadCategories()
  } catch (err: any) {
    formErrors.name = err.errors?.name?.[0] ?? ''
    formError.value = err.message ?? 'Lưu danh mục thất bại.'
  } finally {
    saving.value = false
  }
}

const removeCategory = async (category: Category) => {
  if (!confirm(`Xóa danh mục "${category.name}"?`)) return
  try {
    await api.del(`/admin/categories/${category.id}`)
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

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;

  th, td {
    text-align: left;
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
  }
}

.row-actions button {
  margin-right: 8px;
  background: none;
  border: none;
  color: #2563eb;
  cursor: pointer;
}

.form-panel {
  margin-top: 24px;
  padding: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  max-width: 480px;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.general-error {
  background-color: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 12px 16px;
  font-size: 14px;
  margin-bottom: 16px;
}
</style>
