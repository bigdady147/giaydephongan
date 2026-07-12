import { describe, expect, it } from 'vitest'
import { formatVnd, isNew } from './format'

describe('formatVnd', () => {
  it('formats VND with dot separators and currency sign', () => {
    expect(formatVnd(1290000)).toBe('1.290.000₫')
    expect(formatVnd(0)).toBe('0₫')
  })
})

describe('isNew', () => {
  it('is true within 14 days and false after', () => {
    const now = new Date()
    const recent = new Date(now.getTime() - 5 * 86400000).toISOString()
    const old = new Date(now.getTime() - 30 * 86400000).toISOString()
    expect(isNew(recent)).toBe(true)
    expect(isNew(old)).toBe(false)
  })
})
