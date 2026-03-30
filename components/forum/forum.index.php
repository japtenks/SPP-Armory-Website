<?php
include('forum.func.php');
$realmPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
$items = spp_forum_build_index_items($realmPdo, $user);
?>
