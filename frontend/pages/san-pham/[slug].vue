<template>
  <div v-if="product" class="sf-container pdp-page">
    <Breadcrumbs :items="crumbs" />

    <div class="pdp-layout">
      <ImageGallery :images="product.images" :alt="product.name" />

      <div class="pdp-info">
        <h1>{{ product.name }}</h1>
        <p class="pdp-meta">
          <span v-if="product.brand">{{ product.brand.name }} · </span>
          <span>{{ $t(`storefront.materials.${product.material}`) }}</span>
          <span v-if="isNew(product.created_at)" class="pdp-badge-new">{{ $t('storefront.badgeNew') }}</span>
        </p>

        <PriceTag :base-price="live.base_price" :sale-price="live.sale_price" size="lg" />

        <VariantPicker v-model="selectedVariantId" :variants="live.variants" class="pdp-picker" />

        <button class="pdp-size-guide" @click="sizeGuideOpen = true">📏 {{ $t('storefront.sizeGuide') }}</button>

        <div class="pdp-cta">
          <p class="cta-title">{{ $t('storefront.quickOrder') }}</p>
          <a v-if="settings?.hotline" :href="`tel:${settings.hotline.replace(/\s/g, '')}`" class="cta-call">
            ☎ {{ $t('storefront.callToOrder', { hotline: settings.hotline }) }}
          </a>
          <a v-if="settings?.zalo_url" :href="settings.zalo_url" target="_blank" rel="noopener" class="cta-zalo">
            {{ $t('storefront.chatZalo') }}
          </a>
        </div>
      </div>
    </div>

    <section v-if="product.description" class="pdp-description">
      <SectionHeading :title="$t('storefront.productDescription')" />
      <div class="pdp-desc-body" v-html="sanitizeHtml(product.description)" />
    </section>

    <section v-if="product.related.length">
      <SectionHeading :title="$t('storefront.relatedProducts')" />
      <ProductGrid :products="product.related" />
    </section>

    <ClientOnly>
      <section v-if="recentOthers.length">
        <SectionHeading :title="$t('storefront.recentlyViewed')" />
        <ProductGrid :products="recentOthers" />
      </section>
    </ClientOnly>

    <SizeGuideModal v-model="sizeGuideOpen" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { isNew } from '~/utils/format'
import { sanitizeHtml } from '~/utils/sanitizeHtml'
import type { AvailabilityInfo, ProductDetail } from '~/types/storefront'

const route = useRoute()
const config = useRuntimeConfig()
const api = config.public.apiBase
const siteUrl = config.public.siteUrl
const { t } = useI18n()

const slug = route.params.slug as string

const { data: shared } = await useStorefrontData()
const settings = computed(() => shared.value?.settings)

const { data: product } = await useAsyncData(`pdp-${slug}`, () => $fetch<ProductDetail>(`${api}/products/${slug}`))

if (!product.value) {
  throw createError({ statusCode: 404, statusMessage: 'Không tìm thấy sản phẩm' })
}

// Live price/stock layer: starts from build-time data, refreshed client-side on load
const live = reactive({
  base_price: product.value.base_price,
  sale_price: product.value.sale_price,
  variants: product.value.variants
})

const selectedVariantId = ref<number | null>(null)
const sizeGuideOpen = ref(false)

const { items: recentItems, push: pushRecent } = useRecentlyViewed()
const recentOthers = computed(() => recentItems.value.filter(item => item.id !== product.value!.id))

onMounted(async () => {
  pushRecent({
    id: product.value!.id,
    name: product.value!.name,
    slug: product.value!.slug,
    thumbnail: product.value!.thumbnail,
    base_price: product.value!.base_price,
    sale_price: product.value!.sale_price,
    created_at: product.value!.created_at
  })

  try {
    const fresh = await $fetch<AvailabilityInfo>(`${api}/products/${slug}/availability`)
    live.base_price = fresh.base_price
    live.sale_price = fresh.sale_price
    live.variants = fresh.variants
  } catch {
    // keep build-time data if the refresh fails
  }
})

const crumbs = computed(() => [
  { label: t('storefront.home'), to: '/' },
  ...(product.value?.category ? [{ label: product.value.category.name, to: `/danh-muc/${product.value.category.slug}` }] : []),
  { label: product.value?.name ?? '' }
])

const totalStock = computed(() => live.variants.reduce((sum, v) => sum + v.stock_quantity, 0))

useSeoMeta({
  title: product.value.seo_title ?? `${product.value.name} | Giày dép Hồng An`,
  description: product.value.seo_description ?? undefined,
  ogTitle: product.value.name,
  ogImage: product.value.images[0]?.url ?? product.value.thumbnail ?? undefined
})

useHead({
  link: [{ rel: 'canonical', href: `${siteUrl}/san-pham/${slug}` }],
  script: [{
    type: 'application/ld+json',
    innerHTML: JSON.stringify([
      {
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: product.value.name,
        image: product.value.images.map(image => image.url),
        description: product.value.seo_description ?? undefined,
        brand: product.value.brand ? { '@type': 'Brand', name: product.value.brand.name } : undefined,
        offers: {
          '@type': 'Offer',
          url: `${siteUrl}/san-pham/${slug}`,
          priceCurrency: 'VND',
          price: product.value.sale_price ?? product.value.base_price,
          availability: totalStock.value > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
        }
      },
      {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: crumbs.value.map((crumb, i) => ({
          '@type': 'ListItem',
          position: i + 1,
          name: crumb.label,
          item: crumb.to ? `${siteUrl}${crumb.to}` : `${siteUrl}/san-pham/${slug}`
        }))
      }
    ])
  }]
})
</script>

<style scoped lang="scss">
.pdp-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-top: 8px; }
.pdp-info {
  h1 { font-size: 26px; letter-spacing: -0.02em; margin-bottom: 6px; }
}
.pdp-meta { font-size: 14px; color: $sf-color-muted; margin-bottom: 14px; }
.pdp-badge-new {
  background: $sf-color-accent; color: #fff; font-size: 11px; font-weight: 700;
  padding: 2px 8px; border-radius: 6px; margin-left: 8px;
}
.pdp-picker { margin-top: 18px; }
.pdp-size-guide {
  border: 0; background: none; color: $sf-color-accent; font-size: 14px;
  cursor: pointer; padding: 0; margin: 6px 0 18px; text-decoration: underline;
}
.pdp-cta {
  border: 1px solid $sf-color-border; border-radius: $sf-radius; padding: 16px; background: $sf-color-bg-soft;
  .cta-title { font-weight: 700; font-size: 14px; margin-bottom: 10px; }
  a {
    display: block; text-align: center; border-radius: 8px; padding: 12px;
    font-weight: 600; text-decoration: none; margin-bottom: 8px; font-size: 15px;
  }
  .cta-call { background: $sf-color-accent; color: #fff; &:hover { background: $sf-color-accent-dark; } }
  .cta-zalo { background: #0068ff; color: #fff; }
}
.pdp-description .pdp-desc-body { font-size: 15px; line-height: 1.8; color: #333; max-width: 820px; }
@media (max-width: 768px) { .pdp-layout { grid-template-columns: 1fr; gap: 20px; } }
</style>
