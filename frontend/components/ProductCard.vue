<template>
  <NuxtLink :to="`/san-pham/${product.slug}`" class="product-card">
    <div class="card-media">
      <img v-if="product.thumbnail" :src="product.thumbnail" :alt="product.name" loading="lazy">
      <div v-else class="card-placeholder">👞</div>
      <span v-if="product.sale_price" class="card-badge badge-sale">-{{ discountPercent }}%</span>
      <span v-else-if="isNew(product.created_at)" class="card-badge badge-new">{{ $t('storefront.badgeNew') }}</span>
    </div>
    <div class="card-body">
      <h3 class="card-name">{{ product.name }}</h3>
      <PriceTag :base-price="product.base_price" :sale-price="product.sale_price" />
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { isNew } from '~/utils/format'
import type { CardProduct } from '~/types/storefront'

const props = defineProps<{ product: CardProduct }>()

const discountPercent = computed(() =>
  props.product.sale_price ? Math.round((1 - props.product.sale_price / props.product.base_price) * 100) : 0
)
</script>

<style scoped lang="scss">
.product-card {
  display: block; text-decoration: none; color: $sf-color-text;
  border-radius: $sf-radius; overflow: hidden; background: #fff;
  border: 1px solid $sf-color-border; box-shadow: $sf-shadow-card;
  transition: box-shadow 0.2s, transform 0.2s;
  &:hover { box-shadow: $sf-shadow-card-hover; transform: translateY(-2px); }
}
.card-media {
  position: relative; aspect-ratio: 4 / 5; background: $sf-color-bg-soft;
  img { width: 100%; height: 100%; object-fit: cover; display: block; }
}
.card-placeholder { display: flex; align-items: center; justify-content: center; height: 100%; font-size: 48px; }
.card-badge {
  position: absolute; top: 10px; left: 10px; font-size: 12px; font-weight: 700;
  padding: 3px 8px; border-radius: 6px; color: #fff;
}
.badge-sale { background: $sf-color-sale; }
.badge-new { background: $sf-color-accent; }
.card-body { padding: 12px 14px 14px; }
.card-name {
  font-size: 14px; font-weight: 500; margin-bottom: 8px; line-height: 1.4;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
  min-height: 2.8em;
}
</style>
