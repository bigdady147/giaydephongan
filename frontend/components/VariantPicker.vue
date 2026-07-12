<template>
  <div class="variant-picker">
    <div class="picker-group">
      <span class="picker-label">{{ $t('storefront.selectSize') }}</span>
      <div class="chip-row">
        <button
          v-for="size in sizes"
          :key="size"
          type="button"
          class="chip"
          :class="{ active: selectedSize === size, disabled: !sizeHasStock(size) }"
          @click="pickSize(size)"
        >{{ size }}</button>
      </div>
    </div>

    <div v-if="selectedSize" class="picker-group">
      <span class="picker-label">{{ $t('storefront.selectColor') }}</span>
      <div class="chip-row">
        <button
          v-for="color in colorsForSize"
          :key="color.color"
          type="button"
          class="chip"
          :class="{ active: selectedColor === color.color, disabled: color.stock === 0 }"
          @click="pickColor(color.color)"
        >{{ color.color }}</button>
      </div>
    </div>

    <p v-if="selectedVariant" class="stock-note" :class="{ out: selectedVariant.stock_quantity === 0 }">
      {{ selectedVariant.stock_quantity > 0
        ? $t('storefront.inStock', { count: selectedVariant.stock_quantity })
        : $t('storefront.outOfStock') }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { VariantInfo } from '~/types/storefront'

const props = defineProps<{ variants: VariantInfo[]; modelValue: number | null }>()
const emit = defineEmits<{ (e: 'update:modelValue', id: number | null): void }>()

const selectedSize = ref<string | null>(null)
const selectedColor = ref<string | null>(null)

const sizes = computed(() => [...new Set(props.variants.map(v => v.size))])
const sizeHasStock = (size: string) => props.variants.some(v => v.size === size && v.stock_quantity > 0)
const colorsForSize = computed(() =>
  props.variants.filter(v => v.size === selectedSize.value)
    .map(v => ({ color: v.color, stock: v.stock_quantity }))
)

const selectedVariant = computed(() =>
  props.variants.find(v => v.size === selectedSize.value && v.color === selectedColor.value) ?? null
)

watch(selectedVariant, (variant) => emit('update:modelValue', variant?.id ?? null))
watch(() => props.variants, () => { selectedSize.value = null; selectedColor.value = null })

const pickSize = (size: string) => {
  selectedSize.value = selectedSize.value === size ? null : size
  selectedColor.value = null
}
const pickColor = (color: string) => {
  selectedColor.value = selectedColor.value === color ? null : color
}
</script>

<style scoped lang="scss">
.picker-group { margin-bottom: 14px; }
.picker-label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; }
.chip-row { display: flex; flex-wrap: wrap; gap: 8px; }
.chip {
  min-width: 44px; padding: 8px 12px; border: 1px solid $sf-color-border; background: #fff;
  border-radius: 8px; font-size: 14px; cursor: pointer;
  &.active { border-color: $sf-color-accent; background: $sf-color-accent; color: #fff; }
  &.disabled { opacity: 0.4; text-decoration: line-through; }
}
.stock-note { font-size: 13px; color: #15803d; font-weight: 600; &.out { color: $sf-color-sale; } }
</style>
