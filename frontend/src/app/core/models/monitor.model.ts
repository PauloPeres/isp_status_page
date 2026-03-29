export type MonitorType =
  | 'http'
  | 'ping'
  | 'port'
  | 'heartbeat'
  | 'keyword'
  | 'ssl'
  | 'api'
  | 'ixc_service'
  | 'ixc_equipment'
  | 'zabbix_host'
  | 'zabbix_trigger';

export type MonitorStatus = 'up' | 'down' | 'degraded' | 'unknown';

export interface Monitor {
  id: number;
  name: string;
  description?: string;
  type: MonitorType;
  configuration: any;
  check_interval: number;
  timeout: number;
  status: MonitorStatus;
  active: boolean;
  tags?: string[];
  uptime_percentage?: number;
  last_check_at?: string;
  organization_id: number;
  escalation_policy_id?: number;
  badge_token?: string;
  created: string;
  modified: string;
}
