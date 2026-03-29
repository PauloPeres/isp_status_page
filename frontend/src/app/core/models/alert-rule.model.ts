export type AlertTriggerType = 'down' | 'up' | 'degraded' | 'any';
export type AlertChannel = 'email' | 'telegram' | 'sms' | 'webhook';

export interface AlertRule {
  id: number;
  name: string;
  monitor_id?: number;
  trigger_type: AlertTriggerType;
  channels: AlertChannel[];
  recipients: string[];
  cooldown_minutes: number;
  active: boolean;
  escalation_policy_id?: number;
  organization_id: number;
  created: string;
  modified: string;
}

export interface AlertLog {
  id: number;
  alert_rule_id: number;
  incident_id: number;
  channel: AlertChannel;
  recipient: string;
  status: 'sent' | 'failed';
  error_message?: string;
  created: string;
}
