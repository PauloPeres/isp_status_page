import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

export interface BlogPost {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  content: string | null;
  meta_description: string | null;
  meta_keywords: string | null;
  og_image: string | null;
  author_name: string | null;
  tags: string | null;
  language: 'en' | 'pt' | 'es';
  status: 'draft' | 'published';
  published_at: string | null;
  created: string;
  modified: string;
}

export interface BlogPostListParams {
  page?: number;
  limit?: number;
  search?: string;
  status?: string;
  language?: string;
}

@Injectable({ providedIn: 'root' })
export class BlogPostService {
  constructor(private api: ApiService) {}

  list(params?: BlogPostListParams): Observable<PaginatedResponse<BlogPost>> {
    return this.api.get<PaginatedResponse<BlogPost>>('/super-admin/blog-posts', params as any);
  }

  get(id: number): Observable<BlogPost> {
    return this.api.get<any>(`/super-admin/blog-posts/${id}`).pipe(
      map((data) => data.blog_post),
    );
  }

  create(data: Partial<BlogPost>): Observable<BlogPost> {
    return this.api.post<any>('/super-admin/blog-posts', data).pipe(
      map((res) => res.blog_post),
    );
  }

  update(id: number, data: Partial<BlogPost>): Observable<BlogPost> {
    return this.api.put<any>(`/super-admin/blog-posts/${id}`, data).pipe(
      map((res) => res.blog_post),
    );
  }

  delete(id: number): Observable<any> {
    return this.api.delete<any>(`/super-admin/blog-posts/${id}`);
  }

  publish(id: number): Observable<BlogPost> {
    return this.api.post<any>(`/super-admin/blog-posts/${id}/publish`).pipe(
      map((res) => res.blog_post),
    );
  }

  unpublish(id: number): Observable<BlogPost> {
    return this.api.post<any>(`/super-admin/blog-posts/${id}/unpublish`).pipe(
      map((res) => res.blog_post),
    );
  }
}
