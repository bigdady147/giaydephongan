<template>
  <div class="section-heading">
    <h2>{{ title }}</h2>
    <NuxtLink v-if="viewAllTo" :to="viewAllTo" class="view-all">{{ $t('storefront.viewAll') }} →</NuxtLink>
  </div>
</template>

<script setup lang="ts">
defineProps<{ title: string; viewAllTo?: string }>()
</script>

<style scoped lang="scss">
.section-heading {
  display: flex; align-items: baseline; justify-content: space-between; margin: 40px 0 18px;
  h2 { font-size: 22px; font-weight: 700; letter-spacing: -0.02em; }
}
.view-all { font-size: 14px; color: $sf-color-accent; text-decoration: none; font-weight: 500; }
</style>
