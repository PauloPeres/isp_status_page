import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonInput, IonToggle, IonLabel, IonNote, IonIcon, IonText,
  IonSpinner, IonSelect, IonSelectOption, IonBackButton, IonChip, IonBadge,
  ToastController,
} from '@ionic/angular/standalone';
import { SipConfigurationService, SipConfiguration } from './sip-configuration.service';
import { addIcons } from 'ionicons';
import {
  callOutline, serverOutline, checkmarkCircleOutline, closeCircleOutline,
  refreshOutline, shieldCheckmarkOutline,
} from 'ionicons/icons';

addIcons({
  callOutline, serverOutline, checkmarkCircleOutline, closeCircleOutline,
  refreshOutline, shieldCheckmarkOutline,
});

@Component({
  selector: 'app-sip-settings',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonInput, IonToggle, IonLabel, IonNote, IonIcon, IonText,
    IonSpinner, IonSelect, IonSelectOption, IonBackButton, IonChip, IonBadge,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/settings"></ion-back-button>
        </ion-buttons>
        <ion-title>Voice / SIP Configuration</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="onSave()" fill="solid" color="primary" size="small" [disabled]="saving()">
            @if (saving()) {
              <ion-spinner name="crescent" style="width: 16px; height: 16px"></ion-spinner>
            } @else {
              Save
            }
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      @if (loading()) {
        <div style="text-align: center; padding: 2rem">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {
        <div class="sip-settings-wrapper">
          <!-- Provider Selection -->
          <div class="section-card">
            <div class="section-header">
              <ion-icon name="call-outline" class="section-icon"></ion-icon>
              <div>
                <h3>Voice Provider</h3>
                <p class="section-description">Choose how voice call alerts are delivered. The default provider uses KeepUp's shared Twilio infrastructure. Custom SIP allows you to use your own telephony provider.</p>
              </div>
            </div>
            <ion-list>
              <ion-item>
                <ion-select label="Provider" labelPlacement="stacked" [(ngModel)]="config.provider" interface="popover" (ionChange)="onProviderChange()">
                  <ion-select-option value="keepup_default">KeepUp Default (Twilio)</ion-select-option>
                  <ion-select-option value="custom_sip">Custom SIP Server</ion-select-option>
                  <ion-select-option value="twilio_trunk">Twilio SIP Trunk (BYOC)</ion-select-option>
                </ion-select>
              </ion-item>
            </ion-list>
          </div>

          <!-- Custom SIP Settings -->
          @if (config.provider === 'custom_sip') {
            <div class="section-card">
              <div class="section-header">
                <ion-icon name="server-outline" class="section-icon"></ion-icon>
                <div>
                  <h3>SIP Server Settings</h3>
                  <p class="section-description">Configure your custom SIP server connection details.</p>
                </div>
              </div>
              <ion-list>
                <ion-item>
                  <ion-input label="SIP Host" labelPlacement="stacked" [(ngModel)]="config.sip_host" placeholder="sip.yourprovider.com"></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input label="SIP Port" labelPlacement="stacked" [(ngModel)]="config.sip_port" type="number" placeholder="5060"></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input label="Username" labelPlacement="stacked" [(ngModel)]="config.sip_username" placeholder="SIP account username"></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input label="Password" labelPlacement="stacked" [(ngModel)]="config.sip_password" type="password" placeholder="SIP account password"></ion-input>
                </ion-item>
                <ion-item>
                  <ion-select label="Transport" labelPlacement="stacked" [(ngModel)]="config.sip_transport" interface="popover">
                    <ion-select-option value="udp">UDP</ion-select-option>
                    <ion-select-option value="tcp">TCP</ion-select-option>
                    <ion-select-option value="tls">TLS</ion-select-option>
                  </ion-select>
                </ion-item>
                <ion-item>
                  <ion-input label="Caller ID" labelPlacement="stacked" [(ngModel)]="config.caller_id" placeholder="+15551234567"></ion-input>
                </ion-item>
              </ion-list>
            </div>
          }

          <!-- Twilio Trunk Settings -->
          @if (config.provider === 'twilio_trunk') {
            <div class="section-card">
              <div class="section-header">
                <ion-icon name="server-outline" class="section-icon"></ion-icon>
                <div>
                  <h3>Twilio SIP Trunk (BYOC)</h3>
                  <p class="section-description">Configure your Twilio SIP Trunk for routing calls through your own Twilio account.</p>
                </div>
              </div>
              <ion-list>
                <ion-item>
                  <ion-input label="Trunk SID" labelPlacement="stacked" [(ngModel)]="config.twilio_trunk_sid" placeholder="TK..."></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input label="SIP Host" labelPlacement="stacked" [(ngModel)]="config.sip_host" placeholder="your-trunk.pstn.twilio.com"></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input label="SIP Username" labelPlacement="stacked" [(ngModel)]="config.sip_username" placeholder="Optional SIP auth username"></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input label="SIP Password" labelPlacement="stacked" [(ngModel)]="config.sip_password" type="password" placeholder="Optional SIP auth password"></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input label="Caller ID" labelPlacement="stacked" [(ngModel)]="config.caller_id" placeholder="+15551234567"></ion-input>
                </ion-item>
              </ion-list>
            </div>
          }

          <!-- Test Connection -->
          <div class="section-card">
            <div class="section-header">
              <ion-icon name="shield-checkmark-outline" class="section-icon"></ion-icon>
              <div>
                <h3>Connection Test</h3>
                <p class="section-description">Verify that your voice provider configuration is working correctly.</p>
              </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
              <ion-button (click)="onTest()" [disabled]="testing()" fill="outline" size="default">
                @if (testing()) {
                  <ion-spinner name="crescent" style="width: 16px; height: 16px; margin-right: 8px"></ion-spinner>
                  Testing...
                } @else {
                  <ion-icon name="refresh-outline" slot="start"></ion-icon>
                  Test Connection
                }
              </ion-button>
              @if (testResult) {
                <ion-chip [color]="testResult === 'success' ? 'success' : 'danger'">
                  <ion-icon [name]="testResult === 'success' ? 'checkmark-circle-outline' : 'close-circle-outline'"></ion-icon>
                  <ion-label>{{ testResult === 'success' ? 'Connected' : 'Failed' }}</ion-label>
                </ion-chip>
              }
            </div>
            @if (config.last_tested_at) {
              <ion-note style="display: block; margin-top: 8px; font-size: 0.82rem;">
                Last tested: {{ config.last_tested_at | date:'medium' }}
                &mdash; Result: {{ config.last_test_result || 'unknown' }}
              </ion-note>
            }
          </div>
        </div>
      }
    </ion-content>
  `,
  styles: [`
    .sip-settings-wrapper {
      max-width: 700px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .section-card {
      background: var(--ion-card-background, var(--ion-item-background, #fff));
      border-radius: 12px;
      padding: 20px;
      border: 1px solid var(--ion-color-light-shade, #d7d8da);
    }
    .section-header {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 12px;
    }
    .section-icon {
      font-size: 1.5rem;
      color: var(--ion-color-primary);
      margin-top: 2px;
      flex-shrink: 0;
    }
    .section-header h3 {
      margin: 0 0 4px 0;
      font-size: 1.05rem;
      font-weight: 600;
      color: var(--ion-text-color);
    }
    .section-description {
      margin: 0;
      font-size: 0.85rem;
      color: var(--ion-color-medium);
      line-height: 1.4;
    }
    .section-card ion-list {
      padding: 0;
    }
    .section-card ion-item {
      --padding-start: 0;
      --inner-padding-end: 0;
    }
  `],
})
export class SipSettingsComponent implements OnInit {
  config: SipConfiguration = {
    provider: 'keepup_default',
    sip_host: null,
    sip_port: 5060,
    sip_username: null,
    sip_password: null,
    sip_transport: 'udp',
    caller_id: null,
    twilio_trunk_sid: null,
    active: true,
    last_tested_at: null,
    last_test_result: null,
  };

  loading = signal(false);
  saving = signal(false);
  testing = signal(false);
  testResult: string | null = null;

  constructor(
    private sipService: SipConfigurationService,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    this.loadConfig();
  }

  loadConfig(): void {
    this.loading.set(true);
    this.sipService.get().subscribe({
      next: (data) => {
        if (data?.sip_configuration) {
          this.config = { ...this.config, ...data.sip_configuration };
        }
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  onProviderChange(): void {
    // Reset test result when provider changes
    this.testResult = null;
  }

  async onSave(): Promise<void> {
    this.saving.set(true);
    this.sipService.save(this.config).subscribe({
      next: async (data) => {
        if (data?.sip_configuration) {
          this.config = { ...this.config, ...data.sip_configuration };
        }
        this.saving.set(false);
        const toast = await this.toastCtrl.create({
          message: 'SIP configuration saved',
          color: 'success',
          duration: 2000,
          position: 'bottom',
        });
        await toast.present();
      },
      error: async (err: any) => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({
          message: err?.error?.message || err?.message || 'Failed to save SIP configuration',
          color: 'danger',
          duration: 4000,
          position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  async onTest(): Promise<void> {
    this.testing.set(true);
    this.testResult = null;
    this.sipService.test().subscribe({
      next: async (data) => {
        this.testResult = 'success';
        this.testing.set(false);
        const toast = await this.toastCtrl.create({
          message: data?.message || 'Connection test successful',
          color: 'success',
          duration: 3000,
          position: 'bottom',
        });
        await toast.present();
        // Reload to get updated last_tested_at
        this.loadConfig();
      },
      error: async (err: any) => {
        this.testResult = 'failed';
        this.testing.set(false);
        const toast = await this.toastCtrl.create({
          message: err?.error?.message || err?.message || 'Connection test failed',
          color: 'danger',
          duration: 4000,
          position: 'bottom',
        });
        await toast.present();
      },
    });
  }
}
