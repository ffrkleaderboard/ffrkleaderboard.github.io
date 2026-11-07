<?php

function render_home_page($site, $hardMode)
{
    page_start('FFRK Community Leaderboards', $site);
    ?>
<div class="home">
<header class="hero">
    <img class="site-banner" src="assets/images/ffrk_cast_banner.webp" alt="" width="1600" height="400">
    <h1>FFRK Community Leaderboards</h1>
    <p>FFRecordKeeper Discord</p>
    <p>Thanks to the collaboration of our Community, we have <?= array_sum(array_map('count', $site['records'])) ?> records submitted!</p>
    <p>Good luck and keep it going Keepers!</p>
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
    page_start($sheet . ' · FFRK Community Leaderboards', $site, $sheet);
    ?>
<header class="fight-head">
    <div class="fight-title">
        <h1><?= h($sheet) ?></h1>
        <?php render_mode_links($sheet); ?>
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
    page_start($sheet . ' ' . block_name($block) . ' · FFRK Community Leaderboards', $site, $sheet, $block['position']);
    ?>
<header class="fight-head fight-head-inline">
    <p class="breadcrumb"><a href="<?= h(fight_url($sheet)) ?>">‹ <?= h($sheet) ?></a></p>
    <h1><?= h(block_title($block)) ?></h1>
    <p><?= block_record_count($site, $block) ?> records</p>
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
    page_start($username . ' · FFRK Community Leaderboards', $site, '');
    ?>
<div class="keeper-page">
    <header class="fight-head">
        <h1><?= h($username) ?> <?php render_badges($site, $entries[0]['badges']); ?></h1>
        <p><?= count($entries) ?> records</p>
    </header>
    <?php render_keeper_entries($site, $entries); ?>
</div>
    <?php
    page_end();
}

function render_not_found_page($site)
{
    page_start('Page not found', $site, '');
    echo '<header class="hero"><h1>Page not found</h1></header>';
    page_end();
}
