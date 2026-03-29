import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonSearchbar, IonSpinner,
  IonRefresher, IonRefresherContent,
  ToastController,
} from '@ionic/angular/standalone';
import { SuperAdminService, AdminOrg } from './super-admin.service';
import { addIcons } from 'ionicons';
import { businessOutline, logInOutline } from 'ionicons/icons';

addIcons({ businessOutline, logInOutline });

@Component({
  selector: 'app-super-admin-orgs',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonSearchbar, IonSpinner,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Organizations</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <ion-searchbar [(ngModel)]="search" (ionInput)="onSearch()" placeholder="Search organizations..." debounce="300"></ion-searchbar>

      <ion-list>
        @for (org of items(); track org.id) {
          <ion-item>
            <ion-label>
              <h2>{{ org.name }}</h2>
              <p>
                <ion-badge [color]="getPlanColor(org.plan)" style="margin-right: 6px">{{ org.plan }}</ion-badge>
                {{ org.user_count }} users &middot; {{ org.monitor_count }} monitors
              </p>
              <p style="font-size: 0.7rem; color: var(--ion-color-medium)">
                {{ org.owner_email }} &middot; Created {{ org.created_at | date:'mediumDate' }}
              </p>
            </ion-label>
            <ion-button slot="end" fill="clear" size="small" (click)="onImpersonate(org)" [disabled]="impersonating() === org.id">
              @if (impersonating() === org.id) {
                <ion-spinner name="crescent" style="width: 16px; height: 16px"></ion-spinner>
              } @else {
                <ion-icon name="log-in-outline"></ion-icon>
              }
            </ion-button>
          </ion-item>
        } @empty {
          <div class="empty-state">
            <ion-icon name="business-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No organizations found</h3>
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
export class SuperAdminOrgsComponent implements OnInit {
  items = signal<AdminOrg[]>([]);
  search = '';
  impersonating = signal<number | null>(null);

  constructor(private service: SuperAdminService, private toastCtrl: ToastController) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    const params: any = {};
    if (this.search) params.search = this.search;
    this.service.getOrganizations(params).subscribe((data) => this.items.set(data.items));
  }

  onSearch(): void { this.load(); }

  onRefresh(event: any): void {
    this.service.getOrganizations().subscribe({
      next: (data) => { this.items.set(data.items); event.target.complete(); },
      error: () => event.target.complete(),
    });
  }

  onImpersonate(org: AdminOrg): void {
    this.impersonating.set(org.id);
    this.service.impersonateOrg(org.id).subscribe({
      next: async () => {
        this.impersonating.set(null);
        const toast = await this.toastCtrl.create({
          message: `Impersonating ${org.name}`, color: 'success', duration: 3000, position: 'bottom',
        });
        await toast.present();
        window.location.href = '/dashboard';
      },
      error: async () => {
        this.impersonating.set(null);
        const toast = await this.toastCtrl.create({
          message: 'Impersonation failed', color: 'danger', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  getPlanColor(plan: string): string {
    switch (plan.toLowerCase()) {
      case 'free': return 'medium';
      case 'pro': return 'primary';
      case 'business': return 'success';
      case 'enterprise': return 'tertiary';
      default: return 'medium';
    }
  }
}
