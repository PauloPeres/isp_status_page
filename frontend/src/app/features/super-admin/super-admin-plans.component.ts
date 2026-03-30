import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonChip, IonInput,
  IonToggle, IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonSpinner,
  IonRefresher, IonRefresherContent,
  AlertController, ToastController, ModalController,
} from '@ionic/angular/standalone';
import { ApiService } from '../../core/services/api.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { addIcons } from 'ionicons';
import { addCircleOutline, copyOutline, createOutline } from 'ionicons/icons';

addIcons({
  'add-circle-outline': addCircleOutline,
  'copy-outline': copyOutline,
  'create-outline': createOutline,
});

interface Plan {
  id: number;
  name: string;
  slug: string;
  price_monthly: number;
  price_yearly: number;
  monitor_limit: number;
  check_interval_min: number;
  team_member_limit: number;
  status_page_limit: number;
  api_rate_limit: number;
  data_retention_days: number;
  features: string;
  display_order: number;
  active: boolean;
  organization_count?: number;
}

@Component({
  selector: 'app-super-admin-plans',
  standalone: true,
  imports: [
    CommonModule, FormsModule, ListSkeletonComponent,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonChip, IonInput,
    IonToggle, IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonSpinner,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/super-admin"></ion-back-button>
        </ion-buttons>
        <ion-title>Billing Plans</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="onAdd()">
            <ion-icon name="add-circle-outline" slot="start"></ion-icon>
            New Plan
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <app-list-skeleton [count]="4"></app-list-skeleton>
      } @else {
        <ion-list>
          @for (plan of plans(); track plan.id) {
            <ion-card>
              <ion-card-header>
                <ion-card-title style="display: flex; justify-content: space-between; align-items: center">
                  <span>{{ plan.name }}</span>
                  <div>
                    <ion-badge [color]="plan.active ? 'success' : 'medium'">
                      {{ plan.active ? 'Active' : 'Inactive' }}
                    </ion-badge>
                    @if (plan.organization_count) {
                      <ion-badge color="primary" style="margin-left: 4px">
                        {{ plan.organization_count }} org(s)
                      </ion-badge>
                    }
                  </div>
                </ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <div class="plan-details">
                  <div class="plan-price">
                    @if (plan.price_monthly === 0) {
                      Free
                    } @else {
                      {{ '$' + (plan.price_monthly / 100).toFixed(2) }}/mo
                    }
                  </div>
                  <div class="plan-limits">
                    <ion-chip size="small">Monitors: {{ plan.monitor_limit === -1 ? 'Unlimited' : plan.monitor_limit }}</ion-chip>
                    <ion-chip size="small">Team: {{ plan.team_member_limit === -1 ? 'Unlimited' : plan.team_member_limit }}</ion-chip>
                    <ion-chip size="small">Interval: {{ plan.check_interval_min }}s</ion-chip>
                    <ion-chip size="small">Retention: {{ plan.data_retention_days }}d</ion-chip>
                    <ion-chip size="small">Status Pages: {{ plan.status_page_limit }}</ion-chip>
                  </div>
                  <div class="plan-slug">
                    <ion-note>{{ plan.slug }}</ion-note>
                  </div>
                </div>
                <div class="plan-actions">
                  <ion-button fill="clear" size="small" (click)="onEdit(plan)">
                    <ion-icon name="create-outline" slot="start"></ion-icon> Edit
                  </ion-button>
                  <ion-button fill="clear" size="small" (click)="onDuplicate(plan)">
                    <ion-icon name="copy-outline" slot="start"></ion-icon> Duplicate
                  </ion-button>
                  <ion-button fill="clear" size="small" color="danger" (click)="onDelete(plan)">
                    Delete
                  </ion-button>
                </div>
              </ion-card-content>
            </ion-card>
          } @empty {
            <div style="text-align: center; padding: 3rem; color: var(--ion-color-medium)">
              <p>No plans found. Create your first billing plan.</p>
            </div>
          }
        </ion-list>
      }
    </ion-content>
  `,
  styles: [`
    .plan-details { margin-bottom: 12px; }
    .plan-price { font-size: 1.5rem; font-weight: 700; margin-bottom: 8px; }
    .plan-limits { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px; }
    .plan-limits ion-chip { height: 24px; font-size: 0.7rem; }
    .plan-slug { margin-top: 4px; }
    .plan-actions { display: flex; gap: 0; border-top: 1px solid var(--ion-color-light); padding-top: 8px; }
  `],
})
export class SuperAdminPlansComponent implements OnInit {
  plans = signal<Plan[]>([]);
  loading = signal(true);

  constructor(
    private api: ApiService,
    private alertCtrl: AlertController,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.api.get<any>('/super-admin/plans').subscribe({
      next: (data) => { this.plans.set(data.items); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  onRefresh(event: any): void {
    this.api.get<any>('/super-admin/plans').subscribe({
      next: (data) => { this.plans.set(data.items); event.target.complete(); },
      error: () => event.target.complete(),
    });
  }

  async onAdd(): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'New Plan',
      inputs: [
        { name: 'name', type: 'text', placeholder: 'Plan Name' },
        { name: 'slug', type: 'text', placeholder: 'plan-slug (lowercase, unique)' },
        { name: 'price_monthly', type: 'number', placeholder: 'Monthly price in cents (e.g. 1500 = $15)' },
        { name: 'monitor_limit', type: 'number', placeholder: 'Monitor limit (-1 = unlimited)', value: '50' },
        { name: 'team_member_limit', type: 'number', placeholder: 'Team member limit (-1 = unlimited)', value: '5' },
        { name: 'check_interval_min', type: 'number', placeholder: 'Min check interval (seconds)', value: '60' },
      ],
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Create',
          handler: (data) => {
            this.api.post<any>('/super-admin/plans', {
              name: data.name,
              slug: data.slug,
              price_monthly: parseInt(data.price_monthly) || 0,
              price_yearly: (parseInt(data.price_monthly) || 0) * 10,
              monitor_limit: parseInt(data.monitor_limit) || 50,
              team_member_limit: parseInt(data.team_member_limit) || 5,
              check_interval_min: parseInt(data.check_interval_min) || 60,
              status_page_limit: 3,
              api_rate_limit: 100,
              data_retention_days: 90,
              display_order: 10,
              active: true,
              features: ['email_alerts', 'slack_alerts', 'api_access'],
            }).subscribe({
              next: async () => {
                this.load();
                const toast = await this.toastCtrl.create({ message: 'Plan created', color: 'success', duration: 2000, position: 'bottom' });
                await toast.present();
              },
              error: async (err: any) => {
                const toast = await this.toastCtrl.create({ message: err?.message || 'Failed to create plan', color: 'danger', duration: 4000, position: 'bottom' });
                await toast.present();
              },
            });
          },
        },
      ],
    });
    await alert.present();
  }

  async onEdit(plan: Plan): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: `Edit: ${plan.name}`,
      inputs: [
        { name: 'name', type: 'text', value: plan.name, placeholder: 'Name' },
        { name: 'price_monthly', type: 'number', value: String(plan.price_monthly), placeholder: 'Price (cents)' },
        { name: 'monitor_limit', type: 'number', value: String(plan.monitor_limit), placeholder: 'Monitor limit' },
        { name: 'team_member_limit', type: 'number', value: String(plan.team_member_limit), placeholder: 'Team limit' },
        { name: 'check_interval_min', type: 'number', value: String(plan.check_interval_min), placeholder: 'Min interval (s)' },
      ],
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Save',
          handler: (data) => {
            this.api.put<any>(`/super-admin/plans/${plan.id}`, {
              name: data.name,
              price_monthly: parseInt(data.price_monthly),
              monitor_limit: parseInt(data.monitor_limit),
              team_member_limit: parseInt(data.team_member_limit),
              check_interval_min: parseInt(data.check_interval_min),
            }).subscribe({
              next: async () => {
                this.load();
                const toast = await this.toastCtrl.create({ message: 'Plan updated', color: 'success', duration: 2000, position: 'bottom' });
                await toast.present();
              },
              error: async (err: any) => {
                const toast = await this.toastCtrl.create({ message: err?.message || 'Failed to update', color: 'danger', duration: 4000, position: 'bottom' });
                await toast.present();
              },
            });
          },
        },
      ],
    });
    await alert.present();
  }

  async onDuplicate(plan: Plan): Promise<void> {
    this.api.post<any>(`/super-admin/plans/${plan.id}/duplicate`).subscribe({
      next: async () => {
        this.load();
        const toast = await this.toastCtrl.create({ message: `Duplicated "${plan.name}"`, color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
      },
      error: async (err: any) => {
        const toast = await this.toastCtrl.create({ message: err?.message || 'Failed to duplicate', color: 'danger', duration: 4000, position: 'bottom' });
        await toast.present();
      },
    });
  }

  async onDelete(plan: Plan): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Plan',
      message: `Delete "${plan.name}"? This cannot be undone.`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Delete',
          role: 'destructive',
          handler: () => {
            this.api.delete<any>(`/super-admin/plans/${plan.id}`).subscribe({
              next: async () => {
                this.plans.update((list) => list.filter((p) => p.id !== plan.id));
                const toast = await this.toastCtrl.create({ message: 'Plan deleted', color: 'success', duration: 2000, position: 'bottom' });
                await toast.present();
              },
              error: async (err: any) => {
                const toast = await this.toastCtrl.create({ message: err?.message || 'Failed to delete', color: 'danger', duration: 4000, position: 'bottom' });
                await toast.present();
              },
            });
          },
        },
      ],
    });
    await alert.present();
  }
}
