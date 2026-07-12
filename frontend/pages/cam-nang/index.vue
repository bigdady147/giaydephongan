<template>
  <div class="sf-container blog-index-page">
    <Breadcrumbs :items="[{ label: $t('storefront.home'), to: '/' }, { label: $t('storefront.blog') }]" />
    <h1>{{ $t('storefront.blog') }}</h1>

    <div class="pillar-tabs">
      <button class="pillar-tab" :class="{ active: !activePillar }" @click="applyPillar(undefined)">
        {{ $t('storefront.blogAllTopics') }}
      </button>
      <button
        v-for="pillar in pillars"
        :key="pillar"
        class="pillar-tab"
        :class="{ active: activePillar === pillar }"
        @click="applyPillar(pillar)"
      >{{ $t(`storefront.pillars.${pillar}`) }}</button>
    </div>

    <div v-if="loading" class="blog-grid">
      <div v-for="n in 6" :key="n" class="blog-skeleton" />
    </div>
    <div v-else-if="result?.data.length" class="blog-grid">
      <BlogPostCard v-for="post in result.data" :key="post.id" :post="post" />
    </div>
    <p v-else class="blog-empty">{{ $t('storefront.blogEmpty') }}</p>

    <AppPagination
      :page="result?.current_page ?? 1"
      :last-page="result?.last_page ?? 1"
      @update:page="applyPage"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { BlogPostCard as BlogPostCardType, BlogPillar, Paginated } from '~/types/storefront'

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const api = config.public.apiBase
const siteUrl = config.public.siteUrl
const { t } = useI18n()

const pillars: BlogPillar[] = ['cam-nang-chon-giay', 'bao-quan-giay-da', 'giay-theo-dip']

const activePillar = computed(() => (route.query.pillar as BlogPillar | undefined) ?? undefined)
const currentPage = computed(() => Number(route.query.page) || 1)

const buildQuery = (pillar: BlogPillar | undefined, page: number) => {
  const params = new URLSearchParams()
  if (pillar) params.set('pillar', pillar)
  if (page > 1) params.set('page', String(page))
  const serialized = params.toString()
  return serialized ? `?${serialized}` : ''
}

const fetchPosts = () =>
  $fetch<Paginated<BlogPostCardType>>(`${api}/blog${buildQuery(activePillar.value, currentPage.value)}`)

const { data: initial } = await useAsyncData(
  () => `blog-index-${activePillar.value ?? 'all'}-${currentPage.value}`,
  fetchPosts
)

const result = ref(initial.value)
const loading = ref(false)

watch(() => route.query, async () => {
  loading.value = true
  try {
    result.value = await fetchPosts()
  } finally {
    loading.value = false
  }
})

const applyPillar = (pillar: BlogPillar | undefined) => {
  router.replace({ query: pillar ? { pillar } : {} })
}

const applyPage = (page: number) => {
  const query: Record<string, string> = {}
  if (activePillar.value) query.pillar = activePillar.value
  if (page > 1) query.page = String(page)
  router.replace({ query })
  if (import.meta.client) window.scrollTo({ top: 0, behavior: 'smooth' })
}

useSeoMeta({
  title: `${t('storefront.blog')} | Giày dép Hồng An`,
  description: 'Cẩm nang chọn giày da, bảo quản giày và phối đồ cho phái mạnh.'
})

useHead({
  link: [{ rel: 'canonical', href: `${siteUrl}/cam-nang` }],
  script: [{
    type: 'application/ld+json',
    innerHTML: JSON.stringify({
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Trang chủ', item: siteUrl },
        { '@type': 'ListItem', position: 2, name: 'Cẩm nang', item: `${siteUrl}/cam-nang` }
      ]
    })
  }]
})
</script>

<style scoped lang="scss">
.blog-index-page { padding-top: 8px; h1 { font-size: 26px; margin-bottom: 16px; letter-spacing: -0.02em; } }
.pillar-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; }
.pillar-tab {
  border: 1px solid $sf-color-border; background: #fff; border-radius: 999px;
  padding: 8px 16px; font-size: 14px; cursor: pointer;
  &.active { background: $sf-color-accent; border-color: $sf-color-accent; color: #fff; }
  &:hover:not(.active) { border-color: $sf-color-accent; color: $sf-color-accent; }
}
.blog-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.blog-skeleton {
  aspect-ratio: 16 / 14; border-radius: $sf-radius;
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%; animation: shimmer 1.4s infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }
.blog-empty { text-align: center; color: $sf-color-muted; padding: 40px 0; }
@media (max-width: 768px) { .blog-grid { grid-template-columns: 1fr; } }
</style>
