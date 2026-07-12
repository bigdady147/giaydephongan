<template>
  <div class="sf-container cart-page">
    <Breadcrumbs :items="[{ label: $t('storefront.home'), to: '/' }, { label: $t('storefront.cart') }]" />

    <h1>{{ $t('storefront.cart') }}</h1>

    <div v-if="items.length === 0" class="cart-empty">
      <p>{{ $t('storefront.cartEmpty') }}</p>
      <NuxtLink to="/" class="btn-primary">{{ $t('storefront.backHome') }}</NuxtLink>
    </div>

    <div v-else class="cart-layout">
      <div class="cart-main">
        <table class="cart-table">
          <thead>
            <tr>
              <th>{{ $t('storefront.featuredProducts') }}</th>
              <th align="center">{{ $t('storefront.quantity') }}</th>
              <th align="right">{{ $t('storefront.total') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.variantId">
              <td>
                <div class="cart-product">
                  <img :src="item.thumbnail || '/placeholder.png'" :alt="item.name" class="cart-thumb">
                  <div class="cart-info">
                    <NuxtLink :to="`/san-pham/${item.slug}`" class="cart-name">{{ item.name }}</NuxtLink>
                    <span class="cart-meta">Size: {{ item.size }} · Màu: {{ item.color }}</span>
                    <span class="cart-price">{{ formatCurrency(item.price) }}</span>
                  </div>
                </div>
              </td>
              <td align="center">
                <div class="qty-control">
                  <button @click="updateQty(item.variantId, item.quantity - 1)">–</button>
                  <input type="number" :value="item.quantity" min="1" @change="e => onQtyChange(item.variantId, e)">
                  <button @click="updateQty(item.variantId, item.quantity + 1)">+</button>
                </div>
              </td>
              <td align="right" class="cart-item-total">
                {{ formatCurrency(item.price * item.quantity) }}
              </td>
              <td align="center">
                <button class="btn-remove" :aria-label="'Xóa ' + item.name" @click="removeItem(item.variantId)">✕</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="cart-summary">
        <h2>{{ $t('storefront.total') }}</h2>
        <div class="summary-row">
          <span>{{ $t('storefront.subtotal') }}</span>
          <strong>{{ formatCurrency(total) }}</strong>
        </div>
        <div class="summary-row">
          <span>{{ $t('storefront.shippingFee') }}</span>
          <span>{{ total >= 500000 ? 'Miễn phí' : formatCurrency(30000) }}</span>
        </div>
        <div class="summary-total">
          <span>{{ $t('storefront.total') }}</span>
          <strong>{{ formatCurrency(total + (total >= 500000 ? 0 : 30000)) }}</strong>
        </div>
        <NuxtLink to="/thanh-toan" class="btn-checkout">{{ $t('storefront.checkout') }}</NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCart } from '~/composables/useCart'
import { formatCurrency } from '~/utils/format'

definePageMeta({ ssr: false })

const { items, total, updateQty, removeItem } = useCart()

const onQtyChange = (variantId: number, e: Event) => {
  const input = e.target as HTMLInputElement
  const val = parseInt(input.value, 10)
  if (isNaN(val) || val < 1) {
    input.value = '1'
    updateQty(variantId, 1)
  } else {
    updateQty(variantId, val)
  }
}
</script>

<style scoped lang="scss">
.cart-page {
  padding-top: 8px;
  h1 { font-size: 26px; margin-bottom: 24px; letter-spacing: -0.02em; }
}
.cart-empty {
  text-align: center; padding: 48px 0;
  p { color: $sf-color-muted; margin-bottom: 20px; font-size: 15px; }
  .btn-primary {
    display: inline-block; background: $sf-color-accent; color: #fff; text-decoration: none;
    padding: 12px 24px; border-radius: 8px; font-weight: 600;
    &:hover { background: $sf-color-accent-dark; }
  }
}
.cart-layout { display: grid; grid-template-columns: 1fr 340px; gap: 32px; align-items: start; }
.cart-main {
  border: 1px solid $sf-color-border; border-radius: $sf-radius; overflow: hidden; background: #fff;
}
.cart-table {
  width: 100%; border-collapse: collapse;
  th { background: $sf-color-bg-soft; padding: 14px 16px; font-size: 13px; color: $sf-color-muted; border-bottom: 1px solid $sf-color-border; }
  td { padding: 16px; border-bottom: 1px solid $sf-color-border; }
  tr:last-child td { border-bottom: 0; }
}
.cart-product { display: flex; gap: 14px; align-items: center; }
.cart-thumb { width: 64px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid $sf-color-border; }
.cart-info {
  display: flex; flex-direction: column; gap: 4px;
  .cart-name { font-weight: 600; text-decoration: none; color: $sf-color-text; font-size: 14px; &:hover { color: $sf-color-accent; } }
  .cart-meta { font-size: 12px; color: $sf-color-muted; }
  .cart-price { font-weight: 500; font-size: 13px; }
}
.qty-control {
  display: inline-flex; border: 1px solid $sf-color-border; border-radius: 6px; overflow: hidden;
  button { border: 0; background: $sf-color-bg-soft; width: 28px; height: 28px; cursor: pointer; &:hover { background: #e5e7eb; } }
  input { border: 0; width: 36px; text-align: center; font-size: 13px; outline: none; -moz-appearance: textfield; &::-webkit-inner-spin-button, &::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; } }
}
.cart-item-total { font-weight: 600; font-size: 14px; }
.btn-remove { border: 0; background: none; color: $sf-color-muted; cursor: pointer; padding: 6px; font-size: 14px; &:hover { color: $sf-color-sale; } }

.cart-summary {
  border: 1px solid $sf-color-border; border-radius: $sf-radius; padding: 20px; background: #fff;
  h2 { font-size: 16px; font-weight: 700; margin-bottom: 16px; border-bottom: 1px solid $sf-color-border; padding-bottom: 8px; }
  .summary-row { display: flex; justify-content: space-between; font-size: 14px; color: $sf-color-text; margin-bottom: 12px; }
  .summary-total {
    display: flex; justify-content: space-between; font-size: 15px; font-weight: 700; margin-top: 16px;
    padding-top: 14px; border-top: 1px solid $sf-color-border; margin-bottom: 20px;
    strong { color: $sf-color-accent; font-size: 18px; }
  }
  .btn-checkout {
    display: block; text-align: center; background: $sf-color-accent; color: #fff; text-decoration: none;
    padding: 14px; border-radius: 8px; font-weight: 700; font-size: 15px;
    &:hover { background: $sf-color-accent-dark; }
  }
}

@media (max-width: 768px) {
  .cart-layout { grid-template-columns: 1fr; gap: 20px; }
  .cart-table {
    th:nth-child(3), td:nth-child(3) { display: none; } // hide subtotal column on mobile
  }
}
</style>
