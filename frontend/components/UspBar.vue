<template>
  <div v-if="usps.length" class="usp-bar">
    <div v-for="(usp, i) in usps" :key="i" class="usp-item">
      <span class="usp-icon">{{ ['✓', '🛡', '🚚'][i] ?? '✓' }}</span>
      <span>{{ usp }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ usps: string[] }>()
</script>

<style scoped lang="scss">
.usp-bar {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 20px 0;
}
.usp-item {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  background: $sf-color-bg-soft; border-radius: $sf-radius; padding: 14px 10px;
  font-size: 14px; font-weight: 500;
}
.usp-icon { color: $sf-color-accent; }
@media (max-width: 768px) { .usp-bar { grid-template-columns: 1fr; gap: 8px; } }
</style>
