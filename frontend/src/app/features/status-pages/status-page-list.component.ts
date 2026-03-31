import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonNote, IonIcon, IonChip,
  IonRefresher, IonRefresherContent, IonSearchbar,
  AlertController,
} from '@ionic/angular/standalone';
import { StatusPageService, StatusPage } from './status-page.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { addIcons } from 'ionicons';
import { globeOutline } from 'ionicons/icons';

addIcons({ globeOutline });

@Component({
  selector: 'app-status-page-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonNote, IonIcon, IonChip,
    IonRefresher, IonRefresherContent, IonSearchbar,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Status Pages</ion-title>
        <ion-buttons slot="end">
          <ion-button routerLink="/status-pages/new" fill="solid" color="primary" size="small">+ New Page</ion-button>
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
            <ion-item [routerLink]="['/status-pages', item.id, 'edit']" detail>
              <ion-label>
                <h2>{{ item.name }}</h2>
                <p>
                  <ion-chip size="small" color="medium" style="height: 20px; font-size: 0.7rem">
                    /s/{{ item.slug }}
                  </ion-chip>
                  <span style="margin-left: 8px; font-size: 0.75rem; color: var(--ion-color-medium)">
                    {{ item.monitor_count }} monitor{{ item.monitor_count !== 1 ? 's' : '' }}
                  </span>
                </p>
              </ion-label>
              <ion-badge slot="end" [color]="item.is_active ? 'success' : 'medium'">
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </ion-badge>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="primary" [routerLink]="['/status-pages', item.id, 'edit']">Edit</ion-item-option>
              <ion-item-option color="danger" (click)="onDelete(item)">Delete</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="globe-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No status pages</h3>
            <p>Create a public status page to share with your customers.</p>
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
export class StatusPageListComponent implements OnInit, ViewWillEnter {
  items = signal<StatusPage[]>([]);
  allItems = signal<StatusPage[]>([]);
  loading = signal(true);
  searchQuery = '';

  constructor(private service: StatusPageService, private alertCtrl: AlertController) {}

  ngOnInit(): void { this.load(); }

  ionViewWillEnter(): void { this.load(); }

  load(): void {
    this.service.getAll().subscribe((data) => {
      this.allItems.set(data.items);
      this.applyFilter();
      this.loading.set(false);
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
        item.name.toLowerCase().includes(query)
      )
    );
  }

  async onDelete(item: StatusPage): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Status Page',
      message: `Delete "${item.name}"? This cannot be undone.`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        { text: 'Delete', role: 'destructive', handler: () => {
          this.service.delete(item.id).subscribe(() => {
            this.items.update((list) => list.filter((i) => i.id !== item.id));
          });
        }},
      ],
    });
    await alert.present();
  }
}
