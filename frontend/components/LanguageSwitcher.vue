<template>
  <div class="language-switcher">
    <button 
      v-for="locale in availableLocales" 
      :key="locale.code"
      @click="switchLanguage(locale.code)"
      :class="['lang-btn', { 'active': locale.code === currentLocale }]"
      :title="locale.name"
    >
      {{ locale.code.toUpperCase() }}
    </button>
  </div>
</template>

<script setup>
const { locale, locales } = useI18n()
// const { switchLocalePath } = useI18n()
const switchLocalePath = useSwitchLocalePath()
const currentLocale = computed(() => locale.value)
const availableLocales = computed(() => locales.value)

const switchLanguage = async (newLocale) => {
  if (newLocale === currentLocale.value) return
  
  // Use the official Nuxt i18n API
  const path = switchLocalePath(newLocale)
  await navigateTo(path)
}
</script>

<style lang="scss" scoped>
.language-switcher {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.lang-btn {
  padding: 0.5rem 1rem;
  border: 1px solid var(--border-color);
  background: var(--bg-color);
  color: var(--text-color);
  border-radius: 0.25rem;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s ease;
  
  &:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
  }
  
  &.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
  }
}
</style>
