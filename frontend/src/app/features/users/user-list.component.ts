import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonNote, IonIcon, IonAvatar,
  IonRefresher, IonRefresherContent, IonSearchbar,
  AlertController,
} from '@ionic/angular/standalone';
import { UserService, User } from './user.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { addIcons } from 'ionicons';
import { peopleOutline, personCircleOutline } from 'ionicons/icons';

addIcons({ peopleOutline, personCircleOutline });

@Component({
  selector: 'app-user-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonNote, IonIcon, IonAvatar,
    IonRefresher, IonRefresherContent, IonSearchbar,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Team</ion-title>
        <ion-buttons slot="end">
          <ion-button routerLink="/invitations" fill="solid" color="primary" size="small">+ Invite</ion-button>
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
        @for (user of items(); track user.id) {
          <ion-item-sliding>
            <ion-item>
              <ion-avatar slot="start" style="width: 36px; height: 36px">
                @if (user.avatar_url) {
                  <img [src]="user.avatar_url" [alt]="user.name" />
                } @else {
                  <ion-icon name="person-circle-outline" style="font-size: 36px; color: var(--ion-color-medium)"></ion-icon>
                }
              </ion-avatar>
              <ion-label>
                <h2>{{ user.name }}</h2>
                <p>{{ user.email }}</p>
              </ion-label>
              <ion-badge slot="end" [color]="getRoleColor(user.role)">{{ user.role }}</ion-badge>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="primary" (click)="onChangeRole(user)">Role</ion-item-option>
              <ion-item-option color="danger" (click)="onRemove(user)">Remove</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="people-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No team members</h3>
            <p>Invite people to collaborate on monitoring.</p>
            <ion-button routerLink="/invitations" fill="outline">Send Invitation</ion-button>
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
export class UserListComponent implements OnInit, ViewWillEnter {
  items = signal<User[]>([]);
  allItems = signal<User[]>([]);
  loading = signal(true);
  searchQuery = '';

  constructor(private service: UserService, private alertCtrl: AlertController) {}

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
        item.name.toLowerCase().includes(query) ||
        item.email.toLowerCase().includes(query)
      )
    );
  }

  async onChangeRole(user: User): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Change Role',
      message: `Select new role for ${user.name}`,
      inputs: [
        { type: 'radio', label: 'Admin', value: 'admin', checked: user.role === 'admin' },
        { type: 'radio', label: 'Member', value: 'member', checked: user.role === 'member' },
        { type: 'radio', label: 'Viewer', value: 'viewer', checked: user.role === 'viewer' },
      ],
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        { text: 'Update', handler: (role: string) => {
          if (role && role !== user.role) {
            this.service.updateRole(user.id, role).subscribe(() => {
              this.items.update((list) => list.map((u) => u.id === user.id ? { ...u, role: role as any } : u));
            });
          }
        }},
      ],
    });
    await alert.present();
  }

  async onRemove(user: User): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Remove User',
      message: `Remove ${user.name} from the team?`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        { text: 'Remove', role: 'destructive', handler: () => {
          this.service.remove(user.id).subscribe(() => {
            this.allItems.update((list) => list.filter((u) => u.id !== user.id));
            this.items.update((list) => list.filter((u) => u.id !== user.id));
          });
        }},
      ],
    });
    await alert.present();
  }

  getRoleColor(role: string): string {
    switch (role) {
      case 'owner': return 'danger';
      case 'admin': return 'warning';
      case 'member': return 'primary';
      case 'viewer': return 'medium';
      default: return 'medium';
    }
  }
}
