<template>
  <div>
    <div v-if="loading" class="product-grid">
      <div v-for="n in 8" :key="n" class="grid-skeleton" />
    </div>
    <div v-else-if="products.length" class="product-grid">
      <ProductCard v-for="product in products" :key="product.id" :product="product" />
    </div>
    <div v-else class="grid-empty">
      <p>{{ $t('storefront.empty') }}</p>
      <NuxtLink to="/" class="grid-empty-link">{{ $t('storefront.backHome') }}</NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { CardProduct } from '~/types/storefront'

withDefaults(defineProps<{ products: CardProduct[]; loading?: boolean }>(), { loading: false })
</script>

<style scoped lang="scss">
.product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.grid-skeleton {
  aspect-ratio: 4 / 6; border-radius: $sf-radius; background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%; animation: shimmer 1.4s infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }
.grid-empty { text-align: center; padding: 60px 0; color: $sf-color-muted; }
.grid-empty-link { color: $sf-color-accent; font-weight: 600; }
@media (max-width: 768px) { .product-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
</style>
