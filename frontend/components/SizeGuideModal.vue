<template>
  <Teleport to="body">
    <div v-if="modelValue" class="sgm-backdrop" @click.self="close">
      <div class="sgm-dialog" role="dialog" aria-modal="true">
        <div class="sgm-head">
          <strong>{{ $t('storefront.sizeGuide') }}</strong>
          <button aria-label="Đóng" @click="close">✕</button>
        </div>
        <div v-if="content" class="sgm-body" v-html="content" />
        <p v-else class="sgm-loading">{{ $t('common.loading') }}</p>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { sanitizeHtml } from '~/utils/sanitizeHtml'
import type { PageDetail } from '~/types/storefront'

const props = defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{ (e: 'update:modelValue', open: boolean): void }>()

const api = useRuntimeConfig().public.apiBase
const content = ref('')

watch(() => props.modelValue, async (open) => {
  if (!open || content.value) return
  try {
    const page = await $fetch<PageDetail>(`${api}/pages/huong-dan-chon-size`)
    content.value = sanitizeHtml(page.content)
  } catch {
    content.value = '<p>Chưa có nội dung hướng dẫn chọn size.</p>'
  }
})

const close = () => emit('update:modelValue', false)
</script>

<style scoped lang="scss">
.sgm-backdrop {
  position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 80;
  display: flex; align-items: center; justify-content: center; padding: 16px;
}
.sgm-dialog {
  background: #fff; border-radius: $sf-radius; max-width: 560px; width: 100%;
  max-height: 80vh; overflow-y: auto; padding: 20px;
}
.sgm-head {
  display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;
  button { border: 0; background: none; font-size: 18px; cursor: pointer; }
}
.sgm-body { font-size: 14px; line-height: 1.7; :deep(table) { width: 100%; border-collapse: collapse; } :deep(td), :deep(th) { border: 1px solid $sf-color-border; padding: 6px 10px; text-align: center; } }
.sgm-loading { color: $sf-color-muted; font-size: 14px; }
</style>
