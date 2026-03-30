import { Injectable, NgZone } from '@angular/core';
import { Observable } from 'rxjs';
import { AuthService } from './auth.service';
import { environment } from '../../../environments/environment';

export interface SseEvent {
  type: string;
  data: any;
}

@Injectable({ providedIn: 'root' })
export class SseService {
  private eventSource: EventSource | null = null;

  constructor(
    private auth: AuthService,
    private zone: NgZone,
  ) {}

  connect(): Observable<SseEvent> {
    return new Observable((observer) => {
      const token = this.auth.getAccessToken();
      if (!token) {
        observer.error('No auth token');
        return;
      }

      const url = `${environment.apiUrl}/events/stream?token=${token}`;
      this.eventSource = new EventSource(url);

      // Listen for specific event types
      const eventTypes = [
        'monitor_status',
        'incident_created',
        'incident_resolved',
        'check_completed',
        'heartbeat',
      ];

      for (const eventType of eventTypes) {
        this.eventSource.addEventListener(
          eventType,
          (event: MessageEvent) => {
            this.zone.run(() => {
              observer.next({ type: eventType, data: JSON.parse(event.data) });
            });
          },
        );
      }

      this.eventSource.onerror = () => {
        // EventSource auto-reconnects, but log it
        console.warn('SSE connection error, will retry...');
      };

      // Cleanup on unsubscribe
      return () => {
        this.eventSource?.close();
        this.eventSource = null;
      };
    });
  }

  disconnect(): void {
    this.eventSource?.close();
    this.eventSource = null;
  }
}
