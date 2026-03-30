import { Component, Input } from '@angular/core';
import {
  IonList,
  IonItem,
  IonLabel,
  IonSkeletonText,
} from '@ionic/angular/standalone';

@Component({
  selector: 'app-list-skeleton',
  standalone: true,
  imports: [IonList, IonItem, IonLabel, IonSkeletonText],
  template: `
    <ion-list>
      @for (i of rows; track i) {
        <ion-item>
          <ion-label>
            <h2>
              <ion-skeleton-text [animated]="true" style="width: 50%; height: 1rem;"></ion-skeleton-text>
            </h2>
            <p>
              <ion-skeleton-text [animated]="true" style="width: 70%; height: 0.75rem;"></ion-skeleton-text>
            </p>
          </ion-label>
        </ion-item>
      }
    </ion-list>
  `,
})
export class ListSkeletonComponent {
  @Input() count = 6;

  get rows(): number[] {
    return Array.from({ length: this.count }, (_, i) => i);
  }
}
