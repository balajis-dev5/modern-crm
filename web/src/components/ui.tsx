import type { ReactNode } from 'react'
import type { Stage } from '../lib/types'
import { cx } from '../lib/utils'

export function Card({ children, className }: { children: ReactNode; className?: string }) {
  return (
    <div
      className={cx('rounded-xl border p-4', className)}
      style={{ background: 'var(--surface-card)', borderColor: 'var(--hairline)' }}
    >
      {children}
    </div>
  )
}

export function StatCard({ label, value, hint, tone }: { label: string; value: string; hint?: string; tone?: 'good' | 'critical' }) {
  return (
    <Card>
      <p className="text-xs font-medium uppercase tracking-wide" style={{ color: 'var(--text-muted)' }}>
        {label}
      </p>
      <p
        className="tnum mt-1 text-2xl font-semibold"
        style={{ color: tone ? `var(--${tone})` : 'var(--text-primary)' }}
      >
        {value}
      </p>
      {hint && (
        <p className="mt-0.5 text-xs" style={{ color: 'var(--text-muted)' }}>
          {hint}
        </p>
      )}
    </Card>
  )
}

const STAGE_TONES: Record<Stage, { bg: string; ink: string }> = {
  new: { bg: 'var(--surface-page)', ink: 'var(--text-secondary)' },
  contacted: { bg: 'color-mix(in srgb, var(--chart-1) 14%, transparent)', ink: 'var(--chart-1)' },
  qualified: { bg: 'color-mix(in srgb, var(--chart-5) 14%, transparent)', ink: 'var(--chart-5)' },
  proposal: { bg: 'var(--warning-bg)', ink: 'var(--warning-ink)' },
  won: { bg: 'var(--good-bg)', ink: 'var(--good-ink)' },
  lost: { bg: 'var(--critical-bg)', ink: 'var(--critical-ink)' },
}

export function StageBadge({ stage }: { stage: Stage }) {
  const tone = STAGE_TONES[stage]
  return (
    <span
      className="inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize"
      style={{ background: tone.bg, color: tone.ink }}
    >
      {stage.replace('_', ' ')}
    </span>
  )
}

export function Spinner() {
  return (
    <div className="flex justify-center py-16" role="status" aria-label="Loading">
      <div
        className="h-6 w-6 animate-spin rounded-full border-2 border-t-transparent"
        style={{ borderColor: 'var(--accent)', borderTopColor: 'transparent' }}
      />
    </div>
  )
}

export function EmptyState({ message }: { message: string }) {
  return (
    <p className="py-12 text-center text-sm" style={{ color: 'var(--text-muted)' }}>
      {message}
    </p>
  )
}

export function Modal({ title, onClose, children }: { title: string; onClose: () => void; children: ReactNode }) {
  return (
    <div
      className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4 pt-[8vh]"
      onClick={onClose}
      role="dialog"
      aria-label={title}
    >
      <div
        className="w-full max-w-lg rounded-xl border shadow-2xl"
        style={{ background: 'var(--surface-card)', borderColor: 'var(--hairline)' }}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-center justify-between border-b px-4 py-3" style={{ borderColor: 'var(--hairline)' }}>
          <h2 className="text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
            {title}
          </h2>
          <button onClick={onClose} aria-label="Close" className="text-lg leading-none hover:opacity-70" style={{ color: 'var(--text-muted)' }}>
            ×
          </button>
        </div>
        <div className="p-4">{children}</div>
      </div>
    </div>
  )
}

export function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <label className="block">
      <span className="mb-1 block text-xs font-medium" style={{ color: 'var(--text-secondary)' }}>
        {label}
      </span>
      {children}
    </label>
  )
}

export const inputStyle = {
  background: 'var(--surface-page)',
  borderColor: 'var(--hairline)',
  color: 'var(--text-primary)',
} as const

export const inputClass =
  'w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[var(--accent)]'

export function PrimaryButton({ children, ...props }: React.ButtonHTMLAttributes<HTMLButtonElement>) {
  return (
    <button
      {...props}
      className={cx(
        'rounded-lg px-4 py-2 text-sm font-medium disabled:opacity-50',
        props.className,
      )}
      style={{ background: 'var(--accent)', color: 'var(--accent-ink)' }}
    >
      {children}
    </button>
  )
}
