export interface Check {
  id: number;
  monitor_id: number;
  status: 'up' | 'down' | 'degraded' | 'unknown' | 'success' | 'failure';
  response_time?: number;
  status_code?: number;
  error_message?: string;
  metadata?: Record<string, any>;
  checked_at: string;
  created: string;
}
