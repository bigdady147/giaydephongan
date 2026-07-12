<template>
  <nav v-if="lastPage > 1" class="app-pagination" aria-label="Phân trang">
    <button :disabled="page <= 1" :aria-label="$t('storefront.previousPage')" @click="go(page - 1)">‹</button>
    <button
      v-for="n in visiblePages"
      :key="n"
      :class="{ active: n === page }"
      @click="go(n)"
    >{{ n }}</button>
    <button :disabled="page >= lastPage" :aria-label="$t('storefront.nextPage')" @click="go(page + 1)">›</button>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ page: number; lastPage: number }>()
const emit = defineEmits<{ (e: 'update:page', page: number): void }>()

const visiblePages = computed(() => {
  const start = Math.max(1, Math.min(props.page - 2, props.lastPage - 4))
  const end = Math.min(props.lastPage, start + 4)
  return Array.from({ length: Math.max(0, end - start + 1) }, (_, i) => start + i)
})

const go = (n: number) => {
  if (n >= 1 && n <= props.lastPage && n !== props.page) emit('update:page', n)
}
</script>

<style scoped lang="scss">
.app-pagination { display: flex; gap: 6px; justify-content: center; margin: 28px 0; }
button {
  min-width: 36px; height: 36px; border: 1px solid $sf-color-border; background: #fff;
  border-radius: 8px; cursor: pointer; font-size: 14px;
  &:hover:not(:disabled) { border-color: $sf-color-accent; color: $sf-color-accent; }
  &.active { background: $sf-color-accent; border-color: $sf-color-accent; color: #fff; }
  &:disabled { opacity: 0.4; cursor: default; }
}
</style>
