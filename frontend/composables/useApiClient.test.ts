import { describe, it, expect, beforeEach } from 'vitest'
import { getAuthHeaders, resolveApiUrl, parseApiError, getStoredToken, setStoredToken } from './useApiClient'

describe('getAuthHeaders', () => {
  it('returns headers without Authorization when token is null', () => {
    expect(getAuthHeaders(null)).toEqual({ Accept: 'application/json' })
  })

  it('adds Bearer Authorization header when token is provided', () => {
    expect(getAuthHeaders('abc123')).toEqual({
      Accept: 'application/json',
      Authorization: 'Bearer abc123'
    })
  })
})

describe('resolveApiUrl', () => {
  it('joins base and path with exactly one slash', () => {
    expect(resolveApiUrl('http://localhost:8000/api', '/login')).toBe('http://localhost:8000/api/login')
  })

  it('handles base with trailing slash and path without leading slash', () => {
    expect(resolveApiUrl('http://localhost:8000/api/', 'login')).toBe('http://localhost:8000/api/login')
  })
})

describe('parseApiError', () => {
  it('extracts message and validation errors from a Laravel-style error response', () => {
    const error = {
      response: {
        status: 422,
        _data: { message: 'Validation error', errors: { email: ['The email field is required.'] } }
      }
    }
    expect(parseApiError(error)).toEqual({
      status: 422,
      message: 'Validation error',
      errors: { email: ['The email field is required.'] }
    })
  })

  it('falls back to a generic message when the response has no message', () => {
    expect(parseApiError({}).message).toBe('Đã xảy ra lỗi, vui lòng thử lại.')
  })
})

describe('token storage', () => {
  beforeEach(() => {
    window.localStorage.clear()
  })

  it('returns null when no token is stored', () => {
    expect(getStoredToken()).toBeNull()
  })

  it('stores and retrieves a token', () => {
    setStoredToken('xyz')
    expect(getStoredToken()).toBe('xyz')
  })

  it('removes the token when set to null', () => {
    setStoredToken('xyz')
    setStoredToken(null)
    expect(getStoredToken()).toBeNull()
  })
})
