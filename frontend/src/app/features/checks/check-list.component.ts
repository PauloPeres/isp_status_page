import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonMenuButton,
  IonButtons,
  IonList,
  IonItem,
  IonLabel,
  IonBadge,
  IonNote,
  IonIcon,
  IonRefresher,
  IonRefresherContent,
  IonInfiniteScroll,
  IonInfiniteScrollContent,
  IonSelect,
  IonSelectOption,
  IonSearchbar,
} from '@ionic/angular/standalone';
import { CheckService, CheckWithMonitor } from './check.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { MonitorService } from '../monitors/monitor.service';
import { Monitor } from '../../core/models/monitor.model';
import { addIcons } from 'ionicons';
import { pulseOutline } from 'ionicons/icons';

addIcons({ pulseOutline });

@Component({
  selector: 'app-check-list',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonMenuButton,
    IonButtons,
    IonList,
    IonItem,
    IonLabel,
    IonBadge,
    IonNote,
    IonIcon,
    IonRefresher,
    IonRefresherContent,
    IonInfiniteScroll,
    IonInfiniteScrollContent,
    IonSelect,
    IonSelectOption,
    IonSearchbar,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button></ion-menu-button>
        </ion-buttons>
        <ion-title>Checks</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-item lines="none">
          <ion-select
            label="Monitor"
            [(ngModel)]="monitorFilter"
            (ionChange)="onFilterChange()"
            interface="popover"
            placeholder="All monitors"
          >
            <ion-select-option [value]="0">All monitors</ion-select-option>
            @for (monitor of monitors(); track monitor.id) {
              <ion-select-option [value]="monitor.id">
                {{ monitor.name }}
              </ion-select-option>
            }
          </ion-select>
        </ion-item>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          [(ngModel)]="searchQuery"
          (ionInput)="onSearch()"
          placeholder="Search..."
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
        @for (check of checks(); track check.id) {
          <ion-item>
            <ion-badge
              [color]="getStatusColor(check.status)"
              slot="start"
              class="status-badge-dot"
            ></ion-badge>
            <ion-label>
              <h2>{{ check.monitor?.name ?? 'Monitor #' + check.monitor_id }}</h2>
              <p>
                <ion-badge
                  [color]="getStatusColor(check.status)"
                  style="font-size: 0.65rem; vertical-align: middle"
                >
                  {{ check.status }}
                </ion-badge>
                @if (check.response_time !== null && check.response_time !== undefined) {
                  <span class="response-time">
                    {{ check.response_time }}ms
                  </span>
                }
                @if (check.status_code) {
                  <span class="status-code">
                    HTTP {{ check.status_code }}
                  </span>
                }
              </p>
              @if (check.error_message) {
                <p class="error-text">{{ check.error_message }}</p>
              }
            </ion-label>
            <ion-note slot="end" style="font-size: 0.75rem">
              {{ check.checked_at | date: 'shortTime' }}
              <br />
              {{ check.checked_at | date: 'shortDate' }}
            </ion-note>
          </ion-item>
        } @empty {
          <div class="empty-state">
            <ion-icon
              name="pulse-outline"
              style="font-size: 48px; color: var(--ion-color-medium)"
            ></ion-icon>
            <h3>No checks recorded</h3>
            <p>Check data will appear here as monitors run.</p>
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
      .status-badge-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        --padding-start: 0;
        --padding-end: 0;
        min-width: 12px;
      }
      .response-time {
        margin-left: 8px;
        font-size: 0.8rem;
        color: var(--ion-color-medium);
        font-weight: 500;
      }
      .status-code {
        margin-left: 8px;
        font-size: 0.75rem;
        color: var(--ion-color-medium);
      }
      .error-text {
        color: var(--ion-color-danger);
        font-size: 0.75rem;
      }
      ion-badge {
        text-transform: capitalize;
      }
    `,
  ],
})
export class CheckListComponent implements OnInit, ViewWillEnter {
  checks = signal<CheckWithMonitor[]>([]);
  allChecks = signal<CheckWithMonitor[]>([]);
  monitors = signal<Monitor[]>([]);
  loading = signal(true);
  searchQuery = '';
  monitorFilter = 0;
  page = 1;
  hasMore = true;

  constructor(
    private checkService: CheckService,
    private monitorService: MonitorService,
  ) {}

  ngOnInit(): void {
    this.loadMonitors();
    this.loadChecks();
  }

  ionViewWillEnter(): void {
    this.loadMonitors();
    this.loadChecks();
  }

  loadMonitors(): void {
    this.monitorService
      .getMonitors({ limit: 100 })
      .subscribe((data) => {
        this.monitors.set(data.items);
      });
  }

  loadChecks(append = false): void {
    const params: Record<string, any> = {
      page: this.page,
      limit: 25,
    };
    if (this.monitorFilter) {
      params['monitor_id'] = this.monitorFilter;
    }

    this.checkService.getChecks(params).subscribe((data) => {
      if (append) {
        this.allChecks.update((current) => [...current, ...data.items]);
      } else {
        this.allChecks.set(data.items);
      }
      this.applyFilter();
      this.hasMore = this.page < data.pagination.pages;
      this.loading.set(false);
    });
  }

  onFilterChange(): void {
    this.page = 1;
    this.hasMore = true;
    this.loadChecks();
  }

  onRefresh(event: any): void {
    this.page = 1;
    this.hasMore = true;
    const params: Record<string, any> = { page: 1, limit: 25 };
    if (this.monitorFilter) {
      params['monitor_id'] = this.monitorFilter;
    }

    this.checkService.getChecks(params).subscribe({
      next: (data) => {
        this.allChecks.set(data.items);
        this.applyFilter();
        this.hasMore = this.page < data.pagination.pages;
        event.target.complete();
      },
      error: () => {
        event.target.complete();
      },
    });
  }

  onSearch(): void {
    this.applyFilter();
  }

  applyFilter(): void {
    const query = this.searchQuery.toLowerCase().trim();
    if (!query) {
      this.checks.set(this.allChecks());
      return;
    }
    this.checks.set(
      this.allChecks().filter((item) =>
        (item.monitor?.name ?? '').toLowerCase().includes(query) ||
        item.status.toLowerCase().includes(query)
      )
    );
  }

  loadMore(event: any): void {
    if (!this.hasMore) {
      event.target.complete();
      return;
    }
    this.page++;
    const params: Record<string, any> = { page: this.page, limit: 25 };
    if (this.monitorFilter) {
      params['monitor_id'] = this.monitorFilter;
    }

    this.checkService.getChecks(params).subscribe({
      next: (data) => {
        this.allChecks.update((current) => [...current, ...data.items]);
        this.applyFilter();
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

  getStatusColor(status: string): string {
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
