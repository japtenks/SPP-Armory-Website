<?php
include('forum.func.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die("Realm DB map not loaded");
}

$this_topic = get_topic_byid($_GET['tid']);
$this_forum = get_forum_byid($this_topic['forum_id']);
$realmId = spp_forum_target_realm_id($this_forum, $realmMap, spp_resolve_realm_id($realmMap));
$_vtNewsFid = (int)($MW->getConfig->generic_values->forum->news_forum_id ?? 0);
$_vtIsNewsForum = $_vtNewsFid > 0 && (int)$this_forum['forum_id'] === $_vtNewsFid;
$_vtCanPost = !$_vtIsNewsForum || (int)($user['gmlevel'] ?? 0) >= 3;
$this_topic['show_qr'] = ($this_forum['quick_reply']==1 && $_vtCanPost) ? true : false;
if($this_forum['forum_id']<=0 || $this_topic['topic_id']<=0)exit('This forum or topic does not exist.');
// ================================================= //
$this_forum['linktothis'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=viewforum&fid='.$this_forum['forum_id'].'';
$this_forum['linktonewtopic'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=post&action=newtopic&f='.$this_forum['forum_id'].'';

$this_topic['linktothis'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=viewtopic&tid='.$this_topic['topic_id'].'';
$this_topic['linktoreply'] = $_vtCanPost ? $MW->getConfig->temp->site_href.'index.php?n=forum&sub=post&action=newpost&t='.$this_topic['topic_id'].'&fid='.$this_forum['forum_id'] : '';
$this_topic['linktopostreply'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=post&action=donewpost&t='.$this_topic['topic_id'].'&fid='.$this_forum['forum_id'];
$this_topic['linktodelete'] = spp_forum_action_url($MW->getConfig->temp->site_href.'index.php?n=forum&sub=post&action=dodeletetopic&t='.$this_topic['topic_id']);
$this_topic['linktoclose'] = spp_forum_action_url($MW->getConfig->temp->site_href.'index.php?n=forum&sub=post&action=closetopic&t='.$this_topic['topic_id']);
$this_topic['linktoopen'] = spp_forum_action_url($MW->getConfig->temp->site_href.'index.php?n=forum&sub=post&action=opentopic&t='.$this_topic['topic_id']);
$this_topic['linktostick'] = spp_forum_action_url($MW->getConfig->temp->site_href.'index.php?n=forum&sub=post&action=sticktopic&t='.$this_topic['topic_id']);
$this_topic['linktounstick'] = spp_forum_action_url($MW->getConfig->temp->site_href.'index.php?n=forum&sub=post&action=unsticktopic&t='.$this_topic['topic_id']);
$pathway_info[] = array('title'=>$this_forum['forum_name'],'link'=>$this_forum['linktothis']);
$pathway_info[] = array('title'=>$this_topic['topic_name'],'link'=>'');
// ================================================= //

$dtmp = "templates/".( string ) $MW->getConfig->generic->template;
$bgswitch = '2';
  //===== Calc pages =====//
  $items_per_pages = (int)$MW->getConfig->generic->posts_per_page;
  $itemnum = (int)$this_topic['num_replies'] + 1;
  $pnum = max(1, (int)ceil($itemnum/$items_per_pages));
  $limit_start = ($p-1)*$items_per_pages;
  $pages_str = default_paginate($pnum, $p, 'index.php?n=forum&sub=viewtopic&tid='.(int)$this_topic['topic_id']);
  $this_topic['page_count'] = $pnum;
  $this_topic['linktolastpost'] = $this_topic['linktothis'].'&to=lastpost';

$forumPdo = spp_get_pdo('realmd', $realmId);
$charPdoVt = spp_get_pdo('chars', $realmId);

if($_GETVARS['to']=='lastpost'){
  redirect($this_topic['linktothis']."&p=".$pnum."#post".$this_topic['last_post_id'],1);
}elseif(is_numeric($_GETVARS['to'])){
  $f_post_pos = get_post_pos($this_topic['topic_id'],$_GETVARS['to']);
  $f_post_page = floor($f_post_pos/$items_per_pages)+1;
  redirect($this_topic['linktothis']."&p=".$f_post_page."#post".$_GETVARS['to'],1);
}else{
    // MARKREAD //
    if($user['id']>0){
        $topicsmark = array();
        $stmtMr = $forumPdo->prepare("SELECT * FROM f_markread WHERE marker_member_id=? AND marker_forum_id=?");
        $stmtMr->execute([(int)$user['id'], (int)$this_forum['forum_id']]);
        $mark = $stmtMr->fetch(PDO::FETCH_ASSOC);
        if(!$mark){
            $stmtIns = $forumPdo->prepare("INSERT INTO f_markread SET marker_member_id=?,marker_forum_id=?,marker_topics_read=?");
            $stmtIns->execute([(int)$user['id'], (int)$this_forum['forum_id'], serialize(array())]);
        }
        if($mark['marker_topics_read'])$topicsmark = unserialize($mark['marker_topics_read']);
        //  output_message('debug','<pre>'.print_r($topicsmark,true).'</pre>');
        $time_check = $topicsmark[$this_topic['topic_id']]>$mark['marker_last_cleared']?$topicsmark[$this_topic['topic_id']]:$mark['marker_last_cleared'];
        $read_topics_tid = array( 0 => $this_topic['topic_id'] );
        foreach($topicsmark as $tid => $date){
            if ( $date > $mark['marker_last_cleared'] )
            {
        $read_topics_tid[] = $tid;
            }
        }

        if($this_topic['last_post'] >= $time_check){
            // Count unread themes...
            $unread = $mark['marker_unread'] - 1;
            $topicsmark[$this_topic['topic_id']] = $_SERVER['REQUEST_TIME'];
            if($unread <= 0){
                $inPlaceholders = implode(',', array_fill(0, count($read_topics_tid), '?'));
                $stmtCnt = $forumPdo->prepare("SELECT count(*) as count,MIN(last_post) as min_last_post FROM f_topics WHERE last_post>? AND topic_id NOT IN ($inPlaceholders) AND forum_id=?");
                $stmtCnt->execute(array_merge([(int)$mark['marker_last_cleared']], array_map('intval', $read_topics_tid), [(int)$this_forum['forum_id']]));
                $count = $stmtCnt->fetch(PDO::FETCH_ASSOC);
                $unread = $count['count'];
                if( $unread > 0 AND ( is_array( $topicsmark ) and count( $topicsmark ) ) ){
                    $read_cutoff = $count['min_last_post'] - 1;
                    $topicsmark = array_filter($topicsmark,"clean_read_topics");
                    $save_markers = serialize($topicsmark);
                }else{
                    $save_markers = serialize(array());
                    $mark['marker_last_cleared'] = $_SERVER['REQUEST_TIME'];
                    $unread = 0;
                }
            }else{
                $save_markers = serialize($topicsmark);
            }

            $stmtUpMr = $forumPdo->prepare("UPDATE f_markread SET marker_topics_read=?,marker_last_update=?,marker_unread=?,marker_last_cleared=? WHERE marker_member_id=? AND marker_forum_id=?");
            $stmtUpMr->execute([$save_markers, (int)$_SERVER['REQUEST_TIME'], (int)$unread, (int)$mark['marker_last_cleared'], (int)$user['id'], (int)$this_forum['forum_id']]);
        }
    }
    $stmtView = $forumPdo->prepare("UPDATE f_topics SET num_views=num_views+1 WHERE topic_id=? LIMIT 1");
    $stmtView->execute([(int)$this_topic['topic_id']]);
}

$stmtPosts = $forumPdo->prepare("
    SELECT * FROM f_posts
    LEFT JOIN account ON f_posts.poster_id=account.id
    LEFT JOIN website_accounts ON f_posts.poster_id=website_accounts.account_id
    LEFT JOIN website_identity_profiles ON f_posts.poster_identity_id=website_identity_profiles.identity_id
    LEFT JOIN website_account_groups ON website_accounts.g_id = website_account_groups.g_id
    WHERE topic_id=? ORDER BY posted LIMIT " . (int)$limit_start . "," . (int)$items_per_pages);
$stmtPosts->execute([(int)$this_topic['topic_id']]);
$result = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);
$posts = spp_forum_hydrate_topic_posts(
    $forumPdo,
    $charPdoVt,
    $result,
    $realmId,
    $MW->getConfig->temp->site_href.'index.php?n=forum&sub=viewtopic&tid=' . (int)$this_topic['topic_id'],
    (($p - 1) * $items_per_pages),
    true
);
foreach ($posts as &$post) {
    $pnc++;
    if($bgswitch=='2')$bgswitch = '1';else $bgswitch = '2';
    $post['bg'] = $bgswitch;
}
unset($post);
unset($result);

function clean_read_topics($var)
{
    global $read_cutoff;
    return $var > $read_cutoff;
}
?>
