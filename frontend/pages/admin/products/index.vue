<template>
  <div class="products-page">
    <div class="page-header">
      <h1>Sản phẩm</h1>
      <el-button type="primary" @click="openCreateForm">+ Thêm sản phẩm</el-button>
    </div>

    <el-alert v-if="loadError" :title="loadError" type="error" show-icon class="page-alert" />

    <el-table :data="products" v-loading="loading" stripe style="width: 100%">
      <el-table-column prop="name" label="Tên" />
      <el-table-column label="Danh mục">
        <template #default="{ row }">{{ (row as Product).category?.name }}</template>
      </el-table-column>
      <el-table-column label="Giá" width="130">
        <template #default="{ row }">{{ (row as Product).base_price.toLocaleString('vi-VN') }}đ</template>
      </el-table-column>
      <el-table-column label="Trạng thái" width="110">
        <template #default="{ row }">
          <el-tag :type="statusTagType((row as Product).status)">{{ statusLabel((row as Product).status) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Nổi bật" width="100">
        <template #default="{ row }">
          <el-switch v-model="row.is_featured" @change="toggleFeatured(row as Product)" />
        </template>
      </el-table-column>
      <el-table-column label="Biến thể" width="90">
        <template #default="{ row }">{{ (row as Product).variants.length }}</template>
      </el-table-column>
      <el-table-column label="" width="160">
        <template #default="{ row }">
          <el-button link type="primary" @click="openEditForm(row as Product)">Sửa</el-button>
          <el-button link type="danger" @click="removeProduct(row as Product)">Xóa</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="showForm" :title="editingId ? 'Sửa sản phẩm' : 'Thêm sản phẩm'" width="640px">
      <el-alert v-if="formError" :title="formError" type="error" show-icon class="page-alert" />

      <el-tabs v-model="activeTab">
        <el-tab-pane label="Thông tin" name="info">
          <el-form label-position="top">
            <el-form-item label="Tên sản phẩm" :error="formErrors.name" required>
              <el-input v-model="form.name" />
            </el-form-item>
            <el-form-item label="Danh mục" :error="formErrors.category_id" required>
              <el-select v-model="form.category_id" placeholder="Chọn danh mục" style="width: 100%">
                <el-option v-for="category in categories" :key="category.id" :label="category.name" :value="category.id" />
              </el-select>
            </el-form-item>
            <el-form-item label="Thương hiệu (không bắt buộc)">
              <el-select v-model="form.brand_id" placeholder="Không chọn" clearable style="width: 100%">
                <el-option v-for="brand in brands" :key="brand.id" :label="brand.name" :value="brand.id" />
              </el-select>
            </el-form-item>
            <el-form-item label="Chất liệu">
              <el-select v-model="form.material" style="width: 100%">
                <el-option label="Da bò thật (full-grain)" value="full_grain_leather" />
                <el-option label="Da lộn" value="suede" />
                <el-option label="Da PU" value="pu_leather" />
                <el-option label="Khác" value="other" />
              </el-select>
            </el-form-item>
            <el-form-item label="Giá gốc (VNĐ)" :error="formErrors.base_price" required>
              <el-input-number v-model="form.base_price" :min="0" :step="10000" style="width: 100%" />
            </el-form-item>
            <el-form-item label="Giá khuyến mãi (không bắt buộc)" :error="formErrors.sale_price">
              <el-input-number v-model="form.sale_price" :min="0" :step="10000" style="width: 100%" />
            </el-form-item>
            <el-form-item label="Trạng thái">
              <el-select v-model="form.status" style="width: 100%">
                <el-option label="Nháp" value="draft" />
                <el-option label="Đã đăng" value="published" />
                <el-option label="Lưu trữ" value="archived" />
              </el-select>
            </el-form-item>
            <el-form-item label="Sản phẩm nổi bật">
              <el-switch v-model="form.is_featured" />
            </el-form-item>
          </el-form>

          <fieldset v-if="!editingId" class="variants-fieldset">
            <legend>Biến thể (size / màu)</legend>
            <div v-for="(variant, index) in form.variants" :key="index" class="variant-row">
              <el-input v-model="variant.size" placeholder="Size (vd: 41)" size="small" />
              <el-input v-model="variant.color" placeholder="Màu (vd: Nâu)" size="small" />
              <el-input v-model="variant.sku" placeholder="SKU" size="small" />
              <el-input-number v-model="variant.stock_quantity" :min="0" size="small" />
              <el-button link type="danger" size="small" @click="removeVariantRow(index)">Xóa</el-button>
            </div>
            <el-button size="small" @click="addVariantRow">+ Thêm biến thể</el-button>
          </fieldset>
          <p v-else class="variant-edit-note">Quản lý biến thể của sản phẩm đã lưu ở tab "Biến thể".</p>
        </el-tab-pane>

        <el-tab-pane label="Ảnh" name="images" :disabled="!editingId">
          <ProductImageManager :product-id="editingId" />
        </el-tab-pane>

        <el-tab-pane label="Biến thể" name="variants" :disabled="!editingId">
          <ProductVariantManager :product-id="editingId" @changed="loadAll" />
        </el-tab-pane>
      </el-tabs>

      <template #footer>
        <el-button @click="closeForm">Đóng</el-button>
        <el-button type="primary" :loading="saving" @click="submitForm">Lưu</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

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
  is_featured?: boolean
}

const api = useApiClient()

const products = ref<Product[]>([])
const categories = ref<Category[]>([])
const brands = ref<Brand[]>([])
const loading = ref(true)
const loadError = ref('')
const showForm = ref(false)
const activeTab = ref('info')
const editingId = ref<number | null>(null)
const saving = ref(false)
const formError = ref('')
const formErrors = reactive({ name: '', category_id: '', base_price: '', sale_price: '' })

const emptyForm = () => ({
  name: '',
  category_id: '' as number | '',
  brand_id: null as number | null,
  material: 'full_grain_leather',
  base_price: 0,
  sale_price: null as number | null,
  status: 'draft' as 'draft' | 'published' | 'archived',
  variants: [] as Variant[],
  is_featured: false
})

const form = reactive(emptyForm())

const statusLabel = (status: Product['status']) => ({
  draft: 'Nháp',
  published: 'Đã đăng',
  archived: 'Lưu trữ'
})[status]

const statusTagType = (status: Product['status']) => ({
  draft: 'info',
  published: 'success',
  archived: 'warning'
})[status] as 'info' | 'success' | 'warning'

const loadAll = async () => {
  loading.value = true
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
  } finally {
    loading.value = false
  }
}

const openCreateForm = () => {
  editingId.value = null
  activeTab.value = 'info'
  Object.assign(form, emptyForm())
  showForm.value = true
}

const openEditForm = (product: Product) => {
  editingId.value = product.id
  activeTab.value = 'info'
  Object.assign(form, {
    name: product.name,
    category_id: product.category?.id ?? '',
    brand_id: product.brand?.id ?? null,
    material: product.material,
    base_price: product.base_price,
    sale_price: product.sale_price,
    status: product.status,
    variants: [],
    is_featured: product.is_featured ?? false
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
      const created = await api.post<{ product: Product }>('/admin/products', payload)
      editingId.value = created.product.id
    }
    ElMessage.success('Đã lưu sản phẩm')
    await loadAll()
  } catch (err: any) {
    formErrors.name = err.errors?.name?.[0] ?? ''
    formErrors.category_id = err.errors?.category_id?.[0] ?? ''
    formErrors.base_price = err.errors?.base_price?.[0] ?? ''
    formErrors.sale_price = err.errors?.sale_price?.[0] ?? ''
    formError.value = err.message ?? 'Lưu sản phẩm thất bại.'

    const hasVariantError = Object.keys(err.errors ?? {}).some((key) => key.startsWith('variants.'))
    if (hasVariantError && formError.value === 'Validation error') {
      formError.value = 'Thông tin biến thể không hợp lệ. Vui lòng kiểm tra lại size, màu, SKU và tồn kho.'
    }
  } finally {
    saving.value = false
  }
}

const removeProduct = async (product: Product) => {
  try {
    await ElMessageBox.confirm(`Xóa sản phẩm "${product.name}"?`, 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  try {
    await api.del(`/admin/products/${product.id}`)
    ElMessage.success('Đã xóa sản phẩm')
    await loadAll()
  } catch (err: any) {
    loadError.value = err.message ?? 'Xóa sản phẩm thất bại.'
  }
}

const toggleFeatured = async (product: Product) => {
  try {
    await api.put(`/admin/products/${product.id}`, { is_featured: product.is_featured })
    ElMessage.success('Đã cập nhật trạng thái nổi bật')
  } catch (err: any) {
    product.is_featured = !product.is_featured // revert
    ElMessage.error(err.message ?? 'Cập nhật thất bại')
  }
}

onMounted(loadAll)
</script>

<style scoped lang="scss">
.products-page {
  max-width: 1100px;
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

.variants-fieldset {
  margin-top: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
}

.variant-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr auto;
  gap: 8px;
  margin-bottom: 8px;
  align-items: center;
}

.variant-edit-note {
  margin-top: 12px;
  padding: 12px;
  background-color: #f9fafb;
  border-radius: 8px;
  font-size: 13px;
  color: #6b7280;
}
</style>
