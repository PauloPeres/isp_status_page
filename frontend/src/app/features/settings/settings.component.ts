import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonInput, IonToggle, IonLabel, IonNote, IonIcon, IonText,
  IonSegment, IonSegmentButton, IonSpinner, IonSelect, IonSelectOption,
  IonRefresher, IonRefresherContent, IonSkeletonText, IonTextarea, IonChip,
  ToastController,
} from '@ionic/angular/standalone';
import { RouterLink } from '@angular/router';
import { SettingsService, Settings } from './settings.service';
import { addIcons } from 'ionicons';
import {
  settingsOutline, notificationsOutline, mailOutline, alertCircleOutline,
  timeOutline, peopleOutline, addOutline, closeCircleOutline, trashOutline,
  megaphoneOutline, openOutline, moonOutline, shieldCheckmarkOutline,
} from 'ionicons/icons';

interface TimezoneOption {
  value: string;
  label: string;
  region: string;
}

addIcons({
  settingsOutline, notificationsOutline, mailOutline, alertCircleOutline,
  timeOutline, peopleOutline, addOutline, closeCircleOutline, trashOutline,
  megaphoneOutline, openOutline, moonOutline, shieldCheckmarkOutline,
});

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonInput, IonToggle, IonLabel, IonNote, IonIcon, IonText,
    IonSegment, IonSegmentButton, IonSpinner, IonSelect, IonSelectOption,
    IonRefresher, IonRefresherContent, IonSkeletonText, IonTextarea, IonChip,
    RouterLink,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Settings</ion-title>
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
      <ion-toolbar>
        <ion-segment [(ngModel)]="activeTab" (ionChange)="onTabChange()">
          <ion-segment-button value="general"><ion-label>General</ion-label></ion-segment-button>
          <ion-segment-button value="notifications"><ion-label>Notifications</ion-label></ion-segment-button>
        </ion-segment>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <ion-list>
          @for (i of [1,2,3,4]; track i) {
            <ion-item>
              <ion-label>
                <ion-skeleton-text [animated]="true" style="width: 30%; height: 0.75rem; margin-bottom: 6px;"></ion-skeleton-text>
                <ion-skeleton-text [animated]="true" style="width: 70%; height: 1rem;"></ion-skeleton-text>
              </ion-label>
            </ion-item>
          }
        </ion-list>
      } @else {
        @switch (activeTab) {
          @case ('general') {
            <ion-list>
              <ion-item>
                <ion-input label="Organization Name" labelPlacement="stacked" [(ngModel)]="settings['org_name']" placeholder="My Organization"></ion-input>
              </ion-item>

              <!-- Searchable Timezone Picker -->
              <ion-item>
                <ion-label position="stacked">Timezone</ion-label>
                <div class="tz-search-wrapper">
                  <ion-input
                    [(ngModel)]="timezoneSearch"
                    (ionInput)="onTimezoneSearch()"
                    placeholder="Search timezone..."
                    [clearInput]="true"
                    class="tz-search-input"
                  ></ion-input>
                  <ion-select
                    [(ngModel)]="settings['timezone']"
                    interface="popover"
                    [interfaceOptions]="{ cssClass: 'tz-popover' }"
                    class="tz-select"
                  >
                    @for (tz of visibleTimezones; track tz.value) {
                      <ion-select-option [value]="tz.value">{{ tz.label }}</ion-select-option>
                    }
                  </ion-select>
                </div>
              </ion-item>

              <ion-item>
                <ion-input label="Date Format" labelPlacement="stacked" [(ngModel)]="settings['date_format']" placeholder="YYYY-MM-DD"></ion-input>
              </ion-item>
              <ion-item>
                <ion-input label="Default Check Interval (s)" labelPlacement="stacked" [(ngModel)]="settings['default_check_interval']" type="number" placeholder="300"></ion-input>
              </ion-item>
            </ion-list>
          }
          @case ('notifications') {
            <div class="settings-section">
              <!-- Global Notification Toggle -->
              <div class="section-card">
                <div class="section-header">
                  <ion-icon name="megaphone-outline" class="section-icon"></ion-icon>
                  <div>
                    <h3>Global Notifications</h3>
                    <p class="section-description">Master switch for all notifications in this organization. When disabled, no alerts will be sent through any channel.</p>
                  </div>
                </div>
                <ion-list>
                  <ion-item>
                    <ion-toggle [(ngModel)]="settings['notifications_enabled']">
                      Enable notifications for this organization
                    </ion-toggle>
                  </ion-item>
                </ion-list>
              </div>

              <!-- Default Throttling -->
              <div class="section-card">
                <div class="section-header">
                  <ion-icon name="shield-checkmark-outline" class="section-icon"></ion-icon>
                  <div>
                    <h3>Default Throttling</h3>
                    <p class="section-description">Default cooldown period between repeated alerts for the same incident. Individual alert rules can override this.</p>
                  </div>
                </div>
                <ion-list>
                  <ion-item>
                    <ion-select label="Default Cooldown" labelPlacement="stacked" [(ngModel)]="settings['default_throttle_minutes']" interface="popover">
                      <ion-select-option [value]="0">No cooldown</ion-select-option>
                      <ion-select-option [value]="5">5 minutes</ion-select-option>
                      <ion-select-option [value]="15">15 minutes</ion-select-option>
                      <ion-select-option [value]="30">30 minutes</ion-select-option>
                      <ion-select-option [value]="60">1 hour</ion-select-option>
                      <ion-select-option [value]="120">2 hours</ion-select-option>
                      <ion-select-option [value]="360">6 hours</ion-select-option>
                    </ion-select>
                  </ion-item>
                </ion-list>
              </div>

              <!-- Quiet Hours -->
              <div class="section-card">
                <div class="section-header">
                  <ion-icon name="moon-outline" class="section-icon"></ion-icon>
                  <div>
                    <h3>Quiet Hours</h3>
                    <p class="section-description">Suppress non-critical notifications during specific hours. Critical alerts (monitor down) are always delivered.</p>
                  </div>
                </div>
                <ion-list>
                  <ion-item>
                    <ion-toggle [(ngModel)]="settings['quiet_hours_enabled']">
                      Enable quiet hours
                    </ion-toggle>
                  </ion-item>
                  @if (settings['quiet_hours_enabled']) {
                    <ion-item>
                      <ion-input label="Start Time" labelPlacement="stacked" [(ngModel)]="settings['quiet_hours_start']" type="time" placeholder="22:00"></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-input label="End Time" labelPlacement="stacked" [(ngModel)]="settings['quiet_hours_end']" type="time" placeholder="08:00"></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-note>Times are in your organization's timezone ({{ settings['timezone'] || 'UTC' }}). Critical alerts are never suppressed.</ion-note>
                    </ion-item>
                  }
                </ion-list>
              </div>

              <!-- Channels & Policies Links -->
              <div class="section-card">
                <div class="section-header">
                  <ion-icon name="notifications-outline" class="section-icon"></ion-icon>
                  <div>
                    <h3>Channels & Policies</h3>
                    <p class="section-description">Manage where and how notifications are delivered. Set up channels (Email, Slack, Telegram, etc.) and create escalation policies with multi-step alert chains.</p>
                  </div>
                </div>
                <div class="link-cards">
                  <a class="link-card" routerLink="/app/channels">
                    <ion-icon name="mail-outline"></ion-icon>
                    <div>
                      <strong>Notification Channels</strong>
                      <span>Configure Email, Slack, Discord, Telegram, SMS, Webhooks and more</span>
                    </div>
                    <ion-icon name="open-outline" class="link-arrow"></ion-icon>
                  </a>
                  <a class="link-card" routerLink="/app/notification-policies">
                    <ion-icon name="alert-circle-outline"></ion-icon>
                    <div>
                      <strong>Escalation Policies</strong>
                      <span>Define multi-step alert chains with delays and escalation rules</span>
                    </div>
                    <ion-icon name="open-outline" class="link-arrow"></ion-icon>
                  </a>
                  <a class="link-card" routerLink="/app/notification-schedules">
                    <ion-icon name="time-outline"></ion-icon>
                    <div>
                      <strong>Notification Schedules</strong>
                      <span>Set up suppress/allow windows per channel and severity</span>
                    </div>
                    <ion-icon name="open-outline" class="link-arrow"></ion-icon>
                  </a>
                </div>
              </div>
            </div>
          }
        }
      }
    </ion-content>
  `,
  styles: [`
    .tz-search-wrapper {
      display: flex;
      align-items: center;
      width: 100%;
      gap: 8px;
      padding-top: 8px;
    }
    .tz-search-input {
      flex: 1;
      max-width: 250px;
    }
    .tz-select {
      flex: 1;
    }
    .settings-section {
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
    .link-cards {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .link-card {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      border-radius: 8px;
      border: 1px solid var(--ion-color-light-shade, #d7d8da);
      text-decoration: none;
      color: var(--ion-text-color);
      transition: background-color 0.15s;
      cursor: pointer;
    }
    .link-card:hover {
      background: var(--ion-color-light, #f4f5f8);
    }
    .link-card > ion-icon:first-child {
      font-size: 1.4rem;
      color: var(--ion-color-primary);
      flex-shrink: 0;
    }
    .link-card > div {
      flex: 1;
    }
    .link-card strong {
      display: block;
      font-size: 0.95rem;
      margin-bottom: 2px;
    }
    .link-card span {
      font-size: 0.82rem;
      color: var(--ion-color-medium);
    }
    .link-arrow {
      font-size: 1rem;
      color: var(--ion-color-medium);
      flex-shrink: 0;
    }
  `],
})
export class SettingsComponent implements OnInit {
  settings: Settings = {};
  activeTab = 'general';
  loading = signal(false);
  saving = signal(false);

  // Timezone search
  filteredTimezones: TimezoneOption[] = [];
  visibleTimezones: TimezoneOption[] = [];
  timezoneSearch = '';


  constructor(private service: SettingsService, private toastCtrl: ToastController) {
    this.filteredTimezones = this.buildTimezoneList();
    this.visibleTimezones = this.filteredTimezones.slice(0, 50);
  }

  ngOnInit(): void { this.loadSettings(); }

  loadSettings(): void {
    this.loading.set(true);
    this.service.get().subscribe({
      next: (data) => { this.settings = data || {}; this.initDefaults(); this.ensureCurrentTimezoneVisible(); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  private initDefaults(): void {
    if (this.settings['notifications_enabled'] === undefined) this.settings['notifications_enabled'] = true;
    if (this.settings['default_throttle_minutes'] === undefined) this.settings['default_throttle_minutes'] = 15;
    if (this.settings['quiet_hours_enabled'] === undefined) this.settings['quiet_hours_enabled'] = false;
  }

  onTabChange(): void {}

  onRefresh(event: any): void {
    this.service.get().subscribe({
      next: (data) => { this.settings = data || {}; this.initDefaults(); this.ensureCurrentTimezoneVisible(); event.target.complete(); },
      error: () => event.target.complete(),
    });
  }

  // --- Timezone search (same pattern as profile page) ---

  private buildTimezoneList(): TimezoneOption[] {
    let tzNames: string[];
    try {
      tzNames = (Intl as any).supportedValuesOf('timeZone');
    } catch {
      tzNames = [
        'UTC',
        'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
        'America/Sao_Paulo', 'America/Argentina/Buenos_Aires', 'America/Bogota',
        'America/Mexico_City', 'America/Toronto', 'America/Vancouver', 'America/Lima',
        'America/Santiago', 'America/Caracas', 'America/Anchorage', 'America/Phoenix',
        'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Europe/Madrid', 'Europe/Rome',
        'Europe/Amsterdam', 'Europe/Brussels', 'Europe/Stockholm', 'Europe/Oslo',
        'Europe/Copenhagen', 'Europe/Helsinki', 'Europe/Warsaw', 'Europe/Dublin',
        'Europe/Lisbon', 'Europe/Moscow', 'Europe/Istanbul', 'Europe/Athens',
        'Europe/Zurich', 'Europe/Vienna', 'Europe/Prague',
        'Asia/Tokyo', 'Asia/Shanghai', 'Asia/Singapore', 'Asia/Hong_Kong', 'Asia/Taipei',
        'Asia/Seoul', 'Asia/Kolkata', 'Asia/Dubai', 'Asia/Bangkok', 'Asia/Jakarta',
        'Asia/Kuala_Lumpur', 'Asia/Manila', 'Asia/Ho_Chi_Minh', 'Asia/Karachi',
        'Asia/Dhaka', 'Asia/Tehran', 'Asia/Jerusalem', 'Asia/Riyadh',
        'Pacific/Auckland', 'Pacific/Fiji', 'Pacific/Honolulu', 'Pacific/Guam',
        'Africa/Johannesburg', 'Africa/Cairo', 'Africa/Lagos', 'Africa/Nairobi',
        'Australia/Sydney', 'Australia/Melbourne', 'Australia/Brisbane',
        'Australia/Perth', 'Australia/Adelaide',
      ];
    }

    return tzNames.map(tz => {
      let offset = '';
      try {
        const now = new Date();
        const formatter = new Intl.DateTimeFormat('en-US', {
          timeZone: tz,
          timeZoneName: 'shortOffset',
        });
        const parts = formatter.formatToParts(now);
        const tzPart = parts.find(p => p.type === 'timeZoneName');
        offset = tzPart ? tzPart.value : '';
      } catch {
        offset = '';
      }
      const region = tz.includes('/') ? tz.split('/')[0] : 'Other';
      return {
        value: tz,
        label: `${tz}${offset ? ' (' + offset + ')' : ''}`,
        region,
      };
    });
  }

  ensureCurrentTimezoneVisible(): void {
    const currentTz = this.settings['timezone'];
    if (currentTz && !this.visibleTimezones.find(tz => tz.value === currentTz)) {
      const match = this.filteredTimezones.find(tz => tz.value === currentTz);
      if (match) {
        this.visibleTimezones = [match, ...this.visibleTimezones];
      }
    }
  }

  onTimezoneSearch(): void {
    const q = this.timezoneSearch.toLowerCase().trim();
    if (!q) {
      this.visibleTimezones = this.filteredTimezones.slice(0, 50);
    } else {
      this.visibleTimezones = this.filteredTimezones
        .filter(tz => tz.value.toLowerCase().includes(q) || tz.label.toLowerCase().includes(q))
        .slice(0, 50);
    }
    this.ensureCurrentTimezoneVisible();
  }

  // --- Save ---

  onSave(): void {
    this.saving.set(true);
    this.service.save(this.settings).subscribe({
      next: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: 'Settings saved', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
      },
      error: async (err: any) => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: err?.message || 'Failed to save settings', color: 'danger', duration: 4000, position: 'bottom' });
        await toast.present();
      },
    });
  }
}
