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
import { ApiService } from '../../core/services/api.service';
import { FieldErrorComponent } from '../../shared/components/field-error.component';
import { showApiError } from '../../core/services/plan-error.helper';
import { RouterLink } from '@angular/router';

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
  voice_call: {
    label: 'Voice Call',
    fields: [
      { key: 'phone_numbers', label: 'Phone Numbers', placeholder: '+1234567890 (E.164 format)', multi: true },
      { key: 'ring_timeout', label: 'Ring Timeout (seconds)', placeholder: '30' },
      { key: 'max_escalation_attempts', label: 'Max Escalation Attempts', placeholder: '3' },
    ],
  },
};

@Component({
  selector: 'app-channel-form',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule, FieldErrorComponent, RouterLink,
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

        <!-- Team member selection for person-to-person channels -->
        @if (isPersonToPersonChannel()) {
          @if (form.get('type')?.value === 'email') {
            @if (teamMembers().length > 0) {
              <div style="padding: 8px 0">
                <ion-label style="font-size: 0.85rem; font-weight: 600; padding: 0 0 4px">Team Members to Notify</ion-label>
                @for (member of teamMembers(); track member.id) {
                  <ion-chip [color]="member.selected ? 'primary' : 'medium'" [outline]="!member.selected"
                    (click)="toggleMember(member)" style="height: 32px; cursor: pointer">
                    {{ member.email }}
                  </ion-chip>
                }
              </div>
            } @else {
              <div style="padding: 12px 0">
                <ion-note color="medium">
                  Add team members to notify them.
                  <a routerLink="/settings/team" style="color: var(--ion-color-primary)">Go to Team Settings</a>
                </ion-note>
              </div>
            }
          }

          @if (form.get('type')?.value === 'voice_call' || form.get('type')?.value === 'sms' || form.get('type')?.value === 'whatsapp') {
            @if (teamMembersWithPhone().length > 0) {
              <div style="padding: 8px 0">
                <ion-label style="font-size: 0.85rem; font-weight: 600; padding: 0 0 4px">Team Members to Notify</ion-label>
                @for (member of teamMembersWithPhone(); track member.id) {
                  <ion-chip [color]="member.selected ? 'primary' : 'medium'" [outline]="!member.selected"
                    (click)="toggleMember(member)" style="height: 32px; cursor: pointer">
                    {{ member.username }} ({{ member.phone_number }})
                  </ion-chip>
                }
              </div>
            } @else {
              <div style="padding: 12px 0">
                <ion-note color="medium">
                  Add team members with phone numbers to notify them.
                  <a routerLink="/settings/team" style="color: var(--ion-color-primary)">Go to Team Settings</a>
                </ion-note>
              </div>
            }
          }
        }

        <!-- Voice Call info box -->
        @if (form.get('type')?.value === 'voice_call') {
          <div class="voice-call-info">
            <p style="font-size: 0.8rem; color: var(--ion-color-medium); margin: 8px 0 4px">
              Voice calls cost <strong>3 credits</strong> per call. Requires SIP provider configuration.
            </p>
            <ion-button fill="clear" size="small" routerLink="/settings/sip" style="--padding-start: 0">
              Configure SIP Provider
            </ion-button>
          </div>
        }

        <!-- Dynamic configuration fields (only non-recipient fields for person-to-person channels) -->
        <ion-list>
          @for (field of currentFields(); track field.key) {
            @if (isPersonToPersonChannel() && isRecipientField(field.key)) {
              <!-- Skip raw text input for recipients on person-to-person channels -->
            } @else if (field.multi) {
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
  teamMembers = signal<any[]>([]);
  form: FormGroup;
  configValues: Record<string, any> = {};
  chipValues: Record<string, string[]> = {};
  private channelId: string | null = null;

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
    { value: 'voice_call', label: 'Voice Call' },
  ];

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private service: ChannelService,
    private api: ApiService,
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

    // Load team members for recipient selection
    this.api.get<any>('/users').subscribe(data => {
      this.teamMembers.set((data.users || data.items || []).map((u: any) => ({...u, selected: false})));
    });

    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.channelId = idParam;
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
          this.preselectMembers(channel.type, channel.configuration || {});
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

  toggleMember(member: any): void {
    member.selected = !member.selected;
    this.syncMembersToConfig();
  }

  teamMembersWithPhone(): any[] {
    return this.teamMembers().filter(m => m.phone_number);
  }

  /** Whether the current channel type is a person-to-person channel */
  isPersonToPersonChannel(): boolean {
    const type = this.form.get('type')?.value;
    return ['email', 'sms', 'whatsapp', 'voice_call'].includes(type);
  }

  /** Whether a field key is a recipient/phone field that should be hidden for p2p channels */
  isRecipientField(key: string): boolean {
    return ['recipients', 'phone_numbers'].includes(key);
  }

  syncMembersToConfig(): void {
    const type = this.form.get('type')?.value;
    if (type === 'email') {
      const selectedEmails = this.teamMembers().filter(m => m.selected).map(m => m.email);
      const currentRecipients = this.chipValues['recipients'] || [];
      // Add selected member emails that aren't already in the list
      const combined = [...new Set([...currentRecipients, ...selectedEmails])];
      // Remove unselected member emails
      const memberEmails = this.teamMembers().map(m => m.email);
      const final = combined.filter(r => !memberEmails.includes(r) || selectedEmails.includes(r));
      this.chipValues['recipients'] = final;
    } else if (type === 'sms' || type === 'whatsapp' || type === 'voice_call') {
      const selectedPhones = this.teamMembers().filter(m => m.selected && m.phone_number).map(m => m.phone_number);
      const currentPhones = this.chipValues['phone_numbers'] || [];
      const combined = [...new Set([...currentPhones, ...selectedPhones])];
      const memberPhones = this.teamMembers().filter(m => m.phone_number).map(m => m.phone_number);
      const final = combined.filter(p => !memberPhones.includes(p) || selectedPhones.includes(p));
      this.chipValues['phone_numbers'] = final;
    }
  }

  private preselectMembers(type: string, configuration: any): void {
    if (type === 'email') {
      const recipients: any[] = Array.isArray(configuration.recipients) ? configuration.recipients : [];
      this.teamMembers().forEach(m => {
        // Handle new format (user_id objects) and old format (plain strings)
        m.selected = recipients.some((r: any) =>
          typeof r === 'object' && r !== null
            ? r.user_id === m.id
            : r === m.email
        );
      });
    } else if (type === 'sms' || type === 'whatsapp' || type === 'voice_call') {
      const phones: any[] = Array.isArray(configuration.phone_numbers) ? configuration.phone_numbers : [];
      this.teamMembers().forEach(m => {
        // Handle new format (user_id objects) and old format (plain strings)
        m.selected = m.phone_number && phones.some((p: any) =>
          typeof p === 'object' && p !== null
            ? p.user_id === m.id
            : p === m.phone_number
        );
      });
    }
  }

  private buildConfiguration(): any {
    const config: any = {};
    const type = this.form.get('type')?.value;
    const fields = this.currentFields();

    for (const field of fields) {
      if (field.multi) {
        config[field.key] = this.chipValues[field.key] || [];
      } else {
        config[field.key] = this.configValues[field.key] || '';
      }
    }

    // For person-to-person channels, emit user_id references from selected members
    if (this.isPersonToPersonChannel()) {
      const selectedMembers = this.teamMembers().filter(m => m.selected);
      const recipients = selectedMembers.map((m: any) => ({ user_id: m.id, type: 'member' }));

      if (type === 'email') {
        config['recipients'] = recipients;
      } else if (type === 'sms' || type === 'whatsapp' || type === 'voice_call') {
        config['phone_numbers'] = recipients;
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
        const from = this.route.snapshot.queryParamMap.get('from');
        this.router.navigate([from === 'onboarding' ? '/onboarding' : '/channels']);
      },
      error: async (err: any) => {
        this.saving.set(false);
        await showApiError(err, this.isEdit() ? 'Failed to update channel' : 'Failed to create channel', this.toastCtrl, this.router);
      },
    });
  }
}
