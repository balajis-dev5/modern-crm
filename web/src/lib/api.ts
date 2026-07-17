import { accessToken, logout, refreshToken, saveSession } from './auth'

const BASE = import.meta.env.VITE_API_URL ?? '/api/v1'

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public errors: Record<string, string[]> = {},
  ) {
    super(message)
  }
}

async function rawRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = accessToken()

  const response = await fetch(`${BASE}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  })

  if (response.status === 204) return undefined as T

  const body = await response.json().catch(() => ({}))

  if (!response.ok) {
    throw new ApiError(response.status, body.message ?? 'Request failed', body.errors ?? {})
  }

  return body as T
}

/** One in-flight refresh at a time; concurrent 401s share it. */
let refreshing: Promise<boolean> | null = null

async function tryRefresh(): Promise<boolean> {
  refreshing ??= (async () => {
    const token = refreshToken()
    if (!token) return false

    try {
      const pair = await rawRequest<{ access_token: string; refresh_token: string }>(
        '/auth/refresh',
        { method: 'POST', body: JSON.stringify({ refresh_token: token }) },
      )
      saveSession(pair)
      return true
    } catch {
      return false
    } finally {
      refreshing = null
    }
  })()

  return refreshing
}

export async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  try {
    return await rawRequest<T>(path, options)
  } catch (error) {
    if (error instanceof ApiError && error.status === 401 && !path.startsWith('/auth/')) {
      if (await tryRefresh()) {
        return rawRequest<T>(path, options)
      }
      logout()
      window.location.href = '/login'
    }
    throw error
  }
}

export const get = <T>(path: string) => api<T>(path)
export const post = <T>(path: string, body?: unknown) =>
  api<T>(path, { method: 'POST', body: body === undefined ? undefined : JSON.stringify(body) })
export const patch = <T>(path: string, body?: unknown) =>
  api<T>(path, { method: 'PATCH', body: body === undefined ? undefined : JSON.stringify(body) })
export const put = <T>(path: string, body?: unknown) =>
  api<T>(path, { method: 'PUT', body: JSON.stringify(body) })
export const destroy = (path: string) => api<void>(path, { method: 'DELETE' })
