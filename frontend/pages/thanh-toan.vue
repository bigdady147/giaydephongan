<template>
  <div class="sf-container checkout-page">
    <Breadcrumbs :items="[{ label: $t('storefront.home'), to: '/' }, { label: $t('storefront.cart'), to: '/gio-hang' }, { label: $t('storefront.checkout') }]" />

    <h1>{{ $t('storefront.checkout') }}</h1>

    <div v-if="items.length === 0" class="checkout-empty">
      <p>{{ $t('storefront.cartEmpty') }}</p>
      <NuxtLink to="/" class="btn-primary">{{ $t('storefront.backHome') }}</NuxtLink>
    </div>

    <form v-else class="checkout-layout" @submit.prevent="submitOrder">
      <div class="checkout-main">
        <div class="checkout-card">
          <h2>Thông tin giao hàng</h2>

          <div v-if="!user" class="form-group-row">
            <div class="form-group">
              <label for="guest_name">{{ $t('storefront.guestName') }} *</label>
              <input id="guest_name" v-model="form.guest_name" type="text" required>
            </div>
            <div class="form-group">
              <label for="guest_phone">{{ $t('storefront.guestPhone') }} *</label>
              <input id="guest_phone" v-model="form.guest_phone" type="tel" required>
            </div>
          </div>

          <div v-if="!user" class="form-group">
            <label for="guest_email">{{ $t('storefront.guestEmail') }}</label>
            <input id="guest_email" v-model="form.guest_email" type="email">
          </div>

          <div class="form-group">
            <label for="shipping_address">{{ $t('storefront.shippingAddress') }} *</label>
            <textarea id="shipping_address" v-model="form.shipping_address" rows="3" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"></textarea>
          </div>

          <div class="form-group">
            <label for="note">{{ $t('storefront.note') }}</label>
            <textarea id="note" v-model="form.note" rows="2"></textarea>
          </div>
        </div>

        <div class="checkout-card">
          <h2>Phương thức thanh toán</h2>
          <label class="payment-method-option">
            <input type="radio" checked readonly>
            <span class="payment-icon">💵</span>
            <span class="payment-text">
              <strong>Thanh toán khi nhận hàng (COD)</strong>
              <span>Kiểm tra hàng trước khi thanh toán</span>
            </span>
          </label>
        </div>
      </div>

      <div class="checkout-sidebar">
        <div class="checkout-card">
          <h2>Tóm tắt đơn hàng</h2>

          <div class="checkout-items">
            <div v-for="item in items" :key="item.variantId" class="checkout-item">
              <img :src="item.thumbnail || '/placeholder.png'" :alt="item.name">
              <div class="item-info">
                <span class="item-name">{{ item.name }}</span>
                <span class="item-meta">Size: {{ item.size }} · Màu: {{ item.color }} · SL: {{ item.quantity }}</span>
                <span class="item-price">{{ formatCurrency(item.price * item.quantity) }}</span>
              </div>
            </div>
          </div>

          <div class="discount-code-block">
            <label for="discount_code">{{ $t('storefront.discountCode') }}</label>
            <div class="discount-input">
              <input id="discount_code" v-model="discountCodeInput" type="text" :disabled="!!appliedCode" placeholder="Nhập mã giảm giá">
              <button v-if="!appliedCode" type="button" :disabled="validatingDiscount || !discountCodeInput" @click="applyDiscount">
                {{ $t('storefront.applyDiscount') }}
              </button>
              <button v-else type="button" class="btn-remove-discount" @click="removeDiscount">✕</button>
            </div>
            <p v-if="discountError" class="discount-error">{{ discountError }}</p>
            <p v-if="appliedCode" class="discount-success">Đã áp dụng mã <strong>{{ appliedCode }}</strong></p>
          </div>

          <div class="summary-details">
            <div class="summary-row">
              <span>{{ $t('storefront.subtotal') }}</span>
              <span>{{ formatCurrency(total) }}</span>
            </div>
            <div class="summary-row">
              <span>{{ $t('storefront.shippingFee') }}</span>
              <span>{{ total >= 500000 ? 'Miễn phí' : formatCurrency(30000) }}</span>
            </div>
            <div v-if="discountAmount > 0" class="summary-row discount">
              <span>{{ $t('storefront.discountAmount') }}</span>
              <span>–{{ formatCurrency(discountAmount) }}</span>
            </div>
            <div class="summary-total">
              <span>{{ $t('storefront.total') }}</span>
              <strong>{{ formatCurrency(finalTotal) }}</strong>
            </div>
          </div>

          <p v-if="submitError" class="submit-error">{{ submitError }}</p>

          <button type="submit" class="btn-submit-order" :disabled="submitting">
            {{ submitting ? 'Đang xử lý...' : $t('storefront.orderNow') }}
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useCart } from '~/composables/useCart'
import { formatCurrency } from '~/utils/format'

definePageMeta({ ssr: false })

const router = useRouter()
const config = useRuntimeConfig()
const api = config.public.apiBase
const { user } = useAuth()
const { items, total, clearCart } = useCart()

const form = reactive({
  guest_name: '',
  guest_phone: '',
  guest_email: '',
  shipping_address: '',
  note: ''
})

const discountCodeInput = ref('')
const validatingDiscount = ref(false)
const discountError = ref('')
const appliedCode = ref('')
const discountAmount = ref(0)

const shippingFee = computed(() => (total.value >= 500000 ? 0 : 30000))
const finalTotal = computed(() => Math.max(0, total.value + shippingFee.value - discountAmount.value))

const submitting = ref(false)
const submitError = ref('')

const applyDiscount = async () => {
  if (!discountCodeInput.value) return
  validatingDiscount.value = true
  discountError.value = ''
  try {
    const res = await $fetch<{ valid: boolean; discount_amount: number }>(`${api}/discount-codes/validate`, {
      method: 'POST',
      body: {
        code: discountCodeInput.value.trim(),
        subtotal: total.value
      }
    })
    if (res.valid) {
      appliedCode.value = discountCodeInput.value.trim()
      discountAmount.value = res.discount_amount
    }
  } catch (err: any) {
    discountError.value = err.data?.message ?? 'Không thể áp dụng mã giảm giá.'
  } finally {
    validatingDiscount.value = false
  }
}

const removeDiscount = () => {
  appliedCode.value = ''
  discountAmount.value = 0
  discountCodeInput.value = ''
  discountError.value = ''
}

const submitOrder = async () => {
  submitting.value = true
  submitError.value = ''
  try {
    const payload = {
      shipping_address: form.shipping_address,
      note: form.note,
      discount_code: appliedCode.value || null,
      items: items.value.map(i => ({
        variant_id: i.variantId,
        quantity: i.quantity
      })),
      ...(user.value ? {} : {
        guest_name: form.guest_name,
        guest_phone: form.guest_phone,
        guest_email: form.guest_email || null
      })
    }

    const token = typeof window !== 'undefined' ? window.localStorage.getItem('auth_token') : null
    const headers: Record<string, string> = { Accept: 'application/json' }
    if (token) {
      headers.Authorization = `Bearer ${token}`
    }

    const res = await $fetch<{ order_code: string }>(`${api}/orders`, {
      method: 'POST',
      body: payload,
      headers
    })

    clearCart()
    router.replace(`/dat-hang-thanh-cong?code=${res.order_code}`)
  } catch (err: any) {
    submitError.value = err.data?.message ?? 'Đã xảy ra lỗi khi đặt hàng. Vui lòng thử lại.'
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped lang="scss">
.checkout-page {
  padding-top: 8px;
  h1 { font-size: 26px; margin-bottom: 24px; letter-spacing: -0.02em; }
}
.checkout-empty {
  text-align: center; padding: 48px 0;
  p { color: $sf-color-muted; margin-bottom: 20px; font-size: 15px; }
  .btn-primary {
    display: inline-block; background: $sf-color-accent; color: #fff; text-decoration: none;
    padding: 12px 24px; border-radius: 8px; font-weight: 600;
    &:hover { background: $sf-color-accent-dark; }
  }
}
.checkout-layout { display: grid; grid-template-columns: 1fr 360px; gap: 32px; align-items: start; }
.checkout-main { display: flex; flex-direction: column; gap: 24px; }
.checkout-card {
  border: 1px solid $sf-color-border; border-radius: $sf-radius; padding: 24px; background: #fff;
  h2 { font-size: 17px; font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid $sf-color-border; padding-bottom: 8px; }
}
.form-group-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group {
  margin-bottom: 16px;
  label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
  input, textarea {
    width: 100%; border: 1px solid $sf-color-border; border-radius: 8px; padding: 10px 12px;
    font-size: 14px; outline: none; &:focus { border-color: $sf-color-accent; }
  }
}
.payment-method-option {
  display: flex; gap: 14px; align-items: flex-start; padding: 16px;
  border: 1px solid $sf-color-accent; border-radius: 8px; background: $sf-color-bg-soft;
  input { margin-top: 4px; }
}
.payment-icon { font-size: 20px; }
.payment-text {
  display: flex; flex-direction: column; gap: 4px;
  strong { font-size: 14px; }
  span { font-size: 12px; color: $sf-color-muted; }
}

.checkout-sidebar { display: flex; flex-direction: column; gap: 24px; }
.checkout-items {
  display: flex; flex-direction: column; gap: 14px; max-height: 240px; overflow-y: auto;
  border-bottom: 1px solid $sf-color-border; padding-bottom: 16px; margin-bottom: 16px;
}
.checkout-item {
  display: flex; gap: 12px; align-items: center;
  img { width: 44px; height: 55px; object-fit: cover; border-radius: 4px; border: 1px solid $sf-color-border; }
  .item-info {
    display: flex; flex-direction: column; flex: 1; min-width: 0;
    .item-name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .item-meta { font-size: 11px; color: $sf-color-muted; margin: 2px 0; }
    .item-price { font-size: 12px; font-weight: 500; }
  }
}
.discount-code-block {
  border-bottom: 1px solid $sf-color-border; padding-bottom: 16px; margin-bottom: 16px;
  label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
}
.discount-input {
  display: flex; gap: 8px;
  input { flex: 1; border: 1px solid $sf-color-border; border-radius: 8px; padding: 8px 10px; font-size: 13px; outline: none; }
  button {
    background: $sf-color-accent; color: #fff; border: 0; border-radius: 8px; padding: 0 16px; font-size: 13px; font-weight: 600; cursor: pointer;
    &:disabled { opacity: 0.5; }
  }
  .btn-remove-discount { background: #e5e7eb; color: $sf-color-text; }
}
.discount-error { color: $sf-color-sale; font-size: 12px; margin-top: 6px; }
.discount-success { color: #15803d; font-size: 12px; margin-top: 6px; }

.summary-details {
  .summary-row {
    display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;
    &.discount { color: $sf-color-sale; }
  }
  .summary-total {
    display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; margin-top: 14px;
    padding-top: 12px; border-top: 1px solid $sf-color-border; margin-bottom: 20px;
    strong { color: $sf-color-accent; font-size: 18px; }
  }
}
.submit-error { color: $sf-color-sale; font-size: 13px; font-weight: 600; margin-bottom: 12px; text-align: center; }
.btn-submit-order {
  width: 100%; border: 0; background: $sf-color-accent; color: #fff; font-size: 15px; font-weight: 700; padding: 14px; border-radius: 8px; cursor: pointer;
  &:hover:not(:disabled) { background: $sf-color-accent-dark; }
  &:disabled { opacity: 0.5; cursor: not-allowed; }
}

@media (max-width: 768px) {
  .checkout-layout { grid-template-columns: 1fr; gap: 20px; }
}
</style>
