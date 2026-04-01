import {
  HttpInterceptorFn,
  HttpRequest,
  HttpHandlerFn,
  HttpErrorResponse,
} from '@angular/common/http';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { Observable, catchError, throwError } from 'rxjs';

let isRefreshing = false;
let refreshPromise: Promise<boolean> | null = null;

export const jwtInterceptor: HttpInterceptorFn = (
  req: HttpRequest<unknown>,
  next: HttpHandlerFn,
) => {
  const auth = inject(AuthService);
  const token = auth.getAccessToken();

  if (
    token &&
    !req.url.includes('/auth/login') &&
    !req.url.includes('/auth/refresh')
  ) {
    req = req.clone({
      setHeaders: { Authorization: `Bearer ${token}` },
    });
  }

  return next(req).pipe(
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401 && !req.url.includes('/auth/')) {
        // Use shared refresh promise to prevent race condition
        if (!isRefreshing) {
          isRefreshing = true;
          refreshPromise = auth.refresh().finally(() => {
            isRefreshing = false;
            refreshPromise = null;
          });
        }

        return new Observable<any>((subscriber) => {
          (refreshPromise || auth.refresh()).then((success) => {
            if (success) {
              const newReq = req.clone({
                setHeaders: {
                  Authorization: `Bearer ${auth.getAccessToken()}`,
                },
              });
              next(newReq).subscribe(subscriber);
            } else {
              auth.logout();
              subscriber.error(error);
            }
          });
        });
      }
      return throwError(() => error);
    }),
  );
};
