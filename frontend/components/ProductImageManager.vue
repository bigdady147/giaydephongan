<template>
  <div class="image-manager">
    <p v-if="!productId" class="image-manager-note">Lưu sản phẩm trước khi thêm ảnh.</p>
    <template v-else>
      <el-alert v-if="error" :title="error" type="error" show-icon class="image-manager-alert" />

      <div class="image-grid">
        <div v-for="image in images" :key="image.id" class="image-tile">
          <img :src="image.url" :alt="'Ảnh sản phẩm ' + image.id">
          <el-button link type="danger" size="small" class="image-remove" @click="removeImage(image)">Xóa</el-button>
        </div>
      </div>

      <el-upload :show-file-list="false" :auto-upload="true" accept="image/*" :http-request="uploadImage">
        <el-button :loading="uploading">+ Tải ảnh lên</el-button>
      </el-upload>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { ElMessageBox } from 'element-plus'

interface ProductImage {
  id: number
  url: string
  sort_order: number
}

const props = defineProps<{ productId: number | null }>()

const api = useApiClient()

const images = ref<ProductImage[]>([])
const error = ref('')
const uploading = ref(false)

const loadImages = async () => {
  if (!props.productId) return
  error.value = ''
  try {
    const product = await api.get<{ images: ProductImage[] }>(`/admin/products/${props.productId}`)
    images.value = product.images
  } catch (err: any) {
    error.value = err.message ?? 'Không thể tải ảnh sản phẩm.'
  }
}

const uploadImage = async (options: { file: File }) => {
  if (!props.productId) return
  uploading.value = true
  error.value = ''

  try {
    const body = new FormData()
    body.append('image', options.file)
    await api.post(`/admin/products/${props.productId}/images`, body)
    await loadImages()
  } catch (err: any) {
    error.value = err.message ?? 'Tải ảnh lên thất bại.'
  } finally {
    uploading.value = false
  }
}

const removeImage = async (image: ProductImage) => {
  try {
    await ElMessageBox.confirm('Xóa ảnh này?', 'Xác nhận', {
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
      type: 'warning'
    })
  } catch {
    return
  }

  error.value = ''
  try {
    await api.del(`/admin/images/${image.id}`)
    await loadImages()
  } catch (err: any) {
    error.value = err.message ?? 'Xóa ảnh thất bại.'
  }
}

watch(() => props.productId, loadImages)
onMounted(loadImages)
</script>

<style scoped lang="scss">
.image-manager-note {
  color: #6b7280;
  font-size: 13px;
}

.image-manager-alert {
  margin-bottom: 12px;
}

.image-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.image-tile {
  position: relative;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;

  img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    display: block;
  }

  .image-remove {
    position: absolute;
    bottom: 2px;
    right: 2px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 4px;
  }
}
</style>
