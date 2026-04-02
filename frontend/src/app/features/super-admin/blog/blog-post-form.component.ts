import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonLabel, IonInput, IonTextarea, IonSelect, IonSelectOption,
  IonNote, IonSpinner, IonIcon,
  ToastController,
} from '@ionic/angular/standalone';
import { BlogPostService, BlogPost } from './blog-post.service';
import { addIcons } from 'ionicons';
import { refreshOutline, saveOutline } from 'ionicons/icons';

addIcons({
  'refresh-outline': refreshOutline,
  'save-outline': saveOutline,
});

@Component({
  selector: 'app-blog-post-form',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonLabel, IonInput, IonTextarea, IonSelect, IonSelectOption,
    IonNote, IonSpinner, IonIcon,
  ],
  template: `
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/super-admin/blog-posts"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ isEdit() ? 'Edit Post' : 'New Post' }}</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="onSave()" [disabled]="saving()">
            @if (saving()) {
              <ion-spinner name="crescent" slot="start"></ion-spinner>
            } @else {
              <ion-icon name="save-outline" slot="start"></ion-icon>
            }
            Save
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      @if (loadingPost()) {
        <div style="text-align: center; padding: 3rem">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {
        <ion-list>
          <ion-item>
            <ion-input
              label="Title"
              labelPlacement="stacked"
              placeholder="Blog post title"
              [(ngModel)]="form.title"
              (ionBlur)="onTitleBlur()"
            ></ion-input>
          </ion-item>

          <ion-item>
            <ion-input
              label="Slug"
              labelPlacement="stacked"
              placeholder="url-friendly-slug"
              [(ngModel)]="form.slug"
            ></ion-input>
            <ion-button fill="clear" slot="end" (click)="regenerateSlug()" title="Regenerate slug from title">
              <ion-icon name="refresh-outline" slot="icon-only"></ion-icon>
            </ion-button>
          </ion-item>

          <ion-item>
            <ion-textarea
              label="Excerpt"
              labelPlacement="stacked"
              placeholder="Short summary of the post"
              [(ngModel)]="form.excerpt"
              [rows]="2"
              [autoGrow]="true"
            ></ion-textarea>
          </ion-item>

          <ion-item>
            <ion-textarea
              label="Content (HTML)"
              labelPlacement="stacked"
              placeholder="<p>Your blog post content...</p>"
              [(ngModel)]="form.content"
              [rows]="12"
              [autoGrow]="true"
              style="font-family: monospace; font-size: 0.9rem"
            ></ion-textarea>
          </ion-item>

          <ion-item>
            <ion-textarea
              label="Meta Description"
              labelPlacement="stacked"
              placeholder="SEO meta description (max 320 characters)"
              [(ngModel)]="form.meta_description"
              [rows]="2"
              [autoGrow]="true"
              [maxlength]="320"
            ></ion-textarea>
            <ion-note slot="helper">
              {{ (form.meta_description || '').length }} / 320
            </ion-note>
          </ion-item>

          <ion-item>
            <ion-input
              label="Meta Keywords"
              labelPlacement="stacked"
              placeholder="keyword1, keyword2, keyword3"
              [(ngModel)]="form.meta_keywords"
            ></ion-input>
          </ion-item>

          <ion-item>
            <ion-input
              label="Author Name"
              labelPlacement="stacked"
              placeholder="KeepUp Team"
              [(ngModel)]="form.author_name"
            ></ion-input>
          </ion-item>

          <ion-item>
            <ion-input
              label="Tags"
              labelPlacement="stacked"
              placeholder="tag1, tag2, tag3"
              [(ngModel)]="form.tags"
            ></ion-input>
          </ion-item>

          <ion-item>
            <ion-select
              label="Language"
              labelPlacement="stacked"
              [(ngModel)]="form.language"
              interface="popover"
            >
              <ion-select-option value="en">English</ion-select-option>
              <ion-select-option value="pt">Portuguese</ion-select-option>
              <ion-select-option value="es">Spanish</ion-select-option>
            </ion-select>
          </ion-item>

          <ion-item>
            <ion-select
              label="Status"
              labelPlacement="stacked"
              [(ngModel)]="form.status"
              interface="popover"
            >
              <ion-select-option value="draft">Draft</ion-select-option>
              <ion-select-option value="published">Published</ion-select-option>
            </ion-select>
          </ion-item>

          <ion-item>
            <ion-input
              label="OG Image URL"
              labelPlacement="stacked"
              placeholder="https://example.com/image.jpg"
              [(ngModel)]="form.og_image"
              type="url"
            ></ion-input>
          </ion-item>
        </ion-list>

        <div style="padding: 1rem 0">
          <ion-button expand="block" (click)="onSave()" [disabled]="saving()">
            @if (saving()) {
              <ion-spinner name="crescent" slot="start"></ion-spinner>
            }
            {{ isEdit() ? 'Update Post' : 'Create Post' }}
          </ion-button>
        </div>
      }
    </ion-content>
  `,
  styles: [`
    ion-list {
      background: transparent;
    }
  `],
})
export class BlogPostFormComponent implements OnInit {
  isEdit = signal(false);
  loadingPost = signal(false);
  saving = signal(false);
  private postId: number | null = null;

  form: Partial<BlogPost> = {
    title: '',
    slug: '',
    excerpt: '',
    content: '',
    meta_description: '',
    meta_keywords: '',
    author_name: 'KeepUp Team',
    tags: '',
    language: 'en',
    status: 'draft',
    og_image: '',
  };

  constructor(
    private blogService: BlogPostService,
    private route: ActivatedRoute,
    private router: Router,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.postId = parseInt(idParam, 10);
      this.isEdit.set(true);
      this.loadPost();
    }
  }

  private loadPost(): void {
    if (!this.postId) return;
    this.loadingPost.set(true);
    this.blogService.get(this.postId).subscribe({
      next: (post) => {
        this.form = {
          title: post.title || '',
          slug: post.slug || '',
          excerpt: post.excerpt || '',
          content: post.content || '',
          meta_description: post.meta_description || '',
          meta_keywords: post.meta_keywords || '',
          author_name: post.author_name || '',
          tags: post.tags || '',
          language: post.language || 'en',
          status: post.status || 'draft',
          og_image: post.og_image || '',
        };
        this.loadingPost.set(false);
      },
      error: async () => {
        this.loadingPost.set(false);
        const toast = await this.toastCtrl.create({
          message: 'Failed to load post',
          color: 'danger',
          duration: 3000,
          position: 'bottom',
        });
        await toast.present();
        this.router.navigate(['/super-admin/blog-posts']);
      },
    });
  }

  onTitleBlur(): void {
    // Auto-generate slug from title if slug is empty
    if (!this.form.slug && this.form.title) {
      this.form.slug = this.slugify(this.form.title);
    }
  }

  regenerateSlug(): void {
    if (this.form.title) {
      this.form.slug = this.slugify(this.form.title);
    }
  }

  async onSave(): Promise<void> {
    if (!this.form.title?.trim()) {
      const toast = await this.toastCtrl.create({
        message: 'Title is required',
        color: 'warning',
        duration: 3000,
        position: 'bottom',
      });
      await toast.present();
      return;
    }

    this.saving.set(true);

    const data = { ...this.form };

    const obs = this.isEdit() && this.postId
      ? this.blogService.update(this.postId, data)
      : this.blogService.create(data);

    obs.subscribe({
      next: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({
          message: this.isEdit() ? 'Post updated' : 'Post created',
          color: 'success',
          duration: 2000,
          position: 'bottom',
        });
        await toast.present();
        this.router.navigate(['/super-admin/blog-posts']);
      },
      error: async (err: any) => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Failed to save post',
          color: 'danger',
          duration: 4000,
          position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  private slugify(text: string): string {
    return text
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }
}
