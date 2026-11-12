<?php

function render_home_page($site, $hardMode)
{
    $submissions = array_sum(array_map('count', $site['records']));
    page_start(
        SITE_NAME,
        $site,
        "Fastest clears of FFRK's toughest fights from the FFRecordKeeper Discord: $submissions records, with teams and character usage rates.",
        SITE_BANNER
    );
    render_sidebar($site);
    ?>
<div class="home">
<header class="hero">
    <img class="site-banner" src="assets/images/ffrk_cast_banner.webp" alt="" width="1600" height="400">
    <h1>FFRK Community Leaderboards</h1>
    <p>FFRecordKeeper Discord</p>
    <p>Thanks to the collaboration of our Keepers we have received <?= $submissions ?> submissions!</p>
    <p>Good luck and keep it going!</p>
</header>
<input class="mode-input" type="radio" name="mode" id="mode-normal"<?= $hardMode ? '' : ' checked' ?>>
<input class="mode-input" type="radio" name="mode" id="mode-hard"<?= $hardMode ? ' checked' : '' ?>>
<div class="mode-toggle">
    <label for="mode-normal">Normal</label>
    <label for="mode-hard">Hard Mode</label>
</div>
<div class="sections">
    <?php foreach (home_sections($site) as $title => $sheets): ?>
        <section class="section">
            <h2><?= h($title) ?></h2>
            <?php foreach ($sheets as $sheet): ?>
                <?php
                list($normalBanner, $hardBanner) = fight_banners($site, $sheet);
                render_fight_card($site, $sheet, $normalBanner, 'card-normal');
                render_fight_card($site, $sheet . '-HM', $hardBanner, 'card-hard');
                ?>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
</div>
</div>
    <?php
    page_end();
}

function render_gallery_page($site, $sheet)
{
    $fight = $site['fights'][$sheet];
    $groups = gallery_groups($site['blocks'][$sheet]);
    page_start(
        $sheet,
        $site,
        $fight['subtitle'] . ' · ' . fight_record_count($site, $sheet) . ' records',
        banner_preview(fight_preview_banner($site, $sheet)),
        $sheet
    );
    ?>
<header class="fight-head">
    <div class="fight-title">
        <h1><?= h($sheet) ?></h1>
        <?php render_mode_links($site, $sheet); ?>
    </div>
    <p><?= h($fight['subtitle']) ?> · <?= fight_record_count($site, $sheet) ?> records</p>
</header>
<?php if (isset($groups[''])): ?>
    <?php render_gallery($site, $sheet, $groups['']); ?>
<?php else: ?>
    <div class="sections sections-two">
        <?php foreach ($groups as $title => $blocks): ?>
            <section class="section">
                <h2><?= h($title) ?></h2>
                <?php render_gallery($site, $sheet, $blocks); ?>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
    <?php
    page_end();
}

function render_block_page($site, $sheet, $block)
{
    $ranking = build_ranking($site, $site['records'][block_key($block)] ?? []);
    $description = block_record_count($site, $block) . ' records';
    if ($ranking) {
        $best = $ranking[0];
        $description = 'Fastest: ' . $best['username'] . ' in ' . format_seconds($best['seconds'])
            . ' · ' . $description;
    }
    page_start(
        $sheet . ' ' . block_name($block),
        $site,
        $description,
        banner_preview($block['banner']),
        $sheet,
        $block['position']
    );
    ?>
<header class="fight-head fight-head-inline">
    <p class="breadcrumb"><a href="<?= h(fight_url($sheet)) ?>">‹ <?= h($sheet) ?></a></p>
    <h1><?= h(block_title($block)) ?></h1>
    <p><?= block_record_count($site, $block) ?> records</p>
    <?php render_mode_links($site, $sheet, $block['position']); ?>
</header>
<div class="block-page">
    <?php render_block($site, $block); ?>
</div>
    <?php
    page_end();
}

function render_keeper_page($site, $username)
{
    $entries = keeper_entries($site, $username);
    $medals = array_count_values(array_column($entries, 'rank'));
    $podiums = [];
    foreach (MEDALS as $rank => $name) {
        $podiums[] = ($medals[$rank] ?? 0) . ' ' . strtolower($name);
    }
    page_start(
        $username,
        $site,
        count($entries) . ' records · ' . implode(', ', $podiums),
        SITE_BANNER,
        ''
    );
    ?>
<div class="keeper-page">
    <header class="fight-head">
        <h1><?= h($username) ?> <?php render_badges($site, $entries[0]['badges']); ?></h1>
        <p><?= count($entries) ?> records</p>
        <?php render_podiums($site, $entries); ?>
    </header>
    <?php render_keeper_filters($entries); ?>
    <?php render_keeper_entries($site, $entries); ?>
</div>
<script src="assets/keeper-filters.js" defer></script>
    <?php
    page_end();
}

function render_recent_page($site)
{
    page_start('Recent Records', $site, 'The last ' . RECENT_RECORDS . ' records submitted to the FFRecordKeeper Discord.', SITE_BANNER, '');
    ?>
<div class="recent-page">
    <header class="fight-head">
        <h1>Recent Records</h1>
        <p>The last <?= RECENT_RECORDS ?> submitted records</p>
    </header>
    <?php render_recent_entries($site, recent_entries($site)); ?>
</div>
    <?php
    page_end();
}

function render_hall_of_fame_page($site)
{
    $keepers = hall_of_fame($site);
    $leaders = array_column(array_filter($keepers, function ($keeper) {
        return $keeper['rank'] === 1;
    }), 'username');
    page_start(
        "Keepers' Hall of Fame",
        $site,
        'The keepers with the most medals: ' . implode(' and ', $leaders)
            . (count($leaders) > 1 ? ' lead' : ' leads') . ' with ' . $keepers[0]['points'] . ' points.',
        SITE_BANNER,
        ''
    );
    ?>
<div class="keeper-page">
    <header class="fight-head">
        <h1>Keepers' Hall of Fame</h1>
        <p>Out of all our brave Keepers, we present the ones who have accumulated the most accolades</p>
    </header>
    <?php render_hall_of_fame($site, $keepers); ?>
</div>
    <?php
    page_end();
}

function render_not_found_page($site)
{
    // GitHub Pages serves this page at any missing path, /a/b.html too: '/' keeps its links working there.
    page_start('Page not found', $site, 'This page does not exist.', SITE_BANNER, '', null, '/');
    echo '<header class="hero"><h1>Page not found</h1></header>';
    page_end();
}
