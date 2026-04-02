import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ViewWillEnter } from '@ionic/angular';
import { ActivatedRoute, Router } from '@angular/router';
import {
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonMenuButton,
  IonButtons,
  IonButton,
  IonList,
  IonItem,
  IonLabel,
  IonBadge,
  IonNote,
  IonIcon,
  IonRefresher,
  IonRefresherContent,
  IonSelect,
  IonSelectOption,
  IonSegment,
  IonSegmentButton,
  IonChip,
} from '@ionic/angular/standalone';
import { CheckService, CheckWithMonitor } from './check.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { MonitorService } from '../monitors/monitor.service';
import { Monitor } from '../../core/models/monitor.model';
import { addIcons } from 'ionicons';
import {
  pulseOutline,
  chevronDownOutline,
  chevronUpOutline,
  chevronBackOutline,
  chevronForwardOutline,
  alertCircleOutline,
} from 'ionicons/icons';

addIcons({
  pulseOutline,
  chevronDownOutline,
  chevronUpOutline,
  chevronBackOutline,
  chevronForwardOutline,
  alertCircleOutline,
});

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
    IonButton,
    IonList,
    IonItem,
    IonLabel,
    IonBadge,
    IonNote,
    IonIcon,
    IonRefresher,
    IonRefresherContent,
    IonSelect,
    IonSelectOption,
    IonSegment,
    IonSegmentButton,
    IonChip,
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
        <div class="filter-row">
          <ion-item lines="none" class="filter-item">
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
          <div class="status-chips">
            <ion-chip
              [outline]="statusFilter !== ''"
              [color]="statusFilter === '' ? 'primary' : 'medium'"
              (click)="setStatus('')"
            >All</ion-chip>
            <ion-chip
              [outline]="statusFilter !== 'success'"
              [color]="statusFilter === 'success' ? 'success' : 'medium'"
              (click)="setStatus('success')"
            >
              <span class="chip-dot chip-dot-success"></span>
              Success
            </ion-chip>
            <ion-chip
              [outline]="statusFilter !== 'failure'"
              [color]="statusFilter === 'failure' ? 'danger' : 'medium'"
              (click)="setStatus('failure')"
            >
              <span class="chip-dot chip-dot-failure"></span>
              Failure
            </ion-chip>
            <ion-chip
              [outline]="statusFilter !== 'degraded'"
              [color]="statusFilter === 'degraded' ? 'warning' : 'medium'"
              (click)="setStatus('degraded')"
            >
              <span class="chip-dot chip-dot-degraded"></span>
              Degraded
            </ion-chip>
          </div>
        </div>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <!-- Date Range Filter -->
      <div class="date-filter-row">
        <div class="date-input-group">
          <label class="date-label">From</label>
          <input
            type="datetime-local"
            [(ngModel)]="fromDate"
            (change)="onFilterChange()"
            class="date-input"
          />
        </div>
        <div class="date-input-group">
          <label class="date-label">To</label>
          <input
            type="datetime-local"
            [(ngModel)]="toDate"
            (change)="onFilterChange()"
            class="date-input"
          />
        </div>
        @if (fromDate || toDate) {
          <ion-button fill="clear" size="small" (click)="clearDates()">Clear</ion-button>
        }
      </div>

      <!-- Status Timeline Chart -->
      @if (checks().length > 0) {
        <div class="status-chart">
          <div class="chart-header">
            <span class="chart-title">Status Timeline</span>
            <span class="chart-legend">
              <span class="legend-dot legend-success"></span> Success
              <span class="legend-dot legend-failure"></span> Failure
              <span class="legend-dot legend-degraded"></span> Degraded
            </span>
          </div>
          <div class="chart-bar">
            @for (check of checks(); track check.id) {
              <div
                class="chart-segment"
                [class.seg-success]="check.status === 'success' || check.status === 'up'"
                [class.seg-failure]="check.status === 'failure' || check.status === 'down'"
                [class.seg-degraded]="check.status === 'degraded'"
                [class.seg-unknown]="check.status === 'unknown'"
                [title]="check.monitor?.name + ' — ' + check.status + ' at ' + (check.checked_at | date:'short')"
                (click)="toggleExpand(check.id)"
              ></div>
            }
          </div>
        </div>
      }

      @if (loading()) {
        <app-list-skeleton></app-list-skeleton>
      } @else {
      <ion-list>
        @for (check of checks(); track check.id) {
          <ion-item
            [button]="true"
            [detail]="false"
            (click)="toggleExpand(check.id)"
            class="check-row"
          >
            <ion-badge
              [color]="getStatusColor(check.status)"
              slot="start"
              class="status-badge-dot"
            ></ion-badge>
            <ion-label>
              <h2>{{ check.monitor?.name ?? 'Monitor #' + check.monitor_id }}</h2>
              <p class="check-meta">
                <ion-badge
                  [color]="getStatusColor(check.status)"
                  class="status-text-badge"
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
                @if (check.error_message) {
                  <ion-icon
                    name="alert-circle-outline"
                    color="danger"
                    style="font-size: 14px; vertical-align: middle; margin-left: 4px"
                  ></ion-icon>
                }
              </p>
            </ion-label>
            <ion-note slot="end" class="check-time">
              {{ check.checked_at | date: 'shortTime' }}
              <br />
              {{ check.checked_at | date: 'shortDate' }}
            </ion-note>
          </ion-item>

          <!-- Expanded detail panel -->
          @if (expandedId === check.id) {
            <div class="check-detail">
              <div class="detail-grid">
                <div class="detail-item">
                  <span class="detail-label">Status</span>
                  <span class="detail-value">
                    <ion-badge [color]="getStatusColor(check.status)">{{ check.status }}</ion-badge>
                  </span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Response Time</span>
                  <span class="detail-value">{{ check.response_time !== null && check.response_time !== undefined ? check.response_time + 'ms' : '—' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">HTTP Status</span>
                  <span class="detail-value">{{ check.status_code ?? '—' }}</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Checked At</span>
                  <span class="detail-value">{{ check.checked_at | date: 'medium' }}</span>
                </div>
              </div>
              @if (check.error_message) {
                <div class="error-detail">
                  <span class="detail-label">Error</span>
                  <div class="error-message">{{ check.error_message }}</div>
                </div>
              }
              @if (check.metadata && hasMetadata(check.metadata)) {
                <div class="error-detail">
                  <span class="detail-label">Details</span>
                  <pre class="metadata-block">{{ check.metadata | json }}</pre>
                </div>
              }
            </div>
          }
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

      <!-- Pagination -->
      @if (totalPages() > 0) {
        <div class="pagination">
          <ion-button
            fill="clear"
            size="small"
            [disabled]="page <= 1"
            (click)="goToPage(page - 1)"
          >
            <ion-icon name="chevron-back-outline" slot="icon-only"></ion-icon>
          </ion-button>

          <span class="page-info">
            Page {{ page }} of {{ totalPages() }}
            <span class="total-count">({{ totalItems() }} checks)</span>
          </span>

          <ion-button
            fill="clear"
            size="small"
            [disabled]="page >= totalPages()"
            (click)="goToPage(page + 1)"
          >
            <ion-icon name="chevron-forward-outline" slot="icon-only"></ion-icon>
          </ion-button>
        </div>
      }
      }
    </ion-content>
  `,
  styles: [
    `
      .filter-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 8px;
      }
      .filter-item {
        flex: 0 1 auto;
        --padding-start: 12px;
        --inner-padding-end: 8px;
        max-width: 200px;
      }
      .status-chips {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        padding: 4px 0;
      }
      .status-chips ion-chip {
        cursor: pointer;
        height: 30px;
        font-size: 0.8rem;
        font-weight: 500;
      }
      .chip-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 4px;
      }
      .chip-dot-success { background: var(--ion-color-success); }
      .chip-dot-failure { background: var(--ion-color-danger); }
      .chip-dot-degraded { background: var(--ion-color-warning); }

      /* Date filter */
      .date-filter-row {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        padding: 12px 16px 8px;
        flex-wrap: wrap;
      }
      .date-input-group {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
        min-width: 140px;
      }
      .date-label {
        font-size: 0.7rem;
        color: var(--ion-color-medium);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
      }
      .date-input {
        padding: 6px 10px;
        border: 1px solid var(--ion-color-light-shade);
        border-radius: 6px;
        font-size: 0.85rem;
        background: var(--ion-item-background);
        color: var(--ion-text-color);
        font-family: inherit;
      }
      .date-input:focus {
        outline: none;
        border-color: var(--ion-color-primary);
      }

      /* Status timeline chart */
      .status-chart {
        padding: 8px 16px 12px;
      }
      .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
      }
      .chart-title {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--ion-color-medium);
        text-transform: uppercase;
        letter-spacing: 0.05em;
      }
      .chart-legend {
        font-size: 0.7rem;
        color: var(--ion-color-medium);
        display: flex;
        align-items: center;
        gap: 8px;
      }
      .legend-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 2px;
      }
      .legend-success { background: var(--ion-color-success); }
      .legend-failure { background: var(--ion-color-danger); }
      .legend-degraded { background: var(--ion-color-warning); }

      .chart-bar {
        display: flex;
        height: 36px;
        border-radius: 6px;
        overflow: hidden;
        gap: 1px;
        background: var(--ion-color-light-shade);
        cursor: pointer;
        border: 1px solid var(--ion-color-light-shade);
      }
      .chart-segment {
        flex: 1;
        min-width: 2px;
        transition: opacity 0.15s;
      }
      .chart-segment:hover {
        opacity: 0.7;
      }
      .seg-success { background: var(--ion-color-success); }
      .seg-failure { background: var(--ion-color-danger); }
      .seg-degraded { background: var(--ion-color-warning); }
      .seg-unknown { background: var(--ion-color-medium); }
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
      .status-text-badge {
        font-size: 0.65rem;
        vertical-align: middle;
        text-transform: capitalize;
      }
      .check-meta {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
      }
      .response-time {
        font-size: 0.8rem;
        color: var(--ion-color-medium);
        font-weight: 500;
        margin-left: 4px;
      }
      .status-code {
        font-size: 0.75rem;
        color: var(--ion-color-medium);
        margin-left: 4px;
      }
      .check-time {
        font-size: 0.75rem;
        text-align: right;
      }
      .check-row {
        cursor: pointer;
      }
      ion-badge {
        text-transform: capitalize;
      }

      /* Expanded detail panel */
      .check-detail {
        padding: 12px 16px 16px 44px;
        background: var(--ion-color-light);
        border-bottom: 1px solid var(--ion-color-light-shade);
      }
      .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 12px;
      }
      .detail-item {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      .detail-label {
        font-size: 0.7rem;
        color: var(--ion-color-medium);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
      }
      .detail-value {
        font-size: 0.9rem;
        font-weight: 500;
      }
      .error-detail {
        margin-top: 8px;
      }
      .error-message {
        margin-top: 4px;
        padding: 8px 12px;
        background: rgba(255, 23, 68, 0.06);
        border: 1px solid rgba(255, 23, 68, 0.15);
        border-radius: 6px;
        color: var(--ion-color-danger);
        font-size: 0.85rem;
        font-family: 'JetBrains Mono', monospace;
        word-break: break-all;
        white-space: pre-wrap;
      }
      .metadata-block {
        margin-top: 4px;
        padding: 8px 12px;
        background: var(--ion-color-light-shade);
        border-radius: 6px;
        font-size: 0.8rem;
        font-family: 'JetBrains Mono', monospace;
        overflow-x: auto;
        white-space: pre-wrap;
        word-break: break-all;
      }

      /* Pagination */
      .pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 16px;
      }
      .page-info {
        font-size: 0.85rem;
        color: var(--ion-color-medium);
      }
      .total-count {
        font-size: 0.75rem;
      }

      @media (max-width: 480px) {
        .detail-grid {
          grid-template-columns: 1fr;
        }
        .filter-row {
          flex-direction: column;
          align-items: stretch;
        }
        .filter-item {
          max-width: 100%;
        }
      }
    `,
  ],
})
export class CheckListComponent implements OnInit, ViewWillEnter {
  checks = signal<CheckWithMonitor[]>([]);
  monitors = signal<Monitor[]>([]);
  loading = signal(true);
  totalPages = signal(0);
  totalItems = signal(0);

  monitorFilter = 0;
  statusFilter = '';
  fromDate = '';
  toDate = '';
  page = 1;
  expandedId: number | null = null;

  constructor(
    private checkService: CheckService,
    private monitorService: MonitorService,
    private route: ActivatedRoute,
    private router: Router,
  ) {}

  ngOnInit(): void {
    // Read page from query params for shareable URLs
    const params = this.route.snapshot.queryParams;
    if (params['page']) this.page = +params['page'] || 1;
    if (params['monitor_id']) this.monitorFilter = +params['monitor_id'] || 0;
    if (params['status']) this.statusFilter = params['status'];
    if (params['from']) this.fromDate = params['from'];
    if (params['to']) this.toDate = params['to'];
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

  loadChecks(): void {
    this.loading.set(true);
    const params: Record<string, any> = {
      page: this.page,
      limit: 25,
    };
    if (this.monitorFilter) {
      params['monitor_id'] = this.monitorFilter;
    }
    if (this.statusFilter) {
      params['status'] = this.statusFilter;
    }
    if (this.fromDate) {
      params['from'] = this.fromDate;
    }
    if (this.toDate) {
      params['to'] = this.toDate;
    }

    this.checkService.getChecks(params).subscribe({
      next: (data) => {
        this.checks.set(data.items);
        this.totalPages.set(data.pagination.pages);
        this.totalItems.set(data.pagination.total);
        this.loading.set(false);
        this.updateUrl();
      },
      error: () => {
        this.loading.set(false);
      },
    });
  }

  clearDates(): void {
    this.fromDate = '';
    this.toDate = '';
    this.onFilterChange();
  }

  setStatus(status: string): void {
    this.statusFilter = status;
    this.onFilterChange();
  }

  onFilterChange(): void {
    this.page = 1;
    this.expandedId = null;
    this.loadChecks();
  }

  onRefresh(event: any): void {
    this.expandedId = null;
    const params: Record<string, any> = { page: this.page, limit: 25 };
    if (this.monitorFilter) params['monitor_id'] = this.monitorFilter;
    if (this.statusFilter) params['status'] = this.statusFilter;
    if (this.fromDate) params['from'] = this.fromDate;
    if (this.toDate) params['to'] = this.toDate;

    this.checkService.getChecks(params).subscribe({
      next: (data) => {
        this.checks.set(data.items);
        this.totalPages.set(data.pagination.pages);
        this.totalItems.set(data.pagination.total);
        event.target.complete();
      },
      error: () => {
        event.target.complete();
      },
    });
  }

  goToPage(p: number): void {
    if (p < 1 || p > this.totalPages()) return;
    this.page = p;
    this.expandedId = null;
    this.loadChecks();
  }

  toggleExpand(id: number): void {
    this.expandedId = this.expandedId === id ? null : id;
  }

  hasMetadata(metadata: any): boolean {
    return metadata && typeof metadata === 'object' && Object.keys(metadata).length > 0;
  }

  getStatusColor(status: string): string {
    switch (status) {
      case 'success':
      case 'up':
        return 'success';
      case 'failure':
      case 'down':
        return 'danger';
      case 'degraded':
        return 'warning';
      default:
        return 'medium';
    }
  }

  private updateUrl(): void {
    const queryParams: Record<string, any> = {};
    if (this.page > 1) queryParams['page'] = this.page;
    if (this.monitorFilter) queryParams['monitor_id'] = this.monitorFilter;
    if (this.statusFilter) queryParams['status'] = this.statusFilter;
    if (this.fromDate) queryParams['from'] = this.fromDate;
    if (this.toDate) queryParams['to'] = this.toDate;

    this.router.navigate([], {
      relativeTo: this.route,
      queryParams,
      queryParamsHandling: 'replace',
      replaceUrl: true,
    });
  }
}
