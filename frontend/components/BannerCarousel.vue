<template>
  <div v-if="banners.length" class="banner-carousel" @mouseenter="pause" @mouseleave="resume" @touchstart.passive="onTouchStart" @touchend.passive="onTouchEnd">
    <div class="carousel-track" :style="{ transform: `translateX(-${index * 100}%)` }">
      <div v-for="banner in banners" :key="banner.id" class="carousel-slide">
        <NuxtLink v-if="banner.link" :to="banner.link">
          <img :src="banner.image" alt="" loading="eager">
        </NuxtLink>
        <img v-else :src="banner.image" alt="">
      </div>
    </div>
    <div v-if="banners.length > 1" class="carousel-dots">
      <button
        v-for="(banner, i) in banners"
        :key="banner.id"
        :class="{ active: i === index }"
        :aria-label="`Banner ${i + 1}`"
        @click="go(i)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import type { BannerInfo } from '~/types/storefront'

const props = defineProps<{ banners: BannerInfo[] }>()

const index = ref(0)
let timer: ReturnType<typeof setInterval> | null = null
let touchStartX = 0

const go = (i: number) => { index.value = (i + props.banners.length) % props.banners.length }
const next = () => go(index.value + 1)
const pause = () => { if (timer) { clearInterval(timer); timer = null } }
const resume = () => { if (!timer && props.banners.length > 1) timer = setInterval(next, 5000) }
const onTouchStart = (e: TouchEvent) => { touchStartX = e.changedTouches[0].clientX }
const onTouchEnd = (e: TouchEvent) => {
  const delta = e.changedTouches[0].clientX - touchStartX
  if (Math.abs(delta) > 50) go(index.value + (delta < 0 ? 1 : -1))
}

onMounted(resume)
onUnmounted(pause)
</script>

<style scoped lang="scss">
.banner-carousel { position: relative; overflow: hidden; border-radius: $sf-radius; }
.carousel-track { display: flex; transition: transform 0.45s ease; }
.carousel-slide {
  flex: 0 0 100%;
  img { width: 100%; aspect-ratio: 12 / 5; object-fit: cover; display: block; }
}
.carousel-dots {
  position: absolute; bottom: 12px; left: 0; right: 0; display: flex; justify-content: center; gap: 8px;
  button {
    width: 10px; height: 10px; border-radius: 50%; border: 0; cursor: pointer;
    background: rgba(255, 255, 255, 0.55);
    &.active { background: #fff; }
  }
}
</style>
