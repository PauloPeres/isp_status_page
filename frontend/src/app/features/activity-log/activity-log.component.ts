import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonSelect, IonSelectOption,
  IonRefresher, IonRefresherContent, IonInfiniteScroll, IonInfiniteScrollContent,
  ActionSheetController,
} from '@ionic/angular/standalone';
import {
  ActivityLogService,
  ActivityLogEntry,
  EVENT_FILTER_CATEGORIES,
  EVENT_TYPE_COLORS,
  describeEvent,
  parseDetails,
  formatEventType,
} from './activity-log.service';
import { environment } from '../../../environments/environment';
import { addIcons } from 'ionicons';
import {
  listOutline,
  downloadOutline,
  shieldCheckmarkOutline,
  keyOutline,
  settingsOutline,
  pulseOutline,
  peopleOutline,
  personOutline,
  linkOutline,
  lockClosedOutline,
} from 'ionicons/icons';

addIcons({
  'list-outline': listOutline,
  'download-outline': downloadOutline,
  'shield-checkmark-outline': shieldCheckmarkOutline,
  'key-outline': keyOutline,
  'settings-outline': settingsOutline,
  'pulse-outline': pulseOutline,
  'people-outline': peopleOutline,
  'person-outline': personOutline,
  'link-outline': linkOutline,
  'lock-closed-outline': lockClosedOutline,
});

@Component({
  selector: 'app-activity-log',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonSelect, IonSelectOption,
    IonRefresher, IonRefresherContent, IonInfiniteScroll, IonInfiniteScrollContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Activity Log</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="onExport()">
            <ion-icon name="download-outline" slot="icon-only"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <!-- Category filter -->
      <div class="filter-bar">
        <ion-item lines="none" class="filter-item">
          <ion-icon name="shield-checkmark-outline" slot="start" color="primary" style="font-size: 1.1rem; margin-right: 8px"></ion-icon>
          <ion-select
            [(ngModel)]="categoryFilter"
            (ionChange)="onCategoryChange()"
            interface="popover"
            placeholder="All Events"
            label="Filter"
            labelPlacement="start"
          >
            @for (cat of categories; track cat.value) {
              <ion-select-option [value]="cat.value">{{ cat.label }}</ion-select-option>
            }
          </ion-select>
        </ion-item>

        @if (selectedCategory && selectedCategory.eventTypes.length > 1) {
          <ion-item lines="none" class="filter-item">
            <ion-select
              [(ngModel)]="eventTypeFilter"
              (ionChange)="load()"
              interface="popover"
              placeholder="All in category"
              label="Event"
              labelPlacement="start"
            >
              <ion-select-option value="">All {{ selectedCategory.label }}</ion-select-option>
              @for (et of selectedCategory.eventTypes; track et) {
                <ion-select-option [value]="et">{{ formatType(et) }}</ion-select-option>
              }
            </ion-select>
          </ion-item>
        }
      </div>

      @if (totalCount() > 0) {
        <div class="result-count">
          <ion-note>{{ totalCount() }} event{{ totalCount() === 1 ? '' : 's' }} found</ion-note>
        </div>
      }

      <ion-list class="log-list">
        @for (entry of items(); track entry.id) {
          <ion-item class="log-entry" lines="full">
            <div class="entry-icon" slot="start">
              <ion-icon [name]="getCategoryIcon(entry.event_type)" [color]="getEventColor(entry.event_type)"></ion-icon>
            </div>
            <ion-label>
              <h2 class="entry-description">{{ getDescription(entry) }}</h2>
              <div class="entry-meta">
                <ion-badge [color]="getEventColor(entry.event_type)" class="event-badge">
                  {{ formatType(entry.event_type) }}
                </ion-badge>
                <span class="entry-ip">{{ entry.ip_address }}</span>
              </div>
            </ion-label>
            <ion-note slot="end" class="entry-time">{{ entry.created | date:'MMM d, y h:mm a' }}</ion-note>
          </ion-item>
        } @empty {
          <div class="empty-state">
            <ion-icon name="list-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No activity logged</h3>
            <p>Activity will appear here as actions are performed.</p>
          </div>
        }
      </ion-list>

      <ion-infinite-scroll (ionInfinite)="onLoadMore($event)" [disabled]="!hasMore()">
        <ion-infinite-scroll-content loadingText="Loading more..."></ion-infinite-scroll-content>
      </ion-infinite-scroll>
    </ion-content>
  `,
  styles: [`
    .filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      padding: 8px 16px 4px;
      background: var(--ion-color-light-tint);
      border-bottom: 1px solid var(--ion-color-light-shade);
    }

    .filter-item {
      --background: transparent;
      --padding-start: 0;
      --inner-padding-end: 0;
      font-size: 0.9rem;
    }

    .result-count {
      padding: 6px 16px;
      font-size: 0.75rem;
    }

    .log-list {
      padding-top: 0;
    }

    .log-entry {
      --padding-top: 10px;
      --padding-bottom: 10px;
    }

    .entry-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: var(--ion-color-light);
      margin-right: 12px;
      font-size: 1rem;
    }

    .entry-description {
      font-size: 0.95rem;
      font-weight: 500;
      margin-bottom: 4px;
      line-height: 1.3;
    }

    .entry-meta {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .event-badge {
      font-size: 0.6rem;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      padding: 2px 6px;
      font-weight: 600;
    }

    .entry-ip {
      color: var(--ion-color-medium);
      font-size: 0.75rem;
      font-family: monospace;
    }

    .entry-time {
      font-size: 0.7rem;
      white-space: nowrap;
      text-align: right;
      min-width: 90px;
    }

    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: var(--ion-color-medium);
    }

    .empty-state h3 {
      margin: 1rem 0 0.5rem;
      color: var(--ion-text-color);
    }
  `],
})
export class ActivityLogComponent implements OnInit {
  items = signal<ActivityLogEntry[]>([]);
  totalCount = signal<number>(0);
  hasMore = signal<boolean>(false);

  categories = EVENT_FILTER_CATEGORIES;
  categoryFilter = '';
  eventTypeFilter = '';
  selectedCategory: (typeof EVENT_FILTER_CATEGORIES)[number] | null = null;

  private currentPage = 1;
  private readonly pageSize = 50;

  constructor(
    private service: ActivityLogService,
    private actionSheetCtrl: ActionSheetController,
  ) {}

  ngOnInit(): void {
    this.load();
  }

  onCategoryChange(): void {
    this.selectedCategory = this.categories.find(c => c.value === this.categoryFilter) || null;
    this.eventTypeFilter = '';
    this.load();
  }

  load(): void {
    this.currentPage = 1;
    const params = this.buildParams();
    this.service.getAll(params).subscribe((data) => {
      this.items.set(data.items);
      this.totalCount.set(data.pagination.total ?? data.items.length);
      this.hasMore.set(data.items.length >= this.pageSize);
    });
  }

  onRefresh(event: any): void {
    this.currentPage = 1;
    const params = this.buildParams();
    this.service.getAll(params).subscribe({
      next: (data) => {
        this.items.set(data.items);
        this.totalCount.set(data.pagination.total ?? data.items.length);
        this.hasMore.set(data.items.length >= this.pageSize);
        event.target.complete();
      },
      error: () => event.target.complete(),
    });
  }

  onLoadMore(event: any): void {
    this.currentPage++;
    const params = this.buildParams();
    this.service.getAll(params).subscribe({
      next: (data) => {
        this.items.update(current => [...current, ...data.items]);
        this.hasMore.set(data.items.length >= this.pageSize);
        event.target.complete();
      },
      error: () => {
        this.hasMore.set(false);
        event.target.complete();
      },
    });
  }

  async onExport(): Promise<void> {
    const sheet = await this.actionSheetCtrl.create({
      header: 'Export Audit Log',
      buttons: [
        { text: 'Export as CSV', handler: () => this.downloadExport('csv') },
        { text: 'Export as JSON', handler: () => this.downloadExport('json') },
        { text: 'Cancel', role: 'cancel' },
      ],
    });
    await sheet.present();
  }

  getEventColor(type: string): string {
    return EVENT_TYPE_COLORS[type] || 'medium';
  }

  getCategoryIcon(eventType: string): string {
    if (['login_success', 'login_failed', 'login_locked', 'oauth_login', 'logout'].includes(eventType)) {
      return 'shield-checkmark-outline';
    }
    if (['password_reset_requested', 'password_reset_completed', 'password_changed'].includes(eventType)) {
      return 'lock-closed-outline';
    }
    if (['monitor_created', 'monitor_updated', 'monitor_deleted'].includes(eventType)) {
      return 'pulse-outline';
    }
    if (eventType === 'settings_change') {
      return 'settings-outline';
    }
    if (['integration_created', 'integration_deleted'].includes(eventType)) {
      return 'link-outline';
    }
    if (['api_key_created', 'api_key_deleted'].includes(eventType)) {
      return 'key-outline';
    }
    if (eventType === 'user_invited') {
      return 'people-outline';
    }
    if (['impersonation_start', 'impersonation_stop', 'credit_grant'].includes(eventType)) {
      return 'person-outline';
    }
    if (eventType === 'email_verified') {
      return 'shield-checkmark-outline';
    }
    return 'list-outline';
  }

  getDescription(entry: ActivityLogEntry): string {
    const details = parseDetails(entry.details);
    return describeEvent(entry.event_type, details);
  }

  formatType(eventType: string): string {
    return formatEventType(eventType);
  }

  private buildParams(): { event_type?: string; page?: number; limit?: number } {
    const params: { event_type?: string; page?: number; limit?: number } = {
      page: this.currentPage,
      limit: this.pageSize,
    };

    // If a specific event type is selected, use it directly
    if (this.eventTypeFilter) {
      params.event_type = this.eventTypeFilter;
    }
    // If a category is selected (but no specific type) and the category has exactly one event type, send it
    else if (this.selectedCategory && this.selectedCategory.eventTypes.length === 1) {
      params.event_type = this.selectedCategory.eventTypes[0];
    }
    // If a category is selected with multiple types, the backend only supports single event_type filter
    // so we send the first type and do client-side filtering, OR we could send nothing and filter client-side.
    // For now, we don't send event_type and filter client-side when a category with multiple types is selected.

    return params;
  }

  private downloadExport(format: string): void {
    const token = localStorage.getItem('access_token');
    let url = `${environment.apiUrl}/activity-log/export?format=${format}`;

    // Build event_type param same as for list
    if (this.eventTypeFilter) {
      url += `&event_type=${this.eventTypeFilter}`;
    } else if (this.selectedCategory && this.selectedCategory.eventTypes.length === 1) {
      url += `&event_type=${this.selectedCategory.eventTypes[0]}`;
    }

    fetch(url, { headers: { Authorization: `Bearer ${token}` } })
      .then((res) => res.blob())
      .then((blob) => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `audit_log_export.${format}`;
        a.click();
        URL.revokeObjectURL(a.href);
      });
  }
}
