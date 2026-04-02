import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonNote, IonIcon, IonSearchbar,
  IonRefresher, IonRefresherContent,
  AlertController, ToastController,
} from '@ionic/angular/standalone';
import { BlogPostService, BlogPost } from './blog-post.service';
import { ListSkeletonComponent } from '../../../shared/components/list-skeleton.component';
import { addIcons } from 'ionicons';
import { addCircleOutline, createOutline, trashOutline, cloudUploadOutline, cloudOfflineOutline } from 'ionicons/icons';

addIcons({
  'add-circle-outline': addCircleOutline,
  'create-outline': createOutline,
  'trash-outline': trashOutline,
  'cloud-upload-outline': cloudUploadOutline,
  'cloud-offline-outline': cloudOfflineOutline,
});

@Component({
  selector: 'app-blog-post-list',
  standalone: true,
  imports: [
    CommonModule, ListSkeletonComponent,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonNote, IonIcon, IonSearchbar,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/super-admin"></ion-back-button>
        </ion-buttons>
        <ion-title>Blog Posts</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="onNew()">
            <ion-icon name="add-circle-outline" slot="start"></ion-icon>
            New Post
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
      <ion-toolbar color="dark">
        <ion-searchbar
          placeholder="Search posts..."
          [debounce]="400"
          (ionInput)="onSearch($event)"
        ></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <app-list-skeleton [count]="5"></app-list-skeleton>
      } @else {
        <ion-list>
          @for (post of posts(); track post.id) {
            <ion-item-sliding>
              <ion-item (click)="onEdit(post)" style="cursor: pointer">
                <ion-label>
                  <h2>{{ post.title }}</h2>
                  <p>
                    @if (post.author_name) {
                      <span>{{ post.author_name }}</span>
                      <span> &middot; </span>
                    }
                    @if (post.published_at) {
                      <span>{{ post.published_at | date: 'mediumDate' }}</span>
                    } @else {
                      <span>Not published</span>
                    }
                  </p>
                  @if (post.excerpt) {
                    <p class="excerpt">{{ post.excerpt }}</p>
                  }
                </ion-label>
                <div slot="end" style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px">
                  <ion-badge
                    [color]="post.status === 'published' ? 'success' : 'warning'"
                  >
                    {{ post.status }}
                  </ion-badge>
                  <ion-badge color="medium" style="font-size: 0.6rem; text-transform: uppercase">
                    {{ post.language || 'en' }}
                  </ion-badge>
                </div>
              </ion-item>

              <ion-item-options side="end">
                <ion-item-option color="primary" (click)="onEdit(post)">
                  <ion-icon name="create-outline" slot="icon-only"></ion-icon>
                </ion-item-option>
                @if (post.status === 'draft') {
                  <ion-item-option color="success" (click)="onPublish(post)">
                    <ion-icon name="cloud-upload-outline" slot="icon-only"></ion-icon>
                  </ion-item-option>
                } @else {
                  <ion-item-option color="warning" (click)="onUnpublish(post)">
                    <ion-icon name="cloud-offline-outline" slot="icon-only"></ion-icon>
                  </ion-item-option>
                }
                <ion-item-option color="danger" (click)="onDelete(post)">
                  <ion-icon name="trash-outline" slot="icon-only"></ion-icon>
                </ion-item-option>
              </ion-item-options>
            </ion-item-sliding>
          } @empty {
            <div style="text-align: center; padding: 3rem; color: var(--ion-color-medium)">
              <ion-icon name="document-text-outline" style="font-size: 3rem; display: block; margin-bottom: 1rem"></ion-icon>
              <p>No blog posts yet. Create your first post.</p>
            </div>
          }
        </ion-list>
      }
    </ion-content>
  `,
  styles: [`
    .excerpt {
      font-size: 0.85rem;
      color: var(--ion-color-medium);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 400px;
    }
  `],
})
export class BlogPostListComponent implements OnInit, ViewWillEnter {
  posts = signal<BlogPost[]>([]);
  loading = signal(true);
  private searchTerm = '';

  constructor(
    private blogService: BlogPostService,
    private router: Router,
    private alertCtrl: AlertController,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {}

  ionViewWillEnter(): void {
    this.load();
  }

  load(): void {
    const params: any = {};
    if (this.searchTerm) {
      params.search = this.searchTerm;
    }
    this.blogService.list(params).subscribe({
      next: (data) => {
        this.posts.set(data.items);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  onRefresh(event: any): void {
    const params: any = {};
    if (this.searchTerm) {
      params.search = this.searchTerm;
    }
    this.blogService.list(params).subscribe({
      next: (data) => {
        this.posts.set(data.items);
        event.target.complete();
      },
      error: () => event.target.complete(),
    });
  }

  onSearch(event: any): void {
    this.searchTerm = event.detail.value || '';
    this.load();
  }

  onNew(): void {
    this.router.navigate(['/super-admin/blog-posts/new']);
  }

  onEdit(post: BlogPost): void {
    this.router.navigate(['/super-admin/blog-posts', post.id, 'edit']);
  }

  async onPublish(post: BlogPost): Promise<void> {
    this.blogService.publish(post.id).subscribe({
      next: async () => {
        this.load();
        const toast = await this.toastCtrl.create({
          message: `"${post.title}" published`,
          color: 'success',
          duration: 2000,
          position: 'bottom',
        });
        await toast.present();
      },
      error: async (err: any) => {
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Failed to publish',
          color: 'danger',
          duration: 4000,
          position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  async onUnpublish(post: BlogPost): Promise<void> {
    this.blogService.unpublish(post.id).subscribe({
      next: async () => {
        this.load();
        const toast = await this.toastCtrl.create({
          message: `"${post.title}" unpublished`,
          color: 'warning',
          duration: 2000,
          position: 'bottom',
        });
        await toast.present();
      },
      error: async (err: any) => {
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Failed to unpublish',
          color: 'danger',
          duration: 4000,
          position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  async onDelete(post: BlogPost): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Post',
      message: `Delete "${post.title}"? This cannot be undone.`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Delete',
          role: 'destructive',
          handler: () => {
            this.blogService.delete(post.id).subscribe({
              next: async () => {
                this.posts.update((list) => list.filter((p) => p.id !== post.id));
                const toast = await this.toastCtrl.create({
                  message: 'Post deleted',
                  color: 'success',
                  duration: 2000,
                  position: 'bottom',
                });
                await toast.present();
              },
              error: async (err: any) => {
                const toast = await this.toastCtrl.create({
                  message: err?.message || 'Failed to delete',
                  color: 'danger',
                  duration: 4000,
                  position: 'bottom',
                });
                await toast.present();
              },
            });
          },
        },
      ],
    });
    await alert.present();
  }
}
