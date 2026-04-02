<?php
/**
 * Single blog post view
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BlogPost $post
 */
$this->extend('/layout/marketing');

$this->assign('title', h($post->title) . ' - KeepUp Blog');
$this->assign('meta_description', $post->meta_description ?: $post->excerpt ?: '');
$this->assign('og_title', $post->title);
$this->assign('og_description', $post->meta_description ?: $post->excerpt ?: '');
$this->assign('og_url', 'https://usekeeup.com' . $post->url);
$this->assign('og_type', 'article');
if ($post->og_image) {
    $this->assign('og_image', $post->og_image);
}
?>

<?php $this->start('schema_json_ld'); ?>
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "<?= h($post->title) ?>",
    "description": "<?= h($post->meta_description ?: $post->excerpt ?: '') ?>",
    "author": {
        "@type": "Person",
        "name": "<?= h($post->author_name) ?>"
    },
    "publisher": {
        "@type": "Organization",
        "name": "KeepUp",
        "logo": {
            "@type": "ImageObject",
            "url": "https://usekeeup.com/img/icon_isp_status_page.png"
        }
    },
    "datePublished": "<?= $post->published_at ? $post->published_at->format('c') : '' ?>",
    "dateModified": "<?= $post->modified ? $post->modified->format('c') : '' ?>",
    "url": "https://usekeeup.com<?= h($post->url) ?>",
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "https://usekeeup.com<?= h($post->url) ?>"
    }
    <?php if ($post->og_image): ?>
    ,"image": "<?= h($post->og_image) ?>"
    <?php endif; ?>
}
</script>
<?php $this->end(); ?>

<article class="blog-content">
    <h1><?= h($post->title) ?></h1>

    <div class="blog-meta">
        <span><?= h($post->author_name) ?></span>
        <span><?= $post->published_at ? $post->published_at->format('F j, Y') : '' ?></span>
        <?php if ($post->tags): ?>
        <span>
            <?php foreach ($post->getTagsArray() as $tag): ?>
                <span class="blog-tag"><?= h($tag) ?></span>
            <?php endforeach; ?>
        </span>
        <?php endif; ?>
    </div>

    <div class="blog-body">
        <?= $post->content ?>
    </div>

    <div style="margin-top: 60px; padding-top: 32px; border-top: 1px solid var(--color-gray-200); text-align: center;">
        <a href="/blog" style="color: var(--color-brand-500); text-decoration: none; font-weight: 500;">&larr; Back to Blog</a>
    </div>
</article>

<?= $this->element('blog/cta') ?>
