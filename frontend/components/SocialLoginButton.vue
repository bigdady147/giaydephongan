<template>
  <button class="social-button" @click="$emit('click')">
    <div class="icon-container">
      <template v-if="provider === 'google'">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 12-4.53z" fill="#EA4335"/>
        </svg>
      </template>
      <template v-if="provider === 'apple'">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
          <path d="M17.05 20.28c-.98.95-2.05 1.72-3.23 1.72-1.15 0-1.53-.71-2.92-.71-1.4 0-1.83.69-2.9.71-1.15.02-2.38-.85-3.41-2.34-2.11-3.05-1.61-5.83.47-8.03 1.03-1.08 2.31-1.74 3.51-1.74 1.15 0 2.22.45 2.92.45.69 0 2.02-.56 3.38-.42.57.02 2.17.23 3.19 1.73-.08.05-1.9 1.11-1.88 3.28.02 2.62 2.26 3.54 2.28 3.55-.02.06-.35 1.22-1.2 2.45M12.03 7.25c-.02-2.23 1.84-4.14 4.08-4.18.05.54-.15 1.1-.47 1.63-.64.93-1.54 1.63-2.6 1.63-.12 0-.21-.03-.26-.03-.13-2.73 1.5-1.8 1.5-1.8"/>
        </svg>
      </template>
    </div>
    <span class="provider-name">{{ label }}</span>
  </button>
</template>

<script setup lang="ts">
defineProps({
  provider: {
    type: String as () => 'google' | 'apple',
    required: true
  },
  label: {
    type: String,
    required: true
  }
})

defineEmits(['click'])
</script>

<style scoped lang="scss">
.social-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 100%;
  padding: 10px 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background-color: white;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 500;
  color: #374151;
  font-size: 14px;

  &:hover {
    background-color: #f9fafb;
    border-color: #d1d5db;
  }

  .icon-container {
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
</style>
