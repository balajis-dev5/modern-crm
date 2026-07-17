export const STAGES = ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'] as const
export type Stage = (typeof STAGES)[number]

export const SOURCES = ['website', 'referral', 'ads', 'cold_call', 'event'] as const
export type Source = (typeof SOURCES)[number]

export interface User {
  id: number
  name: string
  email: string
}

export interface StageHistory {
  id: number
  from_stage: Stage
  to_stage: Stage
  changed_by?: string
  created_at: string
}

export interface Lead {
  id: number
  name: string
  email: string | null
  phone: string | null
  company: string | null
  source: Source
  stage: Stage
  deal_value: number
  owner?: User
  stage_history?: StageHistory[]
  customer_id?: number | null
  created_at: string
}

export interface Customer {
  id: number
  lead_id: number | null
  name: string
  email: string | null
  phone: string | null
  company: string | null
  notes: string | null
  owner?: User
  follow_ups?: FollowUp[]
  open_follow_ups_count?: number
  created_at: string
}

export interface FollowUp {
  id: number
  title: string
  due_at: string
  done_at: string | null
  overdue: boolean
  customer?: { id: number; name: string } | null
  lead?: { id: number; name: string } | null
  assignee?: User
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
}

export interface DashboardData {
  kpis: {
    total_leads: number
    new_leads_30d: number
    conversion_rate: number
    pipeline_value: number
    won_value: number
    overdue_follow_ups: number
  }
  trend: { week: string; leads: number; won: number }[]
}

export interface FunnelRow {
  stage: string
  count: number
}

export interface SourceRow {
  source: string
  leads: number
  won: number
  won_value: number
}
