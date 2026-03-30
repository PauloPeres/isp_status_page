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
import { businessOutline } from 'ionicons/icons';

addIcons({ businessOutline });

interface OrgItem {
  id: number;
  name: string;
  plan?: string;
  created: string;
  [key: string]: any;
}

interface OrgsResponse {
  organizations: OrgItem[];
  pagination: { page: number; limit: number };
}

@Component({
  selector: 'app-super-admin-orgs',
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
        <ion-title>Organizations</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <ion-searchbar [(ngModel)]="search" (ionInput)="onSearch()" placeholder="Search organizations..." debounce="300"></ion-searchbar>

      @if (loading()) {
        <app-list-skeleton [count]="6"></app-list-skeleton>
      } @else {
        <ion-list>
          @for (org of filteredItems(); track org.id) {
            <ion-item>
              <ion-label>
                <h2>{{ org.name }}</h2>
                <p>
                  @if (org.plan) {
                    <ion-badge [color]="getPlanColor(org.plan)" style="margin-right: 6px">{{ org.plan }}</ion-badge>
                  }
                  Created {{ org.created | date:'mediumDate' }}
                </p>
              </ion-label>
            </ion-item>
          } @empty {
            <div class="empty-state">
              <ion-icon name="business-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
              <h3>No organizations found</h3>
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
export class SuperAdminOrgsComponent implements OnInit {
  items = signal<OrgItem[]>([]);
  loading = signal(true);
  search = '';

  constructor(private api: ApiService, private toastCtrl: ToastController) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    this.api.get<OrgsResponse>('/super-admin/organizations').subscribe({
      next: (d) => { this.items.set(d.organizations || []); this.loading.set(false); },
      error: async (err) => {
        this.loading.set(false);
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Failed to load organizations', color: 'danger', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  filteredItems(): OrgItem[] {
    const term = this.search.toLowerCase().trim();
    if (!term) return this.items();
    return this.items().filter((org) => org.name.toLowerCase().includes(term));
  }

  onSearch(): void {
    // Client-side filtering via filteredItems()
  }

  onRefresh(event: any): void {
    this.api.get<OrgsResponse>('/super-admin/organizations').subscribe({
      next: (d) => { this.items.set(d.organizations || []); event.target.complete(); },
      error: () => event.target.complete(),
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
