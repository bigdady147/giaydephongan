<template>
  <div class="sf-shell">
    <header class="sf-header">
      <div class="sf-container sf-header-inner">
        <NuxtLink to="/" class="sf-logo">Giày dép <strong>Hồng An</strong></NuxtLink>

        <nav class="sf-nav" :class="{ open: menuOpen }">
          <NuxtLink to="/" class="sf-nav-link" @click="menuOpen = false">{{ $t('storefront.home') }}</NuxtLink>
          <NuxtLink
            v-for="category in shared?.categories ?? []"
            :key="category.id"
            :to="`/danh-muc/${category.slug}`"
            class="sf-nav-link"
            @click="menuOpen = false"
          >{{ category.name }}</NuxtLink>
          <NuxtLink to="/cam-nang" class="sf-nav-link" @click="menuOpen = false">{{ $t('storefront.blog') }}</NuxtLink>
        </nav>

        <form class="sf-search" @submit.prevent="submitSearch">
          <input v-model="searchTerm" type="search" :placeholder="$t('storefront.searchPlaceholder')" :aria-label="$t('storefront.search')">
          <button type="submit" aria-label="Tìm">🔍</button>
        </form>

        <div class="sf-header-actions">
          <a v-if="shared?.settings?.hotline" :href="`tel:${shared.settings.hotline.replace(/\s/g, '')}`" class="sf-hotline">
            ☎ {{ shared.settings.hotline }}
          </a>
          <ClientOnly>
            <NuxtLink to="/gio-hang" class="sf-cart-link">
              🛒 {{ $t('storefront.cart') }} <span v-if="cartCount > 0" class="sf-cart-badge">{{ cartCount }}</span>
            </NuxtLink>
            <NuxtLink v-if="!user" to="/login" class="sf-account">{{ $t('storefront.login') }}</NuxtLink>
            <NuxtLink v-else to="/tai-khoan/don-hang" class="sf-account">👤 {{ user.name }}</NuxtLink>
            <template #fallback>
              <NuxtLink to="/gio-hang" class="sf-cart-link">
                🛒 {{ $t('storefront.cart') }}
              </NuxtLink>
              <NuxtLink to="/login" class="sf-account">{{ $t('storefront.login') }}</NuxtLink>
            </template>
          </ClientOnly>
          <button class="sf-burger" :aria-label="$t('storefront.categories')" @click="menuOpen = !menuOpen">☰</button>
        </div>
      </div>
    </header>

    <main class="sf-main">
      <slot />
    </main>

    <footer class="sf-footer">
      <div class="sf-container sf-footer-grid">
        <div>
          <h4>{{ shared?.settings?.business_name ?? 'Giày dép Hồng An' }}</h4>
          <p v-if="shared?.settings?.address">{{ shared.settings.address }}</p>
          <p v-if="shared?.settings?.hotline">{{ $t('storefront.hotline') }}: {{ shared.settings.hotline }}</p>
          <p v-if="shared?.settings?.email">{{ shared.settings.email }}</p>
          <p v-if="shared?.settings?.open_hours">{{ shared.settings.open_hours }}</p>
        </div>
        <div>
          <h4>{{ $t('storefront.policies') }}</h4>
          <NuxtLink v-for="page in shared?.pages ?? []" :key="page.slug" :to="`/${page.slug}`" class="sf-footer-link">
            {{ page.title }}
          </NuxtLink>
        </div>
        <div>
          <h4>{{ $t('storefront.categories') }}</h4>
          <NuxtLink v-for="category in shared?.categories ?? []" :key="category.id" :to="`/danh-muc/${category.slug}`" class="sf-footer-link">
            {{ category.name }}
          </NuxtLink>
          <NuxtLink to="/cam-nang" class="sf-footer-link">{{ $t('storefront.blog') }}</NuxtLink>
        </div>
        <div>
          <h4>{{ $t('storefront.followUs') }}</h4>
          <a v-if="shared?.settings?.facebook_url" :href="shared.settings.facebook_url" target="_blank" rel="noopener" class="sf-footer-link">Facebook</a>
          <a v-if="shared?.settings?.instagram_url" :href="shared.settings.instagram_url" target="_blank" rel="noopener" class="sf-footer-link">Instagram</a>
          <a v-if="shared?.settings?.zalo_url" :href="shared.settings.zalo_url" target="_blank" rel="noopener" class="sf-footer-link">Zalo</a>
        </div>
      </div>
      <div class="sf-container sf-footer-legal">
        <p v-if="shared?.settings?.business_registration">{{ shared.settings.business_registration }}</p>
        <p>{{ shared?.settings?.copyright ?? '© Giày dép Hồng An' }}</p>
      </div>
    </footer>

    <FloatingContact :settings="shared?.settings ?? {}" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const router = useRouter()
const { user } = useAuth()
const { data: shared } = await useStorefrontData()
const { count: cartCount } = useCart()

const menuOpen = ref(false)
const searchTerm = ref('')

const submitSearch = () => {
  const q = searchTerm.value.trim()
  if (!q) return
  menuOpen.value = false
  router.push({ path: '/tim-kiem', query: { q } })
}
</script>

<style scoped lang="scss">
.sf-shell { min-height: 100vh; display: flex; flex-direction: column; background: #fff; color: $sf-color-text; }
.sf-container { max-width: $sf-container; margin: 0 auto; padding: 0 16px; width: 100%; }

.sf-header { position: sticky; top: 0; z-index: 50; background: #fff; border-bottom: 1px solid $sf-color-border; }
.sf-header-inner { display: flex; align-items: center; gap: 20px; height: 64px; }
.sf-logo { font-size: 18px; text-decoration: none; color: $sf-color-text; white-space: nowrap; strong { color: $sf-color-accent; } }

.sf-nav { display: flex; gap: 4px; flex: 1; overflow-x: auto; }
.sf-nav-link {
  padding: 8px 10px; border-radius: 6px; text-decoration: none; color: $sf-color-text;
  font-size: 14px; font-weight: 500; white-space: nowrap;
  &:hover, &.router-link-active { background: $sf-color-bg-soft; color: $sf-color-accent; }
}

.sf-search {
  display: flex; border: 1px solid $sf-color-border; border-radius: 999px; overflow: hidden;
  input { border: 0; outline: none; padding: 8px 14px; font-size: 14px; width: 180px; }
  button { border: 0; background: none; padding: 0 12px; cursor: pointer; }
}

.sf-header-actions { display: flex; align-items: center; gap: 14px; }
.sf-hotline { color: $sf-color-accent; font-weight: 600; font-size: 14px; text-decoration: none; white-space: nowrap; }
.sf-account { font-size: 14px; color: $sf-color-text; text-decoration: none; white-space: nowrap; }
.sf-cart-link {
  font-size: 14px; color: $sf-color-text; text-decoration: none; white-space: nowrap;
  display: inline-flex; align-items: center; gap: 4px; position: relative;
  &:hover { color: $sf-color-accent; }
}
.sf-cart-badge {
  background: $sf-color-sale; color: #fff; font-size: 10px; font-weight: 700;
  border-radius: 99px; min-width: 18px; height: 18px; display: inline-flex;
  align-items: center; justify-content: center; padding: 0 4px;
}
.sf-burger { display: none; border: 0; background: none; font-size: 22px; cursor: pointer; }

.sf-main { flex: 1; }

.sf-footer { background: $sf-color-bg-soft; border-top: 1px solid $sf-color-border; margin-top: 48px; padding: 40px 0 0; }
.sf-footer-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; padding-bottom: 24px;
  h4 { margin-bottom: 12px; font-size: 15px; }
  p { font-size: 13px; color: $sf-color-muted; margin-bottom: 6px; }
}
.sf-footer-link { display: block; font-size: 13px; color: $sf-color-muted; text-decoration: none; margin-bottom: 6px; &:hover { color: $sf-color-accent; } }
.sf-footer-legal { border-top: 1px solid $sf-color-border; padding: 16px; text-align: center; p { font-size: 12px; color: $sf-color-muted; } }

@media (max-width: 768px) {
  .sf-header-inner { flex-wrap: wrap; height: auto; padding: 10px 16px; }
  .sf-nav {
    display: none; flex-direction: column; width: 100%; order: 4;
    &.open { display: flex; }
  }
  .sf-burger { display: block; }
  .sf-search { order: 3; width: 100%; input { width: 100%; flex: 1; } }
  .sf-hotline { display: none; }
  .sf-footer-grid { grid-template-columns: 1fr 1fr; }
}
</style>
