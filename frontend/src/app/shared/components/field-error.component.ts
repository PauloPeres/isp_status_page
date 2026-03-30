import { Component, Input } from '@angular/core';
import { AbstractControl } from '@angular/forms';
import { IonNote } from '@ionic/angular/standalone';

@Component({
  selector: 'app-field-error',
  standalone: true,
  imports: [IonNote],
  template: `
    @if (control?.touched && control?.invalid) {
      @if (control?.hasError('required')) {
        <ion-note color="danger" class="field-error">{{ label }} is required</ion-note>
      } @else if (control?.hasError('minlength')) {
        <ion-note color="danger" class="field-error">{{ label }} must be at least {{ control?.getError('minlength')?.requiredLength }} characters</ion-note>
      } @else if (control?.hasError('maxlength')) {
        <ion-note color="danger" class="field-error">{{ label }} must be at most {{ control?.getError('maxlength')?.requiredLength }} characters</ion-note>
      } @else if (control?.hasError('min')) {
        <ion-note color="danger" class="field-error">{{ label }} must be at least {{ control?.getError('min')?.min }}</ion-note>
      } @else if (control?.hasError('max')) {
        <ion-note color="danger" class="field-error">{{ label }} must be at most {{ control?.getError('max')?.max }}</ion-note>
      } @else if (control?.hasError('email')) {
        <ion-note color="danger" class="field-error">Please enter a valid email address</ion-note>
      } @else if (control?.hasError('pattern')) {
        <ion-note color="danger" class="field-error">{{ label }} format is invalid</ion-note>
      } @else {
        <ion-note color="danger" class="field-error">{{ label }} is invalid</ion-note>
      }
    }
  `,
  styles: [
    `
      .field-error {
        display: block;
        padding: 4px 0 0;
        font-size: 0.75rem;
      }
    `,
  ],
})
export class FieldErrorComponent {
  @Input() control: AbstractControl | null | undefined;
  @Input() label = 'This field';
}
