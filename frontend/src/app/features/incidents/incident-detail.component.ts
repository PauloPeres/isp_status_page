import { Component, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonBackButton,
  IonButton,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonGrid,
  IonRow,
  IonCol,
  IonItem,
  IonBadge,
  IonChip,
  IonIcon,
  IonRefresher,
  IonRefresherContent,
  IonSkeletonText,
  IonTextarea,
  IonSelect,
  IonSelectOption,
  IonToggle,
  ToastController,
} from '@ionic/angular/standalone';
import { IncidentService } from './incident.service';
import {
  Incident,
  IncidentStatus,
  IncidentSeverity,
  IncidentTimelineEntry,
  NotificationTimelineEntry,
  VoiceCallLogEntry,
} from '../../core/models/incident.model';
import { addIcons } from 'ionicons';
import {
  checkmarkCircleOutline,
  timeOutline,
  alertCircleOutline,
  searchOutline,
  eyeOutline,
  shieldCheckmarkOutline,
  mailOutline,
  callOutline,
  notificationsOutline,
  chatbubbleOutline,
  megaphoneOutline,
  flagOutline,
} from 'ionicons/icons';

addIcons({
  checkmarkCircleOutline,
  timeOutline,
  alertCircleOutline,
  searchOutline,
  eyeOutline,
  shieldCheckmarkOutline,
  mailOutline,
  callOutline,
  notificationsOutline,
  chatbubbleOutline,
  megaphoneOutline,
  flagOutline,
});

@Component({
  selector: 'app-incident-detail',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonButtons,
    IonBackButton,
    IonButton,
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardContent,
    IonGrid,
    IonRow,
    IonCol,
    IonItem,
    IonBadge,
    IonChip,
    IonIcon,
    IonRefresher,
    IonRefresherContent,
    IonSkeletonText,
    IonTextarea,
    IonSelect,
    IonSelectOption,
    IonToggle,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/incidents"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ incident()?.title ?? 'Incident' }}</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <ion-card>
          <ion-card-content>
            <ion-skeleton-text
              [animated]="true"
              style="width: 60%; height: 1.5rem"
            ></ion-skeleton-text>
            <ion-skeleton-text
              [animated]="true"
              style="width: 40%; height: 1rem; margin-top: 8px"
            ></ion-skeleton-text>
          </ion-card-content>
        </ion-card>
      } @else if (incident()) {
        <!-- Info Card -->
        <ion-card>
          <ion-card-content>
            <div class="incident-header">
              <ion-badge
                [color]="getStatusColor(incident()!.status)"
                class="status-badge"
              >
                {{ incident()!.status }}
              </ion-badge>
              <ion-chip
                [color]="getSeverityColor(incident()!.severity)"
                style="height: 24px"
              >
                {{ incident()!.severity }}
              </ion-chip>
            </div>

            @if (incident()!.description) {
              <p class="description">{{ incident()!.description }}</p>
            }

            <ion-grid class="info-grid">
              <ion-row>
                <ion-col size="6">
                  <div class="info-label">Monitor</div>
                  <div class="info-value">
                    {{ incident()!.monitor?.name ?? 'N/A' }}
                  </div>
                </ion-col>
                <ion-col size="6">
                  <div class="info-label">Started</div>
                  <div class="info-value">
                    {{ incident()!.started_at | date: 'medium' }}
                  </div>
                </ion-col>
              </ion-row>
              <ion-row>
                <ion-col size="6">
                  <div class="info-label">Duration</div>
                  <div class="info-value">{{ getDuration() }}</div>
                </ion-col>
                <ion-col size="6">
                  @if (incident()!.resolved_at) {
                    <div class="info-label">Resolved</div>
                    <div class="info-value">
                      {{ incident()!.resolved_at | date: 'medium' }}
                    </div>
                  }
                </ion-col>
              </ion-row>
            </ion-grid>

            <!-- Acknowledge Section -->
            @if (incident()!.acknowledged_at) {
              <div class="ack-badge">
                <ion-icon
                  name="shield-checkmark-outline"
                  style="font-size: 1rem; vertical-align: middle"
                ></ion-icon>
                Acknowledged
                {{ incident()!.acknowledged_at | date: 'medium' }}
                @if (incident()!.acknowledged_via) {
                  via {{ incident()!.acknowledged_via }}
                }
              </div>
            } @else if (incident()!.status !== 'resolved') {
              <ion-button
                expand="block"
                fill="outline"
                color="success"
                (click)="onAcknowledge()"
                style="margin-top: 12px"
              >
                <ion-icon
                  slot="start"
                  name="checkmark-circle-outline"
                ></ion-icon>
                Acknowledge Incident
              </ion-button>
            }
          </ion-card-content>
        </ion-card>

        <!-- Post Update Form -->
        @if (incident()!.status !== 'resolved') {
          <ion-card>
            <ion-card-header>
              <ion-card-title>Post Update</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <ion-item>
                <ion-select
                  label="Status"
                  [(ngModel)]="updateStatus"
                  interface="popover"
                >
                  <ion-select-option value="investigating"
                    >Investigating</ion-select-option
                  >
                  <ion-select-option value="identified"
                    >Identified</ion-select-option
                  >
                  <ion-select-option value="monitoring"
                    >Monitoring</ion-select-option
                  >
                  <ion-select-option value="resolved"
                    >Resolved</ion-select-option
                  >
                </ion-select>
              </ion-item>
              <ion-item>
                <ion-textarea
                  label="Message"
                  [(ngModel)]="updateMessage"
                  placeholder="Describe the current situation..."
                  [autoGrow]="true"
                  [rows]="3"
                ></ion-textarea>
              </ion-item>
              <ion-item>
                <ion-toggle [(ngModel)]="updatePublic">
                  Visible on status page
                </ion-toggle>
              </ion-item>
              <ion-button
                expand="block"
                (click)="onPostUpdate()"
                [disabled]="!updateMessage.trim()"
                style="margin-top: 12px"
              >
                Post Update
              </ion-button>
            </ion-card-content>
          </ion-card>
        }

        <!-- Incident Timeline (audit trail) -->
        @if (auditTimeline().length > 0) {
          <ion-card>
            <ion-card-header>
              <ion-card-title>Incident Timeline</ion-card-title>
            </ion-card-header>
            <ion-card-content class="timeline-card-content">
              <div class="timeline">
                @for (event of auditTimeline(); track event.timestamp + event.type) {
                  <div class="timeline-entry">
                    <div class="timeline-dot-container">
                      <div
                        class="timeline-dot"
                        [style.background]="getAuditDotColor(event)"
                      ></div>
                      <div class="timeline-line"></div>
                    </div>
                    <div class="timeline-content">
                      <div class="timeline-header">
                        <ion-icon
                          [name]="getAuditIcon(event)"
                          style="font-size: 1rem; margin-right: 4px"
                        ></ion-icon>
                        <ion-badge
                          [color]="getAuditBadgeColor(event)"
                          style="font-size: 0.65rem"
                        >
                          {{ event.label }}
                        </ion-badge>
                        <span class="timeline-time">
                          {{ event.timestamp | date: 'medium' }}
                        </span>
                      </div>
                      <p class="timeline-message">{{ event.description }}</p>
                      @if (event.user) {
                        <p class="timeline-user">by {{ event.user }}</p>
                      }
                    </div>
                  </div>
                }
              </div>
            </ion-card-content>
          </ion-card>
        }

        <!-- Timeline -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Timeline</ion-card-title>
          </ion-card-header>
          <ion-card-content class="timeline-card-content">
            @if (incident()!.timeline && incident()!.timeline!.length > 0) {
              <div class="timeline">
                @for (
                  entry of incident()!.timeline;
                  track entry.id
                ) {
                  <div class="timeline-entry">
                    <div class="timeline-dot-container">
                      <div
                        class="timeline-dot"
                        [style.background]="getTimelineDotColor(entry)"
                      ></div>
                      <div class="timeline-line"></div>
                    </div>
                    <div class="timeline-content">
                      <div class="timeline-header">
                        @if (entry.status) {
                          <ion-badge
                            [color]="getStatusColor(entry.status)"
                            style="font-size: 0.65rem"
                          >
                            {{ entry.status }}
                          </ion-badge>
                        }
                        @if (entry.type && entry.type !== 'status_change') {
                          <ion-chip
                            color="medium"
                            style="height: 18px; font-size: 0.6rem"
                          >
                            {{ entry.type }}
                          </ion-chip>
                        }
                        <span class="timeline-time">
                          {{ entry.created | date: 'medium' }}
                        </span>
                      </div>
                      <p class="timeline-message">{{ entry.message }}</p>
                      @if (entry.user?.name) {
                        <p class="timeline-user">by {{ entry.user?.name }}</p>
                      }
                    </div>
                  </div>
                }
              </div>
            } @else {
              <p class="no-timeline">No timeline entries yet.</p>
            }
          </ion-card-content>
        </ion-card>
      }
    </ion-content>
  `,
  styles: [
    `
      .incident-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
      }
      .status-badge {
        font-size: 0.9rem;
        padding: 6px 16px;
        text-transform: capitalize;
      }
      .description {
        color: var(--ion-color-medium);
        margin: 8px 0 12px;
      }
      .info-grid {
        padding: 0;
      }
      .info-label {
        font-size: 0.75rem;
        color: var(--ion-color-medium);
        text-transform: uppercase;
        letter-spacing: 0.05em;
      }
      .info-value {
        font-size: 0.95rem;
        font-weight: 500;
        margin-top: 2px;
      }
      .ack-badge {
        margin-top: 12px;
        padding: 8px 12px;
        background: rgba(0, 200, 83, 0.1);
        color: var(--ion-color-success-shade);
        border: 1px solid rgba(0, 200, 83, 0.2);
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
      }

      /* Timeline */
      .timeline-card-content {
        padding: 16px 8px;
      }
      .timeline {
        position: relative;
      }
      .timeline-entry {
        display: flex;
        gap: 12px;
        min-height: 60px;
      }
      .timeline-dot-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-shrink: 0;
        width: 20px;
      }
      .timeline-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 2px;
      }
      .timeline-line {
        width: 2px;
        flex-grow: 1;
        background: var(--ion-color-light-shade);
        margin: 4px 0;
      }
      .timeline-entry:last-child .timeline-line {
        display: none;
      }
      .timeline-content {
        flex-grow: 1;
        padding-bottom: 16px;
      }
      .timeline-header {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
      }
      .timeline-time {
        font-size: 0.7rem;
        color: var(--ion-color-medium);
      }
      .timeline-message {
        margin: 4px 0 0;
        font-size: 0.9rem;
      }
      .timeline-user {
        font-size: 0.75rem;
        color: var(--ion-color-medium);
        margin: 2px 0 0;
      }
      .no-timeline {
        text-align: center;
        color: var(--ion-color-medium);
        padding: 1rem;
      }

      ion-badge {
        text-transform: capitalize;
      }
    `,
  ],
})
export class IncidentDetailComponent implements OnInit, ViewWillEnter {
  incident = signal<Incident | null>(null);
  loading = signal(true);

  auditTimeline = computed(() => {
    const inc = this.incident();
    if (!inc) return [];

    const events: AuditEvent[] = [];

    // Incident created
    events.push({
      timestamp: inc.started_at,
      type: 'created',
      label: 'Created',
      description: inc.auto_created
        ? 'Incident automatically created when monitor went down'
        : 'Incident manually created',
      icon: 'alert-circle-outline',
      color: 'danger',
      user: undefined,
    });

    // Notifications sent
    if (inc.notification_timeline) {
      for (const n of inc.notification_timeline) {
        const channelLabel = n.channel.charAt(0).toUpperCase() + n.channel.slice(1);
        events.push({
          timestamp: n.timestamp,
          type: 'notification',
          label: `${channelLabel} Sent`,
          description: `${channelLabel} notification to ${n.recipient} - ${n.status}`,
          icon: this.getChannelIcon(n.channel),
          color: n.status === 'sent' ? 'success' : n.status === 'failed' ? 'danger' : 'warning',
          user: n.user?.username,
        });
      }
    }

    // Voice call logs
    if (inc.voice_call_logs) {
      for (const v of inc.voice_call_logs) {
        let desc = `Voice call to ${v.phone_number} - ${v.status}`;
        if (v.dtmf_input === '1') desc += ' (acknowledged)';
        else if (v.dtmf_input === '2') desc += ' (escalated)';
        if (v.duration_seconds) desc += ` (${v.duration_seconds}s)`;
        events.push({
          timestamp: v.timestamp,
          type: 'voice_call',
          label: 'Voice Call',
          description: desc,
          icon: 'call-outline',
          color: v.status === 'completed' ? 'success' : v.status === 'failed' ? 'danger' : 'warning',
          user: undefined,
        });
      }
    }

    // Acknowledged
    if (inc.acknowledged_at) {
      const via = inc.acknowledged_via ?? 'unknown';
      const who = inc.acknowledged_by_user?.username;
      events.push({
        timestamp: inc.acknowledged_at,
        type: 'acknowledged',
        label: 'Acknowledged',
        description: `Incident acknowledged via ${via}`,
        icon: 'shield-checkmark-outline',
        color: 'success',
        user: who,
      });
    }

    // Resolved
    if (inc.resolved_at) {
      events.push({
        timestamp: inc.resolved_at,
        type: 'resolved',
        label: 'Resolved',
        description: `Incident resolved (duration: ${this.getDuration()})`,
        icon: 'checkmark-circle-outline',
        color: 'success',
        user: undefined,
      });
    }

    // Sort chronologically (oldest first)
    events.sort((a, b) => new Date(a.timestamp).getTime() - new Date(b.timestamp).getTime());

    return events;
  });

  updateStatus = 'investigating';
  updateMessage = '';
  updatePublic = true;

  private incidentId = '';

  constructor(
    private route: ActivatedRoute,
    private incidentService: IncidentService,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    this.incidentId = this.route.snapshot.paramMap.get('id') ?? '';
    this.loadData();
  }

  ionViewWillEnter(): void {
    this.loadData();
  }

  loadData(): void {
    this.loading.set(true);
    this.incidentService.getIncident(this.incidentId).subscribe({
      next: (data) => {
        this.incident.set(data);
        if (data.status !== 'resolved') {
          this.updateStatus = data.status;
        }
        this.loading.set(false);
      },
      error: async () => {
        this.loading.set(false);
        const toast = await this.toastCtrl.create({
          message: 'Failed to load incident details',
          duration: 4000,
          color: 'danger',
        });
        await toast.present();
      },
    });
  }

  onRefresh(event: any): void {
    this.incidentService.getIncident(this.incidentId).subscribe({
      next: (data) => {
        this.incident.set(data);
        event.target.complete();
      },
      error: async () => {
        event.target.complete();
        const toast = await this.toastCtrl.create({
          message: 'Failed to refresh incident details',
          duration: 4000,
          color: 'danger',
        });
        await toast.present();
      },
    });
  }

  onAcknowledge(): void {
    this.incidentService.acknowledgeIncident(this.incidentId).subscribe({
      next: async () => {
        this.incident.update((i) =>
          i
            ? {
                ...i,
                acknowledged_at: new Date().toISOString(),
                acknowledged_via: 'web' as const,
              }
            : i,
        );
        const toast = await this.toastCtrl.create({
          message: 'Incident acknowledged',
          duration: 2000,
          color: 'success',
        });
        await toast.present();
      },
      error: async (err: any) => {
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Failed to acknowledge incident',
          duration: 4000,
          color: 'danger',
        });
        await toast.present();
      },
    });
  }

  onPostUpdate(): void {
    if (!this.updateMessage.trim()) return;

    this.incidentService
      .addUpdate(this.incidentId, {
        status: this.updateStatus,
        message: this.updateMessage,
        is_public: this.updatePublic,
      })
      .subscribe({
        next: async (entry) => {
          this.incident.update((i) => {
            if (!i) return i;
            const timeline = [...(i.timeline ?? []), entry];
            return {
              ...i,
              status: (this.updateStatus as IncidentStatus) ?? i.status,
              timeline,
              resolved_at:
                this.updateStatus === 'resolved'
                  ? new Date().toISOString()
                  : i.resolved_at,
            };
          });
          this.updateMessage = '';
          const toast = await this.toastCtrl.create({
            message: 'Update posted',
            duration: 2000,
            color: 'success',
          });
          await toast.present();
        },
        error: async (err: any) => {
          const toast = await this.toastCtrl.create({
            message: err?.message || 'Failed to post update',
            duration: 4000,
            color: 'danger',
          });
          await toast.present();
        },
      });
  }

  getDuration(): string {
    const inc = this.incident();
    if (!inc) return 'N/A';
    const start = new Date(inc.started_at).getTime();
    const end = inc.resolved_at
      ? new Date(inc.resolved_at).getTime()
      : Date.now();
    const diff = end - start;

    const minutes = Math.floor(diff / 60000);
    if (minutes < 60) return `${minutes}m`;

    const hours = Math.floor(minutes / 60);
    const remainMinutes = minutes % 60;
    if (hours < 24) return `${hours}h ${remainMinutes}m`;

    const days = Math.floor(hours / 24);
    const remainHours = hours % 24;
    return `${days}d ${remainHours}h`;
  }

  getStatusColor(status: IncidentStatus): string {
    switch (status) {
      case 'investigating':
        return 'danger';
      case 'identified':
        return 'warning';
      case 'monitoring':
        return 'primary';
      case 'resolved':
        return 'success';
      default:
        return 'medium';
    }
  }

  getSeverityColor(severity: IncidentSeverity): string {
    switch (severity) {
      case 'critical':
        return 'danger';
      case 'major':
        return 'warning';
      case 'minor':
        return 'tertiary';
      case 'info':
        return 'medium';
      default:
        return 'medium';
    }
  }

  getChannelIcon(channel: string): string {
    switch (channel) {
      case 'email': return 'mail-outline';
      case 'sms': return 'chatbubble-outline';
      case 'voice_call': return 'call-outline';
      case 'whatsapp': return 'chatbubble-outline';
      case 'telegram': return 'chatbubble-outline';
      case 'slack': return 'megaphone-outline';
      case 'discord': return 'megaphone-outline';
      default: return 'notifications-outline';
    }
  }

  getAuditIcon(event: AuditEvent): string {
    return event.icon;
  }

  getAuditDotColor(event: AuditEvent): string {
    switch (event.color) {
      case 'danger': return 'var(--ion-color-danger)';
      case 'warning': return 'var(--ion-color-warning)';
      case 'success': return 'var(--ion-color-success)';
      case 'primary': return 'var(--ion-color-primary)';
      default: return 'var(--ion-color-medium)';
    }
  }

  getAuditBadgeColor(event: AuditEvent): string {
    return event.color;
  }

  getTimelineDotColor(entry: IncidentTimelineEntry): string {
    if (entry.status) {
      switch (entry.status) {
        case 'investigating':
          return 'var(--ion-color-danger)';
        case 'identified':
          return 'var(--ion-color-warning)';
        case 'monitoring':
          return 'var(--ion-color-primary)';
        case 'resolved':
          return 'var(--ion-color-success)';
      }
    }
    return 'var(--ion-color-medium)';
  }
}

interface AuditEvent {
  timestamp: string;
  type: string;
  label: string;
  description: string;
  icon: string;
  color: string;
  user?: string;
}
