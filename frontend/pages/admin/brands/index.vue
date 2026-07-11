<template>
  <div class="brands-page">
    <div class="page-header">
      <h1>Thương hiệu</h1>
      <el-button type="primary" @click="openCreateForm">+ Thêm thương hiệu</el-button>
    </div>

    <el-alert v-if="loadError" :title="loadError" type="error" show-icon class="page-alert" />

    <el-table :data="brands" v-loading="loading" stripe style="width: 100%">
      <el-table-column prop="name" label="Tên" />
      <el-table-column prop="slug" label="Slug" />
      <el-table-column label="" width="160">
        <template #default="{ row }">
          <el-button link type="primary" @click="openEditForm(row as Brand)">Sửa</el-button>
          <el-button link type="danger" @click="removeBrand(row as Brand)">Xóa</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="showForm" :title="editingId ? 'Sửa thương hiệu' : 'Thêm thương hiệu'" width="480px">
      <el-alert v-if="formError" :title="formError" type="error" show-icon class="page-alert" />
      <el-form label-position="top">
        <el-form-item label="Tên thương hiệu" :error="formErrors.name" required>
          <el-input v-model="form.name" />
        </el-form-item>
        <el-form-item label="Mô tả">
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

interface Brand {
  id: number
  name: string
  slug: string
  description: string | null
}

const api = useApiClient()

const brands = ref<Brand[]>([])
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

const loadBrands = async () => {
  loading.value = true
  loadError.value = ''
  try {
    brands.value = await api.get<Brand[]>('/admin/brands')
  } catch (err: any) {
    loadError.value = err.message ?? 'Không thể tải thương hiệu.'
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
    ElMessage.success('Đã lưu thương hiệu')
    await loadBrands()
  } catch (err: any) {
    formErrors.name = err.errors?.name?.[0] ?? ''
    formError.value = err.message ?? 'Lưu thương hiệu thất bại.'
  } finally {
    saving.value = false
  }
}

const removeBrand = async (brand: Brand) => {
  try {
    await ElMessageBox.confirm(`Xóa thương hiệu "${brand.name}"?`, 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await api.del(`/admin/brands/${brand.id}`)
    ElMessage.success('Đã xóa thương hiệu')
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
