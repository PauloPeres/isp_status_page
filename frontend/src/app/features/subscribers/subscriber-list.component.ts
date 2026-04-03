import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonNote, IonIcon,
  IonRefresher, IonRefresherContent, IonSearchbar,
  AlertController,
} from '@ionic/angular/standalone';
import { SubscriberService, Subscriber } from './subscriber.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { addIcons } from 'ionicons';
import { peopleOutline } from 'ionicons/icons';

addIcons({ peopleOutline });

@Component({
  selector: 'app-subscriber-list',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonNote, IonIcon,
    IonRefresher, IonRefresherContent, IonSearchbar,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Subscribers</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          [(ngModel)]="searchQuery"
          (ionInput)="onSearch()"
          placeholder="Search by email..."
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
            <ion-item>
              <ion-label>
                <h2>{{ item.email }}</h2>
                <p>
                  Subscribed: {{ item.created | date:'mediumDate' }}
                  @if (item.verified_at) {
                    | Verified: {{ item.verified_at | date:'mediumDate' }}
                  }
                </p>
              </ion-label>
              <ion-badge slot="end" [color]="item.verified ? 'success' : 'warning'">
                {{ item.verified ? 'Verified' : 'Pending' }}
              </ion-badge>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="danger" (click)="onDelete(item)">Delete</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="people-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No subscribers yet</h3>
            <p>Subscribers will appear here once users sign up for notifications.</p>
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
export class SubscriberListComponent implements OnInit, ViewWillEnter {
  items = signal<Subscriber[]>([]);
  allItems = signal<Subscriber[]>([]);
  loading = signal(true);
  searchQuery = '';

  constructor(private service: SubscriberService, private alertCtrl: AlertController) {}

  ngOnInit(): void {}

  ionViewWillEnter(): void { this.load(); }

  load(): void {
    this.service.getAll().subscribe({
      next: (data) => {
        this.allItems.set(data.items);
        this.applyFilter();
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
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
        item.email.toLowerCase().includes(query)
      )
    );
  }

  async onDelete(item: Subscriber): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Subscriber',
      message: `Remove "${item.email}"?`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        { text: 'Delete', role: 'destructive', handler: () => {
          this.service.delete(item.public_id).subscribe(() => {
            this.allItems.update((list) => list.filter((i) => i.id !== item.id));
            this.applyFilter();
          });
        }},
      ],
    });
    await alert.present();
  }
}
