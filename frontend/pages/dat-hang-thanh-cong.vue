<template>
  <div class="sf-container success-page">
    <div class="success-card">
      <div class="success-icon">🎉</div>
      <h1>{{ $t('storefront.orderSuccess') }}</h1>
      <p class="success-desc">{{ $t('storefront.orderSuccessDesc') }}</p>

      <div v-if="orderCode" class="order-code-box">
        <span>Mã đơn hàng của bạn:</span>
        <strong>{{ orderCode }}</strong>
      </div>

      <div class="success-actions">
        <NuxtLink to="/" class="btn-primary">{{ $t('storefront.backHome') }}</NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const route = useRoute()
const orderCode = computed(() => route.query.code as string)

useSeoMeta({
  title: 'Đặt hàng thành công | Giày dép Hồng An',
  robots: 'noindex, nofollow'
})
</script>

<style scoped lang="scss">
.success-page {
  padding: 60px 16px;
  display: flex;
  justify-content: center;
}
.success-card {
  background: #fff; border: 1px solid $sf-color-border; border-radius: $sf-radius;
  padding: 40px; max-width: 500px; width: 100%; text-align: center;
  box-shadow: $sf-shadow-card-hover;
}
.success-icon { font-size: 56px; margin-bottom: 20px; }
h1 { font-size: 24px; font-weight: 800; margin-bottom: 12px; }
.success-desc { color: $sf-color-muted; font-size: 14px; line-height: 1.6; margin-bottom: 24px; }
.order-code-box {
  background: $sf-color-bg-soft; border: 1px dashed $sf-color-border; border-radius: 8px;
  padding: 16px; margin-bottom: 32px;
  span { display: block; font-size: 13px; color: $sf-color-muted; margin-bottom: 4px; }
  strong { font-size: 20px; color: $sf-color-accent; font-weight: 800; letter-spacing: 0.05em; }
}
.success-actions {
  .btn-primary {
    display: inline-block; background: $sf-color-accent; color: #fff; text-decoration: none;
    padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 15px;
    &:hover { background: $sf-color-accent-dark; }
  }
}
</style>
