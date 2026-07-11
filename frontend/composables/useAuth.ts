import { computed } from 'vue'
import { getStoredToken, setStoredToken, useApiClient } from './useApiClient'

export interface AuthUser {
  id: number
  name: string
  username: string
  email: string
  role: 'admin' | 'staff' | 'customer'
  status: string
}

interface LoginPayload {
  login: string
  password: string
}

interface RegisterPayload {
  name: string
  username: string
  email: string
  password: string
  password_confirmation: string
  phone?: string
}

export function computeIsAdmin(user: AuthUser | null): boolean {
  return user?.role === 'admin'
}

export function computeIsStaff(user: AuthUser | null): boolean {
  return user?.role === 'staff' || user?.role === 'admin'
}

export const useAuth = () => {
  const user = useState<AuthUser | null>('auth_user', () => null)
  const api = useApiClient()

  const isAuthenticated = computed(() => user.value !== null)
  const isAdmin = computed(() => computeIsAdmin(user.value))
  const isStaff = computed(() => computeIsStaff(user.value))

  const fetchUser = async () => {
    if (!getStoredToken()) {
      user.value = null
      return null
    }
    try {
      user.value = await api.get<AuthUser>('/user')
      return user.value
    } catch (error: any) {
      // Only discard the token when the server rejected it (expired/revoked).
      // A transient network error or a 5xx must not log the user out.
      if (error?.status === 401 || error?.status === 403) {
        setStoredToken(null)
      }
      user.value = null
      return null
    }
  }

  const login = async (payload: LoginPayload) => {
    const response = await api.post<{ access_token: string; user: AuthUser }>('/login', payload)
    setStoredToken(response.access_token)
    user.value = response.user
    return response.user
  }

  const register = async (payload: RegisterPayload) => {
    const response = await api.post<{ access_token: string; user: AuthUser }>('/register', payload)
    setStoredToken(response.access_token)
    user.value = response.user
    return response.user
  }

  const logout = async () => {
    try {
      await api.post('/logout')
    } finally {
      setStoredToken(null)
      user.value = null
    }
  }

  return {
    user,
    isAuthenticated,
    isAdmin,
    isStaff,
    fetchUser,
    login,
    register,
    logout
  }
}
