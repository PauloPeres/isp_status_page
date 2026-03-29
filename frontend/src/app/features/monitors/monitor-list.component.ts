import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
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
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button></ion-menu-button>
        </ion-buttons>
        <ion-title>Monitors</ion-title>
        <ion-buttons slot="end">
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

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

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
export class MonitorListComponent implements OnInit {
  monitors = signal<Monitor[]>([]);
  searchQuery = '';
  page = 1;
  hasMore = true;

  constructor(
    private monitorService: MonitorService,
    private alertCtrl: AlertController,
  ) {}

  ngOnInit(): void {
    this.loadMonitors();
  }

  loadMonitors(append = false): void {
    this.monitorService
      .getMonitors({
        page: this.page,
        limit: 25,
        search: this.searchQuery || undefined,
      })
      .subscribe((data) => {
        if (append) {
          this.monitors.update((current) => [...current, ...data.items]);
        } else {
          this.monitors.set(data.items);
        }
        this.hasMore = this.page < data.pagination.pages;
      });
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
