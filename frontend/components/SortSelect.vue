<template>
  <label class="sort-select">
    <span>{{ $t('storefront.sortLabel') }}</span>
    <select :value="modelValue ?? 'newest'" @change="onChange">
      <option value="newest">{{ $t('storefront.sortNewest') }}</option>
      <option value="price_asc">{{ $t('storefront.sortPriceAsc') }}</option>
      <option value="price_desc">{{ $t('storefront.sortPriceDesc') }}</option>
      <option value="best_selling">{{ $t('storefront.sortBestSelling') }}</option>
    </select>
  </label>
</template>

<script setup lang="ts">
defineProps<{ modelValue?: string }>()
const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()
const onChange = (e: Event) => emit('update:modelValue', (e.target as HTMLSelectElement).value)
</script>

<style scoped lang="scss">
.sort-select {
  display: inline-flex; align-items: center; gap: 8px; font-size: 14px;
  span { color: $sf-color-muted; }
  select { border: 1px solid $sf-color-border; border-radius: 8px; padding: 8px 10px; font-size: 14px; background: #fff; }
}
</style>
