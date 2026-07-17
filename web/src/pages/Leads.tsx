import { useEffect, useMemo, useState } from 'react'
import { Plus } from 'lucide-react'
import { ApiError, get, patch, post } from '../lib/api'
import type { Lead, Paginated, Source, Stage } from '../lib/types'
import { SOURCES } from '../lib/types'
import { cx, money, shortDate } from '../lib/utils'
import { EmptyState, Field, Modal, PrimaryButton, Spinner, StageBadge, inputClass, inputStyle } from '../components/ui'

const BOARD_STAGES: Stage[] = ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost']

export default function Leads() {
  const [leads, setLeads] = useState<Lead[] | null>(null)
  const [query, setQuery] = useState('')
  const [creating, setCreating] = useState(false)
  const [selected, setSelected] = useState<Lead | null>(null)
  const [dragId, setDragId] = useState<number | null>(null)
  const [error, setError] = useState('')

  useEffect(() => {
    get<Paginated<Lead>>('/leads?per_page=200').then((res) => setLeads(res.data))
  }, [])

  const visible = useMemo(() => {
    if (!leads) return []
    const q = query.trim().toLowerCase()
    if (!q) return leads
    return leads.filter(
      (l) => l.name.toLowerCase().includes(q) || (l.company ?? '').toLowerCase().includes(q),
    )
  }, [leads, query])

  const byStage = useMemo(
    () => Object.fromEntries(BOARD_STAGES.map((s) => [s, visible.filter((l) => l.stage === s)])),
    [visible],
  ) as Record<Stage, Lead[]>

  /** Optimistic move: update the board instantly, roll back if the API rejects. */
  const moveLead = async (id: number, to: Stage) => {
    const before = leads
    if (!before) return
    const lead = before.find((l) => l.id === id)
    if (!lead || lead.stage === to) return

    setLeads(before.map((l) => (l.id === id ? { ...l, stage: to } : l)))
    setError('')

    try {
      await patch(`/leads/${id}/stage`, { stage: to })
    } catch (err) {
      setLeads(before)
      setError(err instanceof ApiError ? err.message : 'Stage change failed — reverted.')
    }
  }

  if (!leads) return <Spinner />

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-3">
        <h1 className="text-lg font-semibold" style={{ color: 'var(--text-primary)' }}>
          Leads
        </h1>
        <span className="tnum text-sm" style={{ color: 'var(--text-muted)' }}>
          {visible.length}
        </span>
        <input
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Search name or company…"
          className={cx(inputClass, 'ml-auto max-w-xs')}
          style={inputStyle}
        />
        <PrimaryButton onClick={() => setCreating(true)}>
          <span className="flex items-center gap-1.5">
            <Plus size={15} aria-hidden /> New lead
          </span>
        </PrimaryButton>
      </div>

      {error && (
        <p className="rounded-lg px-3 py-2 text-xs" style={{ background: 'var(--critical-bg)', color: 'var(--critical-ink)' }}>
          {error}
        </p>
      )}

      <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        {BOARD_STAGES.map((stage) => (
          <div
            key={stage}
            className="rounded-xl border p-2"
            style={{ background: 'var(--surface-card)', borderColor: 'var(--hairline)', minHeight: '16rem' }}
            onDragOver={(e) => e.preventDefault()}
            onDrop={() => {
              if (dragId !== null) moveLead(dragId, stage)
              setDragId(null)
            }}
          >
            <div className="mb-2 flex items-center justify-between px-1">
              <StageBadge stage={stage} />
              <span className="tnum text-xs" style={{ color: 'var(--text-muted)' }}>
                {byStage[stage].length}
              </span>
            </div>
            <div className="space-y-2">
              {byStage[stage].map((lead) => (
                <button
                  key={lead.id}
                  draggable
                  onDragStart={() => setDragId(lead.id)}
                  onDragEnd={() => setDragId(null)}
                  onClick={() => setSelected(lead)}
                  className="w-full cursor-grab rounded-lg border p-2.5 text-left text-sm hover:opacity-90 active:cursor-grabbing"
                  style={{ background: 'var(--surface-page)', borderColor: 'var(--hairline)' }}
                >
                  <p className="truncate font-medium" style={{ color: 'var(--text-primary)' }}>
                    {lead.name}
                  </p>
                  <p className="truncate text-xs" style={{ color: 'var(--text-muted)' }}>
                    {lead.company ?? '—'}
                  </p>
                  <p className="tnum mt-1 text-xs font-medium" style={{ color: 'var(--text-secondary)' }}>
                    {money(lead.deal_value)}
                  </p>
                </button>
              ))}
              {byStage[stage].length === 0 && (
                <p className="px-1 py-6 text-center text-xs" style={{ color: 'var(--text-muted)' }}>
                  Drop leads here
                </p>
              )}
            </div>
          </div>
        ))}
      </div>

      {creating && (
        <CreateLeadModal
          onClose={() => setCreating(false)}
          onCreated={(lead) => {
            setLeads((prev) => (prev ? [lead, ...prev] : [lead]))
            setCreating(false)
          }}
        />
      )}

      {selected && (
        <LeadDetailModal
          leadId={selected.id}
          onClose={() => setSelected(null)}
          onChanged={(updated) =>
            setLeads((prev) => prev?.map((l) => (l.id === updated.id ? { ...l, ...updated } : l)) ?? null)
          }
        />
      )}
    </div>
  )
}

function CreateLeadModal({ onClose, onCreated }: { onClose: () => void; onCreated: (lead: Lead) => void }) {
  const [form, setForm] = useState({ name: '', email: '', company: '', source: 'website' as Source, deal_value: 100000 })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [busy, setBusy] = useState(false)

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    setBusy(true)
    try {
      const res = await post<{ data: Lead }>('/leads', { ...form, email: form.email || null })
      onCreated(res.data)
    } catch (err) {
      if (err instanceof ApiError) setErrors(err.errors)
    } finally {
      setBusy(false)
    }
  }

  return (
    <Modal title="New lead" onClose={onClose}>
      <form onSubmit={submit} className="space-y-3">
        <Field label="Name">
          <input required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={inputClass} style={inputStyle} />
        </Field>
        <div className="grid grid-cols-2 gap-3">
          <Field label="Email">
            <input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className={inputClass} style={inputStyle} />
          </Field>
          <Field label="Company">
            <input value={form.company} onChange={(e) => setForm({ ...form, company: e.target.value })} className={inputClass} style={inputStyle} />
          </Field>
        </div>
        <div className="grid grid-cols-2 gap-3">
          <Field label="Source">
            <select value={form.source} onChange={(e) => setForm({ ...form, source: e.target.value as Source })} className={inputClass} style={inputStyle}>
              {SOURCES.map((s) => (
                <option key={s} value={s}>
                  {s.replace('_', ' ')}
                </option>
              ))}
            </select>
          </Field>
          <Field label="Deal value (₹)">
            <input
              type="number"
              min="0"
              step="1000"
              value={form.deal_value}
              onChange={(e) => setForm({ ...form, deal_value: Number(e.target.value) })}
              className={cx(inputClass, 'tnum')}
              style={inputStyle}
            />
          </Field>
        </div>
        {Object.entries(errors).map(([field, msgs]) => (
          <p key={field} className="text-xs" style={{ color: 'var(--critical-ink)' }}>
            {msgs[0]}
          </p>
        ))}
        <div className="flex justify-end gap-2 pt-1">
          <button type="button" onClick={onClose} className="rounded-lg px-4 py-2 text-sm" style={{ color: 'var(--text-secondary)' }}>
            Cancel
          </button>
          <PrimaryButton type="submit" disabled={busy}>
            {busy ? 'Saving…' : 'Create lead'}
          </PrimaryButton>
        </div>
      </form>
    </Modal>
  )
}

function LeadDetailModal({ leadId, onClose, onChanged }: { leadId: number; onClose: () => void; onChanged: (lead: Lead) => void }) {
  const [lead, setLead] = useState<Lead | null>(null)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    get<{ data: Lead }>(`/leads/${leadId}`).then((res) => setLead(res.data))
  }, [leadId])

  const convert = async () => {
    if (!lead) return
    setBusy(true)
    setError('')
    try {
      await post(`/leads/${lead.id}/convert`)
      const fresh = await get<{ data: Lead }>(`/leads/${lead.id}`)
      setLead(fresh.data)
      onChanged(fresh.data)
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Conversion failed.')
    } finally {
      setBusy(false)
    }
  }

  if (!lead) {
    return (
      <Modal title="Lead" onClose={onClose}>
        <Spinner />
      </Modal>
    )
  }

  const converted = lead.customer_id != null

  return (
    <Modal title={lead.name} onClose={onClose}>
      <div className="space-y-4 text-sm">
        <div className="flex flex-wrap items-center gap-2">
          <StageBadge stage={lead.stage} />
          <span style={{ color: 'var(--text-muted)' }}>·</span>
          <span className="capitalize" style={{ color: 'var(--text-secondary)' }}>
            {lead.source.replace('_', ' ')}
          </span>
          <span className="tnum ml-auto font-semibold" style={{ color: 'var(--text-primary)' }}>
            {money(lead.deal_value)}
          </span>
        </div>

        <dl className="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
          {[
            ['Company', lead.company],
            ['Email', lead.email],
            ['Phone', lead.phone],
            ['Owner', lead.owner?.name],
          ].map(([label, value]) => (
            <div key={label as string}>
              <dt style={{ color: 'var(--text-muted)' }}>{label}</dt>
              <dd style={{ color: 'var(--text-primary)' }}>{value ?? '—'}</dd>
            </div>
          ))}
        </dl>

        <div>
          <h3 className="mb-1.5 text-xs font-semibold uppercase tracking-wide" style={{ color: 'var(--text-muted)' }}>
            Stage history
          </h3>
          {lead.stage_history && lead.stage_history.length > 0 ? (
            <ul className="space-y-1.5">
              {lead.stage_history.map((h) => (
                <li key={h.id} className="flex items-center gap-2 text-xs" style={{ color: 'var(--text-secondary)' }}>
                  <span className="tnum shrink-0" style={{ color: 'var(--text-muted)' }}>
                    {shortDate(h.created_at)}
                  </span>
                  <span className="capitalize">{h.from_stage}</span>
                  <span aria-hidden>→</span>
                  <span className="capitalize font-medium" style={{ color: 'var(--text-primary)' }}>
                    {h.to_stage}
                  </span>
                  {h.changed_by && <span style={{ color: 'var(--text-muted)' }}>by {h.changed_by}</span>}
                </li>
              ))}
            </ul>
          ) : (
            <EmptyState message="No stage changes yet." />
          )}
        </div>

        {error && (
          <p className="rounded-lg px-3 py-2 text-xs" style={{ background: 'var(--critical-bg)', color: 'var(--critical-ink)' }}>
            {error}
          </p>
        )}

        <div className="flex justify-end gap-2 border-t pt-3" style={{ borderColor: 'var(--hairline)' }}>
          {converted ? (
            <span className="rounded-full px-3 py-1.5 text-xs font-medium" style={{ background: 'var(--good-bg)', color: 'var(--good-ink)' }}>
              Converted to customer
            </span>
          ) : (
            <PrimaryButton onClick={convert} disabled={busy}>
              {busy ? 'Converting…' : 'Convert to customer'}
            </PrimaryButton>
          )}
        </div>
      </div>
    </Modal>
  )
}
