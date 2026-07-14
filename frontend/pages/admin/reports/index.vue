<template>
  <div class="reports-page">
    <div class="page-header">
      <h1>Báo cáo doanh thu</h1>
      <el-date-picker
        v-model="dateRange"
        type="daterange"
        start-placeholder="Từ ngày"
        end-placeholder="Đến ngày"
        value-format="YYYY-MM-DD"
        @change="loadAll"
      />
    </div>

    <el-alert v-if="error" :title="error" type="error" show-icon class="page-alert" />

    <el-row :gutter="20" v-loading="loading">
      <el-col :span="8">
        <el-card shadow="hover">
          <el-statistic title="Tổng doanh thu (VNĐ)" :value="revenue.total_revenue" />
          <p class="stat-sub">{{ formatVnd(revenue.total_revenue) }}</p>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <el-statistic title="Tổng số đơn (không tính đơn hủy)" :value="revenue.total_orders" />
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" class="reports-tables">
      <el-col :span="12">
        <el-card shadow="never">
          <template #header>Doanh thu theo ngày</template>
          <el-table :data="revenue.daily" size="small" style="width: 100%">
            <el-table-column prop="date" label="Ngày" />
            <el-table-column label="Doanh thu">
              <template #default="{ row }">{{ formatVnd((row as DailyRevenue).revenue) }}</template>
            </el-table-column>
            <el-table-column prop="orders_count" label="Số đơn" />
          </el-table>
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card shadow="never">
          <template #header>Top sản phẩm bán chạy</template>
          <el-table :data="topProducts.products" size="small" style="width: 100%">
            <el-table-column prop="name" label="Sản phẩm" />
            <el-table-column prop="quantity_sold" label="SL bán" width="90" />
            <el-table-column label="Doanh thu">
              <template #default="{ row }">{{ formatVnd((row as TopProduct).revenue) }}</template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { formatVnd } from '~/utils/format'

definePageMeta({ layout: 'admin' })

interface DailyRevenue { date: string; revenue: number; orders_count: number }
interface TopProduct { name: string; quantity_sold: number; revenue: number }

const api = useApiClient()

const loading = ref(true)
const error = ref('')
const dateRange = ref<[string, string] | null>(null)

const revenue = reactive<{ total_revenue: number; total_orders: number; daily: DailyRevenue[] }>({
  total_revenue: 0,
  total_orders: 0,
  daily: []
})
const topProducts = reactive<{ products: TopProduct[] }>({ products: [] })

const loadAll = async () => {
  loading.value = true
  error.value = ''
  try {
    const query = dateRange.value ? `?from=${dateRange.value[0]}&to=${dateRange.value[1]}` : ''
    const [revenueRes, topRes] = await Promise.all([
      api.get<{ total_revenue: number; total_orders: number; daily: DailyRevenue[] }>(`/admin/reports/revenue${query}`),
      api.get<{ products: TopProduct[] }>(`/admin/reports/top-products${query}`)
    ])
    Object.assign(revenue, revenueRes)
    Object.assign(topProducts, topRes)
  } catch (err: any) {
    error.value = err.message ?? 'Không thể tải báo cáo.'
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)
</script>

<style scoped lang="scss">
.reports-page { max-width: 1300px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-alert { margin-bottom: 16px; }
.stat-sub { margin-top: 8px; font-size: 12px; color: #6b7280; }
.reports-tables { margin-top: 20px; }
</style>
