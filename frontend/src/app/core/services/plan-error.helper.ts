import { ToastController } from '@ionic/angular/standalone';
import { Router } from '@angular/router';
import { ApiError } from './api.service';

/**
 * Show an appropriate toast for API errors.
 * For 402 (plan limit) errors, shows a warning toast with an upgrade action button.
 * For other errors, shows a danger toast.
 */
export async function showApiError(
  err: any,
  fallbackMessage: string,
  toastCtrl: ToastController,
  router?: Router,
): Promise<void> {
  const is402 = err?.status === 402 || err?.error_type === 'plan_limit_exceeded';

  const toast = await toastCtrl.create({
    message: err?.message || fallbackMessage,
    color: is402 ? 'warning' : 'danger',
    duration: is402 ? 6000 : 4000,
    position: 'bottom',
    buttons: is402 && router
      ? [
          {
            text: 'Upgrade',
            handler: () => {
              router.navigate(['/billing']);
            },
          },
        ]
      : [],
  });
  await toast.present();
}
