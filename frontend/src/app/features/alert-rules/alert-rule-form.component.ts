import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonToggle, IonNote, IonSpinner,
  ToastController,
} from '@ionic/angular/standalone';
import { AlertRuleService } from './alert-rule.service';
import { MonitorService } from '../monitors/monitor.service';
import { FieldErrorComponent } from '../../shared/components/field-error.component';
import { showApiError } from '../../core/services/plan-error.helper';

@Component({
  selector: 'app-alert-rule-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule, FieldErrorComponent,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonToggle, IonNote, IonSpinner,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/alert-rules"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ isEdit() ? 'Edit Alert Rule' : 'New Alert Rule' }}</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="onSave()" fill="solid" color="primary" [disabled]="saving()">
            @if (saving()) {
              <ion-spinner name="crescent" style="width: 20px; height: 20px"></ion-spinner>
            } @else {
              Save
            }
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <form [formGroup]="form">
        <ion-list>
          <ion-item>
            <ion-input label="Name" labelPlacement="stacked" placeholder="Alert rule name" formControlName="name" required></ion-input>
          </ion-item>
          <app-field-error [control]="form.get('name')" label="Name"></app-field-error>

          <ion-item>
            <ion-select label="Monitor" labelPlacement="stacked" formControlName="monitor_id" interface="popover">
              @for (m of monitors(); track m.id) {
                <ion-select-option [value]="m.id">{{ m.name }}</ion-select-option>
              }
            </ion-select>
          </ion-item>

          <ion-item>
            <ion-select label="Trigger" labelPlacement="stacked" formControlName="trigger_type" interface="popover">
              <ion-select-option value="down">Down</ion-select-option>
              <ion-select-option value="up">Up</ion-select-option>
              <ion-select-option value="degraded">Degraded</ion-select-option>
              <ion-select-option value="any">Any Change</ion-select-option>
            </ion-select>
          </ion-item>

          <ion-item>
            <ion-select label="Channel" labelPlacement="stacked" formControlName="channel" interface="popover">
              <ion-select-option value="email">Email</ion-select-option>
              <ion-select-option value="slack">Slack</ion-select-option>
              <ion-select-option value="discord">Discord</ion-select-option>
              <ion-select-option value="telegram">Telegram</ion-select-option>
              <ion-select-option value="sms">SMS</ion-select-option>
              <ion-select-option value="pagerduty">PagerDuty</ion-select-option>
              <ion-select-option value="opsgenie">OpsGenie</ion-select-option>
              <ion-select-option value="webhook">Webhook</ion-select-option>
            </ion-select>
          </ion-item>

          <ion-item>
            <ion-input label="Recipients" labelPlacement="stacked" placeholder="email1&#64;example.com, email2&#64;example.com" formControlName="recipients_raw"></ion-input>
          </ion-item>
          <app-field-error [control]="form.get('recipients_raw')" label="Recipients"></app-field-error>

          <ion-item>
            <ion-input label="Cooldown (minutes)" labelPlacement="stacked" formControlName="cooldown_minutes" type="number" placeholder="5"></ion-input>
          </ion-item>
          <app-field-error [control]="form.get('cooldown_minutes')" label="Cooldown"></app-field-error>

          <ion-item>
            <ion-toggle formControlName="active">Active</ion-toggle>
          </ion-item>
        </ion-list>

        <div style="padding: 1rem 0 2rem">
          <ion-button expand="block" (click)="onSave()" [disabled]="saving() || form.invalid">
            {{ isEdit() ? 'Update Rule' : 'Create Rule' }}
          </ion-button>
        </div>
      </form>
    </ion-content>
  `,
})
export class AlertRuleFormComponent implements OnInit {
  isEdit = signal(false);
  saving = signal(false);
  monitors = signal<any[]>([]);
  form: FormGroup;
  private ruleId: number | null = null;

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private service: AlertRuleService,
    private monitorService: MonitorService,
    private toastCtrl: ToastController,
  ) {
    this.form = this.fb.group({
      name: ['', Validators.required],
      monitor_id: [null, Validators.required],
      trigger_type: ['down', Validators.required],
      channel: ['email', Validators.required],
      recipients_raw: ['', Validators.required],
      cooldown_minutes: [5, [Validators.required, Validators.min(1)]],
      active: [true],
    });
  }

  ngOnInit(): void {
    this.monitorService.getMonitors({ limit: 200 }).subscribe((data) => this.monitors.set(data.items));

    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.ruleId = Number(idParam);
      this.isEdit.set(true);
      this.service.get(this.ruleId).subscribe((rule) => {
        this.form.patchValue({
          name: rule.name,
          monitor_id: rule.monitor_id,
          trigger_type: rule.trigger_type,
          channel: rule.channel,
          recipients_raw: (rule.recipients || []).join(', '),
          cooldown_minutes: rule.cooldown_minutes,
          active: rule.active,
        });
      });
    }
  }

  onSave(): void {
    this.form.markAllAsTouched();
    if (this.form.invalid) return;
    this.saving.set(true);

    const val = this.form.getRawValue();
    const payload = {
      name: val.name,
      monitor_id: val.monitor_id,
      trigger_type: val.trigger_type,
      channel: val.channel,
      recipients: val.recipients_raw.split(',').map((r: string) => r.trim()).filter((r: string) => r),
      cooldown_minutes: val.cooldown_minutes,
      active: val.active,
    };

    const req$ = this.isEdit()
      ? this.service.update(this.ruleId!, payload)
      : this.service.create(payload);

    req$.subscribe({
      next: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: 'Saved', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
        this.router.navigate(['/alert-rules']);
      },
      error: async (err: any) => {
        this.saving.set(false);
        await showApiError(err, 'Failed to save alert rule', this.toastCtrl, this.router);
      },
    });
  }
}
