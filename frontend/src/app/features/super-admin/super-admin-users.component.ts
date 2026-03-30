import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
  IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonSearchbar,
  IonRefresher, IonRefresherContent,
  ToastController,
} from '@ionic/angular/standalone';
import { ApiService } from '../../core/services/api.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { addIcons } from 'ionicons';
import { peopleOutline } from 'ionicons/icons';

addIcons({ peopleOutline });

interface UserItem {
  id: number;
  username: string;
  email: string;
  is_super_admin?: boolean;
  created: string;
  [key: string]: any;
}

interface UsersResponse {
  users: UserItem[];
  pagination: { page: number; limit: number };
}

@Component({
  selector: 'app-super-admin-users',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
    IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonSearchbar,
    IonRefresher, IonRefresherContent,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Users</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <ion-searchbar [(ngModel)]="search" (ionInput)="onSearch()" placeholder="Search users..." debounce="300"></ion-searchbar>

      @if (loading()) {
        <app-list-skeleton [count]="6"></app-list-skeleton>
      } @else {
        <ion-list>
          @for (user of items(); track user.id) {
            <ion-item>
              <ion-label>
                <h2>
                  {{ user.username }}
                  @if (user.is_super_admin) {
                    <ion-badge color="danger" style="margin-left: 6px; font-size: 0.6rem; vertical-align: middle;">SUPER ADMIN</ion-badge>
                  }
                </h2>
                <p>{{ user.email }}</p>
              </ion-label>
              <ion-note slot="end" style="font-size: 0.7rem">
                {{ user.created | date:'mediumDate' }}
              </ion-note>
            </ion-item>
          } @empty {
            <div class="empty-state">
              <ion-icon name="people-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
              <h3>No users found</h3>
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
export class SuperAdminUsersComponent implements OnInit {
  items = signal<UserItem[]>([]);
  loading = signal(true);
  search = '';

  constructor(private api: ApiService, private toastCtrl: ToastController) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    const params: Record<string, any> = {};
    if (this.search.trim()) {
      params['search'] = this.search.trim();
    }
    this.api.get<UsersResponse>('/super-admin/users', params).subscribe({
      next: (d) => { this.items.set(d.users || []); this.loading.set(false); },
      error: async (err) => {
        this.loading.set(false);
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Failed to load users', color: 'danger', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  onSearch(): void { this.load(); }

  onRefresh(event: any): void {
    const params: Record<string, any> = {};
    if (this.search.trim()) {
      params['search'] = this.search.trim();
    }
    this.api.get<UsersResponse>('/super-admin/users', params).subscribe({
      next: (d) => { this.items.set(d.users || []); event.target.complete(); },
      error: () => event.target.complete(),
    });
  }
}
