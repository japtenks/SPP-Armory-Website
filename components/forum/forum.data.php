<?php

function get_forum_byid($id){
    $realmId = isset($GLOBALS['realmDbMap']) ? spp_resolve_realm_id($GLOBALS['realmDbMap']) : 1;
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_forums WHERE forum_id=?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_topic_byid($id){
    $realmId = isset($GLOBALS['realmDbMap']) ? spp_resolve_realm_id($GLOBALS['realmDbMap']) : 1;
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_topics WHERE topic_id=?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_post_byid($id){
    $realmId = isset($GLOBALS['realmDbMap']) ? spp_resolve_realm_id($GLOBALS['realmDbMap']) : 1;
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_posts WHERE post_id=?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_last_forum_topic($id){
    $realmId = isset($GLOBALS['realmDbMap']) ? spp_resolve_realm_id($GLOBALS['realmDbMap']) : 1;
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_topics WHERE forum_id=? ORDER BY last_post DESC LIMIT 1");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_last_topic_post($id){
    $realmId = isset($GLOBALS['realmDbMap']) ? spp_resolve_realm_id($GLOBALS['realmDbMap']) : 1;
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_posts WHERE topic_id=? ORDER BY posted DESC LIMIT 1");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_post_pos($tid,$pid){
    $realmId = isset($GLOBALS['realmDbMap']) ? spp_resolve_realm_id($GLOBALS['realmDbMap']) : 1;
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT count(*) FROM f_posts WHERE topic_id=? AND post_id<? ORDER BY posted");
    $stmt->execute([(int)$tid, (int)$pid]);
    return $stmt->fetchColumn();
}
