import { Injectable } from '@angular/core';
import { ApiService } from '../../core/services/api.service';
import { Observable } from 'rxjs';

export interface SipConfiguration {
  provider: string;
  sip_host: string | null;
  sip_port: number | null;
  sip_username: string | null;
  sip_password?: string | null;
  sip_transport: string | null;
  caller_id: string | null;
  twilio_trunk_sid: string | null;
  active: boolean;
  last_tested_at: string | null;
  last_test_result: string | null;
  public_id?: string;
}

@Injectable({ providedIn: 'root' })
export class SipConfigurationService {
  constructor(private api: ApiService) {}

  get(): Observable<{ sip_configuration: SipConfiguration }> {
    return this.api.get<{ sip_configuration: SipConfiguration }>('/sip-configuration');
  }

  save(data: Partial<SipConfiguration>): Observable<{ sip_configuration: SipConfiguration }> {
    return this.api.put<{ sip_configuration: SipConfiguration }>('/sip-configuration', data);
  }

  test(): Observable<{ message: string }> {
    return this.api.post<{ message: string }>('/sip-configuration/test', {});
  }
}
