<?php
/**
 * Reusable blog CTA element — shown at the end of every blog post.
 *
 * Usage: <?= $this->element('blog/cta') ?>
 * Or with custom text: <?= $this->element('blog/cta', ['heading' => '...', 'subtext' => '...']) ?>
 */
$brand = \Cake\Core\Configure::read('Brand.name', 'KeepUp');
$heading = $heading ?? "Start monitoring with {$brand} for free";
$subtext = $subtext ?? "Set up your first monitor in 60 seconds. No credit card required. Free plan includes 5 monitors, status page, and email alerts.";
$btnText = $btnText ?? 'Create Free Account';
$btnLink = $btnLink ?? '/app/register';
?>

<div class="blog-cta-box">
    <div class="blog-cta-inner">
        <div class="blog-cta-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
        </div>
        <h3 class="blog-cta-heading"><?= h($heading) ?></h3>
        <p class="blog-cta-subtext"><?= h($subtext) ?></p>
        <a href="<?= h($btnLink) ?>" class="blog-cta-btn"><?= h($btnText) ?></a>
        <div class="blog-cta-features">
            <span>&#10003; 5 free monitors</span>
            <span>&#10003; Status page included</span>
            <span>&#10003; Email & Slack alerts</span>
            <span>&#10003; No credit card</span>
        </div>
    </div>
</div>

<style>
.blog-cta-box {
    margin: 48px 0 24px;
    padding: 0;
}
.blog-cta-inner {
    background: linear-gradient(135deg, #1A2332 0%, #0F1923 100%);
    border-radius: 16px;
    padding: 48px 32px;
    text-align: center;
    color: #E8EDF2;
}
.blog-cta-icon {
    color: #2979FF;
    margin-bottom: 16px;
}
.blog-cta-heading {
    font-family: 'DM Sans', sans-serif;
    font-size: 1.6rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 12px;
    line-height: 1.3;
}
.blog-cta-subtext {
    font-size: 1rem;
    color: #9CA3AF;
    margin: 0 auto 24px;
    max-width: 500px;
    line-height: 1.6;
}
.blog-cta-btn {
    display: inline-block;
    background: #2979FF;
    color: #fff;
    padding: 14px 36px;
    border-radius: 8px;
    font-size: 1.05rem;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s;
}
.blog-cta-btn:hover {
    background: #2962FF;
    color: #fff;
}
.blog-cta-features {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 20px;
    font-size: 0.85rem;
    color: #6B7280;
}
.blog-cta-features span {
    white-space: nowrap;
}
@media (max-width: 640px) {
    .blog-cta-inner { padding: 32px 20px; }
    .blog-cta-heading { font-size: 1.3rem; }
    .blog-cta-features { flex-direction: column; gap: 8px; }
}
</style>
