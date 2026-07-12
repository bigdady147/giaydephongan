<template>
  <nav class="breadcrumbs" aria-label="Breadcrumb">
    <template v-for="(item, index) in items" :key="index">
      <NuxtLink v-if="item.to" :to="item.to" class="crumb">{{ item.label }}</NuxtLink>
      <span v-else class="crumb current">{{ item.label }}</span>
      <span v-if="index < items.length - 1" class="crumb-sep">/</span>
    </template>
  </nav>
</template>

<script setup lang="ts">
export interface BreadcrumbItem { label: string; to?: string }
defineProps<{ items: BreadcrumbItem[] }>()
</script>

<style scoped lang="scss">
.breadcrumbs { display: flex; align-items: center; gap: 8px; font-size: 13px; margin: 16px 0; flex-wrap: wrap; }
.crumb { color: $sf-color-muted; text-decoration: none; &:hover { color: $sf-color-accent; } }
.crumb.current { color: $sf-color-text; font-weight: 500; }
.crumb-sep { color: $sf-color-border; }
</style>
