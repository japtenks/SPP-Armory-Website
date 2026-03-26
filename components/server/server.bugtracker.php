<?php
if(INCLUDED!==true)exit;

$pathway_info[] = array('title'=>'Bugtracker','link'=>'');

$items_per_page = 20;
if((int)$MW->getConfig->generic_values->forum->bugs_forum_id == 0)output_message('alert','Please define forum id for bugtracker (in config/config.xml)');

$realmPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
$stmt = $realmPdo->prepare("
    SELECT f_topics.*,f_posts.*
    FROM f_topics,f_posts
    WHERE f_topics.forum_id=? AND f_topics.topic_id=f_posts.topic_id AND f_topics.closed!=1 AND f_topics.sticky!=1
    GROUP BY f_topics.topic_id
    ORDER BY topic_posted DESC,f_posts.posted
    LIMIT ?,?");
$stmt->execute([(int)$bugs_forum_id, 0, $items_per_page]);
$alltopics = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
