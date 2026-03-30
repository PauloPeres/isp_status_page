import { Injectable } from '@angular/core';
import { HttpClient, HttpParams, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { environment } from '../../../environments/environment';

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  items: T[];
  pagination: {
    page: number;
    limit: number;
    total: number;
    pages: number;
  };
}

export interface ApiError {
  message: string;
  errors?: Record<string, Record<string, string>>;
  status: number;
}

function extractError(err: HttpErrorResponse): ApiError {
  const body = err.error;
  let message = 'An unexpected error occurred';
  let errors: Record<string, Record<string, string>> | undefined;

  if (body) {
    if (typeof body === 'string') {
      message = body;
    } else if (body.message) {
      message = body.message;
    } else if (body.error) {
      message = body.error;
    }
    if (body.errors && typeof body.errors === 'object') {
      errors = body.errors;
      // Build a readable message from validation errors
      const fieldErrors: string[] = [];
      for (const [field, rules] of Object.entries(errors!)) {
        const msgs = Object.values(rules as Record<string, string>);
        fieldErrors.push(...msgs);
      }
      if (fieldErrors.length > 0 && message === 'Validation failed') {
        message = fieldErrors.join('. ');
      }
    }
  }

  if (err.status === 0) {
    message = 'Unable to connect to server. Check your network connection.';
  } else if (err.status === 401) {
    message = 'Session expired. Please log in again.';
  } else if (err.status === 403) {
    message = 'You do not have permission to perform this action.';
  } else if (err.status === 404 && message === 'An unexpected error occurred') {
    message = 'The requested resource was not found.';
  } else if (err.status >= 500 && message === 'An unexpected error occurred') {
    message = 'Server error. Please try again later.';
  }

  return { message, errors, status: err.status };
}

function handleError(err: HttpErrorResponse): Observable<never> {
  return throwError(() => extractError(err));
}

@Injectable({ providedIn: 'root' })
export class ApiService {
  private baseUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  get<T>(path: string, params?: Record<string, any>): Observable<T> {
    let httpParams = new HttpParams();
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined) {
          httpParams = httpParams.set(key, String(value));
        }
      });
    }
    return this.http
      .get<ApiResponse<T>>(`${this.baseUrl}${path}`, { params: httpParams })
      .pipe(
        map((res) => res.data),
        catchError(handleError),
      );
  }

  post<T>(path: string, body: any = {}): Observable<T> {
    return this.http
      .post<ApiResponse<T>>(`${this.baseUrl}${path}`, body)
      .pipe(
        map((res) => res.data),
        catchError(handleError),
      );
  }

  put<T>(path: string, body: any = {}): Observable<T> {
    return this.http
      .put<ApiResponse<T>>(`${this.baseUrl}${path}`, body)
      .pipe(
        map((res) => res.data),
        catchError(handleError),
      );
  }

  delete<T>(path: string): Observable<T> {
    return this.http
      .delete<ApiResponse<T>>(`${this.baseUrl}${path}`)
      .pipe(
        map((res) => res.data),
        catchError(handleError),
      );
  }
}
