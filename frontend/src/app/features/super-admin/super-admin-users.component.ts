import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
  IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonSearchbar, IonChip,
  IonRefresher, IonRefresherContent,
} from '@ionic/angular/standalone';
import { SuperAdminService, AdminUser } from './super-admin.service';
import { addIcons } from 'ionicons';
import { peopleOutline } from 'ionicons/icons';

addIcons({ peopleOutline });

@Component({
  selector: 'app-super-admin-users',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
    IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonSearchbar, IonChip,
    IonRefresher, IonRefresherContent,
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

      <ion-list>
        @for (user of items(); track user.id) {
          <ion-item>
            <ion-label>
              <h2>
                {{ user.username }}
                @if (user.is_super_admin) {
                  <ion-badge color="danger" style="margin-left: 6px; font-size: 0.6rem">ADMIN</ion-badge>
                }
              </h2>
              <p>{{ user.email }}</p>
              <p>
                @for (org of user.organizations; track org.id) {
                  <ion-chip size="small" style="height: 20px; font-size: 0.65rem">
                    {{ org.name }} ({{ org.role }})
                  </ion-chip>
                }
              </p>
            </ion-label>
            <ion-note slot="end" style="font-size: 0.7rem">
              @if (user.last_login_at) {
                {{ user.last_login_at | date:'short' }}
              } @else {
                Never
              }
            </ion-note>
          </ion-item>
        } @empty {
          <div class="empty-state">
            <ion-icon name="people-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No users found</h3>
          </div>
        }
      </ion-list>
    </ion-content>
  `,
  styles: [`
    .empty-state { text-align: center; padding: 3rem 1rem; color: var(--ion-color-medium); }
    .empty-state h3 { margin: 1rem 0 0.5rem; color: var(--ion-text-color); }
  `],
})
export class SuperAdminUsersComponent implements OnInit {
  items = signal<AdminUser[]>([]);
  search = '';

  constructor(private service: SuperAdminService) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    const params: any = {};
    if (this.search) params.search = this.search;
    this.service.getUsers(params).subscribe((data) => this.items.set(data.items));
  }

  onSearch(): void { this.load(); }

  onRefresh(event: any): void {
    this.service.getUsers().subscribe({
      next: (data) => { this.items.set(data.items); event.target.complete(); },
      error: () => event.target.complete(),
    });
  }
}
