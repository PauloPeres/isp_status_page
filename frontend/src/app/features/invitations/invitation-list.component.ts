import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonNote, IonIcon, IonInput, IonSelect, IonSelectOption,
  IonRefresher, IonRefresherContent, IonSpinner, IonSearchbar,
  IonCard, IonCardContent, IonChip, IonText,
  AlertController, ToastController,
} from '@ionic/angular/standalone';
import { UserService, Invitation } from '../users/user.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { showApiError } from '../../core/services/plan-error.helper';
import { addIcons } from 'ionicons';
import {
  mailOutline, sendOutline, timeOutline, checkmarkCircleOutline,
  alertCircleOutline, refreshOutline, trashOutline, arrowBackOutline,
  peopleOutline,
} from 'ionicons/icons';

addIcons({
  mailOutline, sendOutline, timeOutline, checkmarkCircleOutline,
  alertCircleOutline, refreshOutline, trashOutline, arrowBackOutline,
  peopleOutline,
});

@Component({
  selector: 'app-invitation-list',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonNote, IonIcon, IonInput, IonSelect, IonSelectOption,
    IonRefresher, IonRefresherContent, IonSpinner, IonSearchbar,
    IonCard, IonCardContent, IonChip, IonText,
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
          placeholder="Search by email..."
          [debounce]="300"
        ></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <!-- Quick Invite Card -->
      <ion-card class="invite-card">
        <ion-card-content>
          <div class="invite-form">
            <ion-input
              placeholder="colleague&#64;company.com"
              type="email"
              [(ngModel)]="newEmail"
              enterkeyhint="send"
              (keyup.enter)="onSend()"
              class="invite-input"
            ></ion-input>
            <ion-select [(ngModel)]="newRole" interface="popover" class="role-select">
              <ion-select-option value="admin">Admin</ion-select-option>
              <ion-select-option value="member">Member</ion-select-option>
              <ion-select-option value="viewer">Viewer</ion-select-option>
            </ion-select>
            <ion-button (click)="onSend()" [disabled]="sending() || !newEmail" fill="solid" color="primary" class="send-btn">
              @if (sending()) {
                <ion-spinner name="crescent"></ion-spinner>
              } @else {
                <ion-icon name="send-outline" slot="icon-only"></ion-icon>
              }
            </ion-button>
          </div>
        </ion-card-content>
      </ion-card>

      @if (fromOnboarding) {
        <div class="wizard-link">
          <a (click)="goBackToWizard()">
            <ion-icon name="arrow-back-outline"></ion-icon>
            Back to Setup Wizard
          </a>
        </div>
      }

      <!-- Stats Summary -->
      @if (!loading() && items().length > 0) {
        <div class="stats-row">
          <div class="stat-chip pending">
            <span class="stat-count">{{ countByStatus('pending') }}</span>
            <span class="stat-label">Pending</span>
          </div>
          <div class="stat-chip accepted">
            <span class="stat-count">{{ countByStatus('accepted') }}</span>
            <span class="stat-label">Accepted</span>
          </div>
          <div class="stat-chip expired">
            <span class="stat-count">{{ countByStatus('expired') }}</span>
            <span class="stat-label">Expired</span>
          </div>
        </div>
      }

      <!-- Invitation List -->
      @if (loading()) {
        <app-list-skeleton></app-list-skeleton>
      } @else {
        <ion-list class="invitation-list" lines="none">
          @for (item of items(); track item.id) {
            <ion-item-sliding>
              <ion-item class="invitation-item">
                <div class="status-indicator" [class]="'indicator-' + item.status"></div>
                <ion-label>
                  <div class="invitation-main">
                    <h2 class="invitation-email">{{ item.email }}</h2>
                    <ion-badge [color]="getRoleColor(item.role)" class="role-badge">
                      {{ item.role | titlecase }}
                    </ion-badge>
                  </div>
                  <div class="invitation-meta">
                    @switch (item.status) {
                      @case ('pending') {
                        <span class="meta-status pending">
                          <ion-icon name="time-outline"></ion-icon>
                          Pending &middot; sent {{ getRelativeTime(item.created_at) }}
                        </span>
                        @if (isExpiringSoon(item.expires_at)) {
                          <span class="meta-expiry">Expires {{ getRelativeTime(item.expires_at) }}</span>
                        }
                      }
                      @case ('accepted') {
                        <span class="meta-status accepted">
                          <ion-icon name="checkmark-circle-outline"></ion-icon>
                          Accepted
                        </span>
                      }
                      @case ('expired') {
                        <span class="meta-status expired">
                          <ion-icon name="alert-circle-outline"></ion-icon>
                          Expired
                        </span>
                      }
                    }
                  </div>
                </ion-label>
              </ion-item>

              <ion-item-options side="end">
                @if (item.status === 'pending') {
                  <ion-item-option color="primary" (click)="onResend(item)">
                    <ion-icon name="refresh-outline" slot="icon-only"></ion-icon>
                  </ion-item-option>
                  <ion-item-option color="danger" (click)="onRevoke(item)">
                    <ion-icon name="trash-outline" slot="icon-only"></ion-icon>
                  </ion-item-option>
                }
                @if (item.status === 'expired') {
                  <ion-item-option color="danger" (click)="onRevoke(item)">
                    <ion-icon name="trash-outline" slot="icon-only"></ion-icon>
                  </ion-item-option>
                }
              </ion-item-options>
            </ion-item-sliding>
          } @empty {
            <div class="empty-state">
              <ion-icon name="people-outline"></ion-icon>
              <h3>Your team is waiting</h3>
              <p>Send an invitation above to get started.</p>
            </div>
          }
        </ion-list>
      }
    </ion-content>
  `,
  styles: [`
    /* Quick Invite Card */
    .invite-card {
      margin: 12px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    .invite-card ion-card-content {
      padding: 12px;
    }
    .invite-form {
      display: flex;
      gap: 8px;
      align-items: center;
    }
    .invite-input {
      flex: 1;
      --padding-start: 12px;
      --padding-end: 12px;
      border: 1px solid var(--ion-color-light-shade);
      border-radius: 8px;
      min-height: 42px;
      --background: var(--ion-color-light);
    }
    .invite-input:focus-within {
      border-color: var(--ion-color-primary);
      box-shadow: 0 0 0 2px rgba(var(--ion-color-primary-rgb), 0.15);
    }
    .role-select {
      max-width: 120px;
      min-width: 100px;
      font-size: 0.85rem;
      --padding-start: 8px;
      --padding-end: 8px;
      border: 1px solid var(--ion-color-light-shade);
      border-radius: 8px;
      min-height: 42px;
    }
    .send-btn {
      --border-radius: 8px;
      min-height: 42px;
      margin: 0;
    }
    .send-btn ion-spinner {
      width: 18px;
      height: 18px;
    }

    @media (max-width: 576px) {
      .invite-form {
        flex-wrap: wrap;
      }
      .invite-input {
        width: 100%;
        flex: none;
      }
      .role-select {
        flex: 1;
      }
      .send-btn {
        min-width: 56px;
      }
    }

    /* Wizard Link */
    .wizard-link {
      text-align: center;
      padding: 4px 16px 8px;
    }
    .wizard-link a {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 0.85rem;
      color: var(--ion-color-primary);
      text-decoration: none;
      cursor: pointer;
    }
    .wizard-link a:hover {
      text-decoration: underline;
    }
    .wizard-link ion-icon {
      font-size: 14px;
    }

    /* Stats Row */
    .stats-row {
      display: flex;
      gap: 8px;
      padding: 4px 16px 8px;
      justify-content: center;
    }
    .stat-chip {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      border-radius: 16px;
      font-size: 0.8rem;
    }
    .stat-chip.pending {
      background: rgba(var(--ion-color-warning-rgb), 0.15);
      color: var(--ion-color-warning-shade);
    }
    .stat-chip.accepted {
      background: rgba(var(--ion-color-success-rgb), 0.15);
      color: var(--ion-color-success-shade);
    }
    .stat-chip.expired {
      background: rgba(var(--ion-color-medium-rgb), 0.15);
      color: var(--ion-color-medium-shade);
    }
    .stat-count {
      font-weight: 700;
      font-size: 0.9rem;
    }
    .stat-label {
      font-weight: 500;
    }

    /* Invitation List */
    .invitation-list {
      padding: 0 8px;
    }
    .invitation-item {
      --padding-start: 0;
      --inner-padding-end: 12px;
      margin: 4px 0;
      border-radius: 10px;
      --background: var(--ion-item-background, var(--ion-background-color));
    }
    .status-indicator {
      width: 4px;
      min-height: 100%;
      align-self: stretch;
      border-radius: 4px 0 0 4px;
      margin-right: 12px;
    }
    .indicator-pending {
      background: var(--ion-color-warning);
    }
    .indicator-accepted {
      background: var(--ion-color-success);
    }
    .indicator-expired {
      background: var(--ion-color-medium);
    }

    .invitation-main {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .invitation-email {
      font-size: 1rem;
      font-weight: 600;
      margin: 0;
      color: var(--ion-text-color);
    }
    .role-badge {
      font-size: 0.7rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      padding: 2px 8px;
      border-radius: 4px;
    }

    .invitation-meta {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 4px;
      flex-wrap: wrap;
    }
    .meta-status {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 0.8rem;
    }
    .meta-status ion-icon {
      font-size: 14px;
    }
    .meta-status.pending {
      color: var(--ion-color-warning-shade);
    }
    .meta-status.accepted {
      color: var(--ion-color-success-shade);
    }
    .meta-status.expired {
      color: var(--ion-color-medium);
    }
    .meta-expiry {
      font-size: 0.75rem;
      color: var(--ion-color-danger);
      font-weight: 500;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 4rem 1.5rem;
      color: var(--ion-color-medium);
    }
    .empty-state ion-icon {
      font-size: 56px;
      color: var(--ion-color-medium);
      opacity: 0.6;
    }
    .empty-state h3 {
      margin: 1rem 0 0.5rem;
      color: var(--ion-text-color);
      font-size: 1.2rem;
      font-weight: 600;
    }
    .empty-state p {
      font-size: 0.9rem;
      max-width: 260px;
      margin: 0 auto;
      line-height: 1.4;
    }
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

  fromOnboarding = false;

  constructor(
    private service: UserService,
    private alertCtrl: AlertController,
    private toastCtrl: ToastController,
    private router: Router,
    private route: ActivatedRoute,
  ) {}

  ngOnInit(): void {}

  ionViewWillEnter(): void {
    this.fromOnboarding = this.route.snapshot.queryParamMap.get('from') === 'onboarding';
    this.load();
  }

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
        this.allItems.update((list) => [inv, ...list]);
        this.applyFilter();
        const toast = await this.toastCtrl.create({ message: 'Invitation sent', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
        if (this.fromOnboarding) {
          this.router.navigate(['/onboarding']);
        }
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

  goBackToWizard(): void {
    this.router.navigate(['/onboarding']);
  }

  getRoleColor(role: string): string {
    switch (role) {
      case 'admin': return 'primary';
      case 'member': return 'medium';
      case 'viewer': return 'tertiary';
      default: return 'medium';
    }
  }

  countByStatus(status: string): number {
    return this.allItems().filter((i) => i.status === status).length;
  }

  getRelativeTime(dateStr: string): string {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(Math.abs(diffMs) / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    const isFuture = diffMs < 0;

    if (diffMins < 1) return 'just now';
    if (diffMins < 60) {
      const label = `${diffMins} min${diffMins > 1 ? 's' : ''}`;
      return isFuture ? `in ${label}` : `${label} ago`;
    }
    if (diffHours < 24) {
      const label = `${diffHours} hr${diffHours > 1 ? 's' : ''}`;
      return isFuture ? `in ${label}` : `${label} ago`;
    }
    const label = `${diffDays} day${diffDays > 1 ? 's' : ''}`;
    return isFuture ? `in ${label}` : `${label} ago`;
  }

  isExpiringSoon(expiresAt: string): boolean {
    if (!expiresAt) return false;
    const expires = new Date(expiresAt);
    const now = new Date();
    const hoursLeft = (expires.getTime() - now.getTime()) / (1000 * 60 * 60);
    return hoursLeft > 0 && hoursLeft <= 48;
  }
}
