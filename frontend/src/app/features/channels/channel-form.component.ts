import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonToggle, IonNote, IonSpinner,
  IonChip, IonLabel, IonIcon,
  ToastController,
} from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { closeCircle } from 'ionicons/icons';

addIcons({ closeCircle });
import { ChannelService } from './channel.service';
import { FieldErrorComponent } from '../../shared/components/field-error.component';
import { showApiError } from '../../core/services/plan-error.helper';

interface ChannelTypeConfig {
  label: string;
  fields: { key: string; label: string; placeholder: string; type?: string; multi?: boolean }[];
}

const CHANNEL_TYPE_CONFIGS: Record<string, ChannelTypeConfig> = {
  email: {
    label: 'Email',
    fields: [
      { key: 'recipients', label: 'Recipients', placeholder: 'Enter email and press Enter', multi: true },
    ],
  },
  slack: {
    label: 'Slack',
    fields: [
      { key: 'webhook_url', label: 'Webhook URL', placeholder: 'https://hooks.slack.com/services/...' },
    ],
  },
  discord: {
    label: 'Discord',
    fields: [
      { key: 'webhook_url', label: 'Webhook URL', placeholder: 'https://discord.com/api/webhooks/...' },
    ],
  },
  telegram: {
    label: 'Telegram',
    fields: [
      { key: 'bot_token', label: 'Bot Token', placeholder: '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11' },
      { key: 'chat_id', label: 'Chat ID', placeholder: '-1001234567890' },
    ],
  },
  sms: {
    label: 'SMS',
    fields: [
      { key: 'phone_numbers', label: 'Phone Numbers', placeholder: '+1234567890', multi: true },
    ],
  },
  whatsapp: {
    label: 'WhatsApp',
    fields: [
      { key: 'phone_numbers', label: 'Phone Numbers', placeholder: '+1234567890', multi: true },
    ],
  },
  pagerduty: {
    label: 'PagerDuty',
    fields: [
      { key: 'routing_key', label: 'Routing Key', placeholder: 'Enter PagerDuty routing key' },
    ],
  },
  opsgenie: {
    label: 'OpsGenie',
    fields: [
      { key: 'api_key', label: 'API Key', placeholder: 'Enter OpsGenie API key' },
    ],
  },
  webhook: {
    label: 'Webhook',
    fields: [
      { key: 'url', label: 'URL', placeholder: 'https://your-endpoint.com/webhook' },
      { key: 'secret', label: 'Secret', placeholder: 'Optional shared secret for signing', type: 'password' },
    ],
  },
};

@Component({
  selector: 'app-channel-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule, FieldErrorComponent,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonToggle, IonNote, IonSpinner,
    IonChip, IonLabel, IonIcon,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/channels"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ isEdit() ? 'Edit' : 'New' }} Channel</ion-title>
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
            <ion-input label="Name" labelPlacement="stacked" placeholder="Channel name" formControlName="name" required></ion-input>
          </ion-item>
          <app-field-error [control]="form.get('name')" label="Name"></app-field-error>

          <ion-item>
            <ion-select label="Type" labelPlacement="stacked" formControlName="type" interface="popover" (ionChange)="onTypeChange()">
              @for (t of channelTypes; track t.value) {
                <ion-select-option [value]="t.value">{{ t.label }}</ion-select-option>
              }
            </ion-select>
          </ion-item>
        </ion-list>

        <!-- Dynamic configuration fields -->
        <ion-list>
          @for (field of currentFields(); track field.key) {
            @if (field.multi) {
              <ion-item>
                <ion-label position="stacked">{{ field.label }}</ion-label>
                <div class="recipient-chips">
                  @for (val of getChipValues(field.key); track val) {
                    <ion-chip (click)="removeChipValue(field.key, val)">
                      <ion-label>{{ val }}</ion-label>
                      <ion-icon name="close-circle"></ion-icon>
                    </ion-chip>
                  }
                </div>
                <ion-input [placeholder]="field.placeholder" (keyup.enter)="addChipValue(field.key, $event)" [value]="''"></ion-input>
              </ion-item>
            } @else {
              <ion-item>
                <ion-input
                  [label]="field.label"
                  labelPlacement="stacked"
                  [placeholder]="field.placeholder"
                  [type]="field.type || 'text'"
                  [value]="configValues[field.key] || ''"
                  (ionInput)="onConfigInput(field.key, $event)"
                ></ion-input>
              </ion-item>
            }
          }
        </ion-list>

        <ion-list>
          <ion-item>
            <ion-toggle formControlName="active">Active</ion-toggle>
          </ion-item>
        </ion-list>

        <div style="padding: 1rem 0 2rem">
          <ion-button expand="block" (click)="onSave()" [disabled]="saving() || form.invalid">
            {{ isEdit() ? 'Update Channel' : 'Create Channel' }}
          </ion-button>
        </div>
      </form>
      }
    </ion-content>
  `,
  styles: [`
    .recipient-chips { display: flex; flex-wrap: wrap; gap: 4px; padding: 4px 0; }
    .recipient-chips ion-chip { height: 28px; font-size: 0.8rem; }
  `],
})
export class ChannelFormComponent implements OnInit {
  isEdit = signal(false);
  saving = signal(false);
  loadingData = signal(false);
  currentFields = signal<{ key: string; label: string; placeholder: string; type?: string; multi?: boolean }[]>([]);
  form: FormGroup;
  configValues: Record<string, any> = {};
  chipValues: Record<string, string[]> = {};
  private channelId: number | null = null;

  channelTypes = [
    { value: 'email', label: 'Email' },
    { value: 'slack', label: 'Slack' },
    { value: 'discord', label: 'Discord' },
    { value: 'telegram', label: 'Telegram' },
    { value: 'sms', label: 'SMS' },
    { value: 'whatsapp', label: 'WhatsApp' },
    { value: 'pagerduty', label: 'PagerDuty' },
    { value: 'opsgenie', label: 'OpsGenie' },
    { value: 'webhook', label: 'Webhook' },
  ];

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private service: ChannelService,
    private toastCtrl: ToastController,
  ) {
    this.form = this.fb.group({
      name: ['', Validators.required],
      type: ['email', Validators.required],
      active: [true],
    });
  }

  ngOnInit(): void {
    this.updateFieldsForType('email');

    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.channelId = Number(idParam);
      this.isEdit.set(true);
      this.loadingData.set(true);
      this.service.get(this.channelId).subscribe({
        next: (channel) => {
          this.form.patchValue({
            name: channel.name,
            type: channel.type,
            active: channel.active,
          });
          this.updateFieldsForType(channel.type);
          this.populateConfig(channel.configuration || {});
          this.loadingData.set(false);
        },
        error: () => {
          this.loadingData.set(false);
          this.router.navigate(['/channels']);
        },
      });
    }
  }

  onTypeChange(): void {
    const type = this.form.get('type')?.value || 'email';
    this.configValues = {};
    this.chipValues = {};
    this.updateFieldsForType(type);
  }

  private updateFieldsForType(type: string): void {
    const config = CHANNEL_TYPE_CONFIGS[type];
    this.currentFields.set(config ? config.fields : []);
  }

  private populateConfig(configuration: any): void {
    const fields = this.currentFields();
    for (const field of fields) {
      if (field.multi) {
        const val = configuration[field.key];
        this.chipValues[field.key] = Array.isArray(val) ? [...val] : [];
      } else {
        this.configValues[field.key] = configuration[field.key] || '';
      }
    }
  }

  onConfigInput(key: string, event: any): void {
    this.configValues[key] = event.detail?.value || event.target?.value || '';
  }

  getChipValues(key: string): string[] {
    return this.chipValues[key] || [];
  }

  addChipValue(key: string, event: any): void {
    const value = (event.target?.value || '').trim();
    if (!value) return;
    if (!this.chipValues[key]) {
      this.chipValues[key] = [];
    }
    if (!this.chipValues[key].includes(value)) {
      this.chipValues[key] = [...this.chipValues[key], value];
    }
    event.target.value = '';
  }

  removeChipValue(key: string, value: string): void {
    if (this.chipValues[key]) {
      this.chipValues[key] = this.chipValues[key].filter(v => v !== value);
    }
  }

  private buildConfiguration(): any {
    const config: any = {};
    const fields = this.currentFields();
    for (const field of fields) {
      if (field.multi) {
        config[field.key] = this.chipValues[field.key] || [];
      } else {
        config[field.key] = this.configValues[field.key] || '';
      }
    }
    return config;
  }

  onSave(): void {
    this.form.markAllAsTouched();
    if (this.form.invalid) return;
    this.saving.set(true);

    const val = this.form.getRawValue();
    const payload = {
      name: val.name,
      type: val.type,
      active: val.active,
      configuration: this.buildConfiguration(),
    };

    const req$ = this.isEdit()
      ? this.service.update(this.channelId!, payload)
      : this.service.create(payload);

    req$.subscribe({
      next: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({
          message: this.isEdit() ? 'Channel updated' : 'Channel created',
          color: 'success', duration: 2000, position: 'bottom',
        });
        await toast.present();
        this.router.navigate(['/channels']);
      },
      error: async (err: any) => {
        this.saving.set(false);
        await showApiError(err, this.isEdit() ? 'Failed to update channel' : 'Failed to create channel', this.toastCtrl, this.router);
      },
    });
  }
}
