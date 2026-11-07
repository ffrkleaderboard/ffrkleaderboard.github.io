<?php

function page_start($title, $site, $current = null, $currentBlock = null)
{
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title><?= h($title) ?></title>
    <link rel="icon" type="image/png" href="assets/images/historia_icon_superskill_200001_running_128.png">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php if ($current !== null): ?>
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
    <p>Graphics: Vandalhart · Leaderboards: jebeebus · Data updated <?= h($updated) ?></p>
    <p><a href="<?= h(SHEET_URL) ?>" target="_blank" rel="noopener">FFRecordKeeper Discord: Community Leaderboards</a></p>
</footer>
</body>
</html>
    <?php
}

function render_fight_card($site, $sheet, $banner, $modeClass)
{
    ?>
    <a class="fight-card <?= h($modeClass) ?>" href="<?= h(fight_url($sheet)) ?>">
        <?php if ($banner !== ''): ?>
            <img src="assets/banners/<?= h($banner) ?>" alt="" width="576" height="144" loading="lazy">
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
        <img src="assets/banners/<?= h($block['banner']) ?>" alt="" width="576" height="144" loading="lazy">
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
            <tr<?= $entry['rank'] <= 3 ? ' class="top"' : '' ?> style="background:<?= h($entry['block']['row_odd']) ?>">
                <td class="rank"><?= render_rank($site, $entry['rank']) ?></td>
                <td class="name">
                    <a href="<?= h(fight_url($entry['sheet'], $entry['block']['position'])) ?>"><?= h(block_full_name($entry['block'])) ?></a>
                    <?php if ($entry['video'] !== ''): ?>
                        <a href="<?= h($entry['video']) ?>" target="_blank" rel="noopener"><?php render_video_badge($site); ?></a>
                    <?php endif; ?>
                </td>
                <td class="time"><?= number_format($entry['seconds'], 2, '.', '') ?>s</td>
                <td class="team"><?php render_team($entry['team']); ?></td>
                <td class="date"><?= $entry['date'] === '' ? '' : '⏴' . h($entry['date']) ?></td>
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
                <td class="time"><?= number_format($entry['seconds'], 2, '.', '') ?>s</td>
                <td class="team"><?php render_team($entry['team']); ?></td>
                <?php if ($hasDate): ?><td class="date"><?= $entry['date'] === '' ? '' : '⏴' . h($entry['date']) ?></td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
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
                <img class="banner" src="assets/banners/<?= h($block['banner']) ?>" alt="<?= h(block_title($block)) ?>" width="576" height="144" loading="lazy">
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
