import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonNote, IonSpinner,
  ToastController,
} from '@ionic/angular/standalone';
import { SlaService } from './sla.service';
import { MonitorService } from '../monitors/monitor.service';
import { FieldErrorComponent } from '../../shared/components/field-error.component';
import { showApiError } from '../../core/services/plan-error.helper';

@Component({
  selector: 'app-sla-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule, FieldErrorComponent,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonNote, IonSpinner,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/sla"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ isEdit() ? 'Edit SLA' : 'New SLA' }}</ion-title>
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
            <ion-input label="Name" labelPlacement="stacked" placeholder="SLA name" formControlName="name" required></ion-input>
          </ion-item>
          <app-field-error [control]="form.get('name')" label="Name"></app-field-error>

          <ion-item>
            <ion-select label="Monitor" labelPlacement="stacked" formControlName="monitor_id" interface="popover">
              @for (m of monitors(); track m.id) {
                <ion-select-option [value]="m.id">{{ m.name }}</ion-select-option>
              }
            </ion-select>
          </ion-item>
          <app-field-error [control]="form.get('monitor_id')" label="Monitor"></app-field-error>

          <ion-item>
            <ion-input label="Target Uptime (%)" labelPlacement="stacked" formControlName="target_uptime" type="number" placeholder="99.9" step="0.01"></ion-input>
          </ion-item>
          <app-field-error [control]="form.get('target_uptime')" label="Target uptime"></app-field-error>

          <ion-item>
            <ion-select label="Measurement Period" labelPlacement="stacked" formControlName="measurement_period" interface="popover">
              <ion-select-option value="monthly">Monthly</ion-select-option>
              <ion-select-option value="quarterly">Quarterly</ion-select-option>
              <ion-select-option value="yearly">Yearly</ion-select-option>
            </ion-select>
          </ion-item>
          <app-field-error [control]="form.get('measurement_period')" label="Measurement period"></app-field-error>
        </ion-list>

        <div style="padding: 1rem 0 2rem">
          <ion-button expand="block" (click)="onSave()" [disabled]="saving() || form.invalid">
            {{ isEdit() ? 'Update SLA' : 'Create SLA' }}
          </ion-button>
        </div>
      </form>
    </ion-content>
  `,
})
export class SlaFormComponent implements OnInit {
  isEdit = signal(false);
  saving = signal(false);
  monitors = signal<any[]>([]);
  form: FormGroup;
  private slaId: string | null = null;

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private service: SlaService,
    private monitorService: MonitorService,
    private toastCtrl: ToastController,
  ) {
    this.form = this.fb.group({
      name: ['', Validators.required],
      monitor_id: [null, Validators.required],
      target_uptime: [99.9, [Validators.required, Validators.min(90), Validators.max(100)]],
      measurement_period: ['monthly', Validators.required],
    });
  }

  ngOnInit(): void {
    this.monitorService.getMonitors({ limit: 200 }).subscribe((data) => this.monitors.set(data.items));

    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.slaId = idParam;
      this.isEdit.set(true);
      this.service.get(this.slaId).subscribe((sla) => {
        this.form.patchValue({
          name: sla.name,
          monitor_id: sla.monitor_id,
          target_uptime: sla.target_uptime,
          measurement_period: sla.measurement_period || sla.period,
        });
      });
    }
  }

  onSave(): void {
    this.form.markAllAsTouched();
    if (this.form.invalid) return;
    this.saving.set(true);

    const payload = this.form.getRawValue();
    const req$ = this.isEdit()
      ? this.service.update(this.slaId!, payload)
      : this.service.create(payload);

    req$.subscribe({
      next: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: 'Saved', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
        this.router.navigate(['/sla']);
      },
      error: async (err: any) => {
        this.saving.set(false);
        await showApiError(err, 'Failed to save SLA', this.toastCtrl, this.router);
      },
    });
  }
}
