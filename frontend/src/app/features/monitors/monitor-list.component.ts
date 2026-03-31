import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
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
  AlertController,
} from '@ionic/angular/standalone';
import { MonitorService } from './monitor.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { Monitor, MonitorStatus } from '../../core/models/monitor.model';
import { addIcons } from 'ionicons';
import { pulseOutline } from 'ionicons/icons';

addIcons({ pulseOutline });

@Component({
  selector: 'app-monitor-list',
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
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button></ion-menu-button>
        </ion-buttons>
        <ion-title>Monitors</ion-title>
        <ion-buttons slot="end">
          <ion-button routerLink="/monitors/import" fill="clear" color="medium" title="Import monitors">
            Import
          </ion-button>
          <ion-button
            routerLink="/monitors/new"
            fill="solid"
            color="primary"
            size="small"
          >
            + New Monitor
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          [(ngModel)]="searchQuery"
          (ionInput)="onSearch()"
          placeholder="Search monitors..."
          [debounce]="300"
        ></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <div class="filter-chips" style="padding: 8px 16px; display: flex; gap: 6px; overflow-x: auto;">
      @for (f of statusFilters; track f.value) {
        <ion-chip [color]="statusFilter === f.value ? 'primary' : 'medium'" [outline]="statusFilter !== f.value" (click)="filterByStatus(f.value)" style="height: 28px; font-size: 0.75rem">
          {{ f.label }}
        </ion-chip>
      }
    </div>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <app-list-skeleton></app-list-skeleton>
      } @else {
      <ion-list>
        @for (monitor of monitors(); track monitor.id) {
          <ion-item-sliding>
            <ion-item [routerLink]="['/monitors', monitor.id]" detail>
              <ion-badge
                [color]="getStatusColor(monitor.status)"
                slot="start"
                style="
                  width: 12px;
                  height: 12px;
                  border-radius: 50%;
                  --padding-start: 0;
                  --padding-end: 0;
                  min-width: 12px;
                "
              ></ion-badge>
              <ion-label>
                <h2>{{ monitor.name }}</h2>
                <p>
                  <ion-chip
                    size="small"
                    color="medium"
                    style="height: 20px; font-size: 0.7rem"
                    >{{ monitor.type }}</ion-chip
                  >
                  @if (monitor.uptime_percentage) {
                    <span
                      style="
                        margin-left: 8px;
                        font-size: 0.8rem;
                        color: var(--ion-color-medium);
                      "
                    >
                      {{ monitor.uptime_percentage }}% uptime
                    </span>
                  }
                </p>
              </ion-label>
              <ion-note slot="end" style="font-size: 0.75rem">
                {{ monitor.last_check_at | date: 'shortTime' }}
              </ion-note>
            </ion-item>

            <ion-item-options side="end">
              @if (monitor.active) {
                <ion-item-option color="warning" (click)="onPause(monitor)">
                  Pause
                </ion-item-option>
              } @else {
                <ion-item-option color="success" (click)="onResume(monitor)">
                  Resume
                </ion-item-option>
              }
              <ion-item-option color="danger" (click)="onDelete(monitor)">
                Delete
              </ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon
              name="pulse-outline"
              style="font-size: 48px; color: var(--ion-color-medium)"
            ></ion-icon>
            <h3>No monitors yet</h3>
            <p>Create your first monitor to start tracking uptime.</p>
            <ion-button routerLink="/monitors/new" fill="outline">
              Add Monitor
            </ion-button>
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
    `,
  ],
})
export class MonitorListComponent implements ViewWillEnter {
  monitors = signal<Monitor[]>([]);
  loading = signal(true);
  searchQuery = '';
  page = 1;
  hasMore = true;
  statusFilter = '';
  statusFilters = [
    { label: 'All', value: '' },
    { label: 'Up', value: 'up' },
    { label: 'Down', value: 'down' },
    { label: 'Degraded', value: 'degraded' },
  ];

  constructor(
    private monitorService: MonitorService,
    private alertCtrl: AlertController,
    private route: ActivatedRoute,
  ) {}

  ionViewWillEnter(): void {
    const status = this.route.snapshot.queryParamMap.get('status');
    if (status) {
      this.statusFilter = status;
    }
    this.loadMonitors();
  }

  loadMonitors(append = false): void {
    this.monitorService
      .getMonitors({
        page: this.page,
        limit: 25,
        search: this.searchQuery || undefined,
        status: this.statusFilter || undefined,
      })
      .subscribe({
        next: (data) => {
          if (append) {
            this.monitors.update((current) => [...current, ...data.items]);
          } else {
            this.monitors.set(data.items);
          }
          this.hasMore = this.page < data.pagination.pages;
          this.loading.set(false);
        },
        error: () => {
          this.loading.set(false);
        },
      });
  }

  filterByStatus(status: string): void {
    this.statusFilter = status;
    this.page = 1;
    this.hasMore = true;
    this.loadMonitors();
  }

  onSearch(): void {
    this.page = 1;
    this.hasMore = true;
    this.loadMonitors();
  }

  onRefresh(event: any): void {
    this.page = 1;
    this.hasMore = true;
    this.monitorService
      .getMonitors({
        page: this.page,
        limit: 25,
        search: this.searchQuery || undefined,
        status: this.statusFilter || undefined,
      })
      .subscribe({
        next: (data) => {
          this.monitors.set(data.items);
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
    this.monitorService
      .getMonitors({
        page: this.page,
        limit: 25,
        search: this.searchQuery || undefined,
        status: this.statusFilter || undefined,
      })
      .subscribe({
        next: (data) => {
          this.monitors.update((current) => [...current, ...data.items]);
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

  onPause(monitor: Monitor): void {
    this.monitorService.pauseMonitor(monitor.id).subscribe(() => {
      this.monitors.update((list) =>
        list.map((m) => (m.id === monitor.id ? { ...m, active: false } : m)),
      );
    });
  }

  onResume(monitor: Monitor): void {
    this.monitorService.resumeMonitor(monitor.id).subscribe(() => {
      this.monitors.update((list) =>
        list.map((m) => (m.id === monitor.id ? { ...m, active: true } : m)),
      );
    });
  }

  async onDelete(monitor: Monitor): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Monitor',
      message: `Are you sure you want to delete "${monitor.name}"? This action cannot be undone.`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Delete',
          role: 'destructive',
          handler: () => {
            this.monitorService.deleteMonitor(monitor.id).subscribe(() => {
              this.monitors.update((list) =>
                list.filter((m) => m.id !== monitor.id),
              );
            });
          },
        },
      ],
    });
    await alert.present();
  }

  getStatusColor(status: MonitorStatus): string {
    switch (status) {
      case 'up':
        return 'success';
      case 'down':
        return 'danger';
      case 'degraded':
        return 'warning';
      default:
        return 'medium';
    }
  }
}
