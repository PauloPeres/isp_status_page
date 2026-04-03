import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonChip, IonIcon,
  IonRefresher, IonRefresherContent, IonSpinner, IonSearchbar,
  AlertController, ToastController,
} from '@ionic/angular/standalone';
import { ChannelService, NotificationChannel } from './channel.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { addIcons } from 'ionicons';
import { megaphoneOutline } from 'ionicons/icons';

addIcons({ megaphoneOutline });

@Component({
  selector: 'app-channel-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonChip, IonIcon,
    IonRefresher, IonRefresherContent, IonSpinner, IonSearchbar,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Channels</ion-title>
        <ion-buttons slot="end">
          <ion-button routerLink="/channels/new" fill="solid" color="primary" size="small">+ New Channel</ion-button>
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
            <ion-item [routerLink]="['/channels', item.public_id, 'edit']" detail>
              <ion-label>
                <h2>{{ item.name }}</h2>
                <p>
                  <ion-chip size="small" [color]="getTypeColor(item.type)" style="height: 20px; font-size: 0.7rem">
                    {{ item.type }}
                  </ion-chip>
                  <ion-badge [color]="item.active ? 'success' : 'medium'" style="margin-left: 4px">
                    {{ item.active ? 'Active' : 'Inactive' }}
                  </ion-badge>
                </p>
              </ion-label>
              <ion-button slot="end" fill="clear" size="small" (click)="onTest($event, item)" [disabled]="testing() === item.id">
                @if (testing() === item.id) {
                  <ion-spinner name="crescent" style="width: 16px; height: 16px"></ion-spinner>
                } @else {
                  Test
                }
              </ion-button>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="primary" [routerLink]="['/channels', item.public_id, 'edit']">Edit</ion-item-option>
              <ion-item-option color="danger" (click)="onDelete(item)">Delete</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="megaphone-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No channels configured</h3>
            <p>No channels configured. Create one to start receiving notifications.</p>
            <ion-button routerLink="/channels/new" fill="outline">+ New Channel</ion-button>
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
export class ChannelListComponent implements OnInit, ViewWillEnter {
  items = signal<NotificationChannel[]>([]);
  allItems = signal<NotificationChannel[]>([]);
  loading = signal(true);
  searchQuery = '';
  testing = signal<number | null>(null);

  constructor(
    private service: ChannelService,
    private alertCtrl: AlertController,
    private toastCtrl: ToastController,
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
        item.name.toLowerCase().includes(query) || item.type.toLowerCase().includes(query)
      )
    );
  }

  onTest(event: Event, item: NotificationChannel): void {
    event.stopPropagation();
    event.preventDefault();
    this.testing.set(item.id);
    this.service.test(item.public_id).subscribe({
      next: async (res) => {
        this.testing.set(null);
        const toast = await this.toastCtrl.create({
          message: res.success ? 'Test notification sent successfully' : `Test failed: ${res.message}`,
          color: res.success ? 'success' : 'danger',
          duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
      error: async () => {
        this.testing.set(null);
        const toast = await this.toastCtrl.create({
          message: 'Test notification failed', color: 'danger', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  async onDelete(item: NotificationChannel): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Channel',
      message: `Delete "${item.name}"? This cannot be undone.`,
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

  getTypeColor(type: string): string {
    switch (type) {
      case 'email': return 'primary';
      case 'slack': return 'tertiary';
      case 'discord': return 'secondary';
      case 'telegram': return 'success';
      case 'sms': return 'warning';
      case 'whatsapp': return 'success';
      case 'pagerduty': return 'danger';
      case 'opsgenie': return 'tertiary';
      case 'webhook': return 'medium';
      default: return 'medium';
    }
  }
}
