import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import {
  IonContent,
  IonItem,
  IonInput,
  IonButton,
  IonSpinner,
  IonText,
  IonCheckbox,
  IonIcon,
} from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { logoGoogle, logoMicrosoft } from 'ionicons/icons';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../core/services/auth.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [
    IonContent,
    IonItem,
    IonInput,
    IonButton,
    IonSpinner,
    IonText,
    IonCheckbox,
    IonIcon,
    FormsModule,
    RouterLink,
  ],
  template: `
    <ion-content class="ion-padding" [fullscreen]="true">
      <div class="register-container">
        <div class="register-card">
          <div class="register-header">
            <h1>ISP Status</h1>
            <p>Create your free account</p>
          </div>

          <div class="oauth-buttons">
            <ion-button expand="block" fill="outline" (click)="onOAuth('google')" class="oauth-google">
              <ion-icon name="logo-google" slot="start"></ion-icon>
              Sign up with Google
            </ion-button>
            <ion-button expand="block" fill="outline" (click)="onOAuth('microsoft')" class="oauth-microsoft">
              <ion-icon name="logo-microsoft" slot="start"></ion-icon>
              Sign up with Microsoft
            </ion-button>
          </div>
          <div class="divider">or create an account with email</div>

          <form (ngSubmit)="onRegister()">
            <ion-item>
              <ion-input
                #usernameInput
                label="Username"
                labelPlacement="floating"
                [(ngModel)]="username"
                name="username"
                type="text"
                required
                minlength="3"
                autocomplete="username"
                enterkeyhint="next"
                (keyup.enter)="emailInput.setFocus()"
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-input
                #emailInput
                label="Email"
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
                type="password"
                required
                minlength="8"
                autocomplete="new-password"
                enterkeyhint="next"
                (keyup.enter)="confirmInput.setFocus()"
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-input
                #confirmInput
                label="Confirm Password"
                labelPlacement="floating"
                [(ngModel)]="confirmPassword"
                name="confirmPassword"
                type="password"
                required
                autocomplete="new-password"
                enterkeyhint="done"
              ></ion-input>
            </ion-item>

            <div class="terms-row">
              <ion-checkbox
                [(ngModel)]="agreedToTerms"
                name="agreedToTerms"
                labelPlacement="end"
              >
                <span class="terms-label">
                  I agree to the
                  <a href="/terms" target="_blank">Terms of Service</a>
                  and
                  <a href="/privacy" target="_blank">Privacy Policy</a>
                </span>
              </ion-checkbox>
            </div>

            @if (errorMessage) {
              <ion-text color="danger">
                <p class="error-text">{{ errorMessage }}</p>
              </ion-text>
            }

            @if (successMessage) {
              <ion-text color="success">
                <p class="success-text">{{ successMessage }}</p>
              </ion-text>
            }

            <ion-button
              expand="block"
              type="submit"
              [disabled]="loading || !agreedToTerms"
            >
              @if (loading) {
                <ion-spinner name="crescent"></ion-spinner>
              } @else {
                Create Account
              }
            </ion-button>
          </form>

          <p class="login-link">
            Already have an account?
            <a routerLink="/login">Sign In</a>
          </p>
        </div>
      </div>
    </ion-content>
  `,
  styles: [
    `
      .register-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100%;
      }
      .register-card {
        width: 100%;
        max-width: 420px;
        padding: 2rem;
      }
      .register-header {
        text-align: center;
        margin-bottom: 2rem;
      }
      .register-header h1 {
        font-family: 'DM Sans', sans-serif;
        font-size: 2rem;
        color: var(--ion-color-primary);
      }
      .register-header p {
        color: var(--ion-color-medium);
      }
      .error-text {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
      }
      .success-text {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
      }
      ion-item {
        --padding-start: 12px;
        --inner-padding-end: 12px;
        --min-height: 56px;
        margin-bottom: 1rem;
      }
      .terms-row {
        padding: 0.75rem 12px;
        margin-bottom: 0.5rem;
      }
      .terms-row ion-checkbox {
        --size: 20px;
      }
      .terms-label {
        font-size: 0.82rem;
        color: var(--ion-color-medium);
        line-height: 1.4;
        margin-left: 4px;
      }
      .terms-label a {
        color: var(--ion-color-primary);
        text-decoration: none;
        font-weight: 600;
      }
      ion-button {
        margin-top: 1.5rem;
        --border-radius: 8px;
        height: 48px;
        font-weight: 600;
      }
      .login-link {
        text-align: center;
        margin-top: 1.5rem;
        color: var(--ion-color-medium);
        font-size: 0.9rem;
      }
      .login-link a {
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
export class RegisterComponent {
  username = '';
  email = '';
  password = '';
  confirmPassword = '';
  agreedToTerms = false;
  loading = false;
  errorMessage = '';
  successMessage = '';

  constructor(
    private http: HttpClient,
    private auth: AuthService,
    private router: Router,
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

  async onRegister() {
    this.errorMessage = '';
    this.successMessage = '';

    if (!this.username || !this.email || !this.password) {
      this.errorMessage = 'All fields are required';
      return;
    }

    if (this.password.length < 8) {
      this.errorMessage = 'Password must be at least 8 characters';
      return;
    }

    if (this.password !== this.confirmPassword) {
      this.errorMessage = 'Passwords do not match';
      return;
    }

    if (!this.agreedToTerms) {
      this.errorMessage = 'You must agree to the terms';
      return;
    }

    this.loading = true;

    try {
      const response: any = await this.http
        .post(`${environment.apiUrl}/auth/register`, {
          username: this.username,
          email: this.email,
          password: this.password,
        })
        .toPromise();

      if (response?.success && response.data) {
        // Store tokens and user data (same as login)
        localStorage.setItem('access_token', response.data.access_token);
        localStorage.setItem('refresh_token', response.data.refresh_token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        if (response.data.organization) {
          localStorage.setItem(
            'organization',
            JSON.stringify(response.data.organization),
          );
        }

        // Reload the page to pick up new tokens in AuthService
        window.location.href = '/app/onboarding';
      }
    } catch (error: any) {
      this.errorMessage =
        error.error?.message || 'Registration failed. Please try again.';
    }

    this.loading = false;
  }
}
