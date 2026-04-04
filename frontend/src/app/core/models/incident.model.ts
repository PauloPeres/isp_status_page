export type IncidentSeverity = 'critical' | 'major' | 'minor' | 'info';
export type IncidentStatus = 'investigating' | 'identified' | 'monitoring' | 'resolved';

export interface Incident {
  id: number;
  public_id: string;
  monitor_id: number;
  title: string;
  description?: string;
  severity: IncidentSeverity;
  status: IncidentStatus;
  started_at: string;
  resolved_at?: string;
  acknowledged_by_user_id?: number;
  acknowledged_at?: string;
  acknowledged_via?: 'email' | 'web' | 'telegram' | 'sms' | 'voice_call';
  acknowledged_by_user?: { id: number; username: string };
  auto_created?: boolean;
  organization_id: number;
  created: string;
  modified: string;
  monitor?: { id: number; name: string };
  timeline?: IncidentTimelineEntry[];
  notification_timeline?: NotificationTimelineEntry[];
  voice_call_logs?: VoiceCallLogEntry[];
}

export interface IncidentTimelineEntry {
  id: number;
  public_id: string;
  incident_id: number;
  type: string;
  status?: IncidentStatus;
  message: string;
  is_public?: boolean;
  user_id?: number;
  user?: { id: number; name: string };
  created: string;
}

export interface NotificationTimelineEntry {
  timestamp: string;
  type: 'notification_sent';
  channel: string;
  recipient: string;
  user?: { id: number; username: string } | null;
  status: string;
}

export interface VoiceCallLogEntry {
  timestamp: string;
  type: 'voice_call';
  phone_number: string;
  status: string;
  dtmf_input?: string | null;
  duration_seconds?: number | null;
  user_id?: number | null;
}
