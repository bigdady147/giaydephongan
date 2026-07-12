<template>
  <div class="loyalty-page">
    <h2>Điểm thưởng & Hạng thành viên</h2>

    <div v-if="loading" class="loading-state">
      Đang tải thông tin điểm tích lũy...
    </div>

    <div v-else-if="loyalty" class="loyalty-content">
      <!-- Tier Card -->
      <div :class="['tier-card', tierClass(loyalty.current_tier?.name)]">
        <div class="tier-card-glow"></div>
        <div class="tier-card-header">
          <span class="card-brand">Giày dép Hồng An</span>
          <span class="tier-badge">{{ loyalty.current_tier?.name ?? 'Đồng' }} Member</span>
        </div>
        <div class="tier-card-body">
          <div class="points-display">
            <strong>{{ loyalty.points }}</strong>
            <span>Điểm tích lũy</span>
          </div>
          <div class="tier-benefit" v-if="loyalty.current_tier?.discount_percent > 0">
            Ưu đãi hạng: Giảm <strong>{{ loyalty.current_tier.discount_percent }}%</strong> trên mỗi đơn hàng
          </div>
          <div class="tier-benefit" v-else>
            Ưu đãi hạng: Tích lũy thêm điểm để nhận chiết khấu trực tiếp
          </div>
        </div>
      </div>

      <!-- Progress to next tier -->
      <div v-if="loyalty.next_tier" class="progress-section">
        <div class="progress-label">
          <span>Tiến trình nâng hạng: <strong>{{ loyalty.next_tier.name }}</strong></span>
          <span>Cần thêm <strong>{{ loyalty.next_tier.points_needed }} điểm</strong></span>
        </div>
        <div class="progress-bar-bg">
          <div class="progress-bar-fill" :style="{ width: progressPercentage + '%' }"></div>
        </div>
        <p class="progress-tip">Mỗi 10.000đ khi giao hàng thành công sẽ nhận được 1 điểm tích lũy.</p>
      </div>
      <div v-else class="progress-section max-tier">
        🎉 Bạn đang sở hữu hạng thành viên cao nhất (Bạch Kim). Cảm ơn sự tin yêu của bạn dành cho Hồng An!
      </div>

      <!-- Ledger history -->
      <div class="history-section">
        <h3>Lịch sử điểm thưởng</h3>
        <div v-if="loyalty.history.length === 0" class="empty-history">
          Chưa có lịch sử giao dịch điểm tích lũy.
        </div>
        <div v-else class="history-table-container">
          <table class="history-table">
            <thead>
              <tr>
                <th>Ngày</th>
                <th>Nội dung</th>
                <th align="right">Điểm</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in loyalty.history" :key="log.id">
                <td>{{ formatDate(log.created_at) }}</td>
                <td>{{ log.note }}</td>
                <td align="right" :class="['log-points', log.points > 0 ? 'plus' : 'minus']">
                  {{ log.points > 0 ? '+' : '' }}{{ log.points }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

definePageMeta({ layout: 'account', middleware: 'auth' })

interface Tier {
  id: number
  name: string
  min_points: number
  discount_percent: number
}

interface NextTier {
  name: string
  min_points: number
  points_needed: number
}

interface LoyaltyLog {
  id: number
  points: number
  type: 'earn' | 'redeem' | 'adjust'
  note: string
  created_at: string
}

interface LoyaltyResponse {
  points: number
  current_tier: Tier | null
  next_tier: NextTier | null
  history: LoyaltyLog[]
}

const api = useApiClient()

const loyalty = ref<LoyaltyResponse | null>(null)
const loading = ref(true)

const loadLoyalty = async () => {
  loading.value = true
  try {
    loyalty.value = await api.get<LoyaltyResponse>('/user/loyalty')
  } catch {
    loyalty.value = null
  } finally {
    loading.value = false
  }
}

const tierClass = (tierName: string | undefined) => {
  if (!tierName) return 'tier-bronze'
  const map: Record<string, string> = {
    'Đồng': 'tier-bronze',
    'Bạc': 'tier-silver',
    'Vàng': 'tier-gold',
    'Bạch Kim': 'tier-platinum'
  }
  return map[tierName] ?? 'tier-bronze'
}

const progressPercentage = computed(() => {
  if (!loyalty.value || !loyalty.value.next_tier) return 100
  const currentPoints = loyalty.value.points
  const nextMin = loyalty.value.next_tier.min_points
  const currentMin = loyalty.value.current_tier?.min_points ?? 0
  const range = nextMin - currentMin
  if (range <= 0) return 100
  const completed = currentPoints - currentMin
  return Math.min(100, Math.max(0, Math.round((completed / range) * 100)))
})

const formatDate = (val: string) => {
  return new Date(val).toLocaleDateString('vi-VN')
}

onMounted(loadLoyalty)
</script>

<style scoped lang="scss">
.loyalty-page {
  h2 { font-size: 20px; font-weight: 700; margin-bottom: 20px; letter-spacing: -0.01em; }
}
.loading-state {
  text-align: center; padding: 40px; color: $sf-color-muted; font-size: 14px;
}

// Tier Card Styling with rich rank-based gradients
.tier-card {
  position: relative; border-radius: 16px; padding: 24px; color: #fff; overflow: hidden;
  box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); margin-bottom: 24px;
  display: flex; flex-direction: column; justify-content: space-between; height: 170px;

  &.tier-bronze { background: linear-gradient(135deg, #a78bfa, #8b5e34); } // leather bronze
  &.tier-silver { background: linear-gradient(135deg, #9ca3af, #4b5563); }
  &.tier-gold { background: linear-gradient(135deg, #fbbf24, #d97706); }
  &.tier-platinum { background: linear-gradient(135deg, #374151, #111827); }

  .tier-card-glow {
    position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%);
    pointer-events: none;
  }
}
.tier-card-header {
  display: flex; justify-content: space-between; align-items: center; z-index: 1;
  .card-brand { font-size: 13px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; opacity: 0.8; }
  .tier-badge { background: rgba(255,255,255,0.2); backdrop-filter: blur(4px); padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }
}
.tier-card-body {
  z-index: 1;
  .points-display {
    display: flex; align-items: baseline; gap: 8px; margin-bottom: 12px;
    strong { font-size: 36px; font-weight: 800; line-height: 1; }
    span { font-size: 12px; opacity: 0.8; }
  }
  .tier-benefit {
    font-size: 13px; opacity: 0.9;
    strong { font-size: 14px; text-decoration: underline; }
  }
}

// Progress Bar
.progress-section {
  background: #fff; border: 1px solid $sf-color-border; border-radius: $sf-radius; padding: 20px; margin-bottom: 24px;
  .progress-label { display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
  .progress-bar-bg { background: $sf-color-bg-soft; height: 10px; border-radius: 99px; overflow: hidden; margin-bottom: 8px; }
  .progress-bar-fill { background: $sf-color-accent; height: 100%; border-radius: 99px; transition: width 0.3s ease-out; }
  .progress-tip { font-size: 11px; color: $sf-color-muted; margin: 0; }
  &.max-tier { text-align: center; font-size: 13px; font-weight: 600; color: #15803d; border-color: #bbf7d0; background: #f0fdf4; }
}

// History section
.history-section {
  h3 { font-size: 16px; font-weight: 700; margin-bottom: 14px; border-bottom: 1px solid $sf-color-border; padding-bottom: 8px; }
  .empty-history { text-align: center; color: $sf-color-muted; font-size: 13px; padding: 20px; }
}
.history-table-container { border: 1px solid $sf-color-border; border-radius: 8px; overflow: hidden; background: #fff; }
.history-table {
  width: 100%; border-collapse: collapse; font-size: 13px;
  th { background: $sf-color-bg-soft; padding: 10px 14px; font-weight: 600; text-align: left; }
  td { padding: 12px 14px; border-top: 1px solid $sf-color-border; }
  .log-points {
    font-weight: 700;
    &.plus { color: #16a34a; }
    &.minus { color: #dc2626; }
  }
}
</style>
