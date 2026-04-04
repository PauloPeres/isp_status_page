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
  plan?: string;
  effective_plan?: string;
  is_trial?: boolean;
  trial_expired?: boolean;
  trial_days_remaining?: number;
  trial_ends_at?: string | null;
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
    const user = localStorage.getItem('user');
    const org = localStorage.getItem('organization');
    if (token) this.accessToken.set(token);
    // Backwards compat: if refresh_token is still in localStorage (old sessions), load it
    const legacyRefresh = localStorage.getItem('refresh_token');
    if (legacyRefresh) this.refreshTokenValue.set(legacyRefresh);
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

  async login(email: string, password: string, twoFactorCode?: string, rememberMe: boolean = true): Promise<LoginResponse> {
    const body: Record<string, string | boolean> = { email, password, remember_me: rememberMe };
    if (twoFactorCode) body['two_factor_code'] = twoFactorCode;

    const response = await firstValueFrom(
      this.http.post<LoginResponse>(`${environment.apiUrl}/auth/login`, body, { withCredentials: true })
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
      // Send refresh_token in body only for backwards compat (old sessions with localStorage token)
      // New sessions rely on HttpOnly cookie sent automatically with withCredentials
      const body: Record<string, string> = {};
      const legacyToken = this.refreshTokenValue();
      if (legacyToken) {
        body['refresh_token'] = legacyToken;
      }

      const response = await firstValueFrom(
        this.http.post<LoginResponse>(`${environment.apiUrl}/auth/refresh`, body, { withCredentials: true })
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
      // Find the current organization (the one marked is_current, or the first one)
      const currentOrg = response.data.organizations?.find((o: any) => o.is_current)
        || response.data.organizations?.[0]
        || null;
      this.currentOrg.set(currentOrg);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      if (currentOrg) {
        localStorage.setItem('organization', JSON.stringify(currentOrg));
      }
    }
  }

  logout(): void {
    // Send refresh_token in body for backwards compat; cookie sent via withCredentials
    const body: Record<string, string> = {};
    const legacyToken = this.refreshTokenValue();
    if (legacyToken) {
      body['refresh_token'] = legacyToken;
    }
    this.http
      .post(`${environment.apiUrl}/auth/logout`, body, { withCredentials: true })
      .subscribe({ error: () => {} });
    this.clearTokens();
    this.router.navigate(['/login']);
  }

  private setTokens(access: string, _refresh: string): void {
    this.accessToken.set(access);
    // Do NOT store refresh_token in localStorage — it is now in an HttpOnly cookie.
    // Clear any legacy refresh_token from localStorage.
    localStorage.setItem('access_token', access);
    localStorage.removeItem('refresh_token');
    this.refreshTokenValue.set(null);
  }

  private clearTokens(): void {
    this.accessToken.set(null);
    this.refreshTokenValue.set(null);
    this.currentUser.set(null);
    this.currentOrg.set(null);
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token'); // Clean up any legacy token
    localStorage.removeItem('user');
    localStorage.removeItem('organization');
  }
}
