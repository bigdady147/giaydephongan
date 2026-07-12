<template>
  <div class="settings-page">
    <div class="page-header">
      <h1>Cài đặt cửa hàng</h1>
      <el-button type="primary" :loading="saving" @click="saveSettings">Lưu cấu hình</el-button>
    </div>

    <el-alert v-if="error" :title="error" type="error" show-icon class="page-alert" />

    <el-tabs v-model="activeTab" class="settings-tabs" type="border-card" v-loading="loading">
      <el-tab-pane label="Thông tin liên hệ" name="contact">
        <el-form label-position="top" class="settings-form">
          <el-form-item label="Hotline (điện thoại hỗ trợ)">
            <el-input v-model="localSettings.hotline" placeholder="Ví dụ: 0909 000 000" />
          </el-form-item>
          <el-form-item label="Email liên hệ">
            <el-input v-model="localSettings.email" placeholder="Ví dụ: lienhe@giaydephongan.vn" />
          </el-form-item>
          <el-form-item label="Địa chỉ cửa hàng">
            <el-input v-model="localSettings.address" placeholder="Ví dụ: 123 Lê Lợi, Quận 1, TP.HCM" />
          </el-form-item>
          <el-form-item label="Giờ mở cửa">
            <el-input v-model="localSettings.open_hours" placeholder="Ví dụ: 8:00 – 21:00 (Thứ 2 – Chủ nhật)" />
          </el-form-item>
        </el-form>
      </el-tab-pane>

      <el-tab-pane label="Mạng xã hội & Chat" name="social">
        <el-form label-position="top" class="settings-form">
          <el-form-item label="Đường dẫn Facebook (Page)">
            <el-input v-model="localSettings.facebook_url" placeholder="Ví dụ: https://facebook.com/giaydephongan" />
          </el-form-item>
          <el-form-item label="Đường dẫn Zalo Chat">
            <el-input v-model="localSettings.zalo_url" placeholder="Ví dụ: https://zalo.me/0909000000" />
          </el-form-item>
          <el-form-item label="Đường dẫn Facebook Messenger">
            <el-input v-model="localSettings.messenger_url" placeholder="Ví dụ: https://m.me/giaydephongan" />
          </el-form-item>
          <el-form-item label="Đường dẫn Instagram">
            <el-input v-model="localSettings.instagram_url" placeholder="Ví dụ: https://instagram.com/giaydephongan" />
          </el-form-item>
        </el-form>
      </el-tab-pane>

      <el-tab-pane label="Chân trang (Footer)" name="footer">
        <el-form label-position="top" class="settings-form">
          <el-form-item label="Tên doanh nghiệp / Hộ kinh doanh">
            <el-input v-model="localSettings.business_name" placeholder="Ví dụ: Hộ kinh doanh Giày dép Hồng An" />
          </el-form-item>
          <el-form-item label="Thông tin giấy phép (GPKD)">
            <el-input v-model="localSettings.business_registration" placeholder="Ví dụ: GPKD số 0123456789..." />
          </el-form-item>
          <el-form-item label="Bản quyền (Copyright)">
            <el-input v-model="localSettings.copyright" placeholder="Ví dụ: © 2026 Giày dép Hồng An..." />
          </el-form-item>
        </el-form>
      </el-tab-pane>

      <el-tab-pane label="Cam kết mua hàng (USP)" name="usp">
        <el-form label-position="top" class="settings-form">
          <el-form-item label="Cam kết 1 (Biểu tượng ✓)">
            <el-input v-model="localSettings.usp_1" placeholder="Ví dụ: Da bò thật 100%" />
          </el-form-item>
          <el-form-item label="Cam kết 2 (Biểu tượng 🛡)">
            <el-input v-model="localSettings.usp_2" placeholder="Ví dụ: Bảo hành 12 tháng" />
          </el-form-item>
          <el-form-item label="Cam kết 3 (Biểu tượng 🚚)">
            <el-input v-model="localSettings.usp_3" placeholder="Ví dụ: Freeship đơn từ 500K" />
          </el-form-item>
        </el-form>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'

definePageMeta({ layout: 'admin' })

interface Setting {
  id: number
  key: string
  value: string | null
  group: string
}

const api = useApiClient()

const activeTab = ref('contact')
const loading = ref(true)
const saving = ref(false)
const error = ref('')

const localSettings = reactive<Record<string, string>>({
  hotline: '',
  email: '',
  address: '',
  open_hours: '',
  facebook_url: '',
  zalo_url: '',
  messenger_url: '',
  instagram_url: '',
  business_name: '',
  business_registration: '',
  copyright: '',
  usp_1: '',
  usp_2: '',
  usp_3: ''
})

const loadSettings = async () => {
  loading.value = true
  error.value = ''
  try {
    const list = await api.get<Setting[]>('/admin/settings')
    list.forEach((item) => {
      localSettings[item.key] = item.value ?? ''
    })
  } catch (err: any) {
    error.value = err.message ?? 'Không thể tải cấu hình.'
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  saving.value = true
  error.value = ''

  try {
    const payload: Record<string, string | null> = {}
    for (const key of Object.keys(localSettings)) {
      payload[key] = localSettings[key].trim() || null
    }

    await api.put('/admin/settings', { settings: payload })
    ElMessage.success('Cấu hình đã được lưu thành công')
    await loadSettings()
  } catch (err: any) {
    error.value = err.message ?? 'Không thể lưu cấu hình.'
  } finally {
    saving.value = false
  }
}

onMounted(loadSettings)
</script>

<style scoped lang="scss">
.settings-page {
  max-width: 800px;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.page-alert {
  margin-bottom: 16px;
}
.settings-tabs {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}
.settings-form {
  padding: 10px 0;
  max-width: 560px;
}
</style>
