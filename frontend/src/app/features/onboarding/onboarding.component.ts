import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonMenuButton,
  IonButtons,
  IonButton,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonIcon,
  IonProgressBar,
  IonSkeletonText,
} from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import {
  pulseOutline,
  notificationsOutline,
  globeOutline,
  peopleOutline,
  checkmarkCircle,
  arrowForwardOutline,
  rocketOutline,
  closeOutline,
} from 'ionicons/icons';
import { OnboardingService, OnboardingStep } from './onboarding.service';
import { BRAND } from '../../core/config/brand.config';

addIcons({
  'pulse-outline': pulseOutline,
  'notifications-outline': notificationsOutline,
  'globe-outline': globeOutline,
  'people-outline': peopleOutline,
  'checkmark-circle': checkmarkCircle,
  'arrow-forward-outline': arrowForwardOutline,
  'rocket-outline': rocketOutline,
  'close-outline': closeOutline,
});

@Component({
  selector: 'app-onboarding',
  standalone: true,
  imports: [
    CommonModule,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonMenuButton,
    IonButtons,
    IonButton,
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardContent,
    IonIcon,
    IonProgressBar,
    IonSkeletonText,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button></ion-menu-button>
        </ion-buttons>
        <ion-title>Getting Started</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="skipSetup()">
            Skip
            <ion-icon name="close-outline" slot="end"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="onboarding-container">
        <!-- Welcome Header -->
        <div class="welcome-section">
          <ion-icon name="rocket-outline" class="welcome-icon"></ion-icon>
          <h1>Welcome to {{ brand.name }}!</h1>
          <p>Let's get your monitoring set up in a few quick steps.</p>
        </div>

        <!-- Progress -->
        @if (onboardingService.loading()) {
          <ion-skeleton-text [animated]="true" style="width: 100%; height: 8px; margin: 1.5rem 0;"></ion-skeleton-text>
        } @else if (onboardingService.progress()) {
          <div class="progress-section">
            <div class="progress-text">
              {{ onboardingService.progress()!.completedCount }} of {{ onboardingService.progress()!.totalCount }} steps completed
            </div>
            <ion-progress-bar
              [value]="onboardingService.progress()!.completedCount / onboardingService.progress()!.totalCount"
              [color]="onboardingService.progress()!.allDone ? 'success' : 'primary'"
            ></ion-progress-bar>
          </div>

          <!-- Steps -->
          <div class="steps-list">
            @for (step of onboardingService.progress()!.steps; track step.id) {
              <ion-card
                class="step-card"
                [class.step-completed]="step.completed"
                (click)="goToStep(step)"
                button
              >
                <ion-card-content class="step-content">
                  <div class="step-icon-wrapper" [class.icon-completed]="step.completed">
                    @if (step.completed) {
                      <ion-icon name="checkmark-circle" class="step-icon completed-icon"></ion-icon>
                    } @else {
                      <ion-icon [name]="step.icon" class="step-icon"></ion-icon>
                    }
                  </div>
                  <div class="step-text">
                    <h2>{{ step.title }}</h2>
                    <p>{{ step.description }}</p>
                  </div>
                  @if (!step.completed) {
                    <ion-icon name="arrow-forward-outline" class="step-arrow"></ion-icon>
                  }
                </ion-card-content>
              </ion-card>
            }
          </div>

          @if (onboardingService.progress()!.allDone) {
            <div class="all-done-section">
              <h2>You're all set!</h2>
              <p>Your monitoring is configured. Head to the dashboard to see everything in action.</p>
              <ion-button expand="block" (click)="goToDashboard()">
                Go to Dashboard
              </ion-button>
            </div>
          }
        }
      </div>
    </ion-content>
  `,
  styles: [
    `
      .onboarding-container {
        max-width: 640px;
        margin: 0 auto;
      }

      .welcome-section {
        text-align: center;
        padding: 2rem 1rem 1rem;
      }
      .welcome-icon {
        font-size: 3.5rem;
        color: var(--ion-color-primary);
      }
      .welcome-section h1 {
        font-family: 'DM Sans', sans-serif;
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0.75rem 0 0.5rem;
        color: var(--ion-text-color);
      }
      .welcome-section p {
        color: var(--ion-color-medium);
        font-size: 1rem;
        margin: 0;
      }

      .progress-section {
        margin: 1.5rem 0;
      }
      .progress-text {
        font-size: 0.85rem;
        color: var(--ion-color-medium);
        margin-bottom: 8px;
        text-align: center;
      }

      .steps-list {
        display: flex;
        flex-direction: column;
        gap: 0;
      }

      .step-card {
        margin: 6px 0;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
      }
      .step-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      }
      .step-completed {
        opacity: 0.7;
      }
      .step-completed:hover {
        transform: none;
        box-shadow: none;
      }

      .step-content {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
      }

      .step-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: rgba(41, 121, 255, 0.12);
      }
      .icon-completed {
        background: var(--ion-color-success-tint);
      }

      .step-icon {
        font-size: 1.5rem;
        color: var(--ion-color-primary);
      }
      .completed-icon {
        color: var(--ion-color-success);
      }

      .step-text {
        flex: 1;
        min-width: 0;
      }
      .step-text h2 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 4px;
      }
      .step-text p {
        font-size: 0.85rem;
        color: var(--ion-color-medium);
        margin: 0;
        line-height: 1.4;
      }

      .step-arrow {
        font-size: 1.2rem;
        color: var(--ion-color-medium);
        flex-shrink: 0;
      }

      .all-done-section {
        text-align: center;
        padding: 2rem 1rem;
      }
      .all-done-section h2 {
        color: var(--ion-color-success);
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
      }
      .all-done-section p {
        color: var(--ion-color-medium);
        margin-bottom: 1.5rem;
      }
      .all-done-section ion-button {
        --border-radius: 8px;
        max-width: 300px;
        margin: 0 auto;
      }
    `,
  ],
})
export class OnboardingComponent implements OnInit, ViewWillEnter {
  brand = BRAND;

  constructor(
    public onboardingService: OnboardingService,
    private router: Router,
  ) {}

  ngOnInit(): void {
    this.onboardingService.loadProgress();
  }

  ionViewWillEnter(): void {
    this.onboardingService.loadProgress();
  }

  goToStep(step: OnboardingStep): void {
    if (!step.completed) {
      this.router.navigate([step.route], { queryParams: { from: 'onboarding' } });
    }
  }

  skipSetup(): void {
    this.onboardingService.dismiss();
    this.router.navigate(['/dashboard']);
  }

  goToDashboard(): void {
    this.onboardingService.dismiss();
    this.router.navigate(['/dashboard']);
  }
}
