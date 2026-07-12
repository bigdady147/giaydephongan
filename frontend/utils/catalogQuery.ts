export interface ProductFilters {
  brand?: string
  material?: string
  size?: string
  color?: string
  price_min?: number
  price_max?: number
  q?: string
  sort?: string
  page?: number
}

const STRING_KEYS = ['brand', 'material', 'size', 'color', 'q', 'sort'] as const
const NUMBER_KEYS = ['price_min', 'price_max', 'page'] as const

export function buildProductQuery(filters: ProductFilters & { category?: string }): string {
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(filters)) {
    if (value !== undefined && value !== null && value !== '') {
      params.set(key, String(value))
    }
  }
  const serialized = params.toString()
  return serialized ? `?${serialized}` : ''
}

export function parseFilters(query: Record<string, unknown>): ProductFilters {
  const filters: ProductFilters = {}
  for (const key of STRING_KEYS) {
    const value = query[key]
    if (typeof value === 'string' && value !== '') filters[key] = value
  }
  for (const key of NUMBER_KEYS) {
    const value = Number(query[key])
    if (query[key] !== undefined && query[key] !== '' && Number.isFinite(value) && value >= 0) {
      filters[key] = value
    }
  }
  return filters
}
