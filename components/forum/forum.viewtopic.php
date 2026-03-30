<?php
include('forum.func.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config-protected.php');

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die("Realm DB map not loaded");
}

$realmId = spp_resolve_realm_id($realmMap);


$this_topic = get_topic_byid($_GET['tid']);
$this_forum = get_forum_byid($this_topic['forum_id']);
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
$posterPostCountCache = array();
$posterIdentityMeta = array();
if (!empty($result)) {
    $identityIds = array();
    foreach ($result as $row) {
        $identityId = (int)($row['poster_identity_id'] ?? 0);
        if ($identityId > 0) {
            $identityIds[] = $identityId;
        }
    }

    $identityIds = array_values(array_unique($identityIds));
    if (!empty($identityIds)) {
        try {
            $placeholders = implode(',', array_fill(0, count($identityIds), '?'));
            $stmtIdentityMeta = $forumPdo->prepare("
                SELECT identity_id, identity_type, is_bot
                FROM website_identities
                WHERE identity_id IN ({$placeholders})
            ");
            $stmtIdentityMeta->execute($identityIds);
            foreach ($stmtIdentityMeta->fetchAll(PDO::FETCH_ASSOC) as $identityRow) {
                $posterIdentityMeta[(int)$identityRow['identity_id']] = array(
                    'identity_type' => (string)($identityRow['identity_type'] ?? ''),
                    'is_bot' => (int)($identityRow['is_bot'] ?? 0),
                );
            }
        } catch (Throwable $e) {
            error_log('[forum.viewtopic] Identity meta lookup failed: ' . $e->getMessage());
        }
    }
}
foreach($result as $cur_post)
{
    unset($result['password']);
    // ================================================= //
    $cur_post['linktoprofile'] = $MW->getConfig->temp->site_href.'index.php?n=account&sub=view&action=find&name='.$cur_post['username'].'';
    $cur_post['linktopms'] = $MW->getConfig->temp->site_href.'index.php?n=account&sub=pms&action=add&to='.$cur_post['username'];
    $cur_post['linktothis'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=viewtopic&tid='.$this_topic['topic_id'].'&to='.$cur_post['post_id'];
    $cur_post['linktoedit'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=post&action=editpost&post='.$cur_post['post_id'];
    $cur_post['linktodelete'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=post&action=dodeletepost&post='.$cur_post['post_id'];
    if (!empty($cur_post['poster_character_id'])) {
        $cur_post['linktocharacter_social'] = $MW->getConfig->temp->site_href
            . 'index.php?n=server&sub=character&realm=' . (int)$realmId
            . '&guid=' . (int)$cur_post['poster_character_id']
            . '&tab=social';
    } else {
        $cur_post['linktocharacter_social'] = $cur_post['linktoprofile'];
    }
    // ================================================= //

    $stmtCi = $charPdoVt->prepare("
    SELECT c.race, c.class, c.level, c.gender, g.name as guild
    FROM characters c
    LEFT JOIN guild_member gm ON c.guid = gm.guid
    LEFT JOIN guild g ON gm.guildid = g.guildid
    WHERE c.guid = ?");
    $stmtCi->execute([(int)$cur_post["poster_character_id"]]);
    $charinfo = $stmtCi->fetch(PDO::FETCH_ASSOC);

    if (!empty($charinfo) && !empty($cur_post['poster_character_id'])) {
	    $cur_post['avatar'] = get_character_portrait_path(
        $cur_post["poster_character_id"],
        $charinfo['gender'],
        $charinfo['race'],
        $charinfo['class']
    );
    //gender race class
	    $cur_post['mini_race'] = "$charinfo[race]-$charinfo[gender].gif";
	    $cur_post['mini_class'] = "$charinfo[class].gif";
        $cur_post['level'] = (int)$charinfo['level'];
	    if($charinfo['race']==1 || $charinfo['race']==3 || $charinfo['race']==4 || $charinfo['race']==7 || $charinfo['race']==11)$faction = 'alliance';
            else $faction = 'horde';
	    $cur_post['faction'] = "$faction.gif";
        $cur_post['guild'] = $charinfo['guild'] ?? '';
    } else {
        $cur_post['avatar'] = get_forum_avatar_fallback($cur_post['poster'] ?? '');
        $cur_post['mini_race'] = '';
        $cur_post['mini_class'] = '';
        $cur_post['level'] = 0;
        $cur_post['faction'] = '';
        $cur_post['guild'] = '';
    }

    $posterId = (int)($cur_post['poster_id'] ?? 0);
    $posterIdentityId = (int)($cur_post['poster_identity_id'] ?? 0);
    $identityMeta = $posterIdentityMeta[$posterIdentityId] ?? null;
    $countByIdentity = $posterIdentityId > 0 && !empty($identityMeta)
        && (((int)$identityMeta['is_bot']) === 1 || ($identityMeta['identity_type'] ?? '') === 'bot_character');

    if ($countByIdentity) {
        $cacheKey = 'identity:' . $posterIdentityId;
        if (!isset($posterPostCountCache[$cacheKey])) {
            $stmtPostCount = $forumPdo->prepare("SELECT COUNT(*) FROM f_posts WHERE poster_identity_id = ?");
            $stmtPostCount->execute([$posterIdentityId]);
            $posterPostCountCache[$cacheKey] = (int)$stmtPostCount->fetchColumn();
        }
        $cur_post['forum_post_count'] = $posterPostCountCache[$cacheKey];
    } elseif ($posterId > 0) {
        $cacheKey = 'account:' . $posterId;
        if (!isset($posterPostCountCache[$cacheKey])) {
            $stmtPostCount = $forumPdo->prepare("SELECT COUNT(*) FROM f_posts WHERE poster_id = ?");
            $stmtPostCount->execute([$posterId]);
            $posterPostCountCache[$cacheKey] = (int)$stmtPostCount->fetchColumn();
        }
        $cur_post['forum_post_count'] = $posterPostCountCache[$cacheKey];
    } else {
        $cur_post['forum_post_count'] = 0;
    }

    $pnc++;
    if($bgswitch=='2')$bgswitch = '1';else $bgswitch = '2';
    $cur_post['bg'] = $bgswitch;
    $cur_post['pos_num'] = $pnc+(($p-1)*$items_per_pages);
    $postedTs = (int)$cur_post['posted'];
    if (date('d', $postedTs) == date('d') && $_SERVER['REQUEST_TIME'] - $postedTs < 86400) {
        $cur_post['posted'] = rtrim((string)$lang['today_at']) . ' ' . date('H:i:s', $postedTs);
    } elseif (date('d', $postedTs) == date('d', $yesterday_ts) && $_SERVER['REQUEST_TIME'] - $postedTs < 2 * 86400) {
        $cur_post['posted'] = rtrim((string)$lang['yesterday_at']) . ' ' . date('H:i:s', $postedTs);
    } else {
        $cur_post['posted'] = date('M d, Y H:i:s', $postedTs);
    }

    $rawMessage = (string)($cur_post['message'] ?? '');
    $normalizedMessage = str_replace(
        array('<br />', '<br/>', '<br>'),
        "\n",
        html_entity_decode($rawMessage, ENT_QUOTES, 'UTF-8')
    );
    $normalizedMessage = spp_forum_normalize_legacy_markup($normalizedMessage);
    $cur_post['rendered_message'] = bbcode($normalizedMessage, true, true, true, false);

    $rawSignature = (string)($cur_post['signature'] ?? '');
    if (!empty($cur_post['identity_signature'])) {
        $rawSignature = (string)$cur_post['identity_signature'];
    }
    if ($rawSignature !== '') {
        $normalizedSignature = str_replace(
            array('<br />', '<br/>', '<br>'),
            "\n",
            html_entity_decode($rawSignature, ENT_QUOTES, 'UTF-8')
        );
        $normalizedSignature = spp_forum_normalize_legacy_markup($normalizedSignature);
        $cur_post['rendered_signature'] = bbcode($normalizedSignature, true, true, true, false);
    } else {
        $cur_post['rendered_signature'] = '';
    }

    $posts[] = $cur_post;
}
unset($result);

function clean_read_topics($var)
{
    global $read_cutoff;
    return $var > $read_cutoff;
}
?>
