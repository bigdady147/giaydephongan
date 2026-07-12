<template>
  <div class="sf-container home-page">
    <BannerCarousel :banners="home?.banners.homepage_hero ?? []" />

    <UspBar :usps="usps" />

    <section v-if="home?.categories.length">
      <SectionHeading :title="$t('storefront.categories')" />
      <div class="category-grid">
        <NuxtLink v-for="category in home.categories" :key="category.id" :to="`/danh-muc/${category.slug}`" class="category-tile">
          <img v-if="category.thumbnail" :src="category.thumbnail" :alt="category.name" loading="lazy">
          <div v-else class="category-placeholder">👞</div>
          <span>{{ category.name }}</span>
        </NuxtLink>
      </div>
    </section>

    <section v-if="home?.featured.length">
      <SectionHeading :title="$t('storefront.featuredProducts')" />
      <ProductGrid :products="home.featured" />
    </section>

    <div v-if="home?.banners.homepage_promo.length" class="promo-strip">
      <NuxtLink
        v-for="banner in home.banners.homepage_promo"
        :key="banner.id"
        :to="banner.link ?? '/'"
        class="promo-banner"
      >
        <img :src="banner.image" alt="" loading="lazy">
      </NuxtLink>
    </div>

    <section v-if="home?.latest.length">
      <SectionHeading :title="$t('storefront.newArrivals')" />
      <ProductGrid :products="home.latest" />
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { BannersResponse, CardProduct, CategoryInfo, Paginated } from '~/types/storefront'

const config = useRuntimeConfig()
const api = config.public.apiBase
const siteUrl = config.public.siteUrl

const { data: shared } = await useStorefrontData()

const { data: home } = await useAsyncData('home', async () => {
  const [banners, categories, featured, latest] = await Promise.all([
    $fetch<BannersResponse>(`${api}/banners`),
    $fetch<CategoryInfo[]>(`${api}/categories`),
    $fetch<Paginated<CardProduct>>(`${api}/products?sort=featured`),
    $fetch<Paginated<CardProduct>>(`${api}/products?sort=newest`)
  ])
  return {
    banners,
    categories: categories.filter(c => c.thumbnail).slice(0, 8),
    featured: featured.data.slice(0, 8),
    latest: latest.data.slice(0, 8)
  }
})

const usps = computed(() =>
  ['usp_1', 'usp_2', 'usp_3']
    .map(key => shared.value?.settings?.[key])
    .filter((v): v is string => !!v)
)

useSeoMeta({
  title: 'Giày dép Hồng An — Giày da nam chính hãng',
  description: 'Giày da nam Oxford, Derby, Loafer, boot da bò thật. Bảo hành 12 tháng, freeship đơn từ 500K.',
  ogTitle: 'Giày dép Hồng An',
  ogDescription: 'Giày da nam chính hãng — da bò thật 100%.'
})

useHead({
  link: [{ rel: 'canonical', href: `${siteUrl}/` }],
  script: [{
    type: 'application/ld+json',
    innerHTML: JSON.stringify([
      {
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: 'Giày dép Hồng An',
        url: siteUrl,
        telephone: shared.value?.settings?.hotline ?? undefined
      },
      {
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        url: siteUrl,
        potentialAction: {
          '@type': 'SearchAction',
          target: `${siteUrl}/tim-kiem?q={search_term_string}`,
          'query-input': 'required name=search_term_string'
        }
      }
    ])
  }]
})
</script>

<style scoped lang="scss">
.home-page { padding-top: 20px; }
.category-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.category-tile {
  display: flex; flex-direction: column; text-decoration: none; color: $sf-color-text;
  border: 1px solid $sf-color-border; border-radius: $sf-radius; overflow: hidden;
  transition: box-shadow 0.2s;
  img { aspect-ratio: 16 / 10; object-fit: cover; width: 100%; }
  span { padding: 10px 12px; font-size: 14px; font-weight: 600; text-align: center; }
  &:hover { box-shadow: $sf-shadow-card-hover; }
}
.category-placeholder { aspect-ratio: 16 / 10; display: flex; align-items: center; justify-content: center; font-size: 40px; background: $sf-color-bg-soft; }
.promo-strip { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin: 32px 0; }
.promo-banner img { width: 100%; border-radius: $sf-radius; display: block; }
@media (max-width: 768px) {
  .category-grid { grid-template-columns: repeat(2, 1fr); }
  .promo-strip { grid-template-columns: 1fr; }
}
</style>
