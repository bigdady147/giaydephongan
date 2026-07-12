<template>
  <div class="image-gallery">
    <div class="gallery-main">
      <img v-if="current" :src="current.url" :alt="alt">
      <div v-else class="gallery-placeholder">👞</div>
    </div>
    <div v-if="images.length > 1" class="gallery-thumbs">
      <button
        v-for="image in images"
        :key="image.id"
        :class="{ active: image.id === current?.id }"
        @click="current = image"
      >
        <img :src="image.url" :alt="alt" loading="lazy">
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import type { ProductImageInfo } from '~/types/storefront'

const props = defineProps<{ images: ProductImageInfo[]; alt: string }>()
const current = ref<ProductImageInfo | null>(props.images[0] ?? null)
watch(() => props.images, (images) => { current.value = images[0] ?? null })
</script>

<style scoped lang="scss">
.gallery-main {
  aspect-ratio: 1; border-radius: $sf-radius; overflow: hidden; background: $sf-color-bg-soft;
  border: 1px solid $sf-color-border;
  img { width: 100%; height: 100%; object-fit: cover; display: block; }
}
.gallery-placeholder { display: flex; align-items: center; justify-content: center; height: 100%; font-size: 72px; }
.gallery-thumbs {
  display: flex; gap: 8px; margin-top: 10px; overflow-x: auto;
  button {
    flex: 0 0 64px; height: 64px; border-radius: 8px; overflow: hidden; padding: 0;
    border: 2px solid transparent; cursor: pointer; background: none;
    &.active { border-color: $sf-color-accent; }
    img { width: 100%; height: 100%; object-fit: cover; display: block; }
  }
}
</style>
