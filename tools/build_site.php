<?php

/*
 * Generates the static site in dist/ from data/ and assets/.
 *
 *     php tools/build_site.php
 */

require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/render.php';
require __DIR__ . '/../includes/pages.php';

const DIST_DIR = __DIR__ . '/../dist';

// A warning would otherwise end up inside a published page.
set_error_handler(function ($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});

function remove_tree($path)
{
    if (!is_dir($path)) {
        return;
    }
    foreach (scandir($path) as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $child = $path . '/' . $name;
        is_dir($child) ? remove_tree($child) : unlink($child);
    }
    rmdir($path);
}

function copy_tree($from, $to)
{
    mkdir($to);
    foreach (scandir($from) as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        is_dir($from . '/' . $name)
            ? copy_tree($from . '/' . $name, $to . '/' . $name)
            : copy($from . '/' . $name, $to . '/' . $name);
    }
}

function write_page($file, $render, ...$arguments)
{
    ob_start();
    $render(...$arguments);
    file_put_contents(DIST_DIR . '/' . $file, ob_get_clean());
}

$site = load_site();

remove_tree(DIST_DIR);
mkdir(DIST_DIR);
copy_tree(__DIR__ . '/../assets', DIST_DIR . '/assets');

write_page(home_url(false), 'render_home_page', $site, false);
write_page(home_url(true), 'render_home_page', $site, true);
write_page('404.html', 'render_not_found_page', $site);
write_page(recent_url(), 'render_recent_page', $site);
write_page(hall_of_fame_url(), 'render_hall_of_fame_page', $site);

$pages = 5;
foreach (array_keys($site['fights']) as $sheet) {
    write_page(fight_url($sheet), 'render_gallery_page', $site, $sheet);
    $pages++;
    foreach (open_blocks($site, $sheet) as $block) {
        write_page(fight_url($sheet, $block['position']), 'render_block_page', $site, $sheet, $block);
        $pages++;
    }
}

foreach (keeper_names($site) as $username) {
    write_page(keeper_url($username), 'render_keeper_page', $site, $username);
    $pages++;
}

echo "dist: $pages pages\n";
