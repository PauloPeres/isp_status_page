import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

/**
 * Matches the backend SecurityAuditLog entity fields exactly.
 * Backend returns: id, event_type, user_id, ip_address, user_agent, details (JSON string), created
 */
export interface ActivityLogEntry {
  id: number;
  event_type: string;
  user_id: number | null;
  ip_address: string;
  user_agent: string | null;
  details: string | null;
  created: string;
}

/** Parsed details from the JSON string in the details field. */
export type ActivityLogDetails = Record<string, any>;

/** Filter category grouping event types together. */
export interface EventFilterCategory {
  label: string;
  value: string;
  eventTypes: string[];
}

/** All event type categories for the filter dropdown. */
export const EVENT_FILTER_CATEGORIES: EventFilterCategory[] = [
  { label: 'All Events', value: '', eventTypes: [] },
  { label: 'Authentication', value: 'auth', eventTypes: ['login_success', 'login_failed', 'login_locked', 'oauth_login', 'logout'] },
  { label: 'Passwords', value: 'passwords', eventTypes: ['password_reset_requested', 'password_reset_completed', 'password_changed'] },
  { label: 'Monitors', value: 'monitors', eventTypes: ['monitor_created', 'monitor_updated', 'monitor_deleted'] },
  { label: 'Settings', value: 'settings', eventTypes: ['settings_change'] },
  { label: 'Integrations', value: 'integrations', eventTypes: ['integration_created', 'integration_deleted'] },
  { label: 'API Keys', value: 'api_keys', eventTypes: ['api_key_created', 'api_key_deleted'] },
  { label: 'Team', value: 'team', eventTypes: ['user_invited'] },
  { label: 'Admin', value: 'admin', eventTypes: ['impersonation_start', 'impersonation_stop', 'credit_grant'] },
  { label: 'Account', value: 'account', eventTypes: ['email_verified'] },
];

/** Map of event_type -> color for Ionic badge styling. */
export const EVENT_TYPE_COLORS: Record<string, string> = {
  // Authentication
  login_success: 'success',
  login_failed: 'danger',
  login_locked: 'danger',
  oauth_login: 'success',
  logout: 'medium',
  // Passwords
  password_reset_requested: 'warning',
  password_reset_completed: 'tertiary',
  password_changed: 'tertiary',
  // Monitors
  monitor_created: 'primary',
  monitor_updated: 'secondary',
  monitor_deleted: 'danger',
  // Settings
  settings_change: 'warning',
  // Integrations
  integration_created: 'primary',
  integration_deleted: 'danger',
  // API Keys
  api_key_created: 'primary',
  api_key_deleted: 'danger',
  // Team
  user_invited: 'tertiary',
  // Admin
  impersonation_start: 'warning',
  impersonation_stop: 'medium',
  credit_grant: 'success',
  // Account
  email_verified: 'success',
};

/**
 * Generate a human-readable description from the event type and details.
 */
export function describeEvent(eventType: string, details: ActivityLogDetails | null): string {
  const d = details || {};
  switch (eventType) {
    // Authentication
    case 'login_success':
      return d['username'] ? `Successful login by ${d['username']}` : 'Successful login';
    case 'login_failed':
      return d['username'] ? `Failed login attempt for ${d['username']}` : 'Failed login attempt';
    case 'login_locked':
      return d['username'] ? `Account locked after failed attempts: ${d['username']}` : 'Account locked after failed attempts';
    case 'oauth_login':
      return d['provider'] ? `OAuth login via ${d['provider']}` : 'OAuth login';
    case 'logout':
      return 'User logged out';

    // Passwords
    case 'password_reset_requested':
      return d['email'] ? `Password reset requested for ${d['email']}` : 'Password reset requested';
    case 'password_reset_completed':
      return 'Password reset completed';
    case 'password_changed':
      return 'Password changed';

    // Monitors
    case 'monitor_created':
      return d['name'] ? `Created monitor: ${d['name']}` : 'Monitor created';
    case 'monitor_updated':
      return d['name'] ? `Updated monitor: ${d['name']}` : 'Monitor updated';
    case 'monitor_deleted':
      return d['name'] ? `Deleted monitor: ${d['name']}` : 'Monitor deleted';

    // Settings
    case 'settings_change': {
      if (d['keys'] && Array.isArray(d['keys'])) {
        return `Updated settings: ${d['keys'].join(', ')}`;
      }
      if (d['key']) {
        return `Updated setting: ${d['key']}`;
      }
      return 'Settings changed';
    }

    // Integrations
    case 'integration_created':
      return d['name'] ? `Created integration: ${d['name']}` : 'Integration created';
    case 'integration_deleted':
      return d['name'] ? `Deleted integration: ${d['name']}` : 'Integration deleted';

    // API Keys
    case 'api_key_created':
      return d['name'] ? `Created API key: ${d['name']}` : 'API key created';
    case 'api_key_deleted':
      return d['name'] ? `Deleted API key: ${d['name']}` : 'API key deleted';

    // Team
    case 'user_invited':
      return d['email'] ? `Invited user: ${d['email']}` : 'User invited';

    // Admin
    case 'impersonation_start':
      return d['target_user'] ? `Started impersonating ${d['target_user']}` : 'Impersonation started';
    case 'impersonation_stop':
      return 'Impersonation ended';
    case 'credit_grant':
      return d['amount'] ? `Granted ${d['amount']} credits` : 'Credits granted';

    // Account
    case 'email_verified':
      return d['email'] ? `Email verified: ${d['email']}` : 'Email verified';

    default:
      return formatEventTypeFallback(eventType);
  }
}

/**
 * Fallback: convert snake_case event type to a readable label.
 */
function formatEventTypeFallback(eventType: string): string {
  return eventType
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

/**
 * Safely parse the details JSON string.
 */
export function parseDetails(details: string | null): ActivityLogDetails | null {
  if (!details) return null;
  try {
    return typeof details === 'string' ? JSON.parse(details) : details;
  } catch {
    return null;
  }
}

/**
 * Format an event type for display in badges.
 */
export function formatEventType(eventType: string): string {
  return eventType.replace(/_/g, ' ');
}

@Injectable({ providedIn: 'root' })
export class ActivityLogService {
  constructor(private api: ApiService) {}

  getAll(params?: { event_type?: string; page?: number; limit?: number }): Observable<PaginatedResponse<ActivityLogEntry>> {
    return this.api.get<any>('/activity-log', params).pipe(
      map(data => ({
        items: data.activity_log || data.items || [],
        pagination: data.pagination || { page: 1, limit: 50, total: (data.activity_log || data.items || []).length, pages: 1 },
      }))
    );
  }
}
