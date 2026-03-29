import { Injectable } from '@angular/core';
import { ApiService } from '../../core/services/api.service';
import { Observable } from 'rxjs';

export interface Settings {
  [key: string]: any;
}

@Injectable({ providedIn: 'root' })
export class SettingsService {
  constructor(private api: ApiService) {}

  get(group?: string): Observable<Settings> {
    return this.api.get<Settings>('/settings', group ? { group } : undefined);
  }

  save(data: Settings): Observable<Settings> {
    return this.api.put<Settings>('/settings', data);
  }
}
