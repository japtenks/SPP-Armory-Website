<?php
include('forum.func.php');

$this_forum = get_forum_byid($_GET['fid']);
if($this_forum['forum_id']<=0)exit('This forum does not exist.');
$this_forum['linktonewtopic'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=post&action=newtopic&f='.$this_forum['forum_id'].'';
$this_forum['linktomarkread'] = $MW->getConfig->temp->site_href.'index.php?n=forum&sub=viewforum&fid='.$this_forum['forum_id'].'&markread=1';
// ==================== //
$pathway_info[] = array('title'=>$this_forum['forum_name'],'link'=>'');
// ==================== //

$vfRealmId = spp_forum_target_realm_id($this_forum, $realmDbMap, spp_resolve_realm_id($realmDbMap));
$vfPdo = spp_get_pdo('realmd', $vfRealmId);

list($topicsmark, $mark) = spp_forum_prepare_viewforum_marker($vfPdo, $user, $this_forum);
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

$topics = spp_forum_build_viewforum_topics(
    $vfPdo,
    $this_forum,
    $user,
    $topicsmark,
    $mark,
    $items_per_pages,
    $limit_start,
    $sortField,
    $sortDir
);
?>
