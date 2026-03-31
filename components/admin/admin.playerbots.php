<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.playerbots.helpers.php');
require_once(__DIR__ . '/admin.playerbots.actions.php');
require_once(__DIR__ . '/admin.playerbots.read.php');

$pathway_info[] = array('title' => 'Playerbots Control', 'link' => 'index.php?n=admin&sub=playerbots');

$playerbotsRealmId = spp_resolve_realm_id($realmDbMap);
$playerbotsCharsPdo = spp_get_pdo('chars', $playerbotsRealmId);
spp_admin_playerbots_handle_action($playerbotsCharsPdo, $playerbotsRealmId);

$playerbotsView = spp_admin_playerbots_build_view($realmDbMap);
extract($playerbotsView, EXTR_OVERWRITE);
$admin_playerbots_csrf_token = spp_csrf_token('admin_playerbots');
