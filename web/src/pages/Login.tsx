import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ApiError, post } from '../lib/api'
import { saveSession } from '../lib/auth'
import type { User } from '../lib/types'
import { Field, PrimaryButton, inputClass, inputStyle } from '../components/ui'

interface LoginResponse {
  user: { data?: User } | User
  access_token: string
  refresh_token: string
}

export default function Login() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)
  const navigate = useNavigate()

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    setBusy(true)
    setError('')

    try {
      const res = await post<LoginResponse>('/auth/login', { email, password })
      const user = (res.user as { data?: User }).data ?? (res.user as User)
      saveSession(res, user)
      navigate('/')
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Could not reach the server.')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="grid min-h-screen place-items-center p-4">
      <div
        className="w-full max-w-sm rounded-xl border p-6"
        style={{ background: 'var(--surface-card)', borderColor: 'var(--hairline)' }}
      >
        <div className="mb-5 flex items-center gap-2.5">
          <span
            className="grid h-9 w-9 place-items-center rounded-lg text-base font-bold"
            style={{ background: 'var(--accent)', color: 'var(--accent-ink)' }}
          >
            C
          </span>
          <div>
            <h1 className="text-base font-semibold" style={{ color: 'var(--text-primary)' }}>
              Modern CRM
            </h1>
            <p className="text-xs" style={{ color: 'var(--text-muted)' }}>
              Sign in to your workspace
            </p>
          </div>
        </div>

        <form onSubmit={submit} className="space-y-3">
          <Field label="Email">
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className={inputClass}
              style={inputStyle}
              placeholder="admin@example.com"
            />
          </Field>
          <Field label="Password">
            <input
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className={inputClass}
              style={inputStyle}
              placeholder="••••••••"
            />
          </Field>

          {error && (
            <p className="rounded-lg px-3 py-2 text-xs" style={{ background: 'var(--critical-bg)', color: 'var(--critical-ink)' }}>
              {error}
            </p>
          )}

          <PrimaryButton type="submit" disabled={busy} className="w-full">
            {busy ? 'Signing in…' : 'Sign in'}
          </PrimaryButton>

          <button
            type="button"
            className="w-full text-center text-xs underline-offset-2 hover:underline"
            style={{ color: 'var(--text-muted)' }}
            onClick={() => {
              setEmail('admin@example.com')
              setPassword('password')
            }}
          >
            Fill demo credentials
          </button>
        </form>
      </div>
    </div>
  )
}
