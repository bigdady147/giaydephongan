<template>
  <div class="orders-history">
    <h2>Đơn hàng của tôi</h2>

    <div v-if="loading" class="loading-state">
      Đang tải đơn hàng...
    </div>

    <div v-else-if="orders.length === 0" class="empty-state">
      Bạn chưa đặt đơn hàng nào.
      <NuxtLink to="/" class="btn-shop">Mua sắm ngay</NuxtLink>
    </div>

    <div v-else class="orders-list">
      <div v-for="order in orders" :key="order.id" class="order-card">
        <div class="order-card-header">
          <div class="order-summary-meta">
            <span class="order-code">#{{ order.order_code }}</span>
            <span class="order-date">{{ formatDate(order.created_at) }}</span>
          </div>
          <span :class="['order-status', order.status]">
            {{ statusLabel(order.status) }}
          </span>
        </div>

        <div class="order-card-body">
          <div class="order-ship-to">
            <strong>Địa chỉ giao hàng:</strong> {{ order.shipping_address }}
          </div>
          <div class="order-total-price">
            Tổng thanh toán: <strong>{{ formatVnd(order.total) }}</strong>
          </div>
        </div>

        <div class="order-card-actions">
          <button class="btn-toggle-details" @click="toggleDetails(order.order_code)">
            {{ activeOrderCode === order.order_code ? 'Ẩn chi tiết' : 'Xem chi tiết đơn' }}
          </button>
        </div>

        <!-- Inline details collapsible -->
        <div v-if="activeOrderCode === order.order_code" class="order-details-drawer">
          <div v-if="detailsLoading" class="details-loading">
            Đang tải chi tiết đơn hàng...
          </div>
          <div v-else-if="details" class="details-content">
            <h4>Chi tiết sản phẩm</h4>
            <div class="details-items">
              <div v-for="item in details.items" :key="item.id" class="details-item">
                <div class="item-name-spec">
                  <span class="item-name">{{ item.product_name_snapshot }}</span>
                  <span class="item-spec">{{ item.variant_snapshot }}</span>
                </div>
                <span class="item-qty-price">
                  {{ item.quantity }} x {{ formatVnd(item.price) }}
                </span>
                <span class="item-subtotal">
                  {{ formatVnd(item.subtotal) }}
                </span>
              </div>
            </div>

            <div class="details-pricing">
              <div class="pricing-row">
                <span>Tạm tính</span>
                <span>{{ formatVnd(details.subtotal) }}</span>
              </div>
              <div class="pricing-row">
                <span>Phí vận chuyển</span>
                <span>{{ details.shipping_fee === 0 ? 'Miễn phí' : formatVnd(details.shipping_fee) }}</span>
              </div>
              <div v-if="details.discount_amount > 0" class="pricing-row discount">
                <span>Giảm giá</span>
                <span>–{{ formatVnd(details.discount_amount) }}</span>
              </div>
              <div class="pricing-row total">
                <span>Tổng cộng</span>
                <strong>{{ formatVnd(details.total) }}</strong>
              </div>
            </div>

            <div v-if="details.note" class="details-note">
              <strong>Ghi chú của bạn:</strong> {{ details.note }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { formatVnd } from '~/utils/format'

definePageMeta({ layout: 'account', middleware: 'auth' })

interface OrderItem {
  id: number
  product_name_snapshot: string
  variant_snapshot: string
  price: number
  quantity: number
  subtotal: number
}

interface Order {
  id: number
  order_code: string
  shipping_address: string
  status: 'pending' | 'confirmed' | 'shipping' | 'delivered' | 'cancelled'
  total: number
  subtotal: number
  shipping_fee: number
  discount_amount: number
  note: string | null
  created_at: string
  items?: OrderItem[]
}

const api = useApiClient()

const orders = ref<Order[]>([])
const loading = ref(true)

const activeOrderCode = ref<string | null>(null)
const details = ref<Order | null>(null)
const detailsLoading = ref(false)

const loadOrders = async () => {
  loading.value = true
  try {
    const res = await api.get<{ data: Order[] }>('/user/orders')
    orders.value = res.data
  } catch {
    orders.value = []
  } finally {
    loading.value = false
  }
}

const formatDate = (val: string) => {
  return new Date(val).toLocaleDateString('vi-VN')
}

const statusLabel = (status: Order['status']) => ({
  pending: 'Chờ xác nhận',
  confirmed: 'Đã xác nhận',
  shipping: 'Đang giao',
  delivered: 'Đã giao hàng',
  cancelled: 'Đã hủy'
})[status]

const toggleDetails = async (code: string) => {
  if (activeOrderCode.value === code) {
    activeOrderCode.value = null
    details.value = null
    return
  }
  activeOrderCode.value = code
  detailsLoading.value = true
  try {
    details.value = await api.get<Order>(`/user/orders/${code}`)
  } catch {
    details.value = null
  } finally {
    detailsLoading.value = false
  }
}

onMounted(loadOrders)
</script>

<style scoped lang="scss">
.orders-history {
  h2 { font-size: 20px; font-weight: 700; margin-bottom: 20px; letter-spacing: -0.01em; }
}
.loading-state, .empty-state {
  text-align: center; padding: 40px; color: $sf-color-muted; font-size: 14px;
}
.empty-state {
  display: flex; flex-direction: column; align-items: center; gap: 16px;
  .btn-shop {
    background: $sf-color-accent; color: #fff; text-decoration: none; padding: 10px 20px;
    border-radius: 8px; font-weight: 600; &:hover { background: $sf-color-accent-dark; }
  }
}
.orders-list { display: flex; flex-direction: column; gap: 16px; }
.order-card {
  border: 1px solid $sf-color-border; border-radius: $sf-radius; background: #fff; overflow: hidden;
}
.order-card-header {
  background: $sf-color-bg-soft; padding: 14px 16px; display: flex; justify-content: space-between; align-items: center;
  border-bottom: 1px solid $sf-color-border;
  .order-summary-meta {
    display: flex; gap: 12px; align-items: center;
    .order-code { font-weight: 700; color: $sf-color-text; }
    .order-date { font-size: 12px; color: $sf-color-muted; }
  }
}
.order-status {
  font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 99px;
  &.pending { background: #e5e7eb; color: #4b5563; }
  &.confirmed { background: #dbeafe; color: #2563eb; }
  &.shipping { background: #fef3c7; color: #d97706; }
  &.delivered { background: #dcfce7; color: #16a34a; }
  &.cancelled { background: #fee2e2; color: #dc2626; }
}
.order-card-body {
  padding: 16px; font-size: 14px; display: flex; flex-direction: column; gap: 8px;
  .order-total-price {
    font-size: 14px; strong { color: $sf-color-accent; font-size: 16px; }
  }
}
.order-card-actions {
  border-top: 1px dashed $sf-color-border; padding: 12px 16px; display: flex; justify-content: flex-end;
  .btn-toggle-details {
    background: none; border: 0; color: $sf-color-accent; font-weight: 600; font-size: 13px; cursor: pointer;
    &:hover { text-decoration: underline; }
  }
}

.order-details-drawer {
  background: #fafaf9; border-top: 1px solid $sf-color-border; padding: 16px;
  .details-loading { font-size: 13px; color: $sf-color-muted; text-align: center; padding: 12px; }
  h4 { font-size: 14px; font-weight: 700; margin-bottom: 12px; }
}
.details-items { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.details-item {
  display: flex; justify-content: space-between; align-items: center; font-size: 13px;
  .item-name-spec {
    display: flex; flex-direction: column; flex: 1; min-width: 0;
    .item-name { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .item-spec { font-size: 11px; color: $sf-color-muted; margin-top: 2px; }
  }
  .item-qty-price { color: $sf-color-muted; margin: 0 16px; }
  .item-subtotal { font-weight: 600; }
}
.details-pricing {
  border-top: 1px solid $sf-color-border; padding-top: 12px; display: flex; flex-direction: column; gap: 6px;
  .pricing-row {
    display: flex; justify-content: space-between; font-size: 13px;
    &.discount { color: $sf-color-sale; }
    &.total {
      font-weight: 700; font-size: 14px; border-top: 1px solid $sf-color-border; padding-top: 8px; margin-top: 4px;
      strong { color: $sf-color-accent; font-size: 16px; }
    }
  }
}
.details-note {
  margin-top: 12px; font-size: 12px; color: $sf-color-muted; background: #fff; padding: 10px; border-radius: 6px; border: 1px solid $sf-color-border;
}
</style>
