<template>
  <div class="sf-container plp-page">
    <Breadcrumbs :items="crumbs" />

    <header class="plp-head">
      <h1>{{ category?.name }}</h1>
      <p v-if="category?.description" class="plp-desc">{{ category.description }}</p>
    </header>

    <div class="plp-layout">
      <aside class="plp-sidebar">
        <FilterSidebar :brands="brands ?? []" :model-value="filters" @apply="applyFilters" />
      </aside>

      <div class="plp-content">
        <div class="plp-toolbar">
          <span class="plp-count">{{ $t('storefront.productsCount', { count: result?.total ?? 0 }) }}</span>
          <SortSelect :model-value="filters.sort" @update:model-value="applySort" />
        </div>

        <ProductGrid :products="result?.data ?? []" :loading="loading" />

        <AppPagination
          :page="result?.current_page ?? 1"
          :last-page="result?.last_page ?? 1"
          @update:page="applyPage"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { buildProductQuery, parseFilters, type ProductFilters } from '~/utils/catalogQuery'
import type { BrandInfo, CardProduct, CategoryInfo, Paginated } from '~/types/storefront'

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const api = config.public.apiBase
const siteUrl = config.public.siteUrl
const { t } = useI18n()

const slug = route.params.slug as string

const [{ data: category }, { data: brands }] = await Promise.all([
  useAsyncData(`plp-category-${slug}`, () => $fetch<CategoryInfo>(`${api}/categories/${slug}`)),
  useAsyncData('brands', () => $fetch<BrandInfo[]>(`${api}/brands`))
])

if (!category.value) {
  throw createError({ statusCode: 404, statusMessage: 'Không tìm thấy danh mục' })
}

const fetchProducts = (query: Record<string, unknown>) =>
  $fetch<Paginated<CardProduct>>(`${api}/products${buildProductQuery({ ...parseFilters(query), category: slug })}`)

const { data: initial } = await useAsyncData(`plp-products-${slug}`, () => fetchProducts(route.query))

const result = ref(initial.value)
const loading = ref(false)
const filters = computed(() => parseFilters(route.query))

watch(() => route.query, async (query) => {
  loading.value = true
  try {
    result.value = await fetchProducts(query)
  } finally {
    loading.value = false
  }
})

const pushQuery = (next: ProductFilters) => {
  const query: Record<string, string> = {}
  for (const [key, value] of Object.entries(next)) {
    if (value !== undefined && value !== null && value !== '') query[key] = String(value)
  }
  router.replace({ query })
}

const applyFilters = (next: ProductFilters) => pushQuery({ ...next, sort: filters.value.sort })
const applySort = (sort: string) => pushQuery({ ...filters.value, sort, page: undefined })
const applyPage = (page: number) => {
  pushQuery({ ...filters.value, page: page > 1 ? page : undefined })
  if (import.meta.client) window.scrollTo({ top: 0, behavior: 'smooth' })
}

const crumbs = computed(() => [
  { label: t('storefront.home'), to: '/' },
  { label: category.value?.name ?? '' }
])

useSeoMeta({
  title: category.value.seo_title ?? `${category.value.name} | Giày dép Hồng An`,
  description: category.value.seo_description ?? category.value.description ?? undefined,
  ogTitle: category.value.name
})

useHead({
  link: [{ rel: 'canonical', href: `${siteUrl}/danh-muc/${slug}` }],
  script: [{
    type: 'application/ld+json',
    innerHTML: JSON.stringify([
      {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
          { '@type': 'ListItem', position: 1, name: 'Trang chủ', item: siteUrl },
          { '@type': 'ListItem', position: 2, name: category.value.name, item: `${siteUrl}/danh-muc/${slug}` }
        ]
      },
      {
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        itemListElement: (initial.value?.data ?? []).map((product, i) => ({
          '@type': 'ListItem',
          position: i + 1,
          url: `${siteUrl}/san-pham/${product.slug}`
        }))
      }
    ])
  }]
})
</script>

<style scoped lang="scss">
.plp-head {
  margin-bottom: 20px;
  h1 { font-size: 26px; letter-spacing: -0.02em; }
}
.plp-desc { color: $sf-color-muted; font-size: 14px; margin-top: 8px; max-width: 760px; line-height: 1.6; }
.plp-layout { display: grid; grid-template-columns: 240px 1fr; gap: 28px; }
.plp-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.plp-count { font-size: 14px; color: $sf-color-muted; }
@media (max-width: 768px) {
  .plp-layout { grid-template-columns: 1fr; gap: 0; }
}
</style>
