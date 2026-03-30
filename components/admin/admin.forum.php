<?php
if (INCLUDED !== true) {
    exit;
}

require_once(__DIR__ . '/admin.forum.helpers.php');
require_once(__DIR__ . '/admin.forum.actions.php');
require_once(__DIR__ . '/admin.forum.read.php');

$forumPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));

spp_admin_forum_handle_action($forumPdo, $MW);

$forumView = spp_admin_forum_build_view($forumPdo, $lang);
extract($forumView, EXTR_OVERWRITE);
?>
