import type { User } from './types'

const ACCESS_KEY = 'crm-access-token'
const REFRESH_KEY = 'crm-refresh-token'
const USER_KEY = 'crm-user'

export function saveSession(tokens: { access_token: string; refresh_token: string }, user?: User) {
  localStorage.setItem(ACCESS_KEY, tokens.access_token)
  localStorage.setItem(REFRESH_KEY, tokens.refresh_token)
  if (user) localStorage.setItem(USER_KEY, JSON.stringify(user))
}

export function accessToken(): string | null {
  return localStorage.getItem(ACCESS_KEY)
}

export function refreshToken(): string | null {
  return localStorage.getItem(REFRESH_KEY)
}

export function currentUser(): User | null {
  const raw = localStorage.getItem(USER_KEY)
  if (!raw) return null
  try {
    return JSON.parse(raw) as User
  } catch {
    return null
  }
}

export function isAuthed(): boolean {
  return accessToken() !== null
}

export function logout() {
  localStorage.removeItem(ACCESS_KEY)
  localStorage.removeItem(REFRESH_KEY)
  localStorage.removeItem(USER_KEY)
}
