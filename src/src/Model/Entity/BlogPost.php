<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * BlogPost Entity
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $content
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property string|null $og_image
 * @property string $author_name
 * @property string|null $tags
 * @property string $status
 * @property string $language
 * @property \Cake\I18n\DateTime|null $published_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property string $url
 */
class BlogPost extends Entity
{
    /**
     * Fields that can be mass assigned.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'title' => true,
        'slug' => true,
        'excerpt' => true,
        'content' => true,
        'meta_description' => true,
        'meta_keywords' => true,
        'og_image' => true,
        'author_name' => true,
        'tags' => true,
        'status' => true,
        'language' => true,
        'published_at' => true,
    ];

    /**
     * Virtual field: URL for this blog post.
     *
     * @return string
     */
    protected function _getUrl(): string
    {
        if (!empty($this->language) && $this->language !== 'en') {
            return '/' . $this->language . '/blog/' . $this->slug;
        }

        return '/blog/' . $this->slug;
    }

    /**
     * Get tags as an array.
     *
     * @return array<string>
     */
    public function getTagsArray(): array
    {
        if (empty($this->tags)) {
            return [];
        }

        return array_map('trim', explode(',', $this->tags));
    }
}
