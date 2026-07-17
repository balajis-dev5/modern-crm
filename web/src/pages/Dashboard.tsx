import { useEffect, useState } from 'react'
import { get } from '../lib/api'
import type { DashboardData, FunnelRow, SourceRow } from '../lib/types'
import { money } from '../lib/utils'
import { Card, Spinner, StatCard } from '../components/ui'
import { FunnelChart, SourceBars, TrendChart } from '../components/charts'

export default function Dashboard() {
  const [data, setData] = useState<DashboardData | null>(null)
  const [funnel, setFunnel] = useState<FunnelRow[]>([])
  const [sources, setSources] = useState<SourceRow[]>([])

  useEffect(() => {
    let cancelled = false

    Promise.all([
      get<DashboardData>('/analytics/dashboard'),
      get<{ funnel: FunnelRow[] }>('/analytics/funnel'),
      get<{ sources: SourceRow[] }>('/analytics/sources'),
    ]).then(([dashboard, funnelRes, sourcesRes]) => {
      if (cancelled) return
      setData(dashboard)
      setFunnel(funnelRes.funnel)
      setSources(sourcesRes.sources)
    })

    return () => {
      cancelled = true
    }
  }, [])

  if (!data) return <Spinner />

  const { kpis } = data

  return (
    <div className="space-y-5">
      <h1 className="text-lg font-semibold" style={{ color: 'var(--text-primary)' }}>
        Dashboard
      </h1>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard label="New leads (30d)" value={String(kpis.new_leads_30d)} hint={`${kpis.total_leads} total`} />
        <StatCard label="Conversion rate" value={`${kpis.conversion_rate}%`} hint="won vs closed" />
        <StatCard label="Pipeline value" value={money(kpis.pipeline_value)} hint={`${money(kpis.won_value)} won`} />
        <StatCard
          label="Overdue follow-ups"
          value={String(kpis.overdue_follow_ups)}
          tone={kpis.overdue_follow_ups > 0 ? 'critical' : 'good'}
        />
      </div>

      <div className="grid gap-4 lg:grid-cols-5">
        <Card className="lg:col-span-3">
          <h2 className="mb-3 text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
            Leads per week
          </h2>
          <TrendChart data={data.trend} />
        </Card>
        <Card className="lg:col-span-2">
          <h2 className="mb-3 text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
            Conversion funnel
          </h2>
          <FunnelChart data={funnel} />
        </Card>
      </div>

      <Card>
        <h2 className="mb-3 text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>
          Source performance
        </h2>
        <SourceBars data={sources} />
      </Card>
    </div>
  )
}
