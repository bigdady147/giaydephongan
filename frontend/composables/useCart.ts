import { computed, ref } from 'vue'

export interface CartItem {
  variantId: number
  productId: number
  name: string
  slug: string
  thumbnail: string | null
  size: string
  color: string
  price: number
  quantity: number
}

const STORAGE_KEY = 'hongan_cart'

const items = ref<CartItem[]>([])
const loaded = ref(false)

const load = () => {
  if (typeof window === 'undefined' || loaded.value) return
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY)
    items.value = raw ? JSON.parse(raw) : []
  } catch {
    items.value = []
  } finally {
    loaded.value = true
  }
}

const save = () => {
  if (typeof window === 'undefined') return
  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items.value))
}

export const useCart = () => {
  load()

  const count = computed(() => items.value.reduce((sum, item) => sum + item.quantity, 0))
  const total = computed(() => items.value.reduce((sum, item) => sum + item.price * item.quantity, 0))

  const addItem = (item: Omit<CartItem, 'quantity'>, quantity = 1) => {
    const existing = items.value.find(i => i.variantId === item.variantId)
    if (existing) {
      existing.quantity += quantity
    } else {
      items.value.push({ ...item, quantity })
    }
    save()
  }

  const removeItem = (variantId: number) => {
    items.value = items.value.filter(i => i.variantId !== variantId)
    save()
  }

  const updateQty = (variantId: number, qty: number) => {
    const existing = items.value.find(i => i.variantId === variantId)
    if (existing) {
      existing.quantity = Math.max(1, qty)
      save()
    }
  }

  const clearCart = () => {
    items.value = []
    save()
  }

  return {
    items,
    count,
    total,
    addItem,
    removeItem,
    updateQty,
    clearCart
  }
}
