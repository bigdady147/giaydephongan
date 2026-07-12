<template>
  <div>
    <button class="filter-toggle" @click="open = true">☰ {{ $t('storefront.filter') }}</button>

    <div class="filter-panel" :class="{ open }">
      <div class="filter-head">
        <strong>{{ $t('storefront.filter') }}</strong>
        <button class="filter-close" aria-label="Đóng" @click="open = false">✕</button>
      </div>

      <fieldset>
        <legend>{{ $t('storefront.brand') }}</legend>
        <label v-for="brand in brands" :key="brand.id">
          <input v-model="local.brand" type="radio" :value="brand.slug"> {{ brand.name }}
        </label>
      </fieldset>

      <fieldset>
        <legend>{{ $t('storefront.material') }}</legend>
        <label v-for="material in materials" :key="material">
          <input v-model="local.material" type="radio" :value="material"> {{ $t(`storefront.materials.${material}`) }}
        </label>
      </fieldset>

      <fieldset>
        <legend>{{ $t('storefront.size') }}</legend>
        <div class="chip-row">
          <button
            v-for="size in sizes"
            :key="size"
            type="button"
            class="chip"
            :class="{ active: local.size === size }"
            @click="local.size = local.size === size ? undefined : size"
          >{{ size }}</button>
        </div>
      </fieldset>

      <fieldset>
        <legend>{{ $t('storefront.color') }}</legend>
        <div class="chip-row">
          <button
            v-for="color in colors"
            :key="color"
            type="button"
            class="chip"
            :class="{ active: local.color === color }"
            @click="local.color = local.color === color ? undefined : color"
          >{{ color }}</button>
        </div>
      </fieldset>

      <fieldset>
        <legend>{{ $t('storefront.priceRange') }}</legend>
        <div class="price-inputs">
          <input v-model.number="priceMin" type="number" min="0" step="50000" :placeholder="$t('storefront.priceFrom')">
          <span>–</span>
          <input v-model.number="priceMax" type="number" min="0" step="50000" :placeholder="$t('storefront.priceTo')">
        </div>
      </fieldset>

      <div class="filter-actions">
        <button class="btn-apply" @click="apply">{{ $t('storefront.apply') }}</button>
        <button class="btn-clear" @click="clear">{{ $t('storefront.clearFilters') }}</button>
      </div>
    </div>

    <div v-if="open" class="filter-backdrop" @click="open = false" />
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import type { ProductFilters } from '~/utils/catalogQuery'
import type { BrandInfo } from '~/types/storefront'

const props = defineProps<{ brands: BrandInfo[]; modelValue: ProductFilters }>()
const emit = defineEmits<{ (e: 'apply', filters: ProductFilters): void }>()

const materials = ['full_grain_leather', 'suede', 'pu_leather', 'other']
const sizes = ['38', '39', '40', '41', '42', '43', '44', '45']
const colors = ['Đen', 'Nâu', 'Nâu đậm', 'Xanh navy']

const open = ref(false)
const local = reactive<ProductFilters>({ ...props.modelValue })
const priceMin = ref<number | ''>(props.modelValue.price_min ?? '')
const priceMax = ref<number | ''>(props.modelValue.price_max ?? '')

watch(() => props.modelValue, (value) => {
  Object.assign(local, { brand: undefined, material: undefined, size: undefined, color: undefined, ...value })
  priceMin.value = value.price_min ?? ''
  priceMax.value = value.price_max ?? ''
})

const apply = () => {
  open.value = false
  emit('apply', {
    ...local,
    price_min: priceMin.value === '' ? undefined : Number(priceMin.value),
    price_max: priceMax.value === '' ? undefined : Number(priceMax.value),
    page: undefined
  })
}

const clear = () => {
  open.value = false
  emit('apply', {})
}
</script>

<style scoped lang="scss">
.filter-toggle {
  display: none; border: 1px solid $sf-color-border; background: #fff; border-radius: 8px;
  padding: 8px 14px; font-size: 14px; cursor: pointer; margin-bottom: 12px;
}
.filter-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.filter-close { display: none; border: 0; background: none; font-size: 18px; cursor: pointer; }
fieldset {
  border: 0; border-top: 1px solid $sf-color-border; padding: 12px 0; margin: 0;
  legend { font-size: 13px; font-weight: 700; padding-right: 8px; }
  label { display: block; font-size: 14px; margin: 6px 0; cursor: pointer; }
}
.chip-row { display: flex; flex-wrap: wrap; gap: 6px; }
.chip {
  border: 1px solid $sf-color-border; background: #fff; border-radius: 8px; padding: 6px 10px;
  font-size: 13px; cursor: pointer;
  &.active { border-color: $sf-color-accent; background: $sf-color-accent; color: #fff; }
}
.price-inputs {
  display: flex; align-items: center; gap: 8px;
  input { width: 100%; border: 1px solid $sf-color-border; border-radius: 8px; padding: 8px; font-size: 13px; }
}
.filter-actions { display: flex; gap: 8px; margin-top: 14px; }
.btn-apply {
  flex: 1; background: $sf-color-accent; color: #fff; border: 0; border-radius: 8px;
  padding: 10px; font-weight: 600; cursor: pointer;
  &:hover { background: $sf-color-accent-dark; }
}
.btn-clear { border: 1px solid $sf-color-border; background: #fff; border-radius: 8px; padding: 10px 14px; cursor: pointer; }
.filter-backdrop { display: none; }

@media (max-width: 768px) {
  .filter-toggle { display: inline-block; }
  .filter-close { display: block; }
  .filter-panel {
    position: fixed; left: 0; right: 0; bottom: 0; top: 25%; background: #fff; z-index: 70;
    border-radius: 16px 16px 0 0; padding: 16px; overflow-y: auto;
    transform: translateY(100%); transition: transform 0.25s ease;
    &.open { transform: translateY(0); }
  }
  .filter-backdrop { display: block; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4); z-index: 65; }
}
</style>
