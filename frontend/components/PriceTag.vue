<template>
  <div class="price-tag" :class="`price-${size}`">
    <span class="price-current">{{ formatVnd(salePrice ?? basePrice) }}</span>
    <template v-if="salePrice">
      <span class="price-original">{{ formatVnd(basePrice) }}</span>
      <span class="price-off">-{{ discountPercent }}%</span>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { formatVnd } from '~/utils/format'

const props = withDefaults(defineProps<{
  basePrice: number
  salePrice?: number | null
  size?: 'sm' | 'lg'
}>(), { salePrice: null, size: 'sm' })

const discountPercent = computed(() =>
  props.salePrice ? Math.round((1 - props.salePrice / props.basePrice) * 100) : 0
)
</script>

<style scoped lang="scss">
.price-tag { display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap; }
.price-current { color: $sf-color-accent; font-weight: 700; }
.price-original { color: $sf-color-muted; text-decoration: line-through; font-size: 0.85em; }
.price-off { background: $sf-color-sale; color: #fff; font-size: 11px; font-weight: 700; padding: 2px 6px; border-radius: 4px; }
.price-sm .price-current { font-size: 15px; }
.price-lg .price-current { font-size: 26px; }
</style>
