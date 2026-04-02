<?php
declare(strict_types=1);

namespace App\Controller\Api\V2\SuperAdmin;

use Cake\I18n\DateTime;

/**
 * BlogPostsController — Super Admin
 *
 * CRUD for platform-level blog posts.
 */
class BlogPostsController extends AppController
{
    /**
     * Allowed HTML tags for blog content sanitization.
     * Strips scripts, iframes, event handlers while keeping safe formatting.
     */
    private const ALLOWED_TAGS = '<p><br><h1><h2><h3><h4><h5><h6><ul><ol><li><a><strong><b><em><i><u><s><blockquote><pre><code><img><table><thead><tbody><tr><th><td><hr><div><span><figure><figcaption><video><source><mark><sub><sup><dl><dt><dd>';

    /**
     * Sanitize HTML content to prevent stored XSS.
     * Strips dangerous tags and event handler attributes.
     */
    private function sanitizeHtml(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        // Strip dangerous tags (script, iframe, object, embed, form, etc.)
        $html = strip_tags($html, self::ALLOWED_TAGS);

        // Remove all on* event handler attributes (onclick, onerror, onload, etc.)
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*\S+/i', '', $html);

        // Remove javascript: and data: URIs from href/src attributes
        $html = preg_replace('/(<\w+[^>]*)\s+(href|src)\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', '$1', $html);
        $html = preg_replace('/(<\w+[^>]*)\s+(href|src)\s*=\s*["\']?\s*data:(?!image\/)[^"\'>\s]*/i', '$1', $html);

        return $html;
    }

    /**
     * GET /api/v2/super-admin/blog-posts
     *
     * List all blog posts (published + drafts), paginated, newest first.
     * Supports ?search= for title search.
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('BlogPosts');
        $query = $table->find()
            ->orderBy(['BlogPosts.created' => 'DESC']);

        // Search by title
        $search = $this->request->getQuery('search');
        if ($search) {
            $query->where(['BlogPosts.title ILIKE' => '%' . $search . '%']);
        }

        // Status filter
        $status = $this->request->getQuery('status');
        if ($status) {
            $query->where(['BlogPosts.status' => $status]);
        }

        // Language filter
        $language = $this->request->getQuery('language');
        if ($language && in_array($language, ['en', 'pt', 'es'])) {
            $query->where(['BlogPosts.language' => $language]);
        }

        $page = (int)($this->request->getQuery('page') ?: 1);
        $limit = (int)($this->request->getQuery('limit') ?: 20);

        $total = $query->count();
        $posts = $query
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->all()
            ->toArray();

        $this->success([
            'items' => $posts,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit),
            ],
        ]);
    }

    /**
     * GET /api/v2/super-admin/blog-posts/:id
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('BlogPosts');

        try {
            $post = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Blog post not found', 404);
            return;
        }

        $this->success(['blog_post' => $post]);
    }

    /**
     * POST /api/v2/super-admin/blog-posts
     *
     * Create a new blog post. Auto-generates slug from title if not provided.
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        $table = $this->fetchTable('BlogPosts');
        $data = $this->request->getData();

        // Auto-generate slug from title if not provided
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->slugify($data['title']);
        }

        // Default status
        if (empty($data['status'])) {
            $data['status'] = 'draft';
        }

        // If publishing, set published_at
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = DateTime::now();
        }

        // Sanitize HTML content to prevent stored XSS
        if (!empty($data['content'])) {
            $data['content'] = $this->sanitizeHtml($data['content']);
        }

        $post = $table->newEntity($data);

        if (!$table->save($post)) {
            $this->error('Validation failed', 422, $post->getErrors());
            return;
        }

        $this->success(['blog_post' => $post], 201);
    }

    /**
     * PUT/PATCH /api/v2/super-admin/blog-posts/:id
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put', 'patch']);

        $table = $this->fetchTable('BlogPosts');

        try {
            $post = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Blog post not found', 404);
            return;
        }

        $data = $this->request->getData();

        // Auto-generate slug from title if slug is explicitly empty
        if (array_key_exists('slug', $data) && empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->slugify($data['title']);
        }

        // Sanitize HTML content to prevent stored XSS
        if (!empty($data['content'])) {
            $data['content'] = $this->sanitizeHtml($data['content']);
        }

        $post = $table->patchEntity($post, $data);

        if (!$table->save($post)) {
            $this->error('Validation failed', 422, $post->getErrors());
            return;
        }

        $this->success(['blog_post' => $post]);
    }

    /**
     * DELETE /api/v2/super-admin/blog-posts/:id
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        $table = $this->fetchTable('BlogPosts');

        try {
            $post = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Blog post not found', 404);
            return;
        }

        if ($table->delete($post)) {
            $this->success(['message' => 'Blog post deleted']);
        } else {
            $this->error('Failed to delete blog post', 500);
        }
    }

    /**
     * POST /api/v2/super-admin/blog-posts/:id/publish
     *
     * Set status=published and published_at=now.
     */
    public function publish(string $id): void
    {
        $this->request->allowMethod(['post']);

        $table = $this->fetchTable('BlogPosts');

        try {
            $post = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Blog post not found', 404);
            return;
        }

        $post = $table->patchEntity($post, [
            'status' => 'published',
            'published_at' => DateTime::now(),
        ]);

        if (!$table->save($post)) {
            $this->error('Failed to publish blog post', 500, $post->getErrors());
            return;
        }

        $this->success(['blog_post' => $post]);
    }

    /**
     * POST /api/v2/super-admin/blog-posts/:id/unpublish
     *
     * Set status=draft and published_at=null.
     */
    public function unpublish(string $id): void
    {
        $this->request->allowMethod(['post']);

        $table = $this->fetchTable('BlogPosts');

        try {
            $post = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Blog post not found', 404);
            return;
        }

        $post = $table->patchEntity($post, [
            'status' => 'draft',
            'published_at' => null,
        ]);

        if (!$table->save($post)) {
            $this->error('Failed to unpublish blog post', 500, $post->getErrors());
            return;
        }

        $this->success(['blog_post' => $post]);
    }

    /**
     * Generate a URL-friendly slug from a string.
     */
    private function slugify(string $text): string
    {
        // Transliterate to ASCII
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        // Replace non-alphanumeric with hyphens
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        // Trim hyphens from start/end
        $text = trim($text, '-');

        return $text;
    }
}
