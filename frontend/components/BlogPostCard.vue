<template>
  <NuxtLink :to="`/cam-nang/${post.slug}`" class="blog-card">
    <div class="blog-card-media">
      <img v-if="post.thumbnail" :src="post.thumbnail" :alt="post.title" loading="lazy">
      <div v-else class="blog-card-placeholder">📖</div>
      <span v-if="post.pillar" class="blog-card-pillar">{{ $t(`storefront.pillars.${post.pillar}`) }}</span>
    </div>
    <div class="blog-card-body">
      <h3 class="blog-card-title">{{ post.title }}</h3>
      <p v-if="post.excerpt" class="blog-card-excerpt">{{ post.excerpt }}</p>
      <span v-if="post.published_at" class="blog-card-date">{{ formatDate(post.published_at) }}</span>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import type { BlogPostCard } from '~/types/storefront'

defineProps<{ post: BlogPostCard }>()

const formatDate = (value: string) => new Date(value).toLocaleDateString('vi-VN')
</script>

<style scoped lang="scss">
.blog-card {
  display: block; text-decoration: none; color: $sf-color-text;
  border-radius: $sf-radius; overflow: hidden; background: #fff;
  border: 1px solid $sf-color-border; box-shadow: $sf-shadow-card;
  transition: box-shadow 0.2s, transform 0.2s;
  &:hover { box-shadow: $sf-shadow-card-hover; transform: translateY(-2px); }
}
.blog-card-media {
  position: relative; aspect-ratio: 16 / 10; background: $sf-color-bg-soft;
  img { width: 100%; height: 100%; object-fit: cover; display: block; }
}
.blog-card-placeholder { display: flex; align-items: center; justify-content: center; height: 100%; font-size: 40px; }
.blog-card-pillar {
  position: absolute; top: 10px; left: 10px; font-size: 11px; font-weight: 700;
  padding: 3px 8px; border-radius: 6px; background: $sf-color-accent; color: #fff;
}
.blog-card-body { padding: 14px 16px; }
.blog-card-title {
  font-size: 15px; font-weight: 600; margin-bottom: 8px; line-height: 1.4;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.blog-card-excerpt {
  font-size: 13px; color: $sf-color-muted; line-height: 1.6; margin-bottom: 10px;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.blog-card-date { font-size: 12px; color: $sf-color-muted; }
</style>
