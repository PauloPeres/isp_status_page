import {
  HttpInterceptorFn,
  HttpRequest,
  HttpHandlerFn,
  HttpErrorResponse,
} from '@angular/common/http';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { Observable, catchError, throwError } from 'rxjs';

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
        // Try refresh then retry the original request
        return new Observable<any>((subscriber) => {
          auth.refresh().then((success) => {
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
