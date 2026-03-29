import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { IonContent, IonSpinner } from '@ionic/angular/standalone';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-oauth-callback',
  standalone: true,
  imports: [IonContent, IonSpinner],
  template: `
    <ion-content class="ion-padding" [fullscreen]="true">
      <div
        style="
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 100%;
        text-align: center;
      "
      >
        <ion-spinner name="crescent"></ion-spinner>
        <p style="margin-top: 1rem; color: var(--ion-color-medium)">
          Signing you in...
        </p>
      </div>
    </ion-content>
  `,
})
export class OAuthCallbackComponent implements OnInit {
  constructor(
    private auth: AuthService,
    private router: Router,
  ) {}

  ngOnInit() {
    // Extract tokens from URL fragment
    const fragment = window.location.hash.substring(1);
    const params = new URLSearchParams(fragment);
    const accessToken = params.get('access_token');
    const refreshToken = params.get('refresh_token');

    if (accessToken && refreshToken) {
      // Store tokens
      localStorage.setItem('access_token', accessToken);
      localStorage.setItem('refresh_token', refreshToken);

      // Fetch user info and navigate to dashboard
      this.auth.fetchMe().then(() => {
        this.router.navigate(['/dashboard']);
      }).catch(() => {
        this.router.navigate(['/login'], {
          queryParams: { error: 'oauth_failed' },
        });
      });
    } else {
      this.router.navigate(['/login'], {
        queryParams: { error: 'oauth_failed' },
      });
    }
  }
}
