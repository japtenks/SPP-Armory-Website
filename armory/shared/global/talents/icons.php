<?php
// armory/shared/global/talents/icon.php

// --- basic input ---
$spellId = isset($_GET['spell']) ? (int)$_GET['spell'] : 0;
$iconReq = isset($_GET['icon'])  ? trim($_GET['icon'])  : '';

// where icons live (local files)
$ICONS_DIR = __DIR__ . '/icons';
$FALLBACK  = $ICONS_DIR . '/placeholder.jpg';

// map icon file by name with common extensions
function resolve_icon_file($iconsDir, $iconName) {
    $iconName = preg_replace('~[^A-Za-z0-9_\-]~', '', $iconName); // sanitize
    if ($iconName === '') return null;
    $candidates = [
        $iconsDir . "/{$iconName}.jpg",
        $iconsDir . "/{$iconName}.png",
        $iconsDir . "/{$iconName}.gif",
    ];
    foreach ($candidates as $p) {
        if (is_file($p)) return $p;
    }
    return null;
}

// send file with correct headers
function send_image($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $type = ($ext === 'png') ? 'image/png' : (($ext === 'gif') ? 'image/gif' : 'image/jpeg');

    // light cache headers
    header('Content-Type: ' . $type);
    header('Cache-Control: public, max-age=86400'); // 1 day
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', @filemtime($path) ?: time()) . ' GMT');
    readfile($path);
    exit;
}

// --- fast path: direct icon name ---
if ($iconReq !== '') {
    $file = resolve_icon_file($ICONS_DIR, $iconReq);
    if ($file) send_image($file);
    if (is_file($FALLBACK)) send_image($FALLBACK);
    http_response_code(404);
    exit('Icon not found');
}

// --- DB lookup by spell id ---
if ($spellId > 0) {
    // include your armory DB utils (adjust path if different)
    // icon.php is at armory/shared/global/talents/icon.php
    // we need: armory/configuration/mysql.php
    $mysqlPath = realpath(__DIR__ . '/../../../configuration/mysql.php');
    if ($mysqlPath && is_file($mysqlPath)) {
        require_once $mysqlPath;
    } else {
        // cannot load DB layer — fallback to placeholder
        if (is_file($FALLBACK)) send_image($FALLBACK);
        http_response_code(500);
        exit('DB bootstrap not found');
    }

    // Try the common schema: dbc_spell.spellicon -> dbc_spellicon.iconname
    $row = execute_query(
        'armory',
        "SELECT si.iconname AS icon
           FROM dbc_spell s
           JOIN dbc_spellicon si ON si.id = s.spellicon
          WHERE s.id = ?d
          LIMIT 1",
        1, [$spellId]
    );

    // fallback: some schemas use `name` instead of `iconname`
    if (!$row || empty($row['icon'])) {
        $row = execute_query(
            'armory',
            "SELECT si.name AS icon
               FROM dbc_spell s
               JOIN dbc_spellicon si ON si.id = s.spellicon
              WHERE s.id = ?d
              LIMIT 1",
            1, [$spellId]
        );
    }

    if ($row && !empty($row['icon'])) {
        $iconName = basename($row['icon']); // keep it clean
        $file = resolve_icon_file($ICONS_DIR, $iconName);
        if ($file) send_image($file);
    }

    // no icon found in DB or file missing
    if (is_file($FALLBACK)) send_image($FALLBACK);
    http_response_code(404);
    exit('Icon not found');
}

// no params — show placeholder or 400
if (is_file($FALLBACK)) send_image($FALLBACK);
http_response_code(400);
exit('Missing spell or icon parameter');
