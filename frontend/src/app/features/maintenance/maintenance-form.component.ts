import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonTextarea, IonToggle, IonSelect, IonSelectOption,
  IonSpinner, IonNote, IonSegment, IonSegmentButton, IonLabel, IonChip, IonIcon,
  ToastController,
} from '@ionic/angular/standalone';
import { MaintenanceService } from './maintenance.service';
import { showApiError } from '../../core/services/plan-error.helper';

interface DayOption {
  key: string;
  label: string;
  shortLabel: string;
  selected: boolean;
}

@Component({
  selector: 'app-maintenance-form',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonTextarea, IonToggle, IonSelect, IonSelectOption,
    IonSpinner, IonNote, IonSegment, IonSegmentButton, IonLabel, IonChip, IonIcon,
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

      <!-- ===== Schedule Type Selector ===== -->
      <ion-segment [(ngModel)]="scheduleType" (ionChange)="onScheduleTypeChange()" name="scheduleType">
        <ion-segment-button value="onetime">
          <ion-label>One-Time</ion-label>
        </ion-segment-button>
        <ion-segment-button value="recurring">
          <ion-label>Recurring</ion-label>
        </ion-segment-button>
      </ion-segment>

      <ion-list style="margin-top: 12px">

        <!-- ===== Common Fields ===== -->
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

        <!-- ===== ONE-TIME Fields ===== -->
        @if (scheduleType === 'onetime') {
          <ion-item>
            <ion-input label="Start Date & Time" labelPlacement="stacked" [(ngModel)]="form.starts_at" name="starts_at"
              type="datetime-local" required></ion-input>
          </ion-item>
          @if (submitted && !form.starts_at) {
            <ion-note color="danger" class="field-error">Start time is required</ion-note>
          }

          <ion-item>
            <ion-input label="End Date & Time" labelPlacement="stacked" [(ngModel)]="form.ends_at" name="ends_at"
              type="datetime-local" required></ion-input>
          </ion-item>
          @if (submitted && !form.ends_at) {
            <ion-note color="danger" class="field-error">End time is required</ion-note>
          }
          @if (submitted && form.starts_at && form.ends_at && form.ends_at <= form.starts_at) {
            <ion-note color="danger" class="field-error">End time must be after start time</ion-note>
          }
        }

        <!-- ===== RECURRING Fields ===== -->
        @if (scheduleType === 'recurring') {

          <!-- Repeat Pattern -->
          <ion-item>
            <ion-select label="Repeat" labelPlacement="stacked" [(ngModel)]="form.recurrence_pattern"
              name="recurrence_pattern" interface="popover">
              <ion-select-option value="daily">Daily</ion-select-option>
              <ion-select-option value="weekly">Weekly</ion-select-option>
              <ion-select-option value="biweekly">Every 2 Weeks</ion-select-option>
              <ion-select-option value="monthly">Monthly</ion-select-option>
            </ion-select>
          </ion-item>

          <!-- Day-of-Week Chips (hidden for daily) -->
          @if (form.recurrence_pattern !== 'daily') {
            <div class="form-section">
              <div class="form-section-label">Days of Week</div>
              <div class="day-chips">
                @for (day of days; track day.key) {
                  <ion-chip
                    [color]="day.selected ? 'primary' : 'medium'"
                    [outline]="!day.selected"
                    (click)="toggleDay(day)">
                    <ion-label>{{ day.shortLabel }}</ion-label>
                  </ion-chip>
                }
              </div>
              @if (submitted && !hasSelectedDays()) {
                <ion-note color="danger" class="field-error">Select at least one day</ion-note>
              }
            </div>
          }

          <!-- Time Window -->
          <div class="form-section">
            <div class="form-section-label">Time Window</div>
            <div class="time-row">
              <ion-item class="time-input">
                <ion-input label="Start Time" labelPlacement="stacked" [(ngModel)]="form.recurrence_time_start"
                  name="recurrence_time_start" type="time" required></ion-input>
              </ion-item>
              <span class="time-separator">to</span>
              <ion-item class="time-input">
                <ion-input label="End Time" labelPlacement="stacked" [(ngModel)]="form.recurrence_time_end"
                  name="recurrence_time_end" type="time" required></ion-input>
              </ion-item>
            </div>
          </div>
          @if (submitted && !form.recurrence_time_start) {
            <ion-note color="danger" class="field-error">Start time is required</ion-note>
          }
          @if (submitted && !form.recurrence_time_end) {
            <ion-note color="danger" class="field-error">End time is required</ion-note>
          }

          <!-- Effective Date Range -->
          <ion-item>
            <ion-input label="Effective From" labelPlacement="stacked" [(ngModel)]="form.effective_from"
              name="effective_from" type="date" required></ion-input>
          </ion-item>
          @if (submitted && !form.effective_from) {
            <ion-note color="danger" class="field-error">Effective from date is required</ion-note>
          }

          <ion-item>
            <ion-input label="Effective Until (optional)" labelPlacement="stacked" [(ngModel)]="form.recurrence_end_date"
              name="recurrence_end_date" type="date" placeholder="Leave empty for no end date"></ion-input>
          </ion-item>

          <!-- Preview -->
          @if (recurringPreview) {
            <div class="recurring-preview">
              <ion-note color="primary">{{ recurringPreview }}</ion-note>
            </div>
          }
        }

        <!-- ===== Common Toggles ===== -->
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

    .form-section {
      padding: 12px 16px 4px;
    }
    .form-section-label {
      font-size: 0.75rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--ion-color-medium);
      margin-bottom: 8px;
    }

    .day-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
    }
    .day-chips ion-chip {
      cursor: pointer;
      margin: 0;
      --padding-start: 10px;
      --padding-end: 10px;
    }

    .time-row {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .time-input {
      flex: 1;
    }
    .time-separator {
      font-size: 0.875rem;
      color: var(--ion-color-medium);
      padding-top: 20px;
    }

    .recurring-preview {
      padding: 12px 16px;
      margin: 4px 0;
      background: var(--ion-color-primary-tint);
      border-radius: 8px;
    }
    .recurring-preview ion-note {
      font-size: 0.85rem;
      font-style: italic;
    }
  `],
})
export class MaintenanceFormComponent implements OnInit {
  scheduleType: 'onetime' | 'recurring' = 'onetime';

  form: any = {
    title: '',
    description: '',
    // One-time fields
    starts_at: '',
    ends_at: '',
    // Recurring fields
    recurrence_pattern: 'weekly',
    recurrence_time_start: '',
    recurrence_time_end: '',
    effective_from: '',
    recurrence_end_date: '',
    // Common toggles
    auto_suppress_alerts: true,
    notify_subscribers: false,
  };

  days: DayOption[] = [
    { key: 'mon', label: 'Monday',    shortLabel: 'Mon', selected: false },
    { key: 'tue', label: 'Tuesday',   shortLabel: 'Tue', selected: false },
    { key: 'wed', label: 'Wednesday', shortLabel: 'Wed', selected: false },
    { key: 'thu', label: 'Thursday',  shortLabel: 'Thu', selected: false },
    { key: 'fri', label: 'Friday',    shortLabel: 'Fri', selected: false },
    { key: 'sat', label: 'Saturday',  shortLabel: 'Sat', selected: false },
    { key: 'sun', label: 'Sunday',    shortLabel: 'Sun', selected: false },
  ];

  saving = false;
  submitted = false;
  isEdit = false;
  editId: number | null = null;
  loadingData = false;

  get recurringPreview(): string {
    if (this.scheduleType !== 'recurring') return '';
    if (!this.form.recurrence_time_start || !this.form.recurrence_time_end) return '';

    const pattern = this.form.recurrence_pattern;
    const startTime = this.form.recurrence_time_start;
    const endTime = this.form.recurrence_time_end;

    if (pattern === 'daily') {
      return `Every day from ${startTime} to ${endTime}`;
    }

    const selectedDays = this.days.filter(d => d.selected).map(d => d.label);
    if (selectedDays.length === 0) return '';

    const dayList = selectedDays.length <= 3
      ? selectedDays.join(', ')
      : selectedDays.slice(0, 2).join(', ') + ` +${selectedDays.length - 2} more`;

    const freq = pattern === 'biweekly' ? 'Every 2 weeks' : pattern === 'monthly' ? 'Monthly' : 'Every';

    return `${freq} ${dayList}, ${startTime} - ${endTime}`;
  }

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
          const isRecurring = item.is_recurring ?? false;
          this.scheduleType = isRecurring ? 'recurring' : 'onetime';

          this.form = {
            title: item.title || '',
            description: item.description || '',
            starts_at: isRecurring ? '' : this.toDatetimeLocal(item.starts_at),
            ends_at: isRecurring ? '' : this.toDatetimeLocal(item.ends_at),
            recurrence_pattern: item.recurrence_pattern || 'weekly',
            recurrence_time_start: item.recurrence_time_start || '',
            recurrence_time_end: item.recurrence_time_end || '',
            effective_from: item.effective_from || '',
            recurrence_end_date: item.recurrence_end_date || '',
            auto_suppress_alerts: item.auto_suppress_alerts ?? true,
            notify_subscribers: item.notify_subscribers ?? false,
          };

          // Restore selected days from JSON
          const savedDays: string[] = this.parseDays(item.recurrence_days);
          this.days.forEach(d => d.selected = savedDays.includes(d.key));

          // Backward compatibility: if recurring but no time fields, extract from starts_at/ends_at
          if (isRecurring && !this.form.recurrence_time_start && item.starts_at) {
            this.form.recurrence_time_start = this.extractTime(item.starts_at);
            this.form.recurrence_time_end = this.extractTime(item.ends_at);
            if (!this.form.effective_from) {
              this.form.effective_from = this.extractDate(item.starts_at);
            }
          }

          this.loadingData = false;
        },
        error: () => {
          this.loadingData = false;
          this.router.navigate(['/maintenance']);
        },
      });
    }
  }

  onScheduleTypeChange(): void {
    this.submitted = false;
  }

  toggleDay(day: DayOption): void {
    day.selected = !day.selected;
  }

  hasSelectedDays(): boolean {
    return this.form.recurrence_pattern === 'daily' || this.days.some(d => d.selected);
  }

  onSave(): void {
    this.submitted = true;

    if (!this.form.title) return;

    if (this.scheduleType === 'onetime') {
      if (!this.form.starts_at || !this.form.ends_at) return;
      if (this.form.ends_at <= this.form.starts_at) return;
    } else {
      if (!this.form.recurrence_time_start || !this.form.recurrence_time_end) return;
      if (!this.form.effective_from) return;
      if (this.form.recurrence_pattern !== 'daily' && !this.hasSelectedDays()) return;
    }

    this.saving = true;

    const payload: any = {
      title: this.form.title,
      description: this.form.description || null,
      auto_suppress_alerts: this.form.auto_suppress_alerts,
      notify_subscribers: this.form.notify_subscribers,
    };

    if (!this.isEdit) {
      payload.status = 'scheduled';
    }

    if (this.scheduleType === 'onetime') {
      payload.is_recurring = false;
      payload.starts_at = this.form.starts_at;
      payload.ends_at = this.form.ends_at;
      // Clear recurring fields
      payload.recurrence_pattern = null;
      payload.recurrence_days = null;
      payload.recurrence_time_start = null;
      payload.recurrence_time_end = null;
      payload.effective_from = null;
      payload.recurrence_end_date = null;
    } else {
      payload.is_recurring = true;
      payload.recurrence_pattern = this.form.recurrence_pattern;
      payload.recurrence_time_start = this.form.recurrence_time_start;
      payload.recurrence_time_end = this.form.recurrence_time_end;
      payload.effective_from = this.form.effective_from;

      // Build day selection
      const selectedDayKeys = this.form.recurrence_pattern === 'daily'
        ? ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']
        : this.days.filter(d => d.selected).map(d => d.key);
      payload.recurrence_days = JSON.stringify(selectedDayKeys);

      payload.recurrence_end_date = this.form.recurrence_end_date || null;

      // For recurring, set starts_at/ends_at from effective_from + time for DB compatibility
      payload.starts_at = `${this.form.effective_from}T${this.form.recurrence_time_start}`;
      // If end time is before start time (overnight window), add one day to ends_at
      if (this.form.recurrence_time_end < this.form.recurrence_time_start) {
        const nextDay = new Date(this.form.effective_from);
        nextDay.setDate(nextDay.getDate() + 1);
        const pad = (n: number) => n.toString().padStart(2, '0');
        const nextDayStr = `${nextDay.getFullYear()}-${pad(nextDay.getMonth() + 1)}-${pad(nextDay.getDate())}`;
        payload.ends_at = `${nextDayStr}T${this.form.recurrence_time_end}`;
      } else {
        payload.ends_at = `${this.form.effective_from}T${this.form.recurrence_time_end}`;
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

  // --- Helpers ---

  private toDatetimeLocal(dateStr: string): string {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const pad = (n: number) => n.toString().padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  private extractTime(dateStr: string): string {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    const pad = (n: number) => n.toString().padStart(2, '0');
    return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  private extractDate(dateStr: string): string {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '';
    const pad = (n: number) => n.toString().padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  }

  private parseDays(value: any): string[] {
    if (!value) return [];
    if (Array.isArray(value)) return value;
    try {
      const parsed = JSON.parse(value);
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  }
}
