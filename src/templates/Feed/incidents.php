<?php
/**
 * RSS 2.0 Feed — Incidents
 *
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $incidents
 * @var string $siteName
 * @var string $siteUrl
 */
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title><?= h($siteName) ?> — Incidents</title>
        <link><?= h($siteUrl) ?>/status</link>
        <description>Recent incidents for <?= h($siteName) ?></description>
        <language>en</language>
        <lastBuildDate><?= date('r') ?></lastBuildDate>
        <atom:link href="<?= h($siteUrl) ?>/feed/incidents.rss" rel="self" type="application/rss+xml"/>
        <?php foreach ($incidents as $incident): ?>
            <item>
                <title><?= h($incident->title) ?></title>
                <description><![CDATA[
                    <p><strong>Status:</strong> <?= h($incident->getStatusName()) ?></p>
                    <p><strong>Severity:</strong> <?= h(ucfirst($incident->severity)) ?></p>
                    <?php if ($incident->monitor): ?>
                        <p><strong>Monitor:</strong> <?= h($incident->monitor->name) ?></p>
                    <?php endif; ?>
                    <?php if ($incident->description): ?>
                        <p><?= h($incident->description) ?></p>
                    <?php endif; ?>
                ]]></description>
                <pubDate><?= $incident->created->format('r') ?></pubDate>
                <guid isPermaLink="false">incident-<?= $incident->id ?></guid>
                <link><?= h($siteUrl) ?>/incidents/view/<?= $incident->id ?></link>
            </item>
        <?php endforeach; ?>
    </channel>
</rss>
