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

        <div class="pdp-cart-actions">
          <button class="btn-add-to-cart" :disabled="!selectedVariantId" @click="handleAddToCart(false)">
            🛒 {{ $t('storefront.cart') }}
          </button>
          <button class="btn-buy-now" :disabled="!selectedVariantId" @click="handleBuyNow">
            {{ $t('storefront.orderNow') }}
          </button>
        </div>

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

    <section class="pdp-reviews">
      <SectionHeading :title="`${$t('storefront.reviews')} (${product.reviews_count})`" />

      <div class="reviews-summary">
        <span class="reviews-score">★ {{ product.avg_rating.toFixed(1) }}</span>
        <span class="reviews-sub">{{ product.reviews_count }} đánh giá từ khách hàng</span>
      </div>

      <ClientOnly>
        <form v-if="isAuthenticated && !reviewSubmitted" class="review-form" @submit.prevent="submitReview">
          <label class="review-form-label">{{ $t('storefront.reviewRatingLabel') }}</label>
          <select v-model.number="reviewForm.rating" class="review-form-select">
            <option v-for="n in [5, 4, 3, 2, 1]" :key="n" :value="n">{{ n }} sao</option>
          </select>
          <textarea
            v-model="reviewForm.comment"
            class="review-form-textarea"
            rows="3"
            :placeholder="$t('storefront.reviewPlaceholder')"
          />
          <p v-if="reviewError" class="review-form-error">{{ reviewError }}</p>
          <button type="submit" class="review-form-submit" :disabled="reviewSubmitting">
            {{ reviewSubmitting ? $t('storefront.reviewSubmitting') : $t('storefront.reviewSubmit') }}
          </button>
        </form>
        <p v-else-if="reviewSubmitted" class="review-form-thanks">{{ $t('storefront.reviewThanks') }}</p>
        <p v-else class="review-form-login">
          <NuxtLink to="/login">{{ $t('storefront.login') }}</NuxtLink> {{ $t('storefront.reviewLoginPrompt') }}
        </p>
      </ClientOnly>

      <div v-if="reviews.length" class="review-list">
        <div v-for="review in reviews" :key="review.id" class="review-item">
          <div class="review-item-head">
            <strong>{{ review.user.name }}</strong>
            <span class="review-item-rating">{{ '★'.repeat(review.rating) }}{{ '☆'.repeat(5 - review.rating) }}</span>
          </div>
          <p v-if="review.comment" class="review-item-comment">{{ review.comment }}</p>
          <span class="review-item-date">{{ formatReviewDate(review.created_at) }}</span>
        </div>
      </div>
      <p v-else class="review-list-empty">{{ $t('storefront.reviewsEmpty') }}</p>
    </section>

    <ClientOnly>
      <section v-if="recentOthers.length">
        <SectionHeading :title="$t('storefront.recentlyViewed')" />
        <ProductGrid :products="recentOthers" />
      </section>
    </ClientOnly>

    <SizeGuideModal v-model="sizeGuideOpen" />
    <div v-if="toastMsg" class="pdp-toast">{{ toastMsg }}</div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { isNew } from '~/utils/format'
import { sanitizeHtml } from '~/utils/sanitizeHtml'
import type { AvailabilityInfo, ProductDetail, ReviewInfo } from '~/types/storefront'

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
const toastMsg = ref('')

const router = useRouter()
const { addItem } = useCart()
const { isAuthenticated } = useAuth()
const apiClient = useApiClient()

const reviews = ref<ReviewInfo[]>([])
const reviewForm = reactive({ rating: 5, comment: '' })
const reviewSubmitting = ref(false)
const reviewSubmitted = ref(false)
const reviewError = ref('')

const loadReviews = async () => {
  try {
    const res = await $fetch<{ data: ReviewInfo[] }>(`${api}/products/${slug}/reviews`)
    reviews.value = res.data
  } catch {
    reviews.value = []
  }
}

const submitReview = async () => {
  reviewSubmitting.value = true
  reviewError.value = ''
  try {
    await apiClient.post(`/products/${slug}/reviews`, {
      rating: reviewForm.rating,
      comment: reviewForm.comment || null
    })
    reviewSubmitted.value = true
    await loadReviews()
  } catch (err: any) {
    reviewError.value = err.message ?? 'Gửi đánh giá thất bại.'
  } finally {
    reviewSubmitting.value = false
  }
}

const formatReviewDate = (val: string) => new Date(val).toLocaleDateString('vi-VN')

const handleAddToCart = (redirect = false) => {
  if (!selectedVariantId.value || !product.value) return
  const variant = live.variants.find(v => v.id === selectedVariantId.value)
  if (!variant) return

  addItem({
    variantId: variant.id,
    productId: product.value.id,
    name: product.value.name,
    slug: product.value.slug,
    thumbnail: product.value.thumbnail,
    size: variant.size,
    color: variant.color,
    price: variant.price_override !== null ? variant.price_override : (live.sale_price ?? live.base_price),
  }, 1)

  if (redirect) {
    router.push('/thanh-toan')
  } else {
    toastMsg.value = 'Đã thêm sản phẩm vào giỏ hàng!'
    setTimeout(() => {
      toastMsg.value = ''
    }, 3000)
  }
}

const handleBuyNow = () => {
  handleAddToCart(true)
}

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

  loadReviews()

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
.pdp-cart-actions {
  display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;
  button {
    border: 0; border-radius: 8px; padding: 14px; font-size: 15px; font-weight: 700;
    cursor: pointer; transition: background 0.2s;
    &:disabled { opacity: 0.5; cursor: not-allowed; }
  }
  .btn-add-to-cart {
    background: #fff; color: $sf-color-accent; border: 1px solid $sf-color-accent;
    &:hover:not(:disabled) { background: $sf-color-bg-soft; }
  }
  .btn-buy-now {
    background: $sf-color-accent; color: #fff;
    &:hover:not(:disabled) { background: $sf-color-accent-dark; }
  }
}
.pdp-toast {
  position: fixed; bottom: 24px; right: 24px; background: #1e293b; color: #fff;
  padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 100;
}
.pdp-reviews { margin-top: 40px; }
.reviews-summary {
  display: flex; align-items: baseline; gap: 10px; margin-bottom: 16px;
  .reviews-score { font-size: 22px; font-weight: 700; color: $sf-color-accent; }
  .reviews-sub { font-size: 13px; color: $sf-color-muted; }
}
.review-form {
  display: flex; flex-direction: column; gap: 10px; max-width: 480px;
  border: 1px solid $sf-color-border; border-radius: $sf-radius; padding: 16px; margin-bottom: 24px;
  .review-form-label { font-weight: 600; font-size: 14px; }
  .review-form-select, .review-form-textarea {
    border: 1px solid $sf-color-border; border-radius: 6px; padding: 8px 10px; font-size: 14px; font-family: inherit;
  }
  .review-form-error { color: #dc2626; font-size: 13px; margin: 0; }
  .review-form-submit {
    align-self: flex-start; border: 0; border-radius: 8px; padding: 10px 20px; font-weight: 700;
    background: $sf-color-accent; color: #fff; cursor: pointer;
    &:disabled { opacity: 0.6; cursor: not-allowed; }
    &:hover:not(:disabled) { background: $sf-color-accent-dark; }
  }
}
.review-form-thanks { color: #16a34a; font-weight: 600; margin-bottom: 24px; }
.review-form-login { font-size: 14px; margin-bottom: 24px; a { color: $sf-color-accent; font-weight: 600; } }
.review-list { display: flex; flex-direction: column; gap: 16px; }
.review-item {
  border-bottom: 1px solid $sf-color-border; padding-bottom: 16px;
  .review-item-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
  .review-item-rating { color: $sf-color-accent; letter-spacing: 1px; }
  .review-item-comment { font-size: 14px; color: #333; margin-bottom: 6px; }
  .review-item-date { font-size: 12px; color: $sf-color-muted; }
}
.review-list-empty { color: $sf-color-muted; font-size: 14px; }
@media (max-width: 768px) { .pdp-layout { grid-template-columns: 1fr; gap: 20px; } }
</style>
