<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateBlogPosts extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('blog_posts');
        $table->addColumn('title', 'string', [
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('slug', 'string', [
            'limit' => 255,
            'null' => false,
        ]);
        $table->addColumn('excerpt', 'text', [
            'null' => true,
            'default' => null,
        ]);
        $table->addColumn('content', 'text', [
            'null' => false,
        ]);
        $table->addColumn('meta_description', 'string', [
            'limit' => 320,
            'null' => true,
            'default' => null,
        ]);
        $table->addColumn('meta_keywords', 'string', [
            'limit' => 255,
            'null' => true,
            'default' => null,
        ]);
        $table->addColumn('og_image', 'string', [
            'limit' => 500,
            'null' => true,
            'default' => null,
        ]);
        $table->addColumn('author_name', 'string', [
            'limit' => 100,
            'null' => true,
            'default' => 'KeepUp Team',
        ]);
        $table->addColumn('tags', 'string', [
            'limit' => 500,
            'null' => true,
            'default' => null,
        ]);
        $table->addColumn('status', 'string', [
            'limit' => 20,
            'null' => false,
            'default' => 'draft',
        ]);
        $table->addColumn('published_at', 'timestamp', [
            'null' => true,
            'default' => null,
        ]);
        $table->addColumn('created', 'timestamp', [
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP',
        ]);
        $table->addColumn('modified', 'timestamp', [
            'null' => false,
            'default' => 'CURRENT_TIMESTAMP',
        ]);
        $table->addIndex(['slug'], ['unique' => true]);
        $table->addIndex(['status', 'published_at']);
        $table->create();
    }
}
