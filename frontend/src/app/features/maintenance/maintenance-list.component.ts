import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonNote, IonIcon,
  IonRefresher, IonRefresherContent, IonSearchbar,
  AlertController,
} from '@ionic/angular/standalone';
import { MaintenanceService, MaintenanceWindow } from './maintenance.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { addIcons } from 'ionicons';
import { constructOutline } from 'ionicons/icons';

addIcons({ constructOutline });

@Component({
  selector: 'app-maintenance-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonNote, IonIcon,
    IonRefresher, IonRefresherContent, IonSearchbar,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Maintenance Windows</ion-title>
        <ion-buttons slot="end">
          <ion-button routerLink="/maintenance/new" fill="solid" color="primary" size="small">+ New Window</ion-button>
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

      @if (loading()) {
        <app-list-skeleton></app-list-skeleton>
      } @else {
      <ion-list>
        @for (item of items(); track item.id) {
          <ion-item-sliding>
            <ion-item [routerLink]="['/maintenance', item.public_id, 'edit']" detail>
              <ion-label>
                <h2>
                  {{ item.title }}
                  @if (item.is_recurring) {
                    <ion-badge color="tertiary" style="vertical-align: middle; margin-left: 6px; font-size: 0.6rem">Recurring</ion-badge>
                  }
                </h2>
                @if (item.is_recurring && hasRecurringSchedule(item)) {
                  <p>{{ getRecurringSummary(item) }}</p>
                } @else {
                  <p>{{ item.starts_at | date:'short' }} - {{ item.ends_at | date:'short' }}</p>
                }
                @if (item.description) {
                  <p style="font-size: 0.75rem; color: var(--ion-color-medium)">{{ item.description }}</p>
                }
              </ion-label>
              <ion-badge slot="end" [color]="getStatusColor(item.status)">
                {{ item.status | titlecase }}
              </ion-badge>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="primary" [routerLink]="['/maintenance', item.public_id, 'edit']">Edit</ion-item-option>
              <ion-item-option color="danger" (click)="onDelete(item)">Delete</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="construct-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No maintenance windows</h3>
            <p>Schedule maintenance to inform subscribers in advance.</p>
          </div>
        }
      </ion-list>
      }
    </ion-content>
  `,
  styles: [`
    .empty-state { text-align: center; padding: 3rem 1rem; color: var(--ion-color-medium); }
    .empty-state h3 { margin: 1rem 0 0.5rem; color: var(--ion-text-color); }
  `],
})
export class MaintenanceListComponent implements OnInit, ViewWillEnter {
  items = signal<MaintenanceWindow[]>([]);
  allItems = signal<MaintenanceWindow[]>([]);
  loading = signal(true);
  searchQuery = '';

  constructor(private service: MaintenanceService, private alertCtrl: AlertController) {}

  ngOnInit(): void { this.load(); }

  ionViewWillEnter(): void { this.load(); }

  load(): void {
    this.service.getAll().subscribe({
      next: (data) => {
        this.allItems.set(data.items);
        this.applyFilter();
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
      },
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

  applyFilter(): void {
    const query = this.searchQuery.toLowerCase().trim();
    if (!query) {
      this.items.set(this.allItems());
      return;
    }
    this.items.set(
      this.allItems().filter((item) =>
        item.title.toLowerCase().includes(query)
        || (item.description && item.description.toLowerCase().includes(query))
        || item.status.toLowerCase().includes(query)
      )
    );
  }

  async onDelete(item: MaintenanceWindow): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Maintenance Window',
      message: `Delete "${item.title}"?`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        { text: 'Delete', role: 'destructive', handler: () => {
          this.service.delete(item.public_id).subscribe(() => {
            this.allItems.update((list) => list.filter((i) => i.id !== item.id));
            this.items.update((list) => list.filter((i) => i.id !== item.id));
          });
        }},
      ],
    });
    await alert.present();
  }

  hasRecurringSchedule(item: MaintenanceWindow): boolean {
    return !!item.recurrence_time_start;
  }

  getRecurringSummary(item: MaintenanceWindow): string {
    const pattern = item.recurrence_pattern || 'weekly';
    const startTime = item.recurrence_time_start || '';
    const endTime = item.recurrence_time_end || '';

    let days = '';
    if (pattern !== 'daily' && item.recurrence_days) {
      try {
        const dayArr = typeof item.recurrence_days === 'string' ? JSON.parse(item.recurrence_days) : item.recurrence_days;
        const dayMap: Record<string, string> = { mon: 'Mon', tue: 'Tue', wed: 'Wed', thu: 'Thu', fri: 'Fri', sat: 'Sat', sun: 'Sun' };
        days = dayArr.map((d: string) => dayMap[d] || d).join(', ');
      } catch { days = ''; }
    }

    const freq = pattern === 'daily' ? 'Daily' : pattern === 'biweekly' ? 'Every 2 weeks' : pattern === 'monthly' ? 'Monthly' : 'Weekly';
    const daysPart = days ? ` on ${days}` : '';
    return `${freq}${daysPart}, ${startTime} - ${endTime}`;
  }

  getStatusColor(status: string): string {
    switch (status) {
      case 'scheduled': return 'primary';
      case 'in_progress': return 'warning';
      case 'completed': return 'success';
      case 'cancelled': return 'medium';
      default: return 'medium';
    }
  }
}
