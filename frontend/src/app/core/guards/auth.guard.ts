import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '../services/auth.service';

export const authGuard: CanActivateFn = async (route, state) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (!auth.isAuthenticated()) {
    router.navigate(['/login']);
    return false;
  }

  // Check if token is expired
  const token = auth.getAccessToken();
  if (token) {
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      const exp = payload.exp * 1000;
      if (Date.now() >= exp) {
        // Token expired — try refresh
        const refreshed = await auth.refresh();
        if (!refreshed) {
          auth.logout();
          return false;
        }
      }
    } catch {
      // Invalid token format
    }
  }

  return true;
};

export const superAdminGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);
  if (auth.isAuthenticated() && auth.isSuperAdmin()) return true;
  router.navigate(['/']);
  return false;
};
