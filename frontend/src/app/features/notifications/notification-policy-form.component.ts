import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, FormArray, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonToggle, IonLabel,
  IonNote, IonSpinner, IonIcon, IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  IonAccordionGroup, IonAccordion, IonCheckbox,
  ToastController,
} from '@ionic/angular/standalone';
import { NotificationPolicyService } from './notification-policy.service';
import { ChannelService, NotificationChannel } from '../channels/channel.service';
import { FieldErrorComponent } from '../../shared/components/field-error.component';
import { showApiError } from '../../core/services/plan-error.helper';
import { addIcons } from 'ionicons';
import { addCircleOutline, removeCircleOutline } from 'ionicons/icons';

addIcons({ addCircleOutline, removeCircleOutline });

@Component({
  selector: 'app-notification-policy-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule, FieldErrorComponent,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonToggle, IonLabel,
    IonNote, IonSpinner, IonIcon, IonCard, IonCardHeader, IonCardTitle, IonCardContent,
    IonAccordionGroup, IonAccordion, IonCheckbox,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/notifications"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ isEdit() ? 'Edit Policy' : 'New Policy' }}</ion-title>
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
      @if (loadingData()) {
        <div style="text-align: center; padding: 2rem">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {
      <form [formGroup]="form">
        <ion-list>
          <ion-item>
            <ion-input label="Name" labelPlacement="stacked" placeholder="Policy name" formControlName="name" required></ion-input>
          </ion-item>
          <app-field-error [control]="form.get('name')" label="Name"></app-field-error>

          <ion-item>
            <ion-input label="Description" labelPlacement="stacked" placeholder="Optional description" formControlName="description"></ion-input>
          </ion-item>

          <ion-item>
            <ion-select label="Trigger Type" labelPlacement="stacked" formControlName="trigger_type" interface="popover">
              <ion-select-option value="down">Down</ion-select-option>
              <ion-select-option value="up">Up</ion-select-option>
              <ion-select-option value="degraded">Degraded</ion-select-option>
              <ion-select-option value="any">Any Change</ion-select-option>
            </ion-select>
          </ion-item>
        </ion-list>

        <!-- Step Chain Builder -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0 8px">
          <h3 style="margin: 0">Notification Steps</h3>
          <ion-button fill="clear" size="small" (click)="addStep()">
            <ion-icon name="add-circle-outline" slot="start"></ion-icon>
            Add Escalation Step
          </ion-button>
        </div>

        <div formArrayName="steps">
          @for (step of stepsArray.controls; track $index; let i = $index) {
            <ion-card>
              <ion-card-header>
                <ion-card-title style="font-size: 1rem; display: flex; align-items: center; justify-content: space-between">
                  @if (i === 0) {
                    Step {{ i + 1 }} &mdash; Immediately
                  } @else {
                    Step {{ i + 1 }} &mdash; After delay if not acknowledged
                  }
                  @if (i > 0) {
                    <ion-button fill="clear" color="danger" size="small" (click)="removeStep(i)">
                      <ion-icon name="remove-circle-outline"></ion-icon>
                    </ion-button>
                  }
                </ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <div [formGroupName]="i">
                  <ion-list>
                    @if (i > 0) {
                      <ion-item>
                        <ion-input label="Delay (minutes)" labelPlacement="stacked" formControlName="delay_minutes" type="number" placeholder="5"></ion-input>
                      </ion-item>
                    }
                    <ion-item>
                      <ion-select label="Channel" labelPlacement="stacked" formControlName="notification_channel_id" interface="popover">
                        @for (ch of channels(); track ch.id) {
                          <ion-select-option [value]="ch.id">{{ ch.name }} ({{ ch.type }})</ion-select-option>
                        }
                      </ion-select>
                    </ion-item>
                    <ion-item>
                      <ion-checkbox formControlName="notify_on_resolve">Notify on resolve</ion-checkbox>
                    </ion-item>
                  </ion-list>
                </div>
              </ion-card-content>
            </ion-card>
          }
        </div>

        @if (stepsArray.length === 0) {
          <div style="text-align: center; padding: 2rem; color: var(--ion-color-medium)">
            <p>No steps defined. Add at least one notification step.</p>
          </div>
        }

        <!-- Advanced section -->
        <ion-accordion-group style="margin-top: 16px">
          <ion-accordion value="advanced">
            <ion-item slot="header">
              <ion-label>Advanced</ion-label>
            </ion-item>
            <div slot="content" style="padding: 8px 16px 16px">
              <ion-list>
                <ion-item>
                  <ion-input label="Repeat every (minutes)" labelPlacement="stacked" formControlName="repeat_interval_minutes" type="number" placeholder="0 = send once"></ion-input>
                </ion-item>
                <ion-item>
                  <ion-toggle formControlName="active">Active</ion-toggle>
                </ion-item>
              </ion-list>
            </div>
          </ion-accordion>
        </ion-accordion-group>

        <div style="padding: 1rem 0 2rem">
          <ion-button expand="block" (click)="onSave()" [disabled]="saving() || form.invalid || stepsArray.length === 0">
            {{ isEdit() ? 'Update Policy' : 'Create Policy' }}
          </ion-button>
        </div>
      </form>
      }
    </ion-content>
  `,
})
export class NotificationPolicyFormComponent implements OnInit {
  isEdit = signal(false);
  saving = signal(false);
  loadingData = signal(false);
  channels = signal<NotificationChannel[]>([]);
  form: FormGroup;
  private policyId: string | null = null;

  get stepsArray(): FormArray {
    return this.form.get('steps') as FormArray;
  }

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private service: NotificationPolicyService,
    private channelService: ChannelService,
    private toastCtrl: ToastController,
  ) {
    this.form = this.fb.group({
      name: ['', Validators.required],
      description: [''],
      trigger_type: ['down', Validators.required],
      repeat_interval_minutes: [0],
      active: [true],
      steps: this.fb.array([]),
    });
  }

  ngOnInit(): void {
    // Load channels for the dropdown
    this.channelService.getAll().subscribe({
      next: (data) => this.channels.set(data.items),
    });

    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.policyId = idParam;
      this.isEdit.set(true);
      this.loadingData.set(true);
      this.service.get(this.policyId).subscribe({
        next: (policy) => {
          this.form.patchValue({
            name: policy.name,
            description: policy.description,
            trigger_type: policy.trigger_type,
            repeat_interval_minutes: policy.repeat_interval_minutes,
            active: policy.active,
          });
          (policy.steps || []).forEach((step, index) => {
            this.stepsArray.push(this.fb.group({
              delay_minutes: [step.delay_minutes, index > 0 ? [Validators.required, Validators.min(1)] : []],
              notification_channel_id: [step.notification_channel_id, Validators.required],
              notify_on_resolve: [step.notify_on_resolve],
            }));
          });
          this.loadingData.set(false);
        },
        error: () => {
          this.loadingData.set(false);
          this.router.navigate(['/notifications']);
        },
      });
    } else {
      // Add initial step
      this.addStep();
    }
  }

  addStep(): void {
    const isFirst = this.stepsArray.length === 0;
    this.stepsArray.push(this.fb.group({
      delay_minutes: [isFirst ? 0 : 5, isFirst ? [] : [Validators.required, Validators.min(1)]],
      notification_channel_id: [null, Validators.required],
      notify_on_resolve: [true],
    }));
  }

  removeStep(index: number): void {
    if (index > 0) {
      this.stepsArray.removeAt(index);
    }
  }

  onSave(): void {
    this.form.markAllAsTouched();
    if (this.form.invalid || this.stepsArray.length === 0) return;
    this.saving.set(true);

    const val = this.form.getRawValue();
    const payload = {
      name: val.name,
      description: val.description,
      trigger_type: val.trigger_type,
      repeat_interval_minutes: val.repeat_interval_minutes,
      active: val.active,
      steps: val.steps.map((s: any, index: number) => ({
        delay_minutes: index === 0 ? 0 : s.delay_minutes,
        notification_channel_id: s.notification_channel_id,
        notify_on_resolve: s.notify_on_resolve,
      })),
    };

    const req$ = this.isEdit()
      ? this.service.update(this.policyId!, payload)
      : this.service.create(payload);

    req$.subscribe({
      next: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({
          message: this.isEdit() ? 'Policy updated' : 'Policy created',
          color: 'success', duration: 2000, position: 'bottom',
        });
        await toast.present();
        const from = this.route.snapshot.queryParamMap.get('from');
        this.router.navigate([from === 'onboarding' ? '/onboarding' : '/notifications']);
      },
      error: async (err: any) => {
        this.saving.set(false);
        await showApiError(err, 'Failed to save notification policy', this.toastCtrl, this.router);
      },
    });
  }
}
