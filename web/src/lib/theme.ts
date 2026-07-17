import { useCallback, useSyncExternalStore } from 'react'

const KEY = 'crm-theme'
let listeners: (() => void)[] = []

function isDark(): boolean {
  return document.documentElement.classList.contains('dark')
}

function setDark(dark: boolean) {
  document.documentElement.classList.toggle('dark', dark)
  localStorage.setItem(KEY, dark ? 'dark' : 'light')
  listeners.forEach((l) => l())
}

export function useTheme(): [boolean, () => void] {
  const dark = useSyncExternalStore(
    (cb) => {
      listeners.push(cb)
      return () => {
        listeners = listeners.filter((l) => l !== cb)
      }
    },
    isDark,
  )

  const toggle = useCallback(() => setDark(!isDark()), [])

  return [dark, toggle]
}
