<template>
  <div class="sf-container search-page">
    <Breadcrumbs :items="[{ label: $t('storefront.home'), to: '/' }, { label: $t('storefront.search') }]" />
    <h1 v-if="q">{{ $t('storefront.searchResultsFor', { q }) }}</h1>

    <div class="search-toolbar">
      <span v-if="result">{{ $t('storefront.productsCount', { count: result.total }) }}</span>
      <SortSelect :model-value="filters.sort" @update:model-value="applySort" />
    </div>

    <ProductGrid :products="result?.data ?? []" :loading="loading" />
    <p v-if="result && result.total === 0" class="search-empty">{{ $t('storefront.searchEmpty') }}</p>

    <AppPagination
      :page="result?.current_page ?? 1"
      :last-page="result?.last_page ?? 1"
      @update:page="applyPage"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { buildProductQuery, parseFilters, type ProductFilters } from '~/utils/catalogQuery'
import type { CardProduct, Paginated } from '~/types/storefront'

const route = useRoute()
const router = useRouter()
const api = useRuntimeConfig().public.apiBase

const result = ref<Paginated<CardProduct> | null>(null)
const loading = ref(false)
const filters = computed(() => parseFilters(route.query))
const q = computed(() => filters.value.q ?? '')

const fetchResults = async () => {
  loading.value = true
  try {
    result.value = await $fetch<Paginated<CardProduct>>(`${api}/products${buildProductQuery(filters.value)}`)
  } finally {
    loading.value = false
  }
}

const pushQuery = (next: ProductFilters) => {
  const query: Record<string, string> = {}
  for (const [key, value] of Object.entries(next)) {
    if (value !== undefined && value !== null && value !== '') query[key] = String(value)
  }
  router.replace({ query })
}

const applySort = (sort: string) => pushQuery({ ...filters.value, sort, page: undefined })
const applyPage = (page: number) => pushQuery({ ...filters.value, page: page > 1 ? page : undefined })

onMounted(fetchResults)
watch(() => route.query, fetchResults)

useSeoMeta({ title: 'Tìm kiếm | Giày dép Hồng An', robots: 'noindex, nofollow' })
</script>

<style scoped lang="scss">
.search-page { padding-top: 8px; h1 { font-size: 22px; margin-bottom: 16px; } }
.search-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; font-size: 14px; color: $sf-color-muted; }
.search-empty { text-align: center; color: $sf-color-muted; padding: 20px 0; }
</style>
