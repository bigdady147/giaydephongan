// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  // Development tools
  devtools: { enabled: true },

  // Compatibility
  compatibilityDate: '2025-07-15',

  // Runtime config for API
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api'
    }
  },

  // CSS configuration
  css: [
    '~/assets/scss/main.scss'
  ],

  // Vite configuration for SCSS
  vite: {
    css: {
      preprocessorOptions: {
        scss: {
          additionalData: '@import "~/assets/scss/base/_variables.scss";'
        }
      }
    }
  },

  // Nitro configuration
  nitro: {
    preset: 'node-server'
  },

  // App configuration
  app: {
    head: {
      title: 'Giày dép Hồng An',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'Laravel 12 + Nuxt 4 TypeScript Full-Stack Application' }
      ]
    }
  },

  modules: ['@nuxtjs/i18n'],

  // i18n configuration
  i18n: {
    langDir: '../locales',
    locales: [
      {
        code: 'vi',
        iso: 'vi-VN',
        name: 'Tiếng Việt',
        file: 'vi.json'
      },
      {
        code: 'en',
        iso: 'en-US',
        name: 'English',
        file: 'en.json'
      }
    ],
    defaultLocale: 'vi',
    strategy: 'prefix_except_default',
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'i18n_redirected',
      redirectOn: 'root',
      alwaysRedirect: true,
      fallbackLocale: 'vi'
    }
  }
})