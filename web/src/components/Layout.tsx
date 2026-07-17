import { useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import {
  CalendarClock,
  KanbanSquare,
  LayoutDashboard,
  LogOut,
  Moon,
  PanelLeftClose,
  PanelLeftOpen,
  Sun,
  UserCheck,
} from 'lucide-react'
import { useTheme } from '../lib/theme'
import { currentUser, logout } from '../lib/auth'
import { post } from '../lib/api'
import { cx } from '../lib/utils'

const NAV = [
  { to: '/', label: 'Dashboard', icon: LayoutDashboard },
  { to: '/leads', label: 'Leads', icon: KanbanSquare },
  { to: '/customers', label: 'Customers', icon: UserCheck },
  { to: '/follow-ups', label: 'Follow-ups', icon: CalendarClock },
] as const

export default function Layout() {
  const [collapsed, setCollapsed] = useState(false)
  const [dark, toggleTheme] = useTheme()
  const navigate = useNavigate()
  const user = currentUser()

  const signOut = async () => {
    try {
      await post('/auth/logout')
    } catch {
      // token may already be dead; local logout is what matters
    }
    logout()
    navigate('/login')
  }

  return (
    <div className="flex min-h-screen">
      <aside
        className={cx('flex shrink-0 flex-col border-r transition-all', collapsed ? 'w-14' : 'w-56')}
        style={{ background: 'var(--surface-card)', borderColor: 'var(--hairline)' }}
      >
        <div className="flex h-14 items-center gap-2 border-b px-4" style={{ borderColor: 'var(--hairline)' }}>
          <span
            className="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-sm font-bold"
            style={{ background: 'var(--accent)', color: 'var(--accent-ink)' }}
          >
            C
          </span>
          {!collapsed && (
            <span className="text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
              Modern CRM
            </span>
          )}
        </div>

        <nav className="flex-1 space-y-0.5 p-2">
          {NAV.map((n) => (
            <NavLink
              key={n.to}
              to={n.to}
              end={n.to === '/'}
              title={collapsed ? n.label : undefined}
              className="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium"
              style={({ isActive }) => ({
                background: isActive ? 'var(--accent)' : 'transparent',
                color: isActive ? 'var(--accent-ink)' : 'var(--text-secondary)',
              })}
            >
              <n.icon size={17} className="shrink-0" aria-hidden />
              {!collapsed && n.label}
            </NavLink>
          ))}
        </nav>

        <div className="border-t p-2" style={{ borderColor: 'var(--hairline)' }}>
          <button
            onClick={signOut}
            title={collapsed ? 'Sign out' : undefined}
            className="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium hover:opacity-75"
            style={{ color: 'var(--text-secondary)' }}
          >
            <LogOut size={17} className="shrink-0" aria-hidden />
            {!collapsed && 'Sign out'}
          </button>
        </div>
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header
          className="flex h-14 items-center gap-3 border-b px-4"
          style={{ background: 'var(--surface-card)', borderColor: 'var(--hairline)' }}
        >
          <button
            onClick={() => setCollapsed((c) => !c)}
            aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
            className="rounded-lg p-1.5 hover:opacity-75"
            style={{ color: 'var(--text-secondary)' }}
          >
            {collapsed ? <PanelLeftOpen size={17} aria-hidden /> : <PanelLeftClose size={17} aria-hidden />}
          </button>

          <div className="ml-auto flex items-center gap-3">
            <button
              onClick={toggleTheme}
              aria-label={dark ? 'Switch to light theme' : 'Switch to dark theme'}
              className="rounded-lg p-2 hover:opacity-75"
              style={{ color: 'var(--text-secondary)' }}
            >
              {dark ? <Sun size={17} aria-hidden /> : <Moon size={17} aria-hidden />}
            </button>
            <span className="text-sm" style={{ color: 'var(--text-muted)' }}>
              {user?.name}
            </span>
          </div>
        </header>

        <main className="min-w-0 flex-1 p-5">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
