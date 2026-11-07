<?php

const DATA_DIR = __DIR__ . '/../data';
const SHEET_URL = 'https://docs.google.com/spreadsheets/d/1VSt7_IySH7PxEQNa7h9AYlLtqpO135i1UScY3bR6kzM/edit';
const DATABASE_URL = 'https://docs.google.com/spreadsheets/d/1f8OJIQhpycljDQ8QNDk_va1GJ1u7RVoMaNjFcHH0LKk/edit?gid=938172420#gid=938172420';
const RECENT_RECORDS = 30;
const MEDALS = [1 => 'Gold', 2 => 'Silver', 3 => 'Bronze'];
const MEDAL_POINTS = [1 => 3, 2 => 2, 3 => 1];
const HALL_OF_FAME_SIZE = 15;
const RECORD_STATUS = ['new' => 'New Record!', 'updated' => 'Updated Record'];

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function read_csv($name)
{
    $handle = fopen(DATA_DIR . '/' . $name, 'r');
    if ($handle === false) {
        throw new RuntimeException('Missing data file: ' . $name);
    }
    $header = fgetcsv($handle, 0, ',', '"', '');
    $rows = [];
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
        $rows[] = array_combine($header, $row);
    }
    fclose($handle);
    return $rows;
}

function block_key($row)
{
    return $row['cd'] . '|' . $row['boss'] . '|' . $row['variant'];
}

/** Identifies a submission without its keeper, whose name moderators may correct. */
function change_key($block, $submittedOn)
{
    return block_key($block) . '|' . $submittedOn;
}

/** Loads every CSV into lookups keyed the way the pages need them. */
function load_site()
{
    $site = [
        'fights' => [],
        'blocks' => [],
        'records' => [],
        'characters' => [],
        'badges' => [],
        'icons' => [],
        'changes' => [],
    ];
    foreach (read_csv('fights.csv') as $fight) {
        $site['fights'][$fight['sheet']] = $fight;
    }
    foreach (read_csv('blocks.csv') as $block) {
        $site['blocks'][$block['sheet']][] = $block;
    }
    foreach (read_csv('records.csv') as $record) {
        $site['records'][block_key($record)][] = $record;
    }
    foreach (read_csv('record_changes.csv') as $change) {
        $site['changes'][change_key($change, $change['submitted_on'])] = $change['status'];
    }
    foreach (read_csv('characters.csv') as $character) {
        $site['characters'][$character['code']] = $character;
    }
    foreach (read_csv('badges.csv') as $badge) {
        $site['badges'][$badge['username']] = array_filter(explode(' ', $badge['badges']));
    }
    foreach (read_csv('icons.csv') as $icon) {
        $site['icons'][$icon['icon']] = $icon['url'];
    }
    return $site;
}

function fight_record_count($site, $sheet)
{
    $count = 0;
    foreach ($site['blocks'][$sheet] as $block) {
        $count += count($site['records'][block_key($block)] ?? []);
    }
    return $count;
}

function home_url($hardMode)
{
    return $hardMode ? 'hard.html' : 'index.html';
}

function fight_url($sheet, $position = null)
{
    return $sheet . ($position === null ? '' : '-' . $position) . '.html';
}

function recent_url()
{
    return 'recent.html';
}

function hall_of_fame_url()
{
    return 'hall-of-fame.html';
}

/** Short name of a block within its fight: its element, realm or job. */
function block_name($block)
{
    return $block['variant'] !== '' ? $block['variant'] : $block['boss'];
}

function block_record_count($site, $block)
{
    return count($site['records'][block_key($block)] ?? []);
}

/** Blocks that have a page. The rest are placeholders for fights yet to come. */
function open_blocks($site, $sheet)
{
    return array_filter($site['blocks'][$sheet], function ($block) use ($site) {
        return $block['banner'] !== '' || block_record_count($site, $block) > 0;
    });
}

/**
 * Blocks to list in a fight's gallery. Elemental fights are grouped by attack
 * type; realm and job fights form a single untitled group.
 */
function gallery_groups($blocks)
{
    $listed = array_filter($blocks, function ($block) {
        return $block['banner'] !== '' || $block['label'] !== '';
    });
    if ($blocks[0]['variant'] === '') {
        return ['' => $listed];
    }
    $groups = ['Physical' => [], 'Magical' => []];
    foreach ($listed as $block) {
        $groups[strtok($block['variant'], ' ')][] = $block;
    }
    return $groups;
}

/** Names can hold any character and differ only in case, hence the hash. */
function keeper_url($username)
{
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($username)), '-');
    return 'keeper-' . $slug . '-' . substr(md5($username), 0, 6) . '.html';
}

function keeper_names($site)
{
    $names = [];
    foreach ($site['records'] as $records) {
        foreach ($records as $record) {
            $names[$record['username']] = true;
        }
    }
    return array_map('strval', array_keys($names));
}

/** Full name of a block, e.g. "Physical Water Weak Magus Sisters" or "JCD2 Mage II". */
function block_full_name($block)
{
    return ($block['variant'] !== '' ? $block['variant'] : $block['cd']) . ' ' . $block['boss'];
}

/** A keeper's entries across all fights, in tab order, each with its sheet and block. */
function keeper_entries($site, $username)
{
    $entries = [];
    foreach ($site['blocks'] as $sheet => $blocks) {
        foreach ($blocks as $block) {
            $records = $site['records'][block_key($block)] ?? [];
            if (!in_array($username, array_column($records, 'username'), true)) {
                continue;
            }
            foreach (build_ranking($site, $records) as $entry) {
                if ($entry['username'] === $username) {
                    $entries[] = $entry + ['sheet' => $sheet, 'block' => $block];
                }
            }
        }
    }
    return $entries;
}

/** Every entry of every fight, each with its sheet and block. */
function all_entries($site)
{
    $entries = [];
    foreach ($site['blocks'] as $sheet => $blocks) {
        foreach ($blocks as $block) {
            foreach (build_ranking($site, $site['records'][block_key($block)] ?? []) as $entry) {
                $entries[] = $entry + ['sheet' => $sheet, 'block' => $block];
            }
        }
    }
    return $entries;
}

/**
 * The keepers with the most medal points. Equal points go to whoever has more golds,
 * then silvers. Keepers with the very same medals share a place and the next one is
 * skipped (1, 2, 3, 3, 5), so everyone tied at the last place is listed.
 */
function hall_of_fame($site)
{
    $keepers = [];
    foreach (all_entries($site) as $entry) {
        $username = $entry['username'];
        if (!isset($keepers[$username])) {
            $keepers[$username] = [
                'username' => $username,
                'badges' => $entry['badges'],
                'records' => 0,
                'medals' => [1 => 0, 2 => 0, 3 => 0],
                'points' => 0,
            ];
        }
        $keepers[$username]['records']++;
        $rank = $entry['rank'];
        if ($rank > count(MEDALS)) {
            continue;
        }
        $keepers[$username]['medals'][$rank]++;
        $keepers[$username]['points'] += MEDAL_POINTS[$rank];
    }
    usort($keepers, function ($a, $b) {
        return [$b['points'], $b['medals'][1], $b['medals'][2], strtolower($a['username'])]
            <=> [$a['points'], $a['medals'][1], $a['medals'][2], strtolower($b['username'])];
    });
    $ranked = [];
    foreach ($keepers as $index => $keeper) {
        $tied = $index > 0 && $keeper['medals'] === $keepers[$index - 1]['medals'];
        $keeper['rank'] = $tied ? end($ranked)['rank'] : $index + 1;
        if ($keeper['rank'] > HALL_OF_FAME_SIZE) {
            break;
        }
        $ranked[] = $keeper;
    }
    return $ranked;
}

/**
 * The latest submissions across all fights, newest first. Undated ones are left out.
 * Status is empty for a submission that data/record_changes.csv doesn't cover.
 */
function recent_entries($site)
{
    $entries = [];
    foreach (all_entries($site) as $entry) {
        if ($entry['submitted_on'] !== '') {
            $status = $site['changes'][change_key($entry['block'], $entry['submitted_on'])] ?? '';
            $entries[] = $entry + ['status' => $status];
        }
    }
    usort($entries, function ($a, $b) {
        return strcmp($b['submitted_on'], $a['submitted_on']);
    });
    return array_slice($entries, 0, RECENT_RECORDS);
}

function is_hard_mode($sheet)
{
    return substr($sheet, -3) === '-HM';
}

function fight_section($cd)
{
    if (strpos($cd, 'RCD') === 0) {
        return 'Realm Fights';
    }
    if (strpos($cd, 'JCD') === 0) {
        return 'Job Fights';
    }
    return 'Elemental Fights';
}

/** Normal-mode sheets grouped by home section, keeping the sheet's tab order. */
function home_sections($site)
{
    $sections = ['Elemental Fights' => [], 'Realm Fights' => [], 'Job Fights' => []];
    foreach ($site['fights'] as $sheet => $fight) {
        if (!is_hard_mode($sheet)) {
            $sections[fight_section($site['blocks'][$sheet][0]['cd'])][] = $sheet;
        }
    }
    return $sections;
}

/** Banners of the first block that has one in both modes: [normal, hard]. */
function fight_banners($site, $sheet)
{
    $hardBlocks = $site['blocks'][$sheet . '-HM'];
    foreach ($site['blocks'][$sheet] as $index => $block) {
        if ($block['banner'] !== '' && $hardBlocks[$index]['banner'] !== '') {
            return [$block['banner'], $hardBlocks[$index]['banner']];
        }
    }
    return ['', ''];
}

/** Character for a team slot; unknown codes still render, by their code. */
function find_character($site, $code)
{
    $code = strtolower($code);
    return $site['characters'][$code] ?? ['code' => $code, 'name' => $code, 'icon' => '', 'sprite' => ''];
}

/**
 * Sorts by time, then submission date (undated last), and assigns dense ranks
 * so that tied times share a rank and the next time takes the following one.
 */
function build_ranking($site, $records)
{
    foreach ($records as $index => $record) {
        $records[$index]['seconds'] = (float) $record['time'];
        $records[$index]['index'] = $index;
    }
    usort($records, function ($a, $b) {
        return [$a['seconds'], $a['submitted_on'] === '', $a['submitted_on'], $a['index']]
            <=> [$b['seconds'], $b['submitted_on'] === '', $b['submitted_on'], $b['index']];
    });

    $ranking = [];
    $rank = 0;
    $previous = null;
    foreach ($records as $record) {
        if ($record['seconds'] !== $previous) {
            $rank++;
            $previous = $record['seconds'];
        }
        $team = [];
        for ($slot = 1; $slot <= 5; $slot++) {
            if ($record['char' . $slot] !== '') {
                $team[] = find_character($site, $record['char' . $slot]);
            }
        }
        $date = $record['submitted_on'];
        $ranking[] = [
            'rank' => $rank,
            'username' => $record['username'],
            'seconds' => $record['seconds'],
            'team' => $team,
            'video' => preg_match('#^https?://#i', $record['video']) ? $record['video'] : '',
            'date' => $date === '' ? '' : substr($date, 5, 2) . '/' . substr($date, 2, 2),
            'submitted_on' => $date,
            'badges' => $site['badges'][$record['username']] ?? [],
        ];
    }
    return $ranking;
}

function format_seconds($seconds)
{
    return number_format($seconds, 2, '.', '') . 's';
}

function average_seconds($ranking)
{
    return array_sum(array_column($ranking, 'seconds')) / count($ranking);
}

/** Share of teams using each character. A team counts a character only once. */
function build_usage($ranking)
{
    $usage = [];
    foreach ($ranking as $entry) {
        $seen = [];
        foreach ($entry['team'] as $character) {
            $name = $character['name'];
            if (isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;
            if (!isset($usage[$name])) {
                $usage[$name] = ['name' => $name, 'icon' => $character['icon'], 'count' => 0];
            }
            $usage[$name]['count']++;
        }
    }
    $usage = array_values($usage);
    usort($usage, function ($a, $b) {
        return [$b['count'], strtolower($a['name'])] <=> [$a['count'], strtolower($b['name'])];
    });
    foreach ($usage as $index => $row) {
        $usage[$index]['percent'] = number_format($row['count'] / count($ranking) * 100, 1, '.', '');
    }
    return $usage;
}
