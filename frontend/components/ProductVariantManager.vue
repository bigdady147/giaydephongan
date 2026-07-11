<template>
  <div class="variant-manager">
    <p v-if="!productId" class="variant-manager-note">Lưu sản phẩm trước khi quản lý biến thể.</p>
    <template v-else>
      <el-alert v-if="error" :title="error" type="error" show-icon class="variant-manager-alert" />

      <el-table :data="variants" v-loading="loading" size="small" stripe>
        <el-table-column label="Size" width="90">
          <template #default="{ row }">
            <el-input v-model="(row as Variant).size" size="small" @blur="saveVariant(row as Variant)" />
          </template>
        </el-table-column>
        <el-table-column label="Màu" width="110">
          <template #default="{ row }">
            <el-input v-model="(row as Variant).color" size="small" @blur="saveVariant(row as Variant)" />
          </template>
        </el-table-column>
        <el-table-column label="SKU">
          <template #default="{ row }">
            <el-input v-model="(row as Variant).sku" size="small" @blur="saveVariant(row as Variant)" />
          </template>
        </el-table-column>
        <el-table-column label="Tồn kho" width="130">
          <template #default="{ row }">
            <el-input-number v-model="(row as Variant).stock_quantity" :min="0" size="small" @change="saveVariant(row as Variant)" />
          </template>
        </el-table-column>
        <el-table-column label="" width="70">
          <template #default="{ row }">
            <el-button link type="danger" size="small" @click="removeVariant(row as Variant)">Xóa</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="add-variant-row">
        <el-input v-model="newVariant.size" placeholder="Size" size="small" />
        <el-input v-model="newVariant.color" placeholder="Màu" size="small" />
        <el-input v-model="newVariant.sku" placeholder="SKU" size="small" />
        <el-input-number v-model="newVariant.stock_quantity" :min="0" placeholder="Tồn kho" size="small" />
        <el-button type="primary" size="small" :loading="adding" @click="addVariant">+ Thêm</el-button>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { ElMessageBox } from 'element-plus'

interface Variant {
  id: number
  size: string
  color: string
  sku: string
  stock_quantity: number
}

const props = defineProps<{ productId: number | null }>()

const api = useApiClient()

const variants = ref<Variant[]>([])
const loading = ref(false)
const error = ref('')
const adding = ref(false)

const newVariant = reactive({ size: '', color: '', sku: '', stock_quantity: 0 })

const loadVariants = async () => {
  const requestedId = props.productId
  if (!requestedId) return
  loading.value = true
  error.value = ''
  try {
    const product = await api.get<{ variants: Variant[] }>(`/admin/products/${requestedId}`)
    if (props.productId !== requestedId) return // productId changed while this request was in flight
    variants.value = product.variants
  } catch (err: any) {
    if (props.productId !== requestedId) return
    error.value = err.message ?? 'Không thể tải biến thể.'
  } finally {
    loading.value = false
  }
}

const saveVariant = async (variant: Variant) => {
  error.value = ''
  try {
    await api.put(`/admin/variants/${variant.id}`, {
      size: variant.size,
      color: variant.color,
      sku: variant.sku,
      stock_quantity: variant.stock_quantity
    })
  } catch (err: any) {
    error.value = err.message ?? 'Cập nhật biến thể thất bại.'
    await loadVariants()
  }
}

const addVariant = async () => {
  if (!props.productId) return
  adding.value = true
  error.value = ''
  try {
    await api.post(`/admin/products/${props.productId}/variants`, { ...newVariant })
    newVariant.size = ''
    newVariant.color = ''
    newVariant.sku = ''
    newVariant.stock_quantity = 0
    await loadVariants()
  } catch (err: any) {
    error.value = err.message ?? 'Thêm biến thể thất bại.'
  } finally {
    adding.value = false
  }
}

const removeVariant = async (variant: Variant) => {
  try {
    await ElMessageBox.confirm(`Xóa biến thể "${variant.size} - ${variant.color}"?`, 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  error.value = ''
  try {
    await api.del(`/admin/variants/${variant.id}`)
    await loadVariants()
  } catch (err: any) {
    error.value = err.message ?? 'Xóa biến thể thất bại.'
  }
}

watch(() => props.productId, () => {
  variants.value = []
  loadVariants()
})
onMounted(loadVariants)
</script>

<style scoped lang="scss">
.variant-manager-note {
  color: #6b7280;
  font-size: 13px;
}

.variant-manager-alert {
  margin-bottom: 12px;
}

.add-variant-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr auto;
  gap: 8px;
  margin-top: 12px;
  align-items: center;
}
</style>
