import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { IonContent, IonSpinner } from '@ionic/angular/standalone';
import { firstValueFrom } from 'rxjs';
import { AuthService } from '../../core/services/auth.service';
import { environment } from '../../../environments/environment';

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
    private route: ActivatedRoute,
    private http: HttpClient,
  ) {}

  ngOnInit() {
    // New flow: exchange one-time code for tokens via POST
    const code = this.route.snapshot.queryParamMap.get('code');
    if (code) {
      this.exchangeCode(code);
    } else {
      // Fallback: try old fragment-based approach for backwards compat
      this.handleFragment();
    }
  }

  private async exchangeCode(code: string): Promise<void> {
    try {
      const response = await firstValueFrom(
        this.http.post<any>(`${environment.apiUrl}/auth/oauth/exchange`, {
          code,
        }),
      );
      if (response?.success && response.data) {
        localStorage.setItem('access_token', response.data.access_token);
        if (response.data.user)
          localStorage.setItem('user', JSON.stringify(response.data.user));
        if (response.data.organization)
          localStorage.setItem(
            'organization',
            JSON.stringify(response.data.organization),
          );
        await this.auth.fetchMe();
        this.router.navigate(['/dashboard']);
      } else {
        this.router.navigate(['/login'], {
          queryParams: { error: 'oauth_failed' },
        });
      }
    } catch {
      this.router.navigate(['/login'], {
        queryParams: { error: 'oauth_failed' },
      });
    }
  }

  private handleFragment(): void {
    const fragment = window.location.hash.substring(1);
    const params = new URLSearchParams(fragment);
    const accessToken = params.get('access_token');
    const refreshToken = params.get('refresh_token');

    if (accessToken && refreshToken) {
      localStorage.setItem('access_token', accessToken);
      localStorage.setItem('refresh_token', refreshToken);

      this.auth
        .fetchMe()
        .then(() => {
          this.router.navigate(['/dashboard']);
        })
        .catch(() => {
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
