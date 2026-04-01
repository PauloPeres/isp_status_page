import { Component, ViewChild, ElementRef } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink, ActivatedRoute } from '@angular/router';
import {
  IonContent,
  IonItem,
  IonInput,
  IonButton,
  IonSpinner,
  IonText,
  IonIcon,
} from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { logoGoogle, logoMicrosoft, eyeOutline, eyeOffOutline } from 'ionicons/icons';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    IonContent,
    IonItem,
    IonInput,
    IonButton,
    IonSpinner,
    IonText,
    IonIcon,
    FormsModule,
    RouterLink,
  ],
  template: `
    <ion-content class="ion-padding" [fullscreen]="true">
      <div class="login-container">
        <div class="login-card">
          <div class="login-header">
            <h1>ISP Status</h1>
            <p>Sign in to your account</p>
          </div>

          <div class="oauth-buttons">
            <ion-button expand="block" fill="outline" (click)="onOAuth('google')" class="oauth-google">
              <ion-icon name="logo-google" slot="start"></ion-icon>
              Sign in with Google
            </ion-button>
            <ion-button expand="block" fill="outline" (click)="onOAuth('microsoft')" class="oauth-microsoft">
              <ion-icon name="logo-microsoft" slot="start"></ion-icon>
              Sign in with Microsoft
            </ion-button>
          </div>
          <div class="divider">or sign in with email</div>

          <form (ngSubmit)="onLogin()">
            <ion-item>
              <ion-input
                #emailInput
                label="Email or Username"
                labelPlacement="floating"
                [(ngModel)]="email"
                name="email"
                type="email"
                required
                autocomplete="email"
                enterkeyhint="next"
                (keyup.enter)="passwordInput.setFocus()"
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-input
                #passwordInput
                label="Password"
                labelPlacement="floating"
                [(ngModel)]="password"
                name="password"
                [type]="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                enterkeyhint="go"
                (keyup.enter)="onLogin()"
              ></ion-input>
              <ion-button fill="clear" slot="end" (click)="showPassword = !showPassword" style="margin: 0;">
                <ion-icon [name]="showPassword ? 'eye-off-outline' : 'eye-outline'" slot="icon-only" style="font-size: 1.2rem; color: var(--ion-color-medium)"></ion-icon>
              </ion-button>
            </ion-item>

            @if (requires2fa) {
              <ion-item>
                <ion-input
                  #tfaInput
                  label="2FA Code"
                  labelPlacement="floating"
                  [(ngModel)]="twoFactorCode"
                  name="twoFactorCode"
                  type="text"
                  maxlength="6"
                  inputmode="numeric"
                  enterkeyhint="go"
                  (keyup.enter)="onLogin()"
                ></ion-input>
              </ion-item>
            }

            @if (infoMessage) {
              <ion-text color="primary">
                <p class="info-text">{{ infoMessage }}</p>
              </ion-text>
            }

            @if (errorMessage) {
              <ion-text color="danger">
                <p class="error-text">{{ errorMessage }}</p>
              </ion-text>
            }

            <ion-button expand="block" type="submit" [disabled]="loading">
              @if (loading) {
                <ion-spinner name="crescent"></ion-spinner>
              } @else {
                Sign In
              }
            </ion-button>
          </form>

          <p class="register-link">
            Don't have an account?
            <a routerLink="/register">Create one free</a>
          </p>
        </div>
      </div>
    </ion-content>
  `,
  styles: [
    `
      .login-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100%;
      }
      .login-card {
        width: 100%;
        max-width: 400px;
        padding: 2rem;
      }
      .login-header {
        text-align: center;
        margin-bottom: 2rem;
      }
      .login-header h1 {
        font-family: 'DM Sans', sans-serif;
        font-size: 2rem;
        color: var(--ion-color-primary);
      }
      .login-header p {
        color: var(--ion-color-medium);
      }
      .error-text {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
      }
      .info-text {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
      }
      ion-item {
        --padding-start: 12px;
        --inner-padding-end: 12px;
        --min-height: 56px;
        margin-bottom: 1rem;
      }
      ion-button {
        margin-top: 1.5rem;
        --border-radius: 8px;
        height: 48px;
        font-weight: 600;
      }
      .register-link {
        text-align: center;
        margin-top: 1.5rem;
        color: var(--ion-color-medium);
        font-size: 0.9rem;
      }
      .register-link a {
        color: var(--ion-color-primary);
        text-decoration: none;
        font-weight: 600;
      }
      .oauth-buttons {
        margin-bottom: 1rem;
      }
      .oauth-buttons ion-button {
        margin-top: 0;
        margin-bottom: 8px;
        --border-radius: 8px;
        height: 44px;
        font-weight: 500;
      }
      .oauth-google {
        --border-color: #4285f4;
        --color: #4285f4;
      }
      .oauth-microsoft {
        --border-color: #00a4ef;
        --color: #00a4ef;
      }
      .divider {
        text-align: center;
        margin: 1rem 0;
        color: var(--ion-color-medium);
        font-size: 0.85rem;
      }
    `,
  ],
})
export class LoginComponent {
  email = '';
  password = '';
  twoFactorCode = '';
  loading = false;
  errorMessage = '';
  infoMessage = '';
  requires2fa = false;
  showPassword = false;

  constructor(
    private auth: AuthService,
    private router: Router,
    private route: ActivatedRoute,
  ) {
    addIcons({ logoGoogle, logoMicrosoft, eyeOutline, eyeOffOutline });

    // Check for OAuth error
    const error = this.route.snapshot.queryParamMap.get('error');
    if (error === 'oauth_failed') {
      this.errorMessage = 'Sign-in with the provider failed. Please try again or use email/password.';
    }
  }

  async onOAuth(provider: string) {
    const url = await this.auth.startOAuth(provider);
    if (url) {
      window.location.href = url;
    } else {
      this.errorMessage = `${provider} login is not configured`;
    }
  }

  async onLogin() {
    this.loading = true;
    this.errorMessage = '';
    this.infoMessage = '';
    try {
      const response = await this.auth.login(
        this.email,
        this.password,
        this.twoFactorCode || undefined,
      );
      if (response.success) {
        this.router.navigate(['/dashboard']);
      }
    } catch (error: any) {
      if (error.error?.errors?.requires_2fa) {
        this.requires2fa = true;
        this.infoMessage = 'Enter your 2FA code';
        setTimeout(() => {
          const tfaEl = document.querySelector<HTMLIonInputElement>('ion-input[name="twoFactorCode"]');
          tfaEl?.setFocus();
        }, 200);
      } else if (error.status === 0) {
        this.errorMessage = 'Unable to connect. Check your internet connection.';
      } else if (error.status === 429) {
        this.errorMessage = 'Too many failed attempts. Please try again later.';
      } else if (error.status >= 500) {
        this.errorMessage = 'Server error. Please try again later.';
      } else {
        this.errorMessage = error.error?.message || 'Invalid email or password.';
      }
    }
    this.loading = false;
  }
}
