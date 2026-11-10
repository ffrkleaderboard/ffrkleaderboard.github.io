<?php

/**
 * Opens a page titled $name, followed by the site's name. $description and $image
 * (from banner_preview) fill the preview that Discord and other apps show for a
 * shared link. $current is the sheet whose tab is highlighted, or '' for none; null
 * leaves out the tab bar, as on the home page, whose dropdown has the keeper search.
 */
function page_start($name, $site, $description, $image, $current = null, $currentBlock = null)
{
    $title = $name === SITE_NAME ? $name : $name . ' · ' . SITE_NAME;
    list($imagePath, $imageWidth, $imageHeight) = $image;
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title><?= h($title) ?></title>
    <meta name="description" content="<?= h($description) ?>">
    <meta name="theme-color" content="#10183f">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= h(SITE_NAME) ?>">
    <meta property="og:title" content="<?= h($name) ?>">
    <meta property="og:description" content="<?= h($description) ?>">
    <meta property="og:image" content="<?= h(SITE_URL . $imagePath) ?>">
    <meta property="og:image:width" content="<?= h($imageWidth) ?>">
    <meta property="og:image:height" content="<?= h($imageHeight) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" type="image/png" href="assets/images/historia_icon_superskill_200001_running_128.png">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php render_backdrop(); ?>
<?php if ($current !== null): ?>
<header class="topbar">
<nav class="tabs" aria-label="Fights">
    <a class="tab tab-home" href="<?= h(home_url(is_hard_mode((string) $current))) ?>">Home</a>
    <?php foreach ($site['fights'] as $sheet => $fight): ?>
        <div class="tab-item">
            <a class="tab<?= $sheet === $current ? ' tab-current' : '' ?>" href="<?= h(fight_url($sheet)) ?>"><?= h($sheet) ?></a>
            <ul class="tab-menu">
                <?php foreach (open_blocks($site, $sheet) as $block): ?>
                    <li><a href="<?= h(fight_url($sheet, $block['position'])) ?>"<?= $sheet === $current && $block['position'] === $currentBlock ? ' aria-current="page"' : '' ?>><?= h(block_name($block)) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</nav>
<?php render_keeper_search('topbar-search'); ?>
</header>
<?php endif; ?>
<main>
    <?php
}

function page_end()
{
    $updated = date('Y-m-d');
    ?>
</main>
<footer class="footer">
    <p>Graphics: Vandalhart · Leaderboards: jebeebus · Website: zor · Data updated <?= h($updated) ?></p>
    <p>
        <a href="<?= h(SHEET_URL) ?>" target="_blank" rel="noopener">FFRecordKeeper Discord: Community Leaderboards</a>
        ·
        <a href="<?= h(DATABASE_URL) ?>" target="_blank" rel="noopener">FFRK Community Database</a>
    </p>
</footer>
</body>
</html>
    <?php
}

/**
 * Background of the page: BACKGROUNDS whole, side by side, on a strip that drifts to
 * the left without stopping. The strip holds BACKGROUND_COPIES copies of them and
 * moves by one copy per cycle, so the restart lands on an identical picture.
 *
 * Phones (PHONE_MEDIA) get one still background instead, picked at random on every
 * page load by the script below. The strip's images point to an empty pixel there,
 * so a phone downloads only the one it shows.
 */
function render_backdrop()
{
    $duration = BACKGROUND_SECONDS * count(BACKGROUNDS);
    $images = array_map(function ($image) {
        return 'assets/images/' . $image;
    }, BACKGROUNDS);
    ?>
<div class="backdrop" aria-hidden="true">
    <div class="backdrop-track" style="animation-duration:<?= $duration ?>s">
        <?php for ($copy = 0; $copy < BACKGROUND_COPIES; $copy++): ?>
            <?php foreach ($images as $image): ?>
                <picture>
                    <source media="<?= h(PHONE_MEDIA) ?>" srcset="<?= EMPTY_PIXEL ?>">
                    <img src="<?= h($image) ?>" alt="" width="640" height="1136" decoding="async">
                </picture>
            <?php endforeach; ?>
        <?php endfor; ?>
    </div>
    <script>
        (function () {
            if (!matchMedia(<?= json_encode(PHONE_MEDIA) ?>).matches) return;
            var images = <?= json_encode($images, JSON_UNESCAPED_SLASHES) ?>;
            var img = document.createElement('img');
            img.className = 'backdrop-still';
            img.alt = '';
            img.src = images[Math.floor(Math.random() * images.length)];
            document.currentScript.before(img);
        })();
    </script>
</div>
    <?php
}

/** A tab of the dropdown, unfolding into its fights. Opening one closes the others. */
function render_sidebar_fight($site, $sheet)
{
    ?>
    <li>
        <details name="sidebar-fight">
            <summary><?= h($sheet) ?></summary>
            <ul class="sidebar-blocks">
                <li><a href="<?= h(fight_url($sheet)) ?>">All <?= h($sheet) ?></a></li>
                <?php foreach (open_blocks($site, $sheet) as $block): ?>
                    <li><a href="<?= h(fight_url($sheet, $block['position'])) ?>"><?= h(block_name($block)) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
    </li>
    <?php
}

/** Search box that lists the keepers whose name contains what is typed, from keeper_index_url(). */
function render_keeper_search($class)
{
    ?>
<div class="keeper-finder <?= h($class) ?>" role="search" data-index="<?= h(keeper_index_url()) ?>">
    <input class="keeper-search" type="search" placeholder="Search keeper" aria-label="Search keeper" autocomplete="off" spellcheck="false">
    <ul class="keeper-results" hidden></ul>
</div>
<script src="assets/keeper-search.js" defer></script>
    <?php
}

/** Dropdown of the home page: keeper search, recent records and every fight. */
function render_sidebar($site)
{
    ?>
<details class="sidebar">
    <summary>☰ Menu</summary>
    <div class="sidebar-panel">
        <?php render_keeper_search('sidebar-search'); ?>
        <a class="sidebar-button" href="<?= h(recent_url()) ?>">Recent Records</a>
        <a class="sidebar-button" href="<?= h(hall_of_fame_url()) ?>">Keepers' Hall of Fame</a>
        <?php foreach (home_sections($site) as $title => $sheets): ?>
            <p class="sidebar-heading"><?= h($title) ?></p>
            <ul class="sidebar-fights">
                <?php foreach ($sheets as $sheet): ?>
                    <?php render_sidebar_fight($site, $sheet); ?>
                    <?php render_sidebar_fight($site, $sheet . '-HM'); ?>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </div>
</details>
<script src="assets/sidebar.js" defer></script>
    <?php
}

function render_fight_card($site, $sheet, $banner, $modeClass)
{
    ?>
    <a class="fight-card <?= h($modeClass) ?>" href="<?= h(fight_url($sheet)) ?>">
        <?php if ($banner !== ''): ?>
            <img src="<?= h($banner) ?>" alt="" width="576" height="144" loading="lazy">
        <?php endif; ?>
        <span class="fight-caption">
            <span class="fight-name"><?= h($sheet) ?></span>
            <span class="fight-count"><?= fight_record_count($site, $sheet) ?> records</span>
        </span>
        <span class="fight-subtitle"><?= h($site['fights'][$sheet]['subtitle']) ?></span>
    </a>
    <?php
}

function render_block_card($site, $sheet, $block)
{
    ?>
    <a class="fight-card" href="<?= h(fight_url($sheet, $block['position'])) ?>">
        <img src="<?= h(BLOCK_BANNER_DIR . $block['banner']) ?>" alt="" width="576" height="144" loading="lazy">
        <span class="fight-caption">
            <span class="fight-name"><?= h(block_name($block)) ?></span>
            <span class="fight-count"><?= block_record_count($site, $block) ?> records</span>
        </span>
    </a>
    <?php
}

/** Normal | Hard Mode toggle of a fight gallery: each mode is its own page. */
function render_mode_links($sheet)
{
    $normal = is_hard_mode($sheet) ? substr($sheet, 0, -3) : $sheet;
    $hard = $normal . '-HM';
    ?>
    <nav class="mode-toggle" aria-label="Difficulty">
        <a href="<?= h(fight_url($normal)) ?>"<?= $sheet === $normal ? ' aria-current="page"' : '' ?>>Normal</a>
        <a class="mode-hard" href="<?= h(fight_url($hard)) ?>"<?= $sheet === $hard ? ' aria-current="page"' : '' ?>>Hard Mode</a>
    </nav>
    <?php
}

function render_gallery($site, $sheet, $blocks)
{
    ?>
    <div class="gallery">
        <?php foreach ($blocks as $block): ?>
            <?php if ($block['banner'] !== ''): ?>
                <?php render_block_card($site, $sheet, $block); ?>
            <?php elseif (block_record_count($site, $block) > 0): ?>
                <a class="gallery-pending" style="color:<?= h($block['label_color']) ?>" href="<?= h(fight_url($sheet, $block['position'])) ?>"><?= h($block['label']) ?><em><?= block_record_count($site, $block) ?> records</em></a>
            <?php else: ?>
                <p class="gallery-pending" style="color:<?= h($block['label_color']) ?>"><?= h($block['label']) ?><em>Unreleased</em></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php
}

function block_title($block)
{
    return trim($block['boss'] . ' ' . $block['variant']);
}

function render_rank($site, $rank)
{
    if ($rank <= 3 && isset($site['icons'][$rank])) {
        return '<img class="medal" src="' . h($site['icons'][$rank]) . '" alt="' . $rank . '" width="30" height="30">';
    }
    return h($rank);
}

function render_badges($site, $badges)
{
    foreach ($badges as $badge) {
        if (isset($site['icons'][$badge])) {
            echo '<img class="badge" src="' . h($site['icons'][$badge]) . '" alt="' . h($badge) . '" title="' . h($badge) . '" width="16" height="16" loading="lazy"> ';
        }
    }
}

function render_video_badge($site)
{
    if (isset($site['icons']['YT'])) {
        echo '<img class="badge" src="' . h($site['icons']['YT']) . '" alt="Video" title="Video" width="16" height="16" loading="lazy">';
    }
}

function render_team($team)
{
    foreach ($team as $character) {
        echo '<img src="' . h($character['sprite']) . '" alt="' . h($character['name']) . '" title="' . h($character['name']) . '" width="24" height="24" loading="lazy">';
    }
}

function render_podiums($site, $entries)
{
    $counts = array_count_values(array_column($entries, 'rank'));
    ?>
    <ul class="podiums" aria-label="Podiums">
        <?php foreach (MEDALS as $rank => $name): ?>
            <li><?= render_rank($site, $rank) ?><strong><?= $counts[$rank] ?? 0 ?></strong> <?= h($name) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

/**
 * Buttons that narrow a keeper's records down by kind of fight, difficulty and podium
 * finishes. Hidden until keeper-filters.js runs, since they need it to work.
 */
function render_keeper_filters($entries)
{
    $choices = filter_choices($entries);
    $podiums = count(array_filter(array_column($entries, 'rank'), function ($rank) {
        return $rank <= count(MEDALS);
    }));
    $hasPodiumFilter = $podiums > 0 && $podiums < count($entries);
    if (!$choices && !$hasPodiumFilter) {
        return;
    }
    ?>
    <div class="record-filters" hidden>
        <?php foreach ($choices as $filter => $counts): ?>
            <div class="filter-group" role="group" aria-label="<?= h(FILTER_NAMES[$filter]) ?>">
                <button type="button" data-filter="<?= h($filter) ?>" data-value="" aria-pressed="true">All</button>
                <?php foreach ($counts as $value => $count): ?>
                    <button type="button" data-filter="<?= h($filter) ?>" data-value="<?= h($value) ?>" aria-pressed="false"><?= h($value) ?> <span><?= $count ?></span></button>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($hasPodiumFilter): ?>
            <div class="filter-group">
                <button type="button" data-filter="podium" aria-pressed="false">Podiums only <span><?= $podiums ?></span></button>
            </div>
        <?php endif; ?>
        <p class="filter-count" aria-live="polite"></p>
    </div>
    <?php
}

function render_keeper_entries($site, $entries)
{
    ?>
    <table class="ranking keeper-records">
        <thead>
            <tr>
                <th scope="col">RANK</th>
                <th scope="col" class="name">FIGHT</th>
                <th scope="col">TIME</th>
                <th scope="col">TEAM</th>
                <th scope="col" class="date">DATE</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $entry): ?>
            <?php $filters = entry_filters($entry); ?>
            <tr<?= $entry['rank'] <= 3 ? ' class="top"' : '' ?> style="background:<?= h($entry['block']['row_odd']) ?>" data-type="<?= h($filters['type']) ?>" data-mode="<?= h($filters['mode']) ?>" data-podium="<?= $entry['rank'] <= count(MEDALS) ? 1 : 0 ?>">
                <td class="rank"><?= render_rank($site, $entry['rank']) ?></td>
                <td class="name">
                    <a href="<?= h(fight_url($entry['sheet'], $entry['block']['position'])) ?>"><?= h(block_full_name($entry['block'])) ?></a>
                    <?php if ($entry['video'] !== ''): ?>
                        <a href="<?= h($entry['video']) ?>" target="_blank" rel="noopener"><?php render_video_badge($site); ?></a>
                    <?php endif; ?>
                </td>
                <td class="time"><?= format_seconds($entry['seconds']) ?></td>
                <td class="team"><?php render_team($entry['team']); ?></td>
                <td class="date"><?= $entry['date'] === '' ? '' : '⏴' . h($entry['date']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="empty filter-empty" hidden>No records match these filters</p>
    <?php
}

function render_recent_entries($site, $entries)
{
    ?>
    <table class="ranking keeper-records recent-records">
        <thead>
            <tr>
                <th scope="col">SUBMITTED (UTC)</th>
                <th scope="col" class="name">RECORD KEEPER</th>
                <th scope="col" class="name">FIGHT</th>
                <th scope="col">STATUS</th>
                <th scope="col">RANK</th>
                <th scope="col">TIME</th>
                <th scope="col">TEAM</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $entry): ?>
            <tr<?= $entry['rank'] <= 3 ? ' class="top"' : '' ?> style="background:<?= h($entry['block']['row_odd']) ?>">
                <td class="submitted"><?= h(substr($entry['submitted_on'], 0, 10)) ?> <?= h(substr($entry['submitted_on'], 12, 5)) ?></td>
                <td class="name">
                    <a href="<?= h(keeper_url($entry['username'])) ?>"><?= h($entry['username']) ?></a>
                    <?php render_badges($site, $entry['badges']); ?>
                </td>
                <td class="name fight">
                    <a href="<?= h(fight_url($entry['sheet'], $entry['block']['position'])) ?>"><?= h(block_full_name($entry['block'])) ?></a>
                    <?php if ($entry['video'] !== ''): ?>
                        <a href="<?= h($entry['video']) ?>" target="_blank" rel="noopener"><?php render_video_badge($site); ?></a>
                    <?php endif; ?>
                </td>
                <td class="status">
                    <?php if ($entry['status'] !== ''): ?>
                        <span class="status-<?= h($entry['status']) ?>"><?= h(RECORD_STATUS[$entry['status']]) ?></span>
                    <?php endif; ?>
                </td>
                <td class="rank"><?= render_rank($site, $entry['rank']) ?></td>
                <td class="time"><?= format_seconds($entry['seconds']) ?></td>
                <td class="team"><?php render_team($entry['team']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function render_hall_of_fame($site, $keepers)
{
    ?>
    <table class="ranking hall-of-fame">
        <thead>
            <tr>
                <th scope="col">RANK</th>
                <th scope="col" class="name">RECORD KEEPER</th>
                <th scope="col">RECORDS</th>
                <?php foreach (MEDALS as $name): ?>
                    <th scope="col"><?= h(strtoupper($name)) ?></th>
                <?php endforeach; ?>
                <th scope="col">POINTS</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($keepers as $keeper): ?>
            <?php $rank = $keeper['rank']; ?>
            <tr<?= isset($keeper['medals'][$rank]) ? ' class="top place-' . $rank . '"' : '' ?>>
                <td class="rank"><?= render_rank($site, $rank) ?></td>
                <td class="name">
                    <a href="<?= h(keeper_url($keeper['username'])) ?>"><?= h($keeper['username']) ?></a>
                    <?php render_badges($site, $keeper['badges']); ?>
                </td>
                <td class="record-count"><?= $keeper['records'] ?></td>
                <?php foreach ($keeper['medals'] as $count): ?>
                    <td class="medal-count"><?= $count ?></td>
                <?php endforeach; ?>
                <td class="points"><?= $keeper['points'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function render_ranking($site, $block, $ranking)
{
    $hasDate = $block['has_date'] === '1';
    ?>
    <table class="ranking">
        <thead>
            <tr>
                <th scope="col">RANK</th>
                <th scope="col" class="name">RECORD KEEPER</th>
                <th scope="col">TIME</th>
                <th scope="col">TEAM</th>
                <?php if ($hasDate): ?><th scope="col" class="date">DATE</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($ranking as $entry): ?>
            <tr<?= $entry['rank'] <= 3 ? ' class="top"' : '' ?>>
                <td class="rank"><?= render_rank($site, $entry['rank']) ?></td>
                <td class="name">
                    <span class="keeper" tabindex="0">
                        <?php if ($entry['video'] !== ''): ?>
                            <a href="<?= h($entry['video']) ?>" target="_blank" rel="noopener"><?= h($entry['username']) ?></a>
                        <?php else: ?>
                            <?= h($entry['username']) ?>
                        <?php endif; ?>
                        <a class="keeper-link" href="<?= h(keeper_url($entry['username'])) ?>">Go to <?= h($entry['username']) ?>'s records</a>
                    </span>
                    <?php render_badges($site, $entry['badges']); ?>
                    <?php if ($entry['video'] !== ''): ?>
                        <?php render_video_badge($site); ?>
                    <?php endif; ?>
                </td>
                <td class="time"><?= format_seconds($entry['seconds']) ?></td>
                <td class="team"><?php render_team($entry['team']); ?></td>
                <?php if ($hasDate): ?><td class="date"><?= $entry['date'] === '' ? '' : '⏴' . h($entry['date']) ?></td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="row" colspan="2" class="name">AVERAGE CLEAR TIME</th>
                <td class="time"><?= format_seconds(average_seconds($ranking)) ?></td>
                <td></td>
                <?php if ($hasDate): ?><td class="date"></td><?php endif; ?>
            </tr>
        </tfoot>
    </table>
    <?php
}

function render_usage($site, $ranking)
{
    $target = (int) floor($ranking[0]['seconds']);
    ?>
    <table class="usage">
        <thead>
            <tr>
                <th scope="col" class="count">▼</th>
                <th scope="col" class="char">CHARACTER USAGE RATE</th>
                <th scope="col" class="icon">
                    <?php if (isset($site['icons']['VAJ'])): ?>
                        <img src="<?= h($site['icons']['VAJ']) ?>" alt="" width="36" height="24" loading="lazy">
                    <?php endif; ?>
                </th>
                <th scope="col" class="quote">"Why not sub<?= $target ?>?"</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach (build_usage($ranking) as $row): ?>
            <tr>
                <td class="count"><?= h($row['count']) ?>x</td>
                <td class="char"><?= h($row['name']) ?></td>
                <td class="icon"><img src="<?= h($row['icon']) ?>" alt="" width="28" height="28" loading="lazy"></td>
                <td class="rate">
                    <span class="bar"><span style="width:<?= h($row['percent']) ?>%"></span></span>
                    <span class="percent"><?= h($row['percent']) ?>%</span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function render_block($site, $block)
{
    $ranking = build_ranking($site, $site['records'][block_key($block)] ?? []);
    $style = '--head:' . $block['head'] . ';--head-text:' . $block['head_text']
        . ';--row-odd:' . $block['row_odd'] . ';--row-even:' . $block['row_even']
        . ';--label:' . $block['label_color'];
    ?>
    <section class="block" style="<?= h($style) ?>" aria-label="<?= h(block_title($block)) ?>">
        <header class="block-head">
            <?php if ($block['banner'] !== ''): ?>
                <img class="banner" src="<?= h(BLOCK_BANNER_DIR . $block['banner']) ?>" alt="<?= h(block_title($block)) ?>" width="576" height="144" loading="lazy">
            <?php else: ?>
                <h2 class="block-label"><?= h($block['label'] !== '' ? $block['label'] : block_title($block)) ?></h2>
            <?php endif; ?>
            <?php if ($block['date_label'] !== ''): ?>
                <p class="release"><?= h($block['date_label']) ?></p>
            <?php endif; ?>
        </header>
        <?php if ($ranking): ?>
            <div class="block-columns">
                <div><?php render_ranking($site, $block, $ranking); ?></div>
                <div><?php render_usage($site, $ranking); ?></div>
            </div>
        <?php else: ?>
            <p class="empty">No records yet</p>
        <?php endif; ?>
    </section>
    <?php
}
