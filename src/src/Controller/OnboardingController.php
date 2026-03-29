<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Onboarding Controller
 *
 * Redirects legacy onboarding wizard to the Angular SPA.
 * The Angular app handles the onboarding flow now.
 */
class OnboardingController extends AppController
{
    public function step1()
    {
        return $this->redirect('/app/onboarding');
    }

    public function step2()
    {
        return $this->redirect('/app/onboarding');
    }

    public function step3()
    {
        return $this->redirect('/app/onboarding');
    }

    public function complete()
    {
        return $this->redirect('/app/dashboard');
    }
}
