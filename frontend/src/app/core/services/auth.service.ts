import { Injectable, signal, computed } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface AuthUser {
  id: number;
  username: string;
  email: string;
  is_super_admin: boolean;
}

export interface AuthOrganization {
  id: number;
  role: string;
}

export interface LoginResponse {
  success: boolean;
  data: {
    access_token: string;
    refresh_token: string;
    expires_in: number;
    user: AuthUser;
    organization: AuthOrganization | null;
  };
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private accessToken = signal<string | null>(null);
  private refreshTokenValue = signal<string | null>(null);

  currentUser = signal<AuthUser | null>(null);
  currentOrg = signal<AuthOrganization | null>(null);
  isAuthenticated = computed(() => !!this.accessToken());
  isSuperAdmin = computed(() => this.currentUser()?.is_super_admin ?? false);

  constructor(private http: HttpClient, private router: Router) {
    // Restore from localStorage on init
    const token = localStorage.getItem('access_token');
    const refresh = localStorage.getItem('refresh_token');
    const user = localStorage.getItem('user');
    const org = localStorage.getItem('organization');
    if (token) this.accessToken.set(token);
    if (refresh) this.refreshTokenValue.set(refresh);
    if (user) {
      try { this.currentUser.set(JSON.parse(user)); } catch { localStorage.removeItem('user'); }
    }
    if (org) {
      try { this.currentOrg.set(JSON.parse(org)); } catch { localStorage.removeItem('organization'); }
    }
  }

  getAccessToken(): string | null {
    return this.accessToken();
  }

  async login(email: string, password: string, twoFactorCode?: string): Promise<LoginResponse> {
    const body: Record<string, string> = { email, password };
    if (twoFactorCode) body['two_factor_code'] = twoFactorCode;

    const response = await firstValueFrom(
      this.http.post<LoginResponse>(`${environment.apiUrl}/auth/login`, body)
    );
    if (response?.success && response.data) {
      this.setTokens(response.data.access_token, response.data.refresh_token);
      this.currentUser.set(response.data.user);
      this.currentOrg.set(response.data.organization);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      if (response.data.organization) {
        localStorage.setItem('organization', JSON.stringify(response.data.organization));
      }
    }
    return response!;
  }

  async refresh(): Promise<boolean> {
    try {
      const response = await firstValueFrom(
        this.http.post<LoginResponse>(`${environment.apiUrl}/auth/refresh`, {
          refresh_token: this.refreshTokenValue(),
        })
      );
      if (response?.success && response.data) {
        this.setTokens(response.data.access_token, response.data.refresh_token);
        return true;
      }
    } catch {
      // refresh failed
    }
    return false;
  }

  async startOAuth(provider: string): Promise<string | null> {
    try {
      const response = await firstValueFrom(
        this.http.get<any>(`${environment.apiUrl}/auth/oauth/${provider}/redirect`)
      );
      if (response?.success && response.data?.authorization_url) {
        return response.data.authorization_url;
      }
      return null;
    } catch {
      return null;
    }
  }

  async fetchMe(): Promise<void> {
    const response = await firstValueFrom(
      this.http.get<any>(`${environment.apiUrl}/auth/me`)
    );
    if (response?.success) {
      this.currentUser.set(response.data.user);
      this.currentOrg.set(response.data.organizations?.[0] || null);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      if (response.data.organizations?.[0]) {
        localStorage.setItem(
          'organization',
          JSON.stringify(response.data.organizations[0]),
        );
      }
    }
  }

  logout(): void {
    this.http
      .post(`${environment.apiUrl}/auth/logout`, {
        refresh_token: this.refreshTokenValue(),
      })
      .subscribe({ error: () => {} });
    this.clearTokens();
    this.router.navigate(['/login']);
  }

  private setTokens(access: string, refresh: string): void {
    this.accessToken.set(access);
    this.refreshTokenValue.set(refresh);
    localStorage.setItem('access_token', access);
    localStorage.setItem('refresh_token', refresh);
  }

  private clearTokens(): void {
    this.accessToken.set(null);
    this.refreshTokenValue.set(null);
    this.currentUser.set(null);
    this.currentOrg.set(null);
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('user');
    localStorage.removeItem('organization');
  }
}
