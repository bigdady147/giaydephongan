<template>
  <div class="floating-contact">
    <a v-if="settings.zalo_url" :href="settings.zalo_url" target="_blank" rel="noopener" class="fc-btn fc-zalo" aria-label="Chat Zalo">Zalo</a>
    <a v-if="settings.messenger_url" :href="settings.messenger_url" target="_blank" rel="noopener" class="fc-btn fc-messenger" aria-label="Messenger">Chat</a>
    <button v-show="showTop" class="fc-btn fc-top" aria-label="Lên đầu trang" @click="scrollTop">↑</button>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import type { SettingsMap } from '~/types/storefront'

defineProps<{ settings: SettingsMap }>()

const showTop = ref(false)
const onScroll = () => { showTop.value = window.scrollY > 400 }
const scrollTop = () => window.scrollTo({ top: 0, behavior: 'smooth' })

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', onScroll))
</script>

<style scoped lang="scss">
.floating-contact { position: fixed; right: 16px; bottom: 20px; display: flex; flex-direction: column; gap: 10px; z-index: 60; }
.fc-btn {
  width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: #fff; text-decoration: none; border: 0; cursor: pointer;
  box-shadow: $sf-shadow-card-hover;
}
.fc-zalo { background: #0068ff; }
.fc-messenger { background: #7b3ff2; }
.fc-top { background: $sf-color-text; font-size: 18px; }
</style>
