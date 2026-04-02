<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * All blog posts seed — single source of truth for production deployment.
 * Run: docker compose exec app bin/cake migrations seed --seed AllBlogPostsSeed
 * Uses ON CONFLICT (slug) DO NOTHING for idempotent re-runs.
 * Content loaded from blog_posts_data.json to avoid escaping issues.
 */
class AllBlogPostsSeed extends AbstractSeed
{
    public function run(): void
    {
        $jsonPath = __DIR__ . '/blog_posts_data.json';
        $posts = json_decode(file_get_contents($jsonPath), true);

        if (empty($posts)) {
            echo "No blog posts found in data file.\n";
            return;
        }

        $table = $this->table('blog_posts');
        $now = date('Y-m-d H:i:s');

        foreach ($posts as $post) {
            // Check if slug already exists (idempotent)
            $exists = $this->fetchRow(
                "SELECT id FROM blog_posts WHERE slug = '" . addslashes($post['slug']) . "'"
            );

            if ($exists) {
                continue;
            }

            $table->insert([
                'title' => $post['title'],
                'slug' => $post['slug'],
                'excerpt' => $post['excerpt'] ?: null,
                'content' => $post['content'],
                'meta_description' => $post['meta_description'] ?: null,
                'meta_keywords' => $post['meta_keywords'] ?: null,
                'og_image' => $post['og_image'] ?: null,
                'author_name' => $post['author_name'] ?: 'KeepUp Team',
                'tags' => $post['tags'] ?: null,
                'language' => $post['language'] ?: 'en',
                'status' => 'published',
                'published_at' => $post['published_at'],
                'created' => $now,
                'modified' => $now,
            ])->save();

            // Reset for next insert
            $table = $this->table('blog_posts');
        }

        echo count($posts) . " blog posts seeded.\n";
    }
}
