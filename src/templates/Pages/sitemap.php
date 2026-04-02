<?php
/**
 * Dynamic XML Sitemap
 *
 * @var \App\View\AppView $this
 * @var array $blogPosts
 */
$baseUrl = 'https://usekeeup.com';
$now = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Core pages -->
    <url><loc><?= $baseUrl ?>/</loc><lastmod><?= $now ?></lastmod><changefreq>weekly</changefreq><priority>1.0</priority></url>
    <url><loc><?= $baseUrl ?>/about</loc><lastmod><?= $now ?></lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>
    <url><loc><?= $baseUrl ?>/changelog</loc><lastmod><?= $now ?></lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>
    <url><loc><?= $baseUrl ?>/blog</loc><lastmod><?= $now ?></lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>
    <url><loc><?= $baseUrl ?>/privacy</loc><lastmod><?= $now ?></lastmod><changefreq>yearly</changefreq><priority>0.3</priority></url>
    <url><loc><?= $baseUrl ?>/terms</loc><lastmod><?= $now ?></lastmod><changefreq>yearly</changefreq><priority>0.3</priority></url>

    <!-- Feature pages -->
    <url><loc><?= $baseUrl ?>/features/status-page</loc><lastmod><?= $now ?></lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>
    <url><loc><?= $baseUrl ?>/features/alerting</loc><lastmod><?= $now ?></lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>

    <!-- Use case pages -->
    <url><loc><?= $baseUrl ?>/use-cases/saas</loc><lastmod><?= $now ?></lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>
    <url><loc><?= $baseUrl ?>/use-cases/isp</loc><lastmod><?= $now ?></lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>

    <!-- Alternatives pages -->
    <url><loc><?= $baseUrl ?>/alternatives/uptimerobot</loc><lastmod><?= $now ?></lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>
    <url><loc><?= $baseUrl ?>/alternatives/pingdom</loc><lastmod><?= $now ?></lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>
    <url><loc><?= $baseUrl ?>/alternatives/statuspage-io</loc><lastmod><?= $now ?></lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>

    <!-- Portuguese pages -->
    <url><loc><?= $baseUrl ?>/pt/monitoramento</loc><lastmod><?= $now ?></lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>
    <url><loc><?= $baseUrl ?>/pt/para-provedores</loc><lastmod><?= $now ?></lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>

    <!-- Blog posts -->
    <?php foreach ($blogPosts as $post): ?>
    <url>
        <loc><?= $baseUrl ?><?= h($post->url) ?></loc>
        <lastmod><?= $post->modified ? $post->modified->format('Y-m-d') : $now ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>
</urlset>
