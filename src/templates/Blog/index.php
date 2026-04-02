<?php
/**
 * Blog listing page
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\BlogPost> $posts
 */
$this->extend('/layout/marketing');

$this->assign('title', 'Blog - KeepUp');
$this->assign('meta_description', 'Insights on uptime monitoring, status pages, incident management, and building reliable infrastructure. From the KeepUp team.');
$this->assign('og_title', 'KeepUp Blog - Monitoring & Reliability Insights');
$this->assign('og_url', 'https://usekeeup.com/blog');
?>

<?php $lang = $language ?? 'en'; ?>
<div class="mktg-hero">
    <h1><?= $lang === 'pt' ? 'Blog do KeepUp' : ($lang === 'es' ? 'Blog de KeepUp' : 'The KeepUp Blog') ?></h1>
    <p><?= $lang === 'pt' ? 'Insights sobre monitoramento de uptime, páginas de status e infraestrutura confiável.' : ($lang === 'es' ? 'Insights sobre monitoreo de uptime, páginas de estado e infraestructura confiable.' : 'Insights on uptime monitoring, status pages, incident management, and building reliable infrastructure.') ?></p>
    <div style="margin-top: 12px; display: flex; gap: 8px; justify-content: center;">
        <a href="/blog" style="padding: 6px 14px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; font-weight: 500; <?= $lang === 'en' ? 'background: #2979FF; color: #fff;' : 'background: #E2E8F0; color: #4B5563;' ?>">EN</a>
        <a href="/pt/blog" style="padding: 6px 14px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; font-weight: 500; <?= $lang === 'pt' ? 'background: #2979FF; color: #fff;' : 'background: #E2E8F0; color: #4B5563;' ?>">PT</a>
        <a href="/es/blog" style="padding: 6px 14px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; font-weight: 500; <?= $lang === 'es' ? 'background: #2979FF; color: #fff;' : 'background: #E2E8F0; color: #4B5563;' ?>">ES</a>
    </div>
</div>

<div class="mktg-section" style="padding-top: 0;">
    <div class="mktg-grid-3">
        <?php foreach ($posts as $post): ?>
        <article class="blog-card">
            <div class="blog-card-body">
                <h2 class="blog-card-title">
                    <a href="<?= h($post->url) ?>"><?= h($post->title) ?></a>
                </h2>
                <?php if ($post->excerpt): ?>
                    <p class="blog-card-excerpt"><?= h($post->excerpt) ?></p>
                <?php endif; ?>
                <div class="blog-card-meta">
                    <span><?= h($post->author_name) ?></span>
                    <span><?= $post->published_at ? $post->published_at->format('M j, Y') : '' ?></span>
                </div>
                <?php if ($post->tags): ?>
                <div style="margin-top: 12px;">
                    <?php foreach ($post->getTagsArray() as $tag): ?>
                        <span class="blog-tag"><?= h($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <?php if (empty($posts->toArray())): ?>
    <div style="text-align: center; padding: 60px 0;">
        <p class="mktg-text">No posts yet. Check back soon!</p>
    </div>
    <?php endif; ?>

    <!-- Pagination -->
    <div style="text-align: center; margin-top: 48px;">
        <?= $this->Paginator->numbers([
            'first' => 'First',
            'last' => 'Last',
        ]) ?>
    </div>
</div>
