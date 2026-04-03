export interface Sla {
  id: number;
  public_id: string;
  name: string;
  monitor_id: number;
  target_uptime: number;
  measurement_period: 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'yearly';
  current_uptime?: number;
  breach_count?: number;
  organization_id: number;
  created: string;
  modified: string;
}

export interface SlaReport {
  sla_id: number;
  period_start: string;
  period_end: string;
  uptime_percentage: number;
  downtime_minutes: number;
  breach: boolean;
  incidents_count: number;
}
