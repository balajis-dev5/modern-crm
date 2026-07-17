import { useCallback, useEffect, useState } from 'react'
import { Check } from 'lucide-react'
import { get, patch } from '../lib/api'
import type { FollowUp, Paginated } from '../lib/types'
import { cx, dateTime, relativeDue } from '../lib/utils'
import { EmptyState, Spinner } from '../components/ui'

const BUCKETS = [
  { key: 'overdue', label: 'Overdue' },
  { key: 'today', label: 'Today' },
  { key: 'upcoming', label: 'Upcoming' },
  { key: 'done', label: 'Done' },
] as const

type Bucket = (typeof BUCKETS)[number]['key']

export default function FollowUps() {
  const [bucket, setBucket] = useState<Bucket>('overdue')
  const [items, setItems] = useState<FollowUp[] | null>(null)

  const load = useCallback(() => {
    setItems(null)
    get<Paginated<FollowUp>>(`/follow-ups?bucket=${bucket}&per_page=100`).then((res) => setItems(res.data))
  }, [bucket])

  useEffect(load, [load])

  const complete = async (id: number) => {
    setItems((prev) => prev?.filter((f) => f.id !== id) ?? null)
    try {
      await patch(`/follow-ups/${id}/complete`)
    } catch {
      load()
    }
  }

  return (
    <div className="space-y-4">
      <h1 className="text-lg font-semibold" style={{ color: 'var(--text-primary)' }}>
        Follow-ups
      </h1>

      <div className="flex gap-1 rounded-lg border p-1" style={{ borderColor: 'var(--hairline)', background: 'var(--surface-card)', width: 'fit-content' }}>
        {BUCKETS.map((b) => (
          <button
            key={b.key}
            onClick={() => setBucket(b.key)}
            className={cx('rounded-md px-3 py-1.5 text-sm font-medium')}
            style={{
              background: bucket === b.key ? 'var(--accent)' : 'transparent',
              color: bucket === b.key ? 'var(--accent-ink)' : 'var(--text-secondary)',
            }}
          >
            {b.label}
          </button>
        ))}
      </div>

      {!items ? (
        <Spinner />
      ) : items.length === 0 ? (
        <EmptyState message={bucket === 'overdue' ? 'Nothing overdue — inbox zero.' : 'Nothing here.'} />
      ) : (
        <ul
          className="divide-y rounded-xl border"
          style={{ background: 'var(--surface-card)', borderColor: 'var(--hairline)' }}
        >
          {items.map((f) => (
            <li key={f.id} className="flex items-center gap-3 px-4 py-3" style={{ borderColor: 'var(--hairline)' }}>
              {!f.done_at && (
                <button
                  onClick={() => complete(f.id)}
                  aria-label={`Mark "${f.title}" done`}
                  className="grid h-6 w-6 shrink-0 place-items-center rounded-full border hover:opacity-75"
                  style={{ borderColor: 'var(--baseline)', color: 'var(--good)' }}
                >
                  <Check size={13} aria-hidden />
                </button>
              )}
              <div className="min-w-0">
                <p className="truncate text-sm font-medium" style={{ color: f.done_at ? 'var(--text-muted)' : 'var(--text-primary)' }}>
                  {f.title}
                </p>
                <p className="truncate text-xs" style={{ color: 'var(--text-muted)' }}>
                  {f.customer?.name ?? f.lead?.name ?? '—'} · {f.assignee?.name}
                </p>
              </div>
              <span
                className="tnum ml-auto shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                style={{
                  background: f.done_at ? 'var(--good-bg)' : f.overdue ? 'var(--critical-bg)' : 'var(--surface-page)',
                  color: f.done_at ? 'var(--good-ink)' : f.overdue ? 'var(--critical-ink)' : 'var(--text-secondary)',
                }}
                title={dateTime(f.due_at)}
              >
                {f.done_at ? 'done' : relativeDue(f.due_at)}
              </span>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
