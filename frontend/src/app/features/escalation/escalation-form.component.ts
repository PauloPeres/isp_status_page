import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, FormArray, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonToggle, IonLabel,
  IonNote, IonSpinner, IonIcon, IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  ToastController,
} from '@ionic/angular/standalone';
import { EscalationService } from './escalation.service';
import { addIcons } from 'ionicons';
import { addCircleOutline, removeCircleOutline } from 'ionicons/icons';

addIcons({ addCircleOutline, removeCircleOutline });

@Component({
  selector: 'app-escalation-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonToggle, IonLabel,
    IonNote, IonSpinner, IonIcon, IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/escalation"></ion-back-button>
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
      <form [formGroup]="form">
        <ion-list>
          <ion-item>
            <ion-input label="Name" labelPlacement="stacked" placeholder="Policy name" formControlName="name" required></ion-input>
          </ion-item>
          <ion-item>
            <ion-input label="Description" labelPlacement="stacked" placeholder="Optional description" formControlName="description"></ion-input>
          </ion-item>
          <ion-item>
            <ion-toggle formControlName="active">Active</ion-toggle>
          </ion-item>
        </ion-list>

        <!-- Steps -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0 8px">
          <h3 style="margin: 0">Escalation Steps</h3>
          <ion-button fill="clear" size="small" (click)="addStep()">
            <ion-icon name="add-circle-outline" slot="start"></ion-icon>
            Add Step
          </ion-button>
        </div>

        <div formArrayName="steps">
          @for (step of stepsArray.controls; track $index; let i = $index) {
            <ion-card>
              <ion-card-header>
                <ion-card-title style="font-size: 1rem; display: flex; align-items: center; justify-content: space-between">
                  Step {{ i + 1 }}
                  <ion-button fill="clear" color="danger" size="small" (click)="removeStep(i)">
                    <ion-icon name="remove-circle-outline"></ion-icon>
                  </ion-button>
                </ion-card-title>
              </ion-card-header>
              <ion-card-content>
                <div [formGroupName]="i">
                  <ion-list>
                    <ion-item>
                      <ion-input label="Delay (minutes)" labelPlacement="stacked" formControlName="delay_minutes" type="number" placeholder="5"></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-select label="Channel" labelPlacement="stacked" formControlName="channel" interface="popover">
                        <ion-select-option value="email">Email</ion-select-option>
                        <ion-select-option value="sms">SMS</ion-select-option>
                        <ion-select-option value="telegram">Telegram</ion-select-option>
                        <ion-select-option value="webhook">Webhook</ion-select-option>
                      </ion-select>
                    </ion-item>
                    <ion-item>
                      <ion-input label="Recipients" labelPlacement="stacked" formControlName="recipients_raw" placeholder="email1&#64;example.com, email2&#64;example.com"></ion-input>
                    </ion-item>
                  </ion-list>
                </div>
              </ion-card-content>
            </ion-card>
          }
        </div>

        @if (stepsArray.length === 0) {
          <div style="text-align: center; padding: 2rem; color: var(--ion-color-medium)">
            <p>No steps defined. Add at least one escalation step.</p>
          </div>
        }

        <div style="padding: 1rem 0 2rem">
          <ion-button expand="block" (click)="onSave()" [disabled]="saving() || form.invalid || stepsArray.length === 0">
            {{ isEdit() ? 'Update Policy' : 'Create Policy' }}
          </ion-button>
        </div>
      </form>
    </ion-content>
  `,
})
export class EscalationFormComponent implements OnInit {
  isEdit = signal(false);
  saving = signal(false);
  form: FormGroup;
  private policyId: number | null = null;

  get stepsArray(): FormArray {
    return this.form.get('steps') as FormArray;
  }

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private service: EscalationService,
    private toastCtrl: ToastController,
  ) {
    this.form = this.fb.group({
      name: ['', Validators.required],
      description: [''],
      active: [true],
      steps: this.fb.array([]),
    });
  }

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.policyId = Number(idParam);
      this.isEdit.set(true);
      this.service.get(this.policyId).subscribe((policy) => {
        this.form.patchValue({ name: policy.name, description: policy.description, active: policy.active });
        (policy.steps || []).forEach((step) => {
          this.stepsArray.push(this.fb.group({
            delay_minutes: [step.delay_minutes, [Validators.required, Validators.min(1)]],
            channel: [step.channel, Validators.required],
            recipients_raw: [(step.recipients || []).join(', '), Validators.required],
          }));
        });
      });
    } else {
      this.addStep();
    }
  }

  addStep(): void {
    this.stepsArray.push(this.fb.group({
      delay_minutes: [5, [Validators.required, Validators.min(1)]],
      channel: ['email', Validators.required],
      recipients_raw: ['', Validators.required],
    }));
  }

  removeStep(index: number): void {
    this.stepsArray.removeAt(index);
  }

  onSave(): void {
    if (this.form.invalid || this.stepsArray.length === 0) return;
    this.saving.set(true);

    const val = this.form.getRawValue();
    const payload = {
      name: val.name,
      description: val.description,
      active: val.active,
      steps: val.steps.map((s: any) => ({
        delay_minutes: s.delay_minutes,
        channel: s.channel,
        recipients: s.recipients_raw.split(',').map((r: string) => r.trim()).filter((r: string) => r),
      })),
    };

    const req$ = this.isEdit()
      ? this.service.update(this.policyId!, payload)
      : this.service.create(payload);

    req$.subscribe({
      next: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: 'Saved', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
        this.router.navigate(['/escalation']);
      },
      error: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: 'Failed to save', color: 'danger', duration: 3000, position: 'bottom' });
        await toast.present();
      },
    });
  }
}
