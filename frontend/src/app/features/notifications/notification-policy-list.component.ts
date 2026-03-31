import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonIcon,
  IonRefresher, IonRefresherContent, IonSearchbar,
  AlertController,
} from '@ionic/angular/standalone';
import { NotificationPolicyService, NotificationPolicy } from './notification-policy.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { addIcons } from 'ionicons';
import { notificationsOutline } from 'ionicons/icons';

addIcons({ notificationsOutline });

@Component({
  selector: 'app-notification-policy-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonIcon,
    IonRefresher, IonRefresherContent, IonSearchbar,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Notification Policies</ion-title>
        <ion-buttons slot="end">
          <ion-button routerLink="/notifications/new" fill="solid" color="primary" size="small">+ New Policy</ion-button>
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
            <ion-item [routerLink]="['/notifications', item.id, 'edit']" detail>
              <ion-label>
                <h2>{{ item.name }}</h2>
                <p>
                  <ion-badge [color]="getTriggerColor(item.trigger_type)" style="margin-right: 4px">
                    {{ item.trigger_type }}
                  </ion-badge>
                  <ion-badge color="tertiary" style="margin-right: 4px">{{ item.step_count || 0 }} steps</ion-badge>
                  <ion-badge color="medium">{{ item.monitor_count || 0 }} monitors</ion-badge>
                </p>
              </ion-label>
              <ion-badge slot="end" [color]="item.active ? 'success' : 'medium'">
                {{ item.active ? 'Active' : 'Inactive' }}
              </ion-badge>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="primary" [routerLink]="['/notifications', item.id, 'edit']">Edit</ion-item-option>
              <ion-item-option color="danger" (click)="onDelete(item)">Delete</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="notifications-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No notification policies</h3>
            <p>Create a policy to define how and when notifications are sent.</p>
            <ion-button routerLink="/notifications/new" fill="outline">+ New Policy</ion-button>
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
export class NotificationPolicyListComponent implements OnInit, ViewWillEnter {
  items = signal<NotificationPolicy[]>([]);
  allItems = signal<NotificationPolicy[]>([]);
  loading = signal(true);
  searchQuery = '';

  constructor(
    private service: NotificationPolicyService,
    private alertCtrl: AlertController,
  ) {}

  ngOnInit(): void {}

  ionViewWillEnter(): void { this.load(); }

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

  applyFilter(): void {
    const query = this.searchQuery.toLowerCase().trim();
    if (!query) {
      this.items.set(this.allItems());
      return;
    }
    this.items.set(
      this.allItems().filter((item) =>
        item.name.toLowerCase().includes(query) || item.trigger_type.toLowerCase().includes(query)
      )
    );
  }

  async onDelete(item: NotificationPolicy): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Policy',
      message: `Delete "${item.name}"? This cannot be undone.`,
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

  getTriggerColor(type: string): string {
    switch (type) {
      case 'down': return 'danger';
      case 'up': return 'success';
      case 'degraded': return 'warning';
      case 'any': return 'medium';
      default: return 'medium';
    }
  }
}
