<template>
  <div class="sf-container cms-page">
    <Breadcrumbs :items="[{ label: $t('storefront.home'), to: '/' }, { label: page?.title ?? '' }]" />
    <article>
      <h1>{{ page?.title }}</h1>
      <div class="cms-body" v-html="safeContent" />
    </article>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { sanitizeHtml } from '~/utils/sanitizeHtml'
import type { PageDetail } from '~/types/storefront'

const route = useRoute()
const config = useRuntimeConfig()
const api = config.public.apiBase

const slug = route.params.slug as string

const { data: page, error } = await useAsyncData(`cms-${slug}`, () => $fetch<PageDetail>(`${api}/pages/${slug}`))

if (error.value || !page.value) {
  throw createError({ statusCode: 404, statusMessage: 'Không tìm thấy trang' })
}

const safeContent = computed(() => sanitizeHtml(page.value?.content ?? ''))

useSeoMeta({
  title: page.value.seo_title ?? `${page.value.title} | Giày dép Hồng An`,
  description: page.value.seo_description ?? undefined
})

useHead({ link: [{ rel: 'canonical', href: `${config.public.siteUrl}/${slug}` }] })
</script>

<style scoped lang="scss">
.cms-page {
  max-width: 820px;
  h1 { font-size: 28px; margin-bottom: 18px; letter-spacing: -0.02em; }
}
.cms-body {
  font-size: 15px; line-height: 1.8; color: #333;
  :deep(p) { margin-bottom: 12px; }
  :deep(table) { width: 100%; border-collapse: collapse; margin: 14px 0; }
  :deep(td), :deep(th) { border: 1px solid $sf-color-border; padding: 8px 12px; }
}
</style>
