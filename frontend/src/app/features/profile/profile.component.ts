import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonNote, IonSpinner, IonLabel,
  IonIcon, IonText,
  ToastController,
} from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import {
  eyeOutline, eyeOffOutline, checkmarkCircleOutline, closeCircleOutline,
  searchOutline,
} from 'ionicons/icons';
import { AuthService } from '../../core/services/auth.service';
import { ApiService } from '../../core/services/api.service';

interface CountryCode {
  name: string;
  flag: string;
  code: string;
  dial: string;
}

interface TimezoneOption {
  value: string;
  label: string;
  region: string;
}

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonNote, IonSpinner, IonLabel,
    IonIcon, IonText,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>My Profile</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      @if (loading()) {
        <div style="text-align: center; padding: 2rem;">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {
        <div class="profile-section">
          <ion-list>
            <ion-item>
              <ion-input label="Username" labelPlacement="stacked" [value]="username" [readonly]="true"></ion-input>
            </ion-item>
            <ion-item>
              <ion-input label="Email" labelPlacement="stacked" [value]="email" [readonly]="true"></ion-input>
            </ion-item>

            <!-- Phone Number with Country Code -->
            <ion-item>
              <ion-label position="stacked">Phone Number</ion-label>
              <div class="phone-row">
                <ion-select
                  [(ngModel)]="selectedCountryDial"
                  interface="popover"
                  class="country-select"
                  [interfaceOptions]="{ cssClass: 'country-popover' }"
                >
                  @for (c of countries; track c.code) {
                    <ion-select-option [value]="c.dial">{{ c.flag }} {{ c.dial }}</ion-select-option>
                  }
                </ion-select>
                <ion-input
                  [(ngModel)]="localPhone"
                  placeholder="Phone number"
                  type="tel"
                  inputmode="tel"
                  class="phone-input"
                ></ion-input>
              </div>
            </ion-item>

            <ion-item>
              <ion-select label="Language" labelPlacement="stacked" [(ngModel)]="language" interface="popover">
                <ion-select-option value="en">English</ion-select-option>
                <ion-select-option value="pt">Portugu\u00eas</ion-select-option>
                <ion-select-option value="es">Espa\u00f1ol</ion-select-option>
              </ion-select>
            </ion-item>

            <!-- Timezone Searchable -->
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
                  [(ngModel)]="timezone"
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
          </ion-list>

          <ion-button expand="block" (click)="onSave()" [disabled]="saving()" style="margin-top: 1rem;">
            @if (saving()) {
              <ion-spinner name="crescent" style="width: 16px; height: 16px"></ion-spinner>
            } @else {
              Save Profile
            }
          </ion-button>
        </div>

        <div class="profile-section" style="margin-top: 2rem;">
          <h3>Change Password</h3>

          <div class="password-requirements">
            <p class="req-title">Password must have:</p>
            <div class="req-item" [class.met]="pwRules.length">
              <ion-icon [name]="pwRules.length ? 'checkmark-circle-outline' : 'close-circle-outline'"></ion-icon>
              8+ characters
            </div>
            <div class="req-item" [class.met]="pwRules.uppercase">
              <ion-icon [name]="pwRules.uppercase ? 'checkmark-circle-outline' : 'close-circle-outline'"></ion-icon>
              1 uppercase letter
            </div>
            <div class="req-item" [class.met]="pwRules.lowercase">
              <ion-icon [name]="pwRules.lowercase ? 'checkmark-circle-outline' : 'close-circle-outline'"></ion-icon>
              1 lowercase letter
            </div>
            <div class="req-item" [class.met]="pwRules.number">
              <ion-icon [name]="pwRules.number ? 'checkmark-circle-outline' : 'close-circle-outline'"></ion-icon>
              1 number
            </div>
            <div class="req-item" [class.met]="pwRules.special">
              <ion-icon [name]="pwRules.special ? 'checkmark-circle-outline' : 'close-circle-outline'"></ion-icon>
              1 special character
            </div>
          </div>

          <ion-list>
            <ion-item>
              <ion-input
                #curPwInput
                label="Current Password"
                labelPlacement="stacked"
                [type]="showCurrentPassword ? 'text' : 'password'"
                [(ngModel)]="currentPassword"
                placeholder="Enter current password"
                autocomplete="current-password"
                enterkeyhint="next"
                (keyup.enter)="newPwInput.setFocus()"
              ></ion-input>
              <ion-button fill="clear" slot="end" (click)="showCurrentPassword = !showCurrentPassword" class="toggle-pw-btn">
                <ion-icon [name]="showCurrentPassword ? 'eye-off-outline' : 'eye-outline'" slot="icon-only"></ion-icon>
              </ion-button>
            </ion-item>
            <ion-item>
              <ion-input
                #newPwInput
                label="New Password"
                labelPlacement="stacked"
                [type]="showNewPassword ? 'text' : 'password'"
                [(ngModel)]="newPassword"
                (ngModelChange)="onNewPasswordChange()"
                placeholder="Enter new password"
                autocomplete="new-password"
                enterkeyhint="next"
                (keyup.enter)="confirmPwInput.setFocus()"
              ></ion-input>
              <ion-button fill="clear" slot="end" (click)="showNewPassword = !showNewPassword" class="toggle-pw-btn">
                <ion-icon [name]="showNewPassword ? 'eye-off-outline' : 'eye-outline'" slot="icon-only"></ion-icon>
              </ion-button>
            </ion-item>

            @if (newPassword.length > 0) {
              <div class="strength-bar">
                <div class="strength-fill" [style.width.%]="getPasswordStrength()" [style.background]="getStrengthColor()"></div>
              </div>
              <p class="strength-text" [style.color]="getStrengthColor()">{{ getStrengthLabel() }}</p>
            }

            <ion-item>
              <ion-input
                #confirmPwInput
                label="Confirm Password"
                labelPlacement="stacked"
                [type]="showConfirmPassword ? 'text' : 'password'"
                [(ngModel)]="confirmPassword"
                placeholder="Confirm new password"
                autocomplete="new-password"
                enterkeyhint="done"
                (keyup.enter)="onChangePassword()"
              ></ion-input>
              <ion-button fill="clear" slot="end" (click)="showConfirmPassword = !showConfirmPassword" class="toggle-pw-btn">
                <ion-icon [name]="showConfirmPassword ? 'eye-off-outline' : 'eye-outline'" slot="icon-only"></ion-icon>
              </ion-button>
            </ion-item>

            @if (confirmPassword.length > 0) {
              <div class="match-indicator" [class.match]="confirmPassword === newPassword" [class.no-match]="confirmPassword !== newPassword">
                <ion-icon [name]="confirmPassword === newPassword ? 'checkmark-circle-outline' : 'close-circle-outline'"></ion-icon>
                {{ confirmPassword === newPassword ? 'Passwords match' : 'Passwords do not match' }}
              </div>
            }
          </ion-list>

          @if (passwordError) {
            <ion-note color="danger" style="display: block; padding: 0.5rem 1rem;">{{ passwordError }}</ion-note>
          }

          <ion-button
            expand="block"
            color="secondary"
            (click)="onChangePassword()"
            [disabled]="changingPassword() || !isPasswordFormValid()"
            style="margin-top: 1rem;"
          >
            @if (changingPassword()) {
              <ion-spinner name="crescent" style="width: 16px; height: 16px"></ion-spinner>
            } @else {
              Change Password
            }
          </ion-button>
        </div>
      }
    </ion-content>
  `,
  styles: [`
    .profile-section {
      max-width: 600px;
      margin: 0 auto;
    }
    .phone-row {
      display: flex;
      align-items: center;
      width: 100%;
      gap: 8px;
      padding-top: 8px;
    }
    .country-select {
      min-width: 110px;
      max-width: 130px;
      flex-shrink: 0;
    }
    .phone-input {
      flex: 1;
    }
    .password-requirements {
      background: var(--ion-color-light, #f4f5f8);
      border-radius: 8px;
      padding: 12px 16px;
      margin-bottom: 12px;
    }
    .req-title {
      font-weight: 600;
      font-size: 0.875rem;
      margin: 0 0 8px 0;
      color: var(--ion-color-dark);
    }
    .req-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.82rem;
      color: var(--ion-color-danger);
      margin-bottom: 4px;
    }
    .req-item ion-icon {
      font-size: 1rem;
      flex-shrink: 0;
    }
    .req-item.met {
      color: var(--ion-color-success);
    }
    .strength-bar {
      height: 4px;
      background: var(--ion-color-light, #f4f5f8);
      border-radius: 2px;
      margin: 4px 16px 0;
    }
    .strength-fill {
      height: 100%;
      border-radius: 2px;
      transition: width 0.3s, background 0.3s;
    }
    .strength-text {
      font-size: 0.75rem;
      margin: 2px 16px 0;
    }
    .match-indicator {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.82rem;
      padding: 4px 16px;
    }
    .match-indicator ion-icon {
      font-size: 1rem;
    }
    .match-indicator.match {
      color: var(--ion-color-success);
    }
    .match-indicator.no-match {
      color: var(--ion-color-danger);
    }
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
    .toggle-pw-btn {
      margin: 0;
      --padding-start: 4px;
      --padding-end: 4px;
    }
    .toggle-pw-btn ion-icon {
      font-size: 1.2rem;
      color: var(--ion-color-medium);
    }
  `],
})
export class ProfileComponent implements OnInit {
  username = '';
  email = '';
  localPhone = '';
  selectedCountryDial = '+1';
  language = 'en';
  timezone = 'America/New_York';

  currentPassword = '';
  newPassword = '';
  confirmPassword = '';
  passwordError = '';
  showCurrentPassword = false;
  showNewPassword = false;
  showConfirmPassword = false;

  pwRules = {
    length: false,
    uppercase: false,
    lowercase: false,
    number: false,
    special: false,
  };

  loading = signal(true);
  saving = signal(false);
  changingPassword = signal(false);

  countries: CountryCode[] = [
    { name: 'United States', flag: '\u{1F1FA}\u{1F1F8}', code: 'US', dial: '+1' },
    { name: 'Brazil', flag: '\u{1F1E7}\u{1F1F7}', code: 'BR', dial: '+55' },
    { name: 'United Kingdom', flag: '\u{1F1EC}\u{1F1E7}', code: 'GB', dial: '+44' },
    { name: 'Canada', flag: '\u{1F1E8}\u{1F1E6}', code: 'CA', dial: '+1' },
    { name: 'Australia', flag: '\u{1F1E6}\u{1F1FA}', code: 'AU', dial: '+61' },
    { name: 'Germany', flag: '\u{1F1E9}\u{1F1EA}', code: 'DE', dial: '+49' },
    { name: 'France', flag: '\u{1F1EB}\u{1F1F7}', code: 'FR', dial: '+33' },
    { name: 'Spain', flag: '\u{1F1EA}\u{1F1F8}', code: 'ES', dial: '+34' },
    { name: 'Portugal', flag: '\u{1F1F5}\u{1F1F9}', code: 'PT', dial: '+351' },
    { name: 'Mexico', flag: '\u{1F1F2}\u{1F1FD}', code: 'MX', dial: '+52' },
    { name: 'Argentina', flag: '\u{1F1E6}\u{1F1F7}', code: 'AR', dial: '+54' },
    { name: 'Colombia', flag: '\u{1F1E8}\u{1F1F4}', code: 'CO', dial: '+57' },
    { name: 'Chile', flag: '\u{1F1E8}\u{1F1F1}', code: 'CL', dial: '+56' },
    { name: 'Peru', flag: '\u{1F1F5}\u{1F1EA}', code: 'PE', dial: '+51' },
    { name: 'India', flag: '\u{1F1EE}\u{1F1F3}', code: 'IN', dial: '+91' },
    { name: 'Japan', flag: '\u{1F1EF}\u{1F1F5}', code: 'JP', dial: '+81' },
    { name: 'South Korea', flag: '\u{1F1F0}\u{1F1F7}', code: 'KR', dial: '+82' },
    { name: 'China', flag: '\u{1F1E8}\u{1F1F3}', code: 'CN', dial: '+86' },
    { name: 'Russia', flag: '\u{1F1F7}\u{1F1FA}', code: 'RU', dial: '+7' },
    { name: 'Italy', flag: '\u{1F1EE}\u{1F1F9}', code: 'IT', dial: '+39' },
    { name: 'Netherlands', flag: '\u{1F1F3}\u{1F1F1}', code: 'NL', dial: '+31' },
    { name: 'Sweden', flag: '\u{1F1F8}\u{1F1EA}', code: 'SE', dial: '+46' },
    { name: 'Norway', flag: '\u{1F1F3}\u{1F1F4}', code: 'NO', dial: '+47' },
    { name: 'Denmark', flag: '\u{1F1E9}\u{1F1F0}', code: 'DK', dial: '+45' },
    { name: 'Finland', flag: '\u{1F1EB}\u{1F1EE}', code: 'FI', dial: '+358' },
    { name: 'Poland', flag: '\u{1F1F5}\u{1F1F1}', code: 'PL', dial: '+48' },
    { name: 'Ireland', flag: '\u{1F1EE}\u{1F1EA}', code: 'IE', dial: '+353' },
    { name: 'New Zealand', flag: '\u{1F1F3}\u{1F1FF}', code: 'NZ', dial: '+64' },
    { name: 'South Africa', flag: '\u{1F1FF}\u{1F1E6}', code: 'ZA', dial: '+27' },
    { name: 'UAE', flag: '\u{1F1E6}\u{1F1EA}', code: 'AE', dial: '+971' },
    { name: 'Israel', flag: '\u{1F1EE}\u{1F1F1}', code: 'IL', dial: '+972' },
    { name: 'Singapore', flag: '\u{1F1F8}\u{1F1EC}', code: 'SG', dial: '+65' },
    { name: 'Hong Kong', flag: '\u{1F1ED}\u{1F1F0}', code: 'HK', dial: '+852' },
    { name: 'Taiwan', flag: '\u{1F1F9}\u{1F1FC}', code: 'TW', dial: '+886' },
    { name: 'Thailand', flag: '\u{1F1F9}\u{1F1ED}', code: 'TH', dial: '+66' },
    { name: 'Philippines', flag: '\u{1F1F5}\u{1F1ED}', code: 'PH', dial: '+63' },
    { name: 'Indonesia', flag: '\u{1F1EE}\u{1F1E9}', code: 'ID', dial: '+62' },
    { name: 'Malaysia', flag: '\u{1F1F2}\u{1F1FE}', code: 'MY', dial: '+60' },
  ];

  filteredTimezones: TimezoneOption[] = [];
  visibleTimezones: TimezoneOption[] = [];
  timezoneSearch = '';

  constructor(
    private auth: AuthService,
    private api: ApiService,
    private toastCtrl: ToastController,
  ) {
    addIcons({ eyeOutline, eyeOffOutline, checkmarkCircleOutline, closeCircleOutline, searchOutline });
    this.filteredTimezones = this.buildTimezoneList();
    this.visibleTimezones = this.filteredTimezones.slice(0, 50);
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
  }

  ngOnInit(): void {
    const user = this.auth.currentUser();
    if (user) {
      this.username = user.username;
      this.email = user.email;
    }
    this.api.get<any>('/auth/me').subscribe({
      next: (data) => {
        const u = data?.user || data;
        if (u) {
          this.username = u.username || this.username;
          this.email = u.email || this.email;
          this.language = u.language || this.language;
          this.timezone = u.timezone || this.timezone;
          this.parsePhoneNumber(u.phone_number || '');
        }
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  private parsePhoneNumber(fullPhone: string): void {
    if (!fullPhone) {
      this.selectedCountryDial = '+1';
      this.localPhone = '';
      return;
    }
    // Try to match country code from longest to shortest
    const sortedCountries = [...this.countries].sort((a, b) => b.dial.length - a.dial.length);
    for (const c of sortedCountries) {
      if (fullPhone.startsWith(c.dial)) {
        this.selectedCountryDial = c.dial;
        this.localPhone = fullPhone.substring(c.dial.length);
        return;
      }
    }
    // No match, try with +
    if (fullPhone.startsWith('+')) {
      this.selectedCountryDial = '+1';
      this.localPhone = fullPhone.substring(1);
    } else {
      this.selectedCountryDial = '+1';
      this.localPhone = fullPhone;
    }
  }

  private getFullPhone(): string | null {
    const local = this.localPhone.replace(/\D/g, '');
    if (!local) return null;
    return this.selectedCountryDial + local;
  }

  private buildTimezoneList(): TimezoneOption[] {
    let tzNames: string[];
    try {
      tzNames = (Intl as any).supportedValuesOf('timeZone');
    } catch {
      // Fallback for environments that don't support supportedValuesOf
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

  onNewPasswordChange(): void {
    this.pwRules = {
      length: this.newPassword.length >= 8,
      uppercase: /[A-Z]/.test(this.newPassword),
      lowercase: /[a-z]/.test(this.newPassword),
      number: /[0-9]/.test(this.newPassword),
      special: /[^A-Za-z0-9]/.test(this.newPassword),
    };
  }

  getPasswordStrength(): number {
    let score = 0;
    if (this.newPassword.length >= 8) score += 25;
    if (this.newPassword.length >= 12) score += 15;
    if (/[A-Z]/.test(this.newPassword)) score += 20;
    if (/[a-z]/.test(this.newPassword)) score += 10;
    if (/[0-9]/.test(this.newPassword)) score += 15;
    if (/[^A-Za-z0-9]/.test(this.newPassword)) score += 15;
    return Math.min(score, 100);
  }

  getStrengthColor(): string {
    const s = this.getPasswordStrength();
    if (s < 40) return 'var(--ion-color-danger)';
    if (s < 70) return 'var(--ion-color-warning)';
    return 'var(--ion-color-success)';
  }

  getStrengthLabel(): string {
    const s = this.getPasswordStrength();
    if (s < 40) return 'Weak';
    if (s < 70) return 'Fair';
    return 'Strong';
  }

  isPasswordFormValid(): boolean {
    return (
      this.currentPassword.length > 0 &&
      this.pwRules.length &&
      this.pwRules.uppercase &&
      this.pwRules.lowercase &&
      this.pwRules.number &&
      this.pwRules.special &&
      this.confirmPassword === this.newPassword &&
      this.confirmPassword.length > 0
    );
  }

  onSave(): void {
    this.saving.set(true);
    const payload = {
      language: this.language,
      timezone: this.timezone,
      phone_number: this.getFullPhone(),
    };
    this.api.put('/auth/me', payload).subscribe({
      next: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: 'Profile updated', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
      },
      error: async (err: any) => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: err?.message || 'Failed to update profile', color: 'danger', duration: 4000, position: 'bottom' });
        await toast.present();
      },
    });
  }

  onChangePassword(): void {
    this.passwordError = '';
    if (!this.isPasswordFormValid()) {
      this.passwordError = 'Please ensure all password requirements are met.';
      return;
    }
    this.changingPassword.set(true);
    this.api.post('/auth/change-password', {
      current_password: this.currentPassword,
      new_password: this.newPassword,
    }).subscribe({
      next: async () => {
        this.changingPassword.set(false);
        this.currentPassword = '';
        this.newPassword = '';
        this.confirmPassword = '';
        this.onNewPasswordChange();
        const toast = await this.toastCtrl.create({ message: 'Password changed successfully', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
      },
      error: async (err: any) => {
        this.changingPassword.set(false);
        const toast = await this.toastCtrl.create({ message: err?.message || 'Failed to change password', color: 'danger', duration: 4000, position: 'bottom' });
        await toast.present();
      },
    });
  }
}
