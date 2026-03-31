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
import { ribbonOutline } from 'ionicons/icons';

addIcons({ ribbonOutline });

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
            <ion-item [routerLink]="['/sla', item.id]" detail>
              <ion-label>
                <h2>{{ item.name }}</h2>
                <p>
                  Target: {{ item.target_uptime }}%
                  @if (item.actual_uptime != null) {
                    | Actual: {{ item.actual_uptime }}%
                  }
                  @if (item.monitor_name) {
                    <span style="margin-left: 8px; color: var(--ion-color-medium)">{{ item.monitor_name }}</span>
                  }
                </p>
                <p style="font-size: 0.7rem; margin-top: 2px; color: var(--ion-color-medium)">
                  {{ item.measurement_period | titlecase }}
                </p>
              </ion-label>
              <ion-badge slot="end" [color]="getStatusColor(item.status)">
                {{ item.status }}
              </ion-badge>
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
}
