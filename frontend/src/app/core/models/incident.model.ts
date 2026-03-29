export type IncidentSeverity = 'critical' | 'major' | 'minor' | 'info';
export type IncidentStatus = 'investigating' | 'identified' | 'monitoring' | 'resolved';

export interface Incident {
  id: number;
  monitor_id: number;
  title: string;
  description?: string;
  severity: IncidentSeverity;
  status: IncidentStatus;
  started_at: string;
  resolved_at?: string;
  acknowledged_by_user_id?: number;
  acknowledged_at?: string;
  acknowledged_via?: 'email' | 'web' | 'telegram' | 'sms';
  organization_id: number;
  created: string;
  modified: string;
  monitor?: { id: number; name: string };
  timeline?: IncidentTimelineEntry[];
}

export interface IncidentTimelineEntry {
  id: number;
  incident_id: number;
  type: string;
  status?: IncidentStatus;
  message: string;
  is_public?: boolean;
  user_id?: number;
  user?: { id: number; name: string };
  created: string;
}
