import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonNote, IonIcon, IonInput, IonSelect, IonSelectOption,
  IonRefresher, IonRefresherContent, IonSpinner, IonSearchbar,
  AlertController, ToastController,
} from '@ionic/angular/standalone';
import { UserService, Invitation } from '../users/user.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { showApiError } from '../../core/services/plan-error.helper';
import { addIcons } from 'ionicons';
import { mailOutline } from 'ionicons/icons';

addIcons({ mailOutline });

@Component({
  selector: 'app-invitation-list',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonNote, IonIcon, IonInput, IonSelect, IonSelectOption,
    IonRefresher, IonRefresherContent, IonSpinner, IonSearchbar,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/users"></ion-back-button>
        </ion-buttons>
        <ion-title>Invitations</ion-title>
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

      <!-- Inline send form -->
      <div style="padding: 16px; background: var(--ion-color-light)">
        <h3 style="margin: 0 0 8px">Send Invitation</h3>
        <ion-list>
          <ion-item>
            <ion-input label="Email" labelPlacement="stacked" [(ngModel)]="newEmail" placeholder="user&#64;example.com" type="email"></ion-input>
          </ion-item>
          <ion-item>
            <ion-select label="Role" labelPlacement="stacked" [(ngModel)]="newRole" interface="popover">
              <ion-select-option value="admin">Admin</ion-select-option>
              <ion-select-option value="member">Member</ion-select-option>
              <ion-select-option value="viewer">Viewer</ion-select-option>
            </ion-select>
          </ion-item>
        </ion-list>
        <ion-button expand="block" (click)="onSend()" [disabled]="sending() || !newEmail" style="margin-top: 8px">
          @if (sending()) {
            <ion-spinner name="crescent" style="width: 16px; height: 16px"></ion-spinner>
          } @else {
            Send Invitation
          }
        </ion-button>
      </div>

      <!-- Pending invitations -->
      @if (loading()) {
        <app-list-skeleton></app-list-skeleton>
      } @else {
      <ion-list>
        @for (item of items(); track item.id) {
          <ion-item-sliding>
            <ion-item>
              <ion-label>
                <h2>{{ item.email }}</h2>
                <p>Invited by {{ item.invited_by }} | Expires {{ item.expires_at | date:'shortDate' }}</p>
              </ion-label>
              <ion-badge slot="end" [color]="getStatusColor(item.status)">{{ item.status }}</ion-badge>
            </ion-item>

            <ion-item-options side="end">
              @if (item.status === 'pending') {
                <ion-item-option color="primary" (click)="onResend(item)">Resend</ion-item-option>
                <ion-item-option color="danger" (click)="onRevoke(item)">Revoke</ion-item-option>
              }
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="mail-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No pending invitations</h3>
            <p>Use the form above to invite team members.</p>
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
export class InvitationListComponent implements OnInit, ViewWillEnter {
  items = signal<Invitation[]>([]);
  allItems = signal<Invitation[]>([]);
  loading = signal(true);
  searchQuery = '';
  newEmail = '';
  newRole = 'member';
  sending = signal(false);

  constructor(
    private service: UserService,
    private alertCtrl: AlertController,
    private toastCtrl: ToastController,
    private router: Router,
  ) {}

  ngOnInit(): void {}

  ionViewWillEnter(): void { this.load(); }

  load(): void {
    this.service.getInvitations().subscribe({
      next: (data) => {
        this.allItems.set(data.items);
        this.applyFilter();
        this.loading.set(false);
      },
      error: () => { this.loading.set(false); },
    });
  }

  onRefresh(event: any): void {
    this.service.getInvitations().subscribe({
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

  onSend(): void {
    if (!this.newEmail) return;
    this.sending.set(true);
    this.service.sendInvitation({ email: this.newEmail, role: this.newRole }).subscribe({
      next: async (inv) => {
        this.sending.set(false);
        this.newEmail = '';
        this.items.update((list) => [inv, ...list]);
        const toast = await this.toastCtrl.create({ message: 'Invitation sent', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
      },
      error: async (err: any) => {
        this.sending.set(false);
        await showApiError(err, 'Failed to send invitation', this.toastCtrl, this.router);
      },
    });
  }

  onResend(item: Invitation): void {
    this.service.resendInvitation(item.id).subscribe({
      next: async () => {
        const toast = await this.toastCtrl.create({ message: 'Invitation resent', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
      },
    });
  }

  async onRevoke(item: Invitation): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Revoke Invitation',
      message: `Revoke invitation for ${item.email}?`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        { text: 'Revoke', role: 'destructive', handler: () => {
          this.service.revokeInvitation(item.id).subscribe(() => {
            this.allItems.update((list) => list.filter((i) => i.id !== item.id));
            this.items.update((list) => list.filter((i) => i.id !== item.id));
          });
        }},
      ],
    });
    await alert.present();
  }

  getStatusColor(status: string): string {
    switch (status) {
      case 'pending': return 'warning';
      case 'accepted': return 'success';
      case 'expired': return 'medium';
      default: return 'medium';
    }
  }
}
