<?php
$itemId = isset($_GET['item']) ? (int)$_GET['item'] : 0;
$realmId = isset($_GET['realm']) ? (int)$_GET['realm'] : 0;
$itemGuid = isset($_GET['guid']) ? (int)$_GET['guid'] : 0;

header('Content-Type: text/html; charset=UTF-8');

if ($itemId <= 0 || $realmId <= 0) {
    http_response_code(400);
    exit('');
}

$_GET['realm'] = $realmId;
$_REQUEST['realm'] = $realmId;

require_once(__DIR__ . '/xfer/includes/bootstrap.php');
require_once(__DIR__ . '/xfer/includes/helpers.php');
require_once(__DIR__ . '/xfer/includes/item_tooltip_renderer.php');

$item = world_query("SELECT entry, name FROM item_template WHERE entry = {$itemId} LIMIT 1", 1);
if (!$item) {
    http_response_code(404);
    exit('');
}

$item['guid'] = $itemGuid;

$html = spp_render_item_tooltip_html($item);
if ($html === '') {
    http_response_code(404);
    exit('');
}

echo $html;
