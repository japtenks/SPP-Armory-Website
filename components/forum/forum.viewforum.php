<?php
include('forum.func.php');

$this_forum = get_forum_byid($_GET['fid']);
if($this_forum['forum_id']<=0)exit('This forum does not exist.');
$this_forum['linktonewtopic'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=post&action=newtopic&f='.$this_forum['forum_id'].'';
$this_forum['linktomarkread'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=viewforum&fid='.$this_forum['forum_id'].'&markread=1';
// ==================== //
$pathway_info[] = array('title'=>$this_forum['forum_name'],'link'=>'');
// ==================== //

$vfRealmId = spp_resolve_realm_id($realmDbMap);
$vfPdo = spp_get_pdo('realmd', $vfRealmId);

// MARKREAD //
if($user['id']>0){
    $topicsmark = array();
    $mark = array(
        'marker_topics_read' => serialize(array()),
        'marker_last_update' => 0,
        'marker_unread' => 0,
        'marker_last_cleared' => 0,
    );
    if($_GETVARS['markread']==1){
        $stmtMr = $vfPdo->prepare("UPDATE f_markread SET marker_topics_read=?,marker_last_update=?,marker_unread=0,marker_last_cleared=? WHERE marker_member_id=? AND marker_forum_id=?");
        $stmtMr->execute([serialize($topicsmark), (int)$_SERVER['REQUEST_TIME'], (int)$_SERVER['REQUEST_TIME'], (int)$user['id'], (int)$this_forum['forum_id']]);
        redirect($MW->getConfig->temp->site_href.'index.php?n=forum&sub=viewforum&fid='.$this_forum['forum_id'],1);
    }
    $stmtGetMr = $vfPdo->prepare("SELECT * FROM f_markread WHERE marker_member_id=? AND marker_forum_id=?");
    $stmtGetMr->execute([(int)$user['id'], (int)$this_forum['forum_id']]);
    $mark = $stmtGetMr->fetch(PDO::FETCH_ASSOC);
    if(!$mark){
        $stmtInsMr = $vfPdo->prepare("INSERT INTO f_markread SET marker_member_id=?,marker_forum_id=?,marker_topics_read=?");
        $stmtInsMr->execute([(int)$user['id'], (int)$this_forum['forum_id'], serialize(array())]);
    }
    if(!empty($mark['marker_topics_read']))$topicsmark = unserialize($mark['marker_topics_read']);
}
//===== Calc pages =====//
$allowedTopicPageSizes = array(10, 25, 50);
$requestedTopicPageSize = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 0;
$items_per_pages = in_array($requestedTopicPageSize, $allowedTopicPageSizes, true)
    ? $requestedTopicPageSize
    : (int)$MW->getConfig->generic->topics_per_page;
if (!in_array($items_per_pages, $allowedTopicPageSizes, true)) {
    $items_per_pages = 25;
}
$itemnum = $this_forum['num_topics'];
$pnum = ceil($itemnum/$items_per_pages);
$limit_start = ($p-1)*$items_per_pages;
$this_forum['pnum'] = $pnum;
$this_forum['items_per_page'] = $items_per_pages;
$this_forum['allowed_page_sizes'] = $allowedTopicPageSizes;

$allowedSortFields = array(
    'subject' => 'f_topics.topic_name',
    'author' => 'topic_author_display',
    'posted' => 'f_topics.topic_posted',
    'replies' => 'f_topics.num_replies',
    'views' => 'f_topics.num_views',
    'last_reply' => 'f_topics.last_post',
);
$requestedSort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'posted';
$sortField = isset($allowedSortFields[$requestedSort]) ? $requestedSort : 'posted';
$requestedDir = isset($_GET['dir']) ? strtolower((string)$_GET['dir']) : 'desc';
$sortDir = ($requestedDir === 'asc') ? 'ASC' : 'DESC';
$this_forum['sort_field'] = $sortField;
$this_forum['sort_dir'] = strtolower($sortDir);

$topics = array();
$stmtAt = $vfPdo->prepare("
    SELECT f_topics.*,account.username,
           COALESCE(NULLIF(f_topics.topic_poster, ''), account.username) AS topic_author_display
    FROM f_topics
    LEFT JOIN account ON f_topics.topic_poster_id=account.id
    WHERE forum_id=?
    ORDER BY sticky DESC, " . $allowedSortFields[$sortField] . " " . $sortDir . ", f_topics.last_post DESC, f_topics.topic_id DESC
    LIMIT " . (int)$limit_start . "," . (int)$items_per_pages);
$stmtAt->execute([(int)$this_forum['forum_id']]);
$alltopics = $stmtAt->fetchAll(PDO::FETCH_ASSOC);
foreach($alltopics as $cur_topic)
{
    $topicLastRead = isset($topicsmark[$cur_topic['topic_id']]) ? (int)$topicsmark[$cur_topic['topic_id']] : 0;
    if($user['id']>0 && $cur_topic['last_post'] > (int)$mark['marker_last_cleared']){
        $cur_topic['isnew']=true;
        if($cur_topic['last_post'] > $topicLastRead){
            $cur_topic['isnew']=true;
        }else{
            $cur_topic['isnew']=false;
        }
    }else{
        $cur_topic['isnew']=true;
    }

    $pnum = max(1, (int)ceil(((int)$cur_topic['num_replies'] + 1)/(int)$MW->getConfig->generic->posts_per_page));
    if($pnum>1){
        $cur_topic['pages_str'] = '&laquo; ';
        for($pi=1;$pi<=$pnum;$pi++){ $cur_topic['pages_str'].='<a href="index.php?n=forum&sub=viewtopic&tid='.$cur_topic['topic_id'].'&p='.$pi.'">'.$pi.'</a> '; }
        $cur_topic['pages_str'] .= ' &raquo;';
    }
    $cur_topic['pnum'] = $pnum;
    if(date('d',$cur_topic['topic_posted'])==date('d') && $_SERVER['REQUEST_TIME']-$cur_topic['topic_posted']<86400)$cur_topic['topic_posted'] = rtrim((string)$lang['today_at']) . ' ' . date('H:i',$cur_topic['topic_posted']);
    elseif(date('d',$cur_topic['topic_posted'])==date('d',$yesterday_ts) && $_SERVER['REQUEST_TIME']-$cur_topic['topic_posted']<2*86400)$cur_topic['topic_posted'] = rtrim((string)$lang['yesterday_at']) . ' ' . date('H:i',$cur_topic['topic_posted']);
    else $cur_topic['topic_posted'] = date('M d, Y H:i',$cur_topic['topic_posted']);

    if(date('d',$cur_topic['last_post'])==date('d') && $_SERVER['REQUEST_TIME']-$cur_topic['last_post']<86400)$cur_topic['last_post'] = rtrim((string)$lang['today_at']) . ' ' . date('H:i',$cur_topic['last_post']);
    elseif(date('d',$cur_topic['last_post'])==date('d',$yesterday_ts) && $_SERVER['REQUEST_TIME']-$cur_topic['last_post']<2*86400)$cur_topic['last_post'] = rtrim((string)$lang['yesterday_at']) . ' ' . date('H:i',$cur_topic['last_post']);
    else $cur_topic['last_post'] = date('M d, Y H:i',$cur_topic['last_post']);

    $cur_topic['linktothis'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=viewtopic&tid='.$cur_topic['topic_id'].'';
    $cur_topic['linktolastpost'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=viewtopic&tid='.$cur_topic['topic_id'].'&to=lastpost';
    $cur_topic['linktoprofile1'] = $MW->getConfig->temp->site_href.'index.php?n=account&sub=view&action=find&name='.$cur_topic['username'].'';
    $cur_topic['linktoprofile2'] = $MW->getConfig->temp->site_href.'index.php?n=account&sub=view&action=find&name='.$cur_topic['last_poster'].'';

    $topics[] = $cur_topic;
}
unset($alltopics);
?>
