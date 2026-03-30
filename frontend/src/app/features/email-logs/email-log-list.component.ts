import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
  IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon,
  IonRefresher, IonRefresherContent, IonSearchbar,
} from '@ionic/angular/standalone';
import { EmailLogService, EmailLog } from './email-log.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { addIcons } from 'ionicons';
import { mailOutline } from 'ionicons/icons';

addIcons({ mailOutline });

@Component({
  selector: 'app-email-log-list',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
    IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon,
    IonRefresher, IonRefresherContent, IonSearchbar,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Email Logs</ion-title>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          [(ngModel)]="searchQuery"
          (ionInput)="onSearch()"
          placeholder="Search by email or subject..."
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
          <ion-item>
            <ion-label>
              <h2>{{ item.to_email }}</h2>
              <p>{{ item.subject }}</p>
              <p style="font-size: 0.7rem; color: var(--ion-color-medium)">
                {{ item.created | date:'medium' }}
                @if (item.error_message) {
                  <span style="color: var(--ion-color-danger)"> — {{ item.error_message }}</span>
                }
              </p>
            </ion-label>
            <ion-badge slot="end" [color]="item.status === 'sent' ? 'success' : 'danger'">
              {{ item.status }}
            </ion-badge>
          </ion-item>
        } @empty {
          <div class="empty-state">
            <ion-icon name="mail-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No email logs</h3>
            <p>Email delivery logs will appear here.</p>
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
export class EmailLogListComponent implements OnInit {
  items = signal<EmailLog[]>([]);
  allItems = signal<EmailLog[]>([]);
  loading = signal(true);
  searchQuery = '';

  constructor(private service: EmailLogService) {}

  ngOnInit(): void { this.load(); }

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
        item.to_email.toLowerCase().includes(query) ||
        item.subject.toLowerCase().includes(query)
      )
    );
  }
}
