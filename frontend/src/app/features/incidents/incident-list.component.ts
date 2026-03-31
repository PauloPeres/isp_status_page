import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonMenuButton,
  IonButtons,
  IonButton,
  IonSearchbar,
  IonList,
  IonItem,
  IonItemSliding,
  IonItemOptions,
  IonItemOption,
  IonLabel,
  IonBadge,
  IonChip,
  IonNote,
  IonIcon,
  IonRefresher,
  IonRefresherContent,
  IonInfiniteScroll,
  IonInfiniteScrollContent,
  IonSegment,
  IonSegmentButton,
  ToastController,
} from '@ionic/angular/standalone';
import { IncidentService } from './incident.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import {
  Incident,
  IncidentStatus,
  IncidentSeverity,
} from '../../core/models/incident.model';
import { addIcons } from 'ionicons';
import { warningOutline, checkmarkCircleOutline } from 'ionicons/icons';

addIcons({ warningOutline, checkmarkCircleOutline });

@Component({
  selector: 'app-incident-list',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    FormsModule,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonMenuButton,
    IonButtons,
    IonButton,
    IonSearchbar,
    IonList,
    IonItem,
    IonItemSliding,
    IonItemOptions,
    IonItemOption,
    IonLabel,
    IonBadge,
    IonChip,
    IonNote,
    IonIcon,
    IonRefresher,
    IonRefresherContent,
    IonInfiniteScroll,
    IonInfiniteScrollContent,
    IonSegment,
    IonSegmentButton,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button></ion-menu-button>
        </ion-buttons>
        <ion-title>Incidents</ion-title>
        <ion-buttons slot="end">
          <ion-button
            routerLink="/incidents/new"
            fill="solid"
            color="primary"
            size="small"
          >
            + New Incident
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
      <ion-toolbar>
        <ion-segment
          [(ngModel)]="statusFilter"
          (ionChange)="onFilterChange()"
        >
          <ion-segment-button value="all">
            <ion-label>All</ion-label>
          </ion-segment-button>
          <ion-segment-button value="active">
            <ion-label>Active</ion-label>
          </ion-segment-button>
          <ion-segment-button value="resolved">
            <ion-label>Resolved</ion-label>
          </ion-segment-button>
        </ion-segment>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          [(ngModel)]="searchQuery"
          (ionInput)="onSearch()"
          placeholder="Search incidents..."
          [debounce]="300"
        ></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <app-list-skeleton></app-list-skeleton>
      } @else {
      <ion-list>
        @for (incident of incidents(); track incident.id) {
          <ion-item-sliding>
            <ion-item
              [routerLink]="['/incidents', incident.id]"
              detail
            >
              <div slot="start" class="severity-indicator">
                <ion-badge
                  [color]="getSeverityColor(incident.severity)"
                  class="severity-dot"
                ></ion-badge>
              </div>
              <ion-label>
                <h2>{{ incident.title }}</h2>
                <p>
                  <ion-badge
                    [color]="getStatusColor(incident.status)"
                    style="font-size: 0.65rem; vertical-align: middle"
                  >
                    {{ incident.status }}
                  </ion-badge>
                  <ion-chip
                    [color]="getSeverityColor(incident.severity)"
                    size="small"
                    style="height: 20px; font-size: 0.65rem; margin-left: 4px"
                  >
                    {{ incident.severity }}
                  </ion-chip>
                  @if (incident.monitor?.name) {
                    <span class="monitor-name">
                      {{ incident.monitor?.name }}
                    </span>
                  }
                </p>
                @if (incident.acknowledged_at) {
                  <p class="ack-info">
                    <ion-icon
                      name="checkmark-circle-outline"
                      style="
                        font-size: 0.8rem;
                        vertical-align: middle;
                        color: var(--ion-color-success);
                      "
                    ></ion-icon>
                    Acknowledged
                    {{ incident.acknowledged_at | date: 'short' }}
                  </p>
                }
              </ion-label>
              <ion-note slot="end" style="font-size: 0.75rem">
                {{ incident.started_at | date: 'shortTime' }}
                <br />
                {{ incident.started_at | date: 'shortDate' }}
              </ion-note>
            </ion-item>

            <ion-item-options side="end">
              @if (!incident.acknowledged_at && incident.status !== 'resolved') {
                <ion-item-option
                  color="success"
                  (click)="onAcknowledge(incident)"
                >
                  Acknowledge
                </ion-item-option>
              }
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon
              name="warning-outline"
              style="font-size: 48px; color: var(--ion-color-medium)"
            ></ion-icon>
            <h3>No incidents</h3>
            <p>No incidents match the current filters.</p>
          </div>
        }
      </ion-list>
      }

      <ion-infinite-scroll (ionInfinite)="loadMore($event)">
        <ion-infinite-scroll-content
          loadingText="Loading..."
        ></ion-infinite-scroll-content>
      </ion-infinite-scroll>
    </ion-content>
  `,
  styles: [
    `
      .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--ion-color-medium);
      }
      .empty-state h3 {
        margin: 1rem 0 0.5rem;
        color: var(--ion-text-color);
      }
      .severity-indicator {
        display: flex;
        align-items: center;
      }
      .severity-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        --padding-start: 0;
        --padding-end: 0;
        min-width: 12px;
      }
      .monitor-name {
        margin-left: 8px;
        font-size: 0.75rem;
        color: var(--ion-color-medium);
      }
      .ack-info {
        font-size: 0.75rem;
        color: var(--ion-color-success);
      }
      ion-badge {
        text-transform: capitalize;
      }
    `,
  ],
})
export class IncidentListComponent implements OnInit, ViewWillEnter {
  incidents = signal<Incident[]>([]);
  loading = signal(true);
  searchQuery = '';
  statusFilter = 'all';
  page = 1;
  hasMore = true;

  constructor(
    private incidentService: IncidentService,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    this.loadIncidents();
  }

  ionViewWillEnter(): void {
    this.loadIncidents();
  }

  loadIncidents(append = false): void {
    const params: Record<string, any> = {
      page: this.page,
      limit: 25,
    };
    if (this.searchQuery) {
      params['search'] = this.searchQuery;
    }
    if (this.statusFilter === 'active') {
      params['status'] = 'investigating,identified,monitoring';
    } else if (this.statusFilter === 'resolved') {
      params['status'] = 'resolved';
    }

    this.incidentService.getIncidents(params).subscribe((data) => {
      if (append) {
        this.incidents.update((current) => [...current, ...data.items]);
      } else {
        this.incidents.set(data.items);
      }
      this.hasMore = this.page < data.pagination.pages;
      this.loading.set(false);
    });
  }

  onSearch(): void {
    this.page = 1;
    this.hasMore = true;
    this.loadIncidents();
  }

  onFilterChange(): void {
    this.page = 1;
    this.hasMore = true;
    this.loadIncidents();
  }

  onRefresh(event: any): void {
    this.page = 1;
    this.hasMore = true;
    const params: Record<string, any> = { page: 1, limit: 25 };
    if (this.searchQuery) {
      params['search'] = this.searchQuery;
    }
    if (this.statusFilter === 'active') {
      params['status'] = 'investigating,identified,monitoring';
    } else if (this.statusFilter === 'resolved') {
      params['status'] = 'resolved';
    }

    this.incidentService.getIncidents(params).subscribe({
      next: (data) => {
        this.incidents.set(data.items);
        this.hasMore = this.page < data.pagination.pages;
        event.target.complete();
      },
      error: () => {
        event.target.complete();
      },
    });
  }

  loadMore(event: any): void {
    if (!this.hasMore) {
      event.target.complete();
      return;
    }
    this.page++;
    const params: Record<string, any> = { page: this.page, limit: 25 };
    if (this.searchQuery) {
      params['search'] = this.searchQuery;
    }
    if (this.statusFilter === 'active') {
      params['status'] = 'investigating,identified,monitoring';
    } else if (this.statusFilter === 'resolved') {
      params['status'] = 'resolved';
    }

    this.incidentService.getIncidents(params).subscribe({
      next: (data) => {
        this.incidents.update((current) => [...current, ...data.items]);
        this.hasMore = this.page < data.pagination.pages;
        event.target.complete();
        if (!this.hasMore) {
          event.target.disabled = true;
        }
      },
      error: () => {
        event.target.complete();
      },
    });
  }

  onAcknowledge(incident: Incident): void {
    this.incidentService.acknowledgeIncident(incident.id).subscribe({
      next: async () => {
        this.incidents.update((list) =>
          list.map((i) =>
            i.id === incident.id
              ? {
                  ...i,
                  acknowledged_at: new Date().toISOString(),
                  acknowledged_via: 'web' as const,
                }
              : i,
          ),
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
}
