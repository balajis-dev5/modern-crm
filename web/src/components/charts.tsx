import { useState } from 'react'
import type { FunnelRow, SourceRow } from '../lib/types'
import { money } from '../lib/utils'

/** Two-series SVG line chart with hover tooltip (leads vs won per week). */
export function TrendChart({ data }: { data: { week: string; leads: number; won: number }[] }) {
  const [hover, setHover] = useState<number | null>(null)

  const W = 560
  const H = 180
  const PAD = { top: 12, right: 12, bottom: 24, left: 32 }
  const max = Math.max(...data.map((d) => d.leads), 1)

  const x = (i: number) => PAD.left + (i / Math.max(data.length - 1, 1)) * (W - PAD.left - PAD.right)
  const y = (v: number) => PAD.top + (1 - v / max) * (H - PAD.top - PAD.bottom)

  const path = (key: 'leads' | 'won') =>
    data.map((d, i) => `${i === 0 ? 'M' : 'L'}${x(i).toFixed(1)},${y(d[key]).toFixed(1)}`).join(' ')

  const ticks = [0, Math.round(max / 2), max]

  return (
    <div className="relative">
      <svg viewBox={`0 0 ${W} ${H}`} className="w-full" role="img" aria-label="Weekly leads trend">
        {ticks.map((t) => (
          <g key={t}>
            <line x1={PAD.left} x2={W - PAD.right} y1={y(t)} y2={y(t)} stroke="var(--gridline)" strokeWidth="1" />
            <text x={PAD.left - 6} y={y(t) + 3} textAnchor="end" fontSize="10" fill="var(--text-muted)" className="tnum">
              {t}
            </text>
          </g>
        ))}
        <path d={path('leads')} fill="none" stroke="var(--chart-1)" strokeWidth="2" />
        <path d={path('won')} fill="none" stroke="var(--chart-2)" strokeWidth="2" />
        {data.map((d, i) => (
          <g key={d.week}>
            <rect
              x={x(i) - 18}
              y={0}
              width={36}
              height={H}
              fill="transparent"
              onMouseEnter={() => setHover(i)}
              onMouseLeave={() => setHover(null)}
            />
            {hover === i && (
              <>
                <line x1={x(i)} x2={x(i)} y1={PAD.top} y2={H - PAD.bottom} stroke="var(--baseline)" strokeWidth="1" />
                <circle cx={x(i)} cy={y(d.leads)} r="3.5" fill="var(--chart-1)" />
                <circle cx={x(i)} cy={y(d.won)} r="3.5" fill="var(--chart-2)" />
              </>
            )}
            <text x={x(i)} y={H - 8} textAnchor="middle" fontSize="10" fill="var(--text-muted)">
              {d.week}
            </text>
          </g>
        ))}
      </svg>
      {hover !== null && (
        <div
          className="pointer-events-none absolute rounded-lg border px-2.5 py-1.5 text-xs shadow-lg"
          style={{
            background: 'var(--surface-card)',
            borderColor: 'var(--hairline)',
            left: `${(x(hover) / W) * 100}%`,
            top: 0,
            transform: hover > data.length / 2 ? 'translateX(-110%)' : 'translateX(10%)',
          }}
        >
          <p className="font-medium" style={{ color: 'var(--text-primary)' }}>{data[hover].week}</p>
          <p style={{ color: 'var(--chart-1)' }}>{data[hover].leads} leads</p>
          <p style={{ color: 'var(--chart-2)' }}>{data[hover].won} won</p>
        </div>
      )}
      <div className="mt-1 flex gap-4 text-xs" style={{ color: 'var(--text-muted)' }}>
        <span className="flex items-center gap-1.5">
          <span className="h-0.5 w-4 rounded" style={{ background: 'var(--chart-1)' }} /> New leads
        </span>
        <span className="flex items-center gap-1.5">
          <span className="h-0.5 w-4 rounded" style={{ background: 'var(--chart-2)' }} /> Won
        </span>
      </div>
    </div>
  )
}

/** Horizontal funnel: full-width bars shrinking per stage, count + drop-off %. */
export function FunnelChart({ data }: { data: FunnelRow[] }) {
  const top = data[0]?.count ?? 1

  return (
    <div className="space-y-2">
      {data.map((row, i) => {
        const pct = top > 0 ? row.count / top : 0
        return (
          <div key={row.stage} className="flex items-center gap-3">
            <span className="w-20 text-xs capitalize" style={{ color: 'var(--text-secondary)' }}>
              {row.stage}
            </span>
            <div className="h-6 flex-1 rounded" style={{ background: 'var(--surface-page)' }}>
              <div
                className="flex h-6 min-w-8 items-center rounded px-2"
                style={{ width: `${Math.max(pct * 100, 6)}%`, background: `var(--chart-${i + 1})` }}
              >
                <span className="tnum text-xs font-medium text-white">{row.count}</span>
              </div>
            </div>
            <span className="tnum w-12 text-right text-xs" style={{ color: 'var(--text-muted)' }}>
              {Math.round(pct * 100)}%
            </span>
          </div>
        )
      })}
    </div>
  )
}

/** Source performance: volume bars with win-rate labels. */
export function SourceBars({ data }: { data: SourceRow[] }) {
  const max = Math.max(...data.map((d) => d.leads), 1)

  return (
    <div className="space-y-2.5">
      {data.map((row) => (
        <div key={row.source} className="flex items-center gap-3">
          <span className="w-20 text-xs capitalize" style={{ color: 'var(--text-secondary)' }}>
            {row.source.replace('_', ' ')}
          </span>
          <div className="h-5 flex-1 rounded" style={{ background: 'var(--surface-page)' }}>
            <div
              className="h-5 rounded"
              style={{ width: `${(row.leads / max) * 100}%`, background: 'var(--chart-1)' }}
            />
          </div>
          <span className="tnum w-28 text-right text-xs" style={{ color: 'var(--text-muted)' }}>
            {row.won}/{row.leads} won · {money(row.won_value)}
          </span>
        </div>
      ))}
    </div>
  )
}
