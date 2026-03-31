import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonTextarea, IonToggle, IonSelect, IonSelectOption,
  IonSpinner, IonNote,
  ToastController,
} from '@ionic/angular/standalone';
import { MaintenanceService } from './maintenance.service';
import { showApiError } from '../../core/services/plan-error.helper';

@Component({
  selector: 'app-maintenance-form',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonTextarea, IonToggle, IonSelect, IonSelectOption,
    IonSpinner, IonNote,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/maintenance"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ isEdit ? 'Edit' : 'New' }} Maintenance Window</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      @if (loadingData) {
        <div style="text-align: center; padding: 2rem">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {
      <ion-list>
        <ion-item>
          <ion-input label="Title" labelPlacement="stacked" [(ngModel)]="form.title" name="title" required
            placeholder="e.g. Database maintenance" enterkeyhint="next"></ion-input>
        </ion-item>
        @if (submitted && !form.title) {
          <ion-note color="danger" class="field-error">Title is required</ion-note>
        }

        <ion-item>
          <ion-textarea label="Description" labelPlacement="stacked" [(ngModel)]="form.description" name="description"
            rows="3" placeholder="What will be done during this maintenance?"></ion-textarea>
        </ion-item>

        <ion-item>
          <ion-input label="Start Time" labelPlacement="stacked" [(ngModel)]="form.starts_at" name="starts_at"
            type="datetime-local" required></ion-input>
        </ion-item>
        @if (submitted && !form.starts_at) {
          <ion-note color="danger" class="field-error">Start time is required</ion-note>
        }

        <ion-item>
          <ion-input label="End Time" labelPlacement="stacked" [(ngModel)]="form.ends_at" name="ends_at"
            type="datetime-local" required></ion-input>
        </ion-item>
        @if (submitted && !form.ends_at) {
          <ion-note color="danger" class="field-error">End time is required</ion-note>
        }
        @if (submitted && form.starts_at && form.ends_at && form.ends_at <= form.starts_at) {
          <ion-note color="danger" class="field-error">End time must be after start time</ion-note>
        }

        <ion-item>
          <ion-toggle [(ngModel)]="form.auto_suppress_alerts" name="auto_suppress_alerts">
            Suppress alerts during maintenance
          </ion-toggle>
        </ion-item>

        <ion-item>
          <ion-toggle [(ngModel)]="form.notify_subscribers" name="notify_subscribers">
            Notify subscribers
          </ion-toggle>
        </ion-item>

        <!-- Recurring -->
        <ion-item>
          <ion-toggle [(ngModel)]="form.is_recurring" name="is_recurring">
            Recurring maintenance
          </ion-toggle>
        </ion-item>

        @if (form.is_recurring) {
          <ion-item>
            <ion-select label="Repeat" labelPlacement="stacked" [(ngModel)]="form.recurrence_pattern" name="recurrence_pattern" interface="popover">
              <ion-select-option value="daily">Daily</ion-select-option>
              <ion-select-option value="weekly">Weekly</ion-select-option>
              <ion-select-option value="biweekly">Every 2 weeks</ion-select-option>
              <ion-select-option value="monthly">Monthly</ion-select-option>
            </ion-select>
          </ion-item>

          <ion-item>
            <ion-input label="Repeat Until (optional)" labelPlacement="stacked" [(ngModel)]="form.recurrence_end_date"
              name="recurrence_end_date" type="date" placeholder="Leave empty for no end"></ion-input>
          </ion-item>
        }
      </ion-list>

      <ion-button expand="block" (click)="onSave()" [disabled]="saving" style="margin-top: 16px">
        @if (saving) { <ion-spinner name="crescent"></ion-spinner> }
        @else { {{ isEdit ? 'Update' : 'Create' }} Maintenance Window }
      </ion-button>
      }
    </ion-content>
  `,
  styles: [`
    .field-error { display: block; padding: 4px 16px; font-size: 0.75rem; }
  `],
})
export class MaintenanceFormComponent implements OnInit {
  form: any = {
    title: '',
    description: '',
    starts_at: '',
    ends_at: '',
    auto_suppress_alerts: true,
    notify_subscribers: false,
    is_recurring: false,
    recurrence_pattern: 'weekly',
    recurrence_end_date: '',
  };
  saving = false;
  submitted = false;
  isEdit = false;
  editId: number | null = null;
  loadingData = false;

  constructor(
    private service: MaintenanceService,
    private router: Router,
    private route: ActivatedRoute,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.isEdit = true;
      this.editId = +idParam;
      this.loadingData = true;
      this.service.get(this.editId).subscribe({
        next: (item) => {
          this.form = {
            title: item.title || '',
            description: item.description || '',
            starts_at: this.toDatetimeLocal(item.starts_at),
            ends_at: this.toDatetimeLocal(item.ends_at),
            auto_suppress_alerts: item.auto_suppress_alerts ?? true,
            notify_subscribers: item.notify_subscribers ?? false,
            is_recurring: item.is_recurring ?? false,
            recurrence_pattern: item.recurrence_pattern || 'weekly',
            recurrence_end_date: item.recurrence_end_date || '',
          };
          this.loadingData = false;
        },
        error: () => {
          this.loadingData = false;
          this.router.navigate(['/maintenance']);
        },
      });
    }
  }

  private toDatetimeLocal(dateStr: string): string {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const pad = (n: number) => n.toString().padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  onSave(): void {
    this.submitted = true;
    if (!this.form.title || !this.form.starts_at || !this.form.ends_at) return;
    if (this.form.ends_at <= this.form.starts_at) return;
    this.saving = true;

    const payload: any = {
      title: this.form.title,
      description: this.form.description || null,
      starts_at: this.form.starts_at,
      ends_at: this.form.ends_at,
      auto_suppress_alerts: this.form.auto_suppress_alerts,
      notify_subscribers: this.form.notify_subscribers,
    };

    if (!this.isEdit) {
      payload.status = 'scheduled';
    }

    if (this.form.is_recurring) {
      payload.is_recurring = true;
      payload.recurrence_pattern = this.form.recurrence_pattern;
      if (this.form.recurrence_end_date) {
        payload.recurrence_end_date = this.form.recurrence_end_date;
      }
    }

    const request$ = this.isEdit
      ? this.service.update(this.editId!, payload)
      : this.service.create(payload);

    request$.subscribe({
      next: async () => {
        this.saving = false;
        const toast = await this.toastCtrl.create({
          message: this.isEdit ? 'Maintenance window updated' : 'Maintenance window created',
          color: 'success', duration: 2000, position: 'bottom',
        });
        await toast.present();
        this.router.navigate(['/maintenance']);
      },
      error: async (err: any) => {
        this.saving = false;
        await showApiError(err, this.isEdit ? 'Failed to update maintenance window' : 'Failed to create maintenance window', this.toastCtrl, this.router);
      },
    });
  }
}
