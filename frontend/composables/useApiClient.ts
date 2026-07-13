export interface ApiError {
  status: number
  message: string
  errors?: Record<string, string[]>
}

export function getAuthHeaders(token: string | null): Record<string, string> {
  const headers: Record<string, string> = {
    Accept: 'application/json'
  }
  if (token) {
    headers.Authorization = `Bearer ${token}`
  }
  return headers
}

export function resolveApiUrl(base: string, path: string): string {
  const cleanBase = base.replace(/\/+$/, '')
  const cleanPath = path.replace(/^\/+/, '')
  return `${cleanBase}/${cleanPath}`
}

export function parseApiError(error: any): ApiError {
  const status = error?.response?.status ?? 0
  const data = error?.response?._data ?? {}
  return {
    status,
    message: data.message || 'Đã xảy ra lỗi, vui lòng thử lại.',
    errors: data.errors
  }
}

const AUTH_TOKEN_KEY = 'auth_token'

export function getStoredToken(): string | null {
  if (typeof window === 'undefined') return null
  return window.localStorage.getItem(AUTH_TOKEN_KEY)
}

export function setStoredToken(token: string | null): void {
  if (typeof window === 'undefined') return
  if (token) {
    window.localStorage.setItem(AUTH_TOKEN_KEY, token)
  } else {
    window.localStorage.removeItem(AUTH_TOKEN_KEY)
  }
}

export const useApiClient = () => {
  const config = useRuntimeConfig()

  const request = async <T>(path: string, options: { method?: string; body?: unknown } = {}): Promise<T> => {
    const url = resolveApiUrl(config.public.apiBase, path)
    const token = getStoredToken()

    try {
      return await $fetch<T>(url, {
        method: (options.method ?? 'GET') as any,
        body: options.body as BodyInit | Record<string, any> | null | undefined,
        headers: getAuthHeaders(token)
      })
    } catch (error) {
      throw parseApiError(error)
    }
  }

  return {
    get: <T>(path: string) => request<T>(path),
    post: <T>(path: string, body?: unknown) => request<T>(path, { method: 'POST', body }),
    put: <T>(path: string, body?: unknown) => request<T>(path, { method: 'PUT', body }),
    patch: <T>(path: string, body?: unknown) => request<T>(path, { method: 'PATCH', body }),
    del: <T>(path: string) => request<T>(path, { method: 'DELETE' })
  }
}
