import { describe, expect, it } from 'vitest'
import { buildProductQuery, parseFilters } from './catalogQuery'

describe('buildProductQuery', () => {
  it('serializes only present filters', () => {
    expect(buildProductQuery({ brand: 'hong-an', page: 2 })).toBe('?brand=hong-an&page=2')
    expect(buildProductQuery({})).toBe('')
  })

  it('skips empty strings and undefined', () => {
    expect(buildProductQuery({ q: '', sort: undefined, size: '42' })).toBe('?size=42')
  })
})

describe('parseFilters', () => {
  it('extracts known string and numeric params', () => {
    const parsed = parseFilters({ brand: 'x', price_min: '500000', page: '3', junk: 'y' })
    expect(parsed).toEqual({ brand: 'x', price_min: 500000, page: 3 })
  })

  it('drops invalid numbers', () => {
    expect(parseFilters({ price_min: 'abc' })).toEqual({})
  })
})
