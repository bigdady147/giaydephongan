import { describe, it, expect, beforeEach } from 'vitest'
import { useCart, type CartItem } from './useCart'

describe('useCart Composable', () => {
  beforeEach(() => {
    const { clearCart } = useCart()
    clearCart()
  })

  it('can add items and calculate total and count', () => {
    const { items, count, total, addItem } = useCart()

    const item1: Omit<CartItem, 'quantity'> = {
      variantId: 101,
      productId: 1,
      name: 'Giày Tây Oxford',
      slug: 'giay-tay-oxford',
      thumbnail: null,
      size: '42',
      color: 'Đen',
      price: 500000,
    }

    addItem(item1, 2)

    expect(items.value.length).toBe(1)
    expect(items.value[0].quantity).toBe(2)
    expect(count.value).toBe(2)
    expect(total.value).toBe(1000000)

    // Add same item again
    addItem(item1, 1)
    expect(items.value.length).toBe(1)
    expect(items.value[0].quantity).toBe(3)
    expect(count.value).toBe(3)
    expect(total.value).toBe(1500000)
  })

  it('can update quantity and remove items', () => {
    const { items, addItem, updateQty, removeItem, total } = useCart()

    const item1: Omit<CartItem, 'quantity'> = {
      variantId: 101,
      productId: 1,
      name: 'Giày Tây Oxford',
      slug: 'giay-tay-oxford',
      thumbnail: null,
      size: '42',
      color: 'Đen',
      price: 500000,
    }

    addItem(item1, 1)
    updateQty(101, 5)

    expect(items.value[0].quantity).toBe(5)
    expect(total.value).toBe(2500000)

    removeItem(101)
    expect(items.value.length).toBe(0)
    expect(total.value).toBe(0)
  })
})
