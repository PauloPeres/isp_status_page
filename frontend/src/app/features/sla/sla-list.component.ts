import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonNote, IonIcon, IonProgressBar,
  IonRefresher, IonRefresherContent, IonSearchbar, IonChip,
  AlertController,
} from '@ionic/angular/standalone';
import { SlaService, Sla } from './sla.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { addIcons } from 'ionicons';
import { ribbonOutline, shieldCheckmarkOutline, warningOutline, alertCircleOutline } from 'ionicons/icons';

addIcons({ ribbonOutline, shieldCheckmarkOutline, warningOutline, alertCircleOutline });

@Component({
  selector: 'app-sla-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonNote, IonIcon, IonProgressBar,
    IonRefresher, IonRefresherContent, IonSearchbar, IonChip,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>SLA</ion-title>
        <ion-buttons slot="end">
          <ion-button routerLink="/sla/new" fill="solid" color="primary" size="small">+ New SLA</ion-button>
        </ion-buttons>
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

      <div style="padding: 8px 16px; display: flex; gap: 6px; overflow-x: auto;">
        @for (f of statusFilters; track f.value) {
          <ion-chip [color]="statusFilter === f.value ? f.color : 'medium'" [outline]="statusFilter !== f.value" (click)="filterByStatus(f.value)" style="height: 28px; font-size: 0.75rem">
            {{ f.label }}
          </ion-chip>
        }
      </div>

      @if (loading()) {
        <app-list-skeleton></app-list-skeleton>
      } @else {
      <ion-list>
        @for (item of items(); track item.id) {
          <ion-item-sliding>
            <ion-item [routerLink]="['/sla', item.id]" detail class="sla-list-item">
              <div class="sla-row">
                <div class="sla-row-main">
                  <div class="sla-row-top">
                    <h2 class="sla-name">{{ item.name }}</h2>
                    <div class="sla-status-badge"
                         [class.badge-compliant]="item.status === 'compliant'"
                         [class.badge-at-risk]="item.status === 'at_risk'"
                         [class.badge-breached]="item.status === 'breached'"
                         [class.badge-unknown]="!item.status">
                      @if (item.status === 'compliant') {
                        <ion-icon name="shield-checkmark-outline"></ion-icon>
                        <span>Compliant</span>
                      } @else if (item.status === 'at_risk') {
                        <ion-icon name="warning-outline"></ion-icon>
                        <span>At Risk</span>
                      } @else if (item.status === 'breached') {
                        <ion-icon name="alert-circle-outline"></ion-icon>
                        <span>Breached</span>
                      } @else {
                        <span>Unknown</span>
                      }
                    </div>
                  </div>
                  @if (item.monitor_name) {
                    <div class="sla-monitor-name">{{ item.monitor_name }}</div>
                  }
                  <div class="sla-row-metrics">
                    <div class="sla-uptime-section">
                      <span class="sla-uptime-label">Actual</span>
                      <span class="sla-uptime-value"
                            [class.uptime-green]="item.status === 'compliant'"
                            [class.uptime-amber]="item.status === 'at_risk'"
                            [class.uptime-red]="item.status === 'breached'">
                        {{ item.actual_uptime != null ? (item.actual_uptime | number:'1.3-3') + '%' : 'N/A' }}
                      </span>
                      <span class="sla-target-label">/ {{ item.target_uptime }}% target</span>
                    </div>
                    <div class="sla-budget-bar">
                      <div class="sla-budget-track">
                        <div class="sla-budget-fill"
                             [style.width.%]="getBudgetPct(item)"
                             [class.fill-green]="getBudgetPct(item) <= 50"
                             [class.fill-amber]="getBudgetPct(item) > 50 && getBudgetPct(item) <= 80"
                             [class.fill-red]="getBudgetPct(item) > 80">
                        </div>
                      </div>
                      <span class="sla-budget-label">{{ getBudgetPct(item) | number:'1.0-0' }}% budget used</span>
                    </div>
                  </div>
                  <div class="sla-row-period">
                    {{ item.measurement_period | titlecase }}
                  </div>
                </div>
              </div>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="primary" [routerLink]="['/sla', item.id, 'edit']">Edit</ion-item-option>
              <ion-item-option color="danger" (click)="onDelete(item)">Delete</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="ribbon-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No SLA definitions</h3>
            <p>Define SLA targets to track uptime commitments.</p>
            <ion-button routerLink="/sla/new" fill="outline">Add SLA</ion-button>
          </div>
        }
      </ion-list>
      }
    </ion-content>
  `,
  styles: [`
    .empty-state { text-align: center; padding: 3rem 1rem; color: var(--ion-color-medium); }
    .empty-state h3 { margin: 1rem 0 0.5rem; color: var(--ion-text-color); }

    .sla-list-item {
      --padding-start: 16px;
      --padding-end: 8px;
      --inner-padding-end: 8px;
    }

    .sla-row {
      width: 100%;
      padding: 10px 0;
    }

    .sla-row-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }

    .sla-name {
      font-size: 1rem;
      font-weight: 700;
      color: var(--ion-text-color);
      margin: 0;
      flex: 1;
      min-width: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    /* Status badges */
    .sla-status-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.03em;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .sla-status-badge ion-icon { font-size: 0.85rem; }

    .badge-compliant {
      background: rgba(0, 200, 83, 0.12);
      color: #00C853;
    }
    .badge-at-risk {
      background: rgba(255, 234, 0, 0.15);
      color: #F9A825;
    }
    .badge-breached {
      background: rgba(255, 23, 68, 0.1);
      color: #FF1744;
    }
    .badge-unknown {
      background: var(--ion-color-light);
      color: var(--ion-color-medium);
    }

    .sla-monitor-name {
      font-size: 0.75rem;
      color: var(--ion-color-medium);
      margin-top: 2px;
      font-weight: 500;
    }

    .sla-row-metrics {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-top: 8px;
    }

    .sla-uptime-section {
      display: flex;
      align-items: baseline;
      gap: 4px;
      flex-shrink: 0;
    }

    .sla-uptime-label {
      font-size: 0.68rem;
      color: var(--ion-color-medium);
      text-transform: uppercase;
      font-weight: 600;
    }

    .sla-uptime-value {
      font-family: 'DM Sans', monospace;
      font-size: 0.95rem;
      font-weight: 700;
    }
    .uptime-green { color: #00C853; }
    .uptime-amber { color: #F9A825; }
    .uptime-red { color: #FF1744; }

    .sla-target-label {
      font-size: 0.68rem;
      color: var(--ion-color-medium);
    }

    /* Budget progress bar */
    .sla-budget-bar {
      flex: 1;
      min-width: 0;
    }

    .sla-budget-track {
      height: 6px;
      background: var(--ion-color-light-shade);
      border-radius: 3px;
      overflow: hidden;
    }

    .sla-budget-fill {
      height: 100%;
      border-radius: 3px;
      transition: width 0.3s ease;
    }
    .fill-green { background: #00C853; }
    .fill-amber { background: #F9A825; }
    .fill-red { background: #FF1744; }

    .sla-budget-label {
      font-size: 0.65rem;
      color: var(--ion-color-medium);
      margin-top: 2px;
      display: block;
    }

    .sla-row-period {
      font-size: 0.68rem;
      margin-top: 6px;
      color: var(--ion-color-medium);
      font-weight: 500;
    }
  `],
})
export class SlaListComponent implements OnInit, ViewWillEnter {
  items = signal<Sla[]>([]);
  allItems = signal<Sla[]>([]);
  loading = signal(true);
  searchQuery = '';
  statusFilter = '';

  statusFilters = [
    { label: 'All', value: '', color: 'primary' },
    { label: 'Compliant', value: 'compliant', color: 'success' },
    { label: 'At Risk', value: 'at_risk', color: 'warning' },
    { label: 'Breached', value: 'breached', color: 'danger' },
  ];

  constructor(private service: SlaService, private alertCtrl: AlertController, private route: ActivatedRoute) {}

  ngOnInit(): void {
    const status = this.route.snapshot.queryParamMap.get('status');
    if (status) {
      this.statusFilter = status;
    }
  }

  ionViewWillEnter(): void {
    const status = this.route.snapshot.queryParamMap.get('status');
    if (status) {
      this.statusFilter = status;
    }
    this.load();
  }

  load(): void {
    this.service.getAll().subscribe({
      next: (data) => {
        this.allItems.set(data.items);
        this.applyFilter();
        this.loading.set(false);
      },
      error: () => { this.loading.set(false); },
    });
  }

  onRefresh(event: any): void {
    this.service.getAll().subscribe({
      next: (data) => {
        this.allItems.set(data.items);
        this.applyFilter();
        event.target.complete();
      },
      error: () => event.target.complete(),
    });
  }

  onSearch(): void {
    this.applyFilter();
  }

  filterByStatus(status: string): void {
    this.statusFilter = status;
    this.applyFilter();
  }

  applyFilter(): void {
    let filtered = this.allItems();

    if (this.statusFilter) {
      filtered = filtered.filter((item) => item.status === this.statusFilter);
    }

    const query = this.searchQuery.toLowerCase().trim();
    if (query) {
      filtered = filtered.filter((item) =>
        item.name.toLowerCase().includes(query)
      );
    }

    this.items.set(filtered);
  }

  async onDelete(item: Sla): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete SLA',
      message: `Delete "${item.name}"?`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        { text: 'Delete', role: 'destructive', handler: () => {
          this.service.delete(item.id).subscribe(() => {
            this.allItems.update((list) => list.filter((i) => i.id !== item.id));
            this.items.update((list) => list.filter((i) => i.id !== item.id));
          });
        }},
      ],
    });
    await alert.present();
  }

  getStatusColor(status: string | undefined): string {
    switch (status) {
      case 'compliant':
      case 'met': return 'success';
      case 'at_risk': return 'warning';
      case 'breached': return 'danger';
      default: return 'medium';
    }
  }

  getBudgetPct(item: any): number {
    // Use pre-computed value from API if available
    if (item.budget_used_pct != null) return item.budget_used_pct;
    if (item.actual_uptime == null || item.target_uptime == null) return 0;
    const allowedDowntimePct = 100 - item.target_uptime;
    if (allowedDowntimePct <= 0) return item.actual_uptime < 100 ? 100 : 0;
    const actualDowntimePct = 100 - item.actual_uptime;
    return Math.min(Math.max((actualDowntimePct / allowedDowntimePct) * 100, 0), 100);
  }
}
