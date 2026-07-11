import { describe, it, expect } from 'vitest'
import { computeIsAdmin, computeIsStaff, type AuthUser } from './useAuth'

const makeUser = (role: AuthUser['role']): AuthUser => ({
  id: 1,
  name: 'Test',
  username: 'test',
  email: 't@example.com',
  role,
  status: 'active'
})

describe('computeIsAdmin', () => {
  it('is true only for admin role', () => {
    expect(computeIsAdmin(makeUser('admin'))).toBe(true)
    expect(computeIsAdmin(makeUser('staff'))).toBe(false)
    expect(computeIsAdmin(makeUser('customer'))).toBe(false)
    expect(computeIsAdmin(null)).toBe(false)
  })
})

describe('computeIsStaff', () => {
  it('is true for staff and admin, false for customer', () => {
    expect(computeIsStaff(makeUser('staff'))).toBe(true)
    expect(computeIsStaff(makeUser('admin'))).toBe(true)
    expect(computeIsStaff(makeUser('customer'))).toBe(false)
    expect(computeIsStaff(null)).toBe(false)
  })
})
