import { Component, Input } from '@angular/core';
import { Router } from '@angular/router';
import {
  IonCard, IonCardContent, IonIcon, IonButton,
} from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { lockClosedOutline, arrowUpCircleOutline } from 'ionicons/icons';
import { ApiError } from '../../core/services/api.service';

addIcons({
  'lock-closed-outline': lockClosedOutline,
  'arrow-up-circle-outline': arrowUpCircleOutline,
});

@Component({
  selector: 'app-upgrade-prompt',
  standalone: true,
  imports: [IonCard, IonCardContent, IonIcon, IonButton],
  template: `
    <ion-card class="upgrade-card">
      <ion-card-content class="upgrade-content">
        <ion-icon name="lock-closed-outline" class="upgrade-icon"></ion-icon>
        <div class="upgrade-text">
          <h3>{{ title }}</h3>
          <p>{{ message }}</p>
          @if (currentUsage) {
            <p class="usage-text">Currently using {{ currentUsage }} of {{ usageLimit }}</p>
          }
        </div>
        <ion-button fill="solid" color="primary" (click)="goToBilling()">
          <ion-icon name="arrow-up-circle-outline" slot="start"></ion-icon>
          Upgrade Plan
        </ion-button>
      </ion-card-content>
    </ion-card>
  `,
  styles: [`
    .upgrade-card {
      --background: var(--ion-color-warning-tint, #FFF8E1);
      border: 1px solid var(--ion-color-warning);
    }
    .upgrade-content {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }
    .upgrade-icon {
      font-size: 2rem;
      color: var(--ion-color-warning-shade);
      flex-shrink: 0;
    }
    .upgrade-text {
      flex: 1;
      min-width: 200px;
    }
    .upgrade-text h3 {
      margin: 0 0 4px;
      font-size: 1rem;
      font-weight: 600;
    }
    .upgrade-text p {
      margin: 0;
      font-size: 0.85rem;
      color: var(--ion-color-medium);
    }
    .usage-text {
      font-weight: 600;
      color: var(--ion-color-warning-shade) !important;
      margin-top: 4px !important;
    }
    ion-button {
      --border-radius: 8px;
      flex-shrink: 0;
    }
  `],
})
export class UpgradePromptComponent {
  @Input() title = 'Upgrade Required';
  @Input() message = 'This feature is not available on your current plan.';
  @Input() currentUsage: number | null = null;
  @Input() usageLimit: number | string | null = null;

  constructor(private router: Router) {}

  /**
   * Create from an ApiError returned by a 402 response.
   */
  static fromApiError(err: ApiError): { title: string; message: string; current: number | null; limit: number | string | null } {
    return {
      title: 'Plan Limit Reached',
      message: err.message,
      current: err.data?.current ?? null,
      limit: err.data?.limit ?? null,
    };
  }

  goToBilling(): void {
    this.router.navigate(['/billing']);
  }
}
