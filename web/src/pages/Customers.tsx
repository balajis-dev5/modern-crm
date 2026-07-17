import { useEffect, useState } from 'react'
import { get } from '../lib/api'
import type { Customer, Paginated } from '../lib/types'
import { cx, dateTime, shortDate } from '../lib/utils'
import { EmptyState, Modal, Spinner, inputClass, inputStyle } from '../components/ui'

export default function Customers() {
  const [customers, setCustomers] = useState<Customer[] | null>(null)
  const [meta, setMeta] = useState({ page: 1, lastPage: 1, total: 0 })
  const [query, setQuery] = useState('')
  const [selected, setSelected] = useState<number | null>(null)

  useEffect(() => {
    const handle = setTimeout(() => {
      const params = new URLSearchParams({ per_page: '25', page: String(meta.page) })
      if (query.trim()) params.set('q', query.trim())

      get<Paginated<Customer>>(`/customers?${params}`).then((res) => {
        setCustomers(res.data)
        setMeta((m) => ({ ...m, lastPage: res.meta.last_page, total: res.meta.total }))
      })
    }, query ? 250 : 0)

    return () => clearTimeout(handle)
  }, [query, meta.page])

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-3">
        <h1 className="text-lg font-semibold" style={{ color: 'var(--text-primary)' }}>
          Customers
        </h1>
        <span className="tnum text-sm" style={{ color: 'var(--text-muted)' }}>
          {meta.total}
        </span>
        <input
          value={query}
          onChange={(e) => {
            setQuery(e.target.value)
            setMeta((m) => ({ ...m, page: 1 }))
          }}
          placeholder="Search name or company…"
          className={cx(inputClass, 'ml-auto max-w-xs')}
          style={inputStyle}
        />
      </div>

      {!customers ? (
        <Spinner />
      ) : customers.length === 0 ? (
        <EmptyState message="No customers match." />
      ) : (
        <div
          className="overflow-x-auto rounded-xl border"
          style={{ background: 'var(--surface-card)', borderColor: 'var(--hairline)' }}
        >
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left text-xs uppercase tracking-wide" style={{ borderColor: 'var(--hairline)', color: 'var(--text-muted)' }}>
                <th className="px-4 py-2.5 font-medium">Name</th>
                <th className="px-4 py-2.5 font-medium">Company</th>
                <th className="px-4 py-2.5 font-medium">Owner</th>
                <th className="px-4 py-2.5 font-medium">Open follow-ups</th>
                <th className="px-4 py-2.5 font-medium">Since</th>
              </tr>
            </thead>
            <tbody>
              {customers.map((c) => (
                <tr
                  key={c.id}
                  onClick={() => setSelected(c.id)}
                  className="cursor-pointer border-b last:border-0 hover:opacity-80"
                  style={{ borderColor: 'var(--hairline)' }}
                >
                  <td className="px-4 py-2.5 font-medium" style={{ color: 'var(--text-primary)' }}>
                    {c.name}
                  </td>
                  <td className="px-4 py-2.5" style={{ color: 'var(--text-secondary)' }}>
                    {c.company ?? '—'}
                  </td>
                  <td className="px-4 py-2.5" style={{ color: 'var(--text-secondary)' }}>
                    {c.owner?.name ?? '—'}
                  </td>
                  <td className="tnum px-4 py-2.5" style={{ color: (c.open_follow_ups_count ?? 0) > 0 ? 'var(--warning-ink)' : 'var(--text-muted)' }}>
                    {c.open_follow_ups_count ?? 0}
                  </td>
                  <td className="tnum px-4 py-2.5 text-xs" style={{ color: 'var(--text-muted)' }}>
                    {shortDate(c.created_at)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {meta.lastPage > 1 && (
        <div className="flex items-center justify-end gap-2 text-sm">
          <button
            disabled={meta.page <= 1}
            onClick={() => setMeta((m) => ({ ...m, page: m.page - 1 }))}
            className="rounded-lg border px-3 py-1.5 disabled:opacity-40"
            style={{ borderColor: 'var(--hairline)', color: 'var(--text-secondary)' }}
          >
            Prev
          </button>
          <span className="tnum" style={{ color: 'var(--text-muted)' }}>
            {meta.page} / {meta.lastPage}
          </span>
          <button
            disabled={meta.page >= meta.lastPage}
            onClick={() => setMeta((m) => ({ ...m, page: m.page + 1 }))}
            className="rounded-lg border px-3 py-1.5 disabled:opacity-40"
            style={{ borderColor: 'var(--hairline)', color: 'var(--text-secondary)' }}
          >
            Next
          </button>
        </div>
      )}

      {selected !== null && <CustomerDetail id={selected} onClose={() => setSelected(null)} />}
    </div>
  )
}

function CustomerDetail({ id, onClose }: { id: number; onClose: () => void }) {
  const [customer, setCustomer] = useState<Customer | null>(null)

  useEffect(() => {
    get<{ data: Customer }>(`/customers/${id}`).then((res) => setCustomer(res.data))
  }, [id])

  if (!customer) {
    return (
      <Modal title="Customer" onClose={onClose}>
        <Spinner />
      </Modal>
    )
  }

  return (
    <Modal title={customer.name} onClose={onClose}>
      <div className="space-y-4 text-sm">
        <dl className="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
          {[
            ['Company', customer.company],
            ['Email', customer.email],
            ['Phone', customer.phone],
            ['Owner', customer.owner?.name],
          ].map(([label, value]) => (
            <div key={label as string}>
              <dt style={{ color: 'var(--text-muted)' }}>{label}</dt>
              <dd style={{ color: 'var(--text-primary)' }}>{value ?? '—'}</dd>
            </div>
          ))}
        </dl>

        {customer.notes && (
          <p className="rounded-lg p-3 text-xs" style={{ background: 'var(--surface-page)', color: 'var(--text-secondary)' }}>
            {customer.notes}
          </p>
        )}

        <div>
          <h3 className="mb-1.5 text-xs font-semibold uppercase tracking-wide" style={{ color: 'var(--text-muted)' }}>
            Follow-ups
          </h3>
          {customer.follow_ups && customer.follow_ups.length > 0 ? (
            <ul className="space-y-1.5">
              {customer.follow_ups.map((f) => (
                <li key={f.id} className="flex items-center gap-2 text-xs">
                  <span
                    className="h-1.5 w-1.5 shrink-0 rounded-full"
                    style={{ background: f.done_at ? 'var(--good)' : f.overdue ? 'var(--critical)' : 'var(--chart-3)' }}
                    aria-hidden
                  />
                  <span style={{ color: f.done_at ? 'var(--text-muted)' : 'var(--text-primary)' }}>{f.title}</span>
                  <span className="tnum ml-auto shrink-0" style={{ color: 'var(--text-muted)' }}>
                    {dateTime(f.due_at)}
                  </span>
                </li>
              ))}
            </ul>
          ) : (
            <EmptyState message="No follow-ups yet." />
          )}
        </div>
      </div>
    </Modal>
  )
}
