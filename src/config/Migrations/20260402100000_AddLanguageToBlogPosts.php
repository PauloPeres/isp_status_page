<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddLanguageToBlogPosts extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('blog_posts');
        $table->addColumn('language', 'string', [
            'limit' => 5,
            'default' => 'en',
            'null' => false,
            'after' => 'status',
        ]);
        $table->addIndex(['language']);
        $table->addIndex(['language', 'status', 'published_at']);
        $table->update();
    }
}
