<!-- frontend/pages/admin/brands/index.vue -->
<template>
  <div class="brands-page">
    <div class="header">
      <h1>Thương hiệu</h1>
      <BaseButton @click="openCreateForm">+ Thêm thương hiệu</BaseButton>
    </div>

    <div v-if="loadError" class="general-error">{{ loadError }}</div>

    <table class="data-table">
      <thead>
        <tr>
          <th>Tên</th>
          <th>Slug</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="brand in brands" :key="brand.id">
          <td>{{ brand.name }}</td>
          <td>{{ brand.slug }}</td>
          <td class="row-actions">
            <button @click="openEditForm(brand)">Sửa</button>
            <button @click="removeBrand(brand)">Xóa</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showForm" class="form-panel">
      <h2>{{ editingId ? 'Sửa thương hiệu' : 'Thêm thương hiệu' }}</h2>
      <div v-if="formError" class="general-error">{{ formError }}</div>
      <form @submit.prevent="submitForm">
        <BaseInput v-model="form.name" label="Tên thương hiệu" required :error="formErrors.name" />
        <BaseInput v-model="form.description" label="Mô tả" />
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

interface Brand {
  id: number
  name: string
  slug: string
  description: string | null
}

const api = useApiClient()

const brands = ref<Brand[]>([])
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

const loadBrands = async () => {
  loadError.value = ''
  try {
    brands.value = await api.get<Brand[]>('/admin/brands')
  } catch (err: any) {
    loadError.value = err.message ?? 'Không thể tải thương hiệu.'
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

const openEditForm = (brand: Brand) => {
  editingId.value = brand.id
  form.name = brand.name
  form.description = brand.description ?? ''
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
      await api.put(`/admin/brands/${editingId.value}`, { ...form })
    } else {
      await api.post('/admin/brands', { ...form })
    }
    showForm.value = false
    await loadBrands()
  } catch (err: any) {
    formErrors.name = err.errors?.name?.[0] ?? ''
    formError.value = err.message ?? 'Lưu thương hiệu thất bại.'
  } finally {
    saving.value = false
  }
}

const removeBrand = async (brand: Brand) => {
  if (!confirm(`Xóa thương hiệu "${brand.name}"?`)) return
  try {
    await api.del(`/admin/brands/${brand.id}`)
    await loadBrands()
  } catch (err: any) {
    loadError.value = err.message ?? 'Xóa thương hiệu thất bại.'
  }
}

onMounted(loadBrands)
</script>

<style scoped lang="scss">
.brands-page {
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
