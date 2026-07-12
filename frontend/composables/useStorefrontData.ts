import type { CategoryInfo, PageLink, SettingsMap } from '~/types/storefront'

export const useStorefrontData = () => {
  const config = useRuntimeConfig()
  const api = config.public.apiBase

  return useAsyncData('storefront-shared', async () => {
    const [categories, settings, pages] = await Promise.all([
      $fetch<CategoryInfo[]>(`${api}/categories`),
      $fetch<SettingsMap>(`${api}/settings`),
      $fetch<PageLink[]>(`${api}/pages`)
    ])
    return { categories, settings, pages }
  })
}
