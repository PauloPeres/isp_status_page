import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
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
import { logoGoogle, logoMicrosoft } from 'ionicons/icons';
import { AuthService } from '../../core/services/auth.service';
import { environment } from '../../../environments/environment';

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
                label="Email or Username"
                labelPlacement="floating"
                [(ngModel)]="email"
                name="email"
                type="email"
                required
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-input
                label="Password"
                labelPlacement="floating"
                [(ngModel)]="password"
                name="password"
                type="password"
                required
              ></ion-input>
            </ion-item>

            @if (requires2fa) {
              <ion-item>
                <ion-input
                  label="2FA Code"
                  labelPlacement="floating"
                  [(ngModel)]="twoFactorCode"
                  name="twoFactorCode"
                  type="text"
                  maxlength="6"
                  inputmode="numeric"
                ></ion-input>
              </ion-item>
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
  requires2fa = false;

  constructor(
    private auth: AuthService,
    private router: Router,
    private http: HttpClient,
  ) {
    addIcons({ logoGoogle, logoMicrosoft });
  }

  async onOAuth(provider: string) {
    try {
      const response = await this.http
        .get<any>(`${environment.apiUrl}/auth/oauth/${provider}/redirect`)
        .toPromise();
      if (response?.success && response.data?.authorization_url) {
        window.location.href = response.data.authorization_url;
      } else {
        this.errorMessage = `${provider} login is not configured`;
      }
    } catch {
      this.errorMessage = `Unable to connect to ${provider}`;
    }
  }

  async onLogin() {
    this.loading = true;
    this.errorMessage = '';
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
        this.errorMessage = 'Enter your 2FA code';
      } else {
        this.errorMessage = error.error?.message || 'Login failed';
      }
    }
    this.loading = false;
  }
}
