<template>
  <div class="sf-container blog-detail-page">
    <Breadcrumbs :items="crumbs" />

    <article class="blog-article">
      <span v-if="post?.pillar" class="blog-pillar-tag">{{ $t(`storefront.pillars.${post.pillar}`) }}</span>
      <h1>{{ post?.title }}</h1>
      <p v-if="post?.published_at" class="blog-date">{{ formatDate(post.published_at) }}</p>

      <img v-if="post?.thumbnail" :src="post.thumbnail" :alt="post.title" class="blog-cover">

      <div class="blog-body" v-html="safeContent" />
    </article>

    <section v-if="post?.related.length">
      <SectionHeading :title="$t('storefront.blogRelated')" />
      <div class="blog-related-grid">
        <BlogPostCard v-for="related in post.related" :key="related.id" :post="related" />
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { sanitizeHtml } from '~/utils/sanitizeHtml'
import type { BlogPostDetail } from '~/types/storefront'

const route = useRoute()
const config = useRuntimeConfig()
const api = config.public.apiBase
const siteUrl = config.public.siteUrl
const { t } = useI18n()

const slug = route.params.slug as string

const { data: post, error } = await useAsyncData(`blog-${slug}`, () => $fetch<BlogPostDetail>(`${api}/blog/${slug}`))

if (error.value || !post.value) {
  throw createError({ statusCode: 404, statusMessage: 'Không tìm thấy bài viết' })
}

const safeContent = computed(() => sanitizeHtml(post.value?.content ?? ''))
const formatDate = (value: string) => new Date(value).toLocaleDateString('vi-VN')

const crumbs = computed(() => [
  { label: t('storefront.home'), to: '/' },
  { label: t('storefront.blog'), to: '/cam-nang' },
  { label: post.value?.title ?? '' }
])

useSeoMeta({
  title: post.value.seo_title ?? `${post.value.title} | Giày dép Hồng An`,
  description: post.value.seo_description ?? post.value.excerpt ?? undefined,
  ogTitle: post.value.title,
  ogImage: post.value.thumbnail ?? undefined
})

useHead({
  link: [{ rel: 'canonical', href: `${siteUrl}/cam-nang/${slug}` }],
  script: [{
    type: 'application/ld+json',
    innerHTML: JSON.stringify([
      {
        '@context': 'https://schema.org',
        '@type': 'Article',
        headline: post.value.title,
        image: post.value.thumbnail ? [post.value.thumbnail] : undefined,
        datePublished: post.value.published_at ?? undefined,
        description: post.value.seo_description ?? post.value.excerpt ?? undefined
      },
      {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: crumbs.value.map((crumb, i) => ({
          '@type': 'ListItem',
          position: i + 1,
          name: crumb.label,
          item: crumb.to ? `${siteUrl}${crumb.to}` : `${siteUrl}/cam-nang/${slug}`
        }))
      }
    ])
  }]
})
</script>

<style scoped lang="scss">
.blog-detail-page { padding-top: 8px; max-width: 820px; margin-left: auto; margin-right: auto; }
.blog-article { margin-bottom: 40px; }
.blog-pillar-tag {
  display: inline-block; background: $sf-color-accent; color: #fff; font-size: 12px;
  font-weight: 700; padding: 4px 10px; border-radius: 6px; margin-bottom: 10px;
}
.blog-article h1 { font-size: 30px; letter-spacing: -0.02em; margin-bottom: 8px; }
.blog-date { font-size: 13px; color: $sf-color-muted; margin-bottom: 20px; }
.blog-cover { width: 100%; border-radius: $sf-radius; margin-bottom: 24px; display: block; }
.blog-body {
  font-size: 16px; line-height: 1.85; color: #333;
  :deep(p) { margin-bottom: 14px; }
  :deep(h2), :deep(h3) { margin: 24px 0 12px; letter-spacing: -0.01em; }
  :deep(img) { max-width: 100%; border-radius: 8px; }
  :deep(table) { width: 100%; border-collapse: collapse; margin: 14px 0; }
  :deep(td), :deep(th) { border: 1px solid $sf-color-border; padding: 8px 12px; }
}
.blog-related-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
@media (max-width: 768px) { .blog-related-grid { grid-template-columns: 1fr; } }
</style>
