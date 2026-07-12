import { ref } from 'vue'
import type { CardProduct } from '~/types/storefront'

const STORAGE_KEY = 'recently_viewed'
const MAX_ITEMS = 8

export const useRecentlyViewed = () => {
  const items = ref<CardProduct[]>([])

  const load = () => {
    if (typeof window === 'undefined') return
    try {
      items.value = JSON.parse(window.localStorage.getItem(STORAGE_KEY) ?? '[]')
    } catch {
      items.value = []
    }
  }

  const push = (product: CardProduct) => {
    if (typeof window === 'undefined') return
    load()
    const next = [product, ...items.value.filter(item => item.id !== product.id)].slice(0, MAX_ITEMS)
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next))
    items.value = next
  }

  load()

  return { items, push }
}
