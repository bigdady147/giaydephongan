<template>
  <div class="products-page">
    <div class="header">
      <h1>Sản phẩm</h1>
      <BaseButton @click="openCreateForm">+ Thêm sản phẩm</BaseButton>
    </div>

    <div v-if="loadError" class="general-error">{{ loadError }}</div>

    <table class="data-table">
      <thead>
        <tr>
          <th>Tên</th>
          <th>Danh mục</th>
          <th>Giá</th>
          <th>Trạng thái</th>
          <th>Biến thể</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="product in products" :key="product.id">
          <td>{{ product.name }}</td>
          <td>{{ product.category?.name }}</td>
          <td>{{ product.base_price.toLocaleString('vi-VN') }}đ</td>
          <td>{{ statusLabel(product.status) }}</td>
          <td>{{ product.variants.length }}</td>
          <td class="row-actions">
            <button @click="openEditForm(product)">Sửa</button>
            <button @click="removeProduct(product)">Xóa</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="showForm" class="form-panel">
      <h2>{{ editingId ? 'Sửa sản phẩm' : 'Thêm sản phẩm' }}</h2>
      <div v-if="formError" class="general-error">{{ formError }}</div>
      <form @submit.prevent="submitForm">
        <BaseInput v-model="form.name" label="Tên sản phẩm" required :error="formErrors.name" />

        <label class="field-label">Danh mục</label>
        <select v-model.number="form.category_id" required class="native-select">
          <option value="" disabled>Chọn danh mục</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </select>
        <span v-if="formErrors.category_id" class="error-message">{{ formErrors.category_id }}</span>

        <label class="field-label">Thương hiệu (không bắt buộc)</label>
        <select v-model="form.brand_id" class="native-select">
          <option :value="null">Không chọn</option>
          <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
        </select>

        <label class="field-label">Chất liệu</label>
        <select v-model="form.material" class="native-select">
          <option value="full_grain_leather">Da bò thật (full-grain)</option>
          <option value="suede">Da lộn</option>
          <option value="pu_leather">Da PU</option>
          <option value="other">Khác</option>
        </select>

        <BaseInput v-model="form.base_price" label="Giá gốc (VNĐ)" type="number" required :error="formErrors.base_price" />
        <BaseInput v-model="form.sale_price" label="Giá khuyến mãi (không bắt buộc)" type="number" :error="formErrors.sale_price" />

        <label class="field-label">Trạng thái</label>
        <select v-model="form.status" class="native-select">
          <option value="draft">Nháp</option>
          <option value="published">Đã đăng</option>
          <option value="archived">Lưu trữ</option>
        </select>

        <fieldset v-if="!editingId" class="variants-fieldset">
          <legend>Biến thể (size / màu)</legend>
          <div v-for="(variant, index) in form.variants" :key="index" class="variant-row">
            <input v-model="variant.size" placeholder="Size (vd: 41)" required>
            <input v-model="variant.color" placeholder="Màu (vd: Nâu)" required>
            <input v-model="variant.sku" placeholder="SKU" required>
            <input v-model.number="variant.stock_quantity" type="number" placeholder="Tồn kho" required>
            <button type="button" @click="removeVariantRow(index)">Xóa</button>
          </div>
          <button type="button" @click="addVariantRow">+ Thêm biến thể</button>
        </fieldset>
        <p v-else class="variant-edit-note">
          Sản phẩm này có sẵn biến thể — chỉnh sửa biến thể sẽ được bổ sung sau (chưa hỗ trợ trong màn hình này).
        </p>

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

interface Category { id: number; name: string }
interface Brand { id: number; name: string }
interface Variant { id?: number; size: string; color: string; sku: string; stock_quantity: number }
interface Product {
  id: number
  name: string
  base_price: number
  sale_price: number | null
  material: 'full_grain_leather' | 'suede' | 'pu_leather' | 'other'
  status: 'draft' | 'published' | 'archived'
  category?: Category
  brand?: Brand
  variants: Variant[]
}

const api = useApiClient()

const products = ref<Product[]>([])
const categories = ref<Category[]>([])
const brands = ref<Brand[]>([])
const loadError = ref('')
const showForm = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const formError = ref('')
const formErrors = reactive({ name: '', category_id: '', base_price: '', sale_price: '' })

const emptyForm = () => ({
  name: '',
  category_id: '' as number | '',
  brand_id: null as string | number | null,
  material: 'full_grain_leather',
  base_price: 0,
  sale_price: undefined as number | undefined,
  status: 'draft' as 'draft' | 'published' | 'archived',
  variants: [] as Variant[]
})

const form = reactive(emptyForm())

const statusLabel = (status: Product['status']) => ({
  draft: 'Nháp',
  published: 'Đã đăng',
  archived: 'Lưu trữ'
})[status]

const loadAll = async () => {
  loadError.value = ''
  try {
    const [productList, categoryList, brandList] = await Promise.all([
      api.get<{ data: Product[] }>('/admin/products'),
      api.get<Category[]>('/admin/categories'),
      api.get<Brand[]>('/admin/brands')
    ])
    products.value = productList.data
    categories.value = categoryList
    brands.value = brandList
  } catch (err: any) {
    loadError.value = err.message ?? 'Không thể tải sản phẩm.'
  }
}

const openCreateForm = () => {
  editingId.value = null
  Object.assign(form, emptyForm())
  showForm.value = true
}

const openEditForm = (product: Product) => {
  editingId.value = product.id
  Object.assign(form, {
    name: product.name,
    category_id: product.category?.id ?? '',
    brand_id: product.brand?.id ?? null,
    material: product.material,
    base_price: product.base_price,
    sale_price: product.sale_price,
    status: product.status,
    variants: []
  })
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
}

const addVariantRow = () => {
  form.variants.push({ size: '', color: '', sku: '', stock_quantity: 0 })
}

const removeVariantRow = (index: number) => {
  form.variants.splice(index, 1)
}

const submitForm = async () => {
  saving.value = true
  formError.value = ''
  formErrors.name = ''
  formErrors.category_id = ''
  formErrors.base_price = ''
  formErrors.sale_price = ''

  try {
    const payload = {
      ...form,
      base_price: Number(form.base_price) || 0,
      sale_price: form.sale_price ? Number(form.sale_price) : null
    }
    if (editingId.value) {
      await api.put(`/admin/products/${editingId.value}`, payload)
    } else {
      await api.post('/admin/products', payload)
    }
    showForm.value = false
    await loadAll()
  } catch (err: any) {
    formErrors.name = err.errors?.name?.[0] ?? ''
    formErrors.category_id = err.errors?.category_id?.[0] ?? ''
    formErrors.base_price = err.errors?.base_price?.[0] ?? ''
    formErrors.sale_price = err.errors?.sale_price?.[0] ?? ''
    formError.value = err.message ?? 'Lưu sản phẩm thất bại.'
  } finally {
    saving.value = false
  }
}

const removeProduct = async (product: Product) => {
  if (!confirm(`Xóa sản phẩm "${product.name}"?`)) return
  try {
    await api.del(`/admin/products/${product.id}`)
    await loadAll()
  } catch (err: any) {
    loadError.value = err.message ?? 'Xóa sản phẩm thất bại.'
  }
}

onMounted(loadAll)
</script>

<style scoped lang="scss">
.products-page {
  max-width: 1100px;
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
  max-width: 560px;
}

.field-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  margin: 12px 0 4px;
}

.native-select {
  width: 100%;
  padding: 8px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
}

.variants-fieldset {
  margin-top: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
}

.variant-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr auto;
  gap: 8px;
  margin-bottom: 8px;

  input {
    padding: 6px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
  }
}

.variant-edit-note {
  margin-top: 20px;
  padding: 12px;
  background-color: #f9fafb;
  border-radius: 8px;
  font-size: 13px;
  color: #6b7280;
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

.error-message {
  color: #dc2626;
  font-size: 12px;
}
</style>
