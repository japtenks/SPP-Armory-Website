<?php
if(INCLUDED!==true)exit;

if (!function_exists('spp_admin_forum_action_url')) {
    function spp_admin_forum_action_url(array $params) {
        return spp_action_url('index.php', $params, 'admin_forum');
    }
}

if (!function_exists('spp_admin_forum_filter_category_fields')) {
    function spp_admin_forum_filter_category_fields(array $data) {
        $allowed = array('cat_name', 'cat_disp_position');
        return spp_filter_allowed_fields($data, $allowed);
    }
}

if (!function_exists('spp_admin_forum_filter_forum_fields')) {
    function spp_admin_forum_filter_forum_fields(array $data) {
        $allowed = array('cat_id', 'forum_name', 'forum_desc', 'disp_position');
        return spp_filter_allowed_fields($data, $allowed);
    }
}

$forumPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));

$pathway_info[] = array('title'=>$lang['forums_manage'],'link'=>'index.php?n=admin&sub=forum');
if(!$_GET['action']){
    if($_GET['cat_id']){
        $stmt = $forumPdo->prepare("
            SELECT * FROM f_forums
            JOIN f_categories ON f_forums.cat_id=f_categories.cat_id
            WHERE f_forums.cat_id=? ORDER BY disp_position,forum_name");
        $stmt->execute([(int)$_GET['cat_id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pathway_info[] = array('title'=>$items[0]['cat_name'],'link'=>'');
    }elseif($_GET['forum_id'] && $_GET['topic_id']){
        $fid = (int)$_GET['forum_id'];
        $tid = (int)$_GET['topic_id'];
        $stmt = $forumPdo->prepare("SELECT * FROM f_topics WHERE topic_id=? LIMIT 1");
        $stmt->execute([$tid]);
        $this_topic = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $forumPdo->prepare("SELECT * FROM f_forums WHERE forum_id=? LIMIT 1");
        $stmt->execute([$fid]);
        $this_forum = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $forumPdo->prepare("SELECT post_id, poster, posted, LEFT(message,120) AS excerpt FROM f_posts WHERE topic_id=? ORDER BY posted");
        $stmt->execute([$tid]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pathway_info[] = array('title' => $this_forum['forum_name'], 'link' => 'index.php?n=admin&sub=forum&forum_id=' . $fid);
        $pathway_info[] = array('title' => $this_topic['topic_name'], 'link' => '');
    }elseif($_GET['forum_id']){
        $fid = (int)$_GET['forum_id'];
        $stmt = $forumPdo->prepare("SELECT * FROM f_forums WHERE forum_id=? LIMIT 1");
        $stmt->execute([$fid]);
        $this_forum = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $forumPdo->prepare("SELECT topic_id, topic_name, topic_poster, topic_posted, num_replies FROM f_topics WHERE forum_id=? ORDER BY topic_posted DESC");
        $stmt->execute([$fid]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pathway_info[] = array('title' => $this_forum['forum_name'], 'link' => '');
    }else{
        $pathway_info[] = array('title'=>$lang['categories'],'link'=>'');
        $stmt = $forumPdo->query("SELECT * FROM f_categories ORDER BY cat_disp_position,cat_name");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}elseif($_GET['action']=='moveup'){
    spp_require_csrf('admin_forum');
    moveup($_GET['cat_id'],$_GET['forum_id']);
    redirect($MW->getConfig->temp->site_href."index.php?n=admin&sub=forum",1); exit;
}elseif($_GET['action']=='movedown'){
    spp_require_csrf('admin_forum');
    movedown($_GET['cat_id'],$_GET['forum_id']);
    redirect($MW->getConfig->temp->site_href."index.php?n=admin&sub=forum",1); exit;
   // redirect($MW->getConfig->temp->site_href."index.php?n=admin&sub=forum&cat_id=".$_GET['cat_id']."&forum_id=".$_GET['forum_id'],1);
}elseif($_GET['action']=='open'){
    spp_require_csrf('admin_forum');
    $stmt = $forumPdo->prepare("UPDATE f_forums SET closed=0 WHERE forum_id=? LIMIT 1");
    $stmt->execute([(int)$_GET['forum_id']]);
    redirect($_SERVER['HTTP_REFERER'],1);
}elseif($_GET['action']=='close'){
    spp_require_csrf('admin_forum');
    $stmt = $forumPdo->prepare("UPDATE f_forums SET closed=1 WHERE forum_id=? LIMIT 1");
    $stmt->execute([(int)$_GET['forum_id']]);
    redirect($_SERVER['HTTP_REFERER'],1);
}elseif($_GET['action']=='show'){
    spp_require_csrf('admin_forum');
    $stmt = $forumPdo->prepare("UPDATE f_forums SET hidden=0 WHERE forum_id=? LIMIT 1");
    $stmt->execute([(int)$_GET['forum_id']]);
    redirect($_SERVER['HTTP_REFERER'],1);
}elseif($_GET['action']=='hide'){
    spp_require_csrf('admin_forum');
    $stmt = $forumPdo->prepare("UPDATE f_forums SET hidden=1 WHERE forum_id=? LIMIT 1");
    $stmt->execute([(int)$_GET['forum_id']]);
    redirect($_SERVER['HTTP_REFERER'],1);
}elseif($_GET['action']=='updforumsorder'){
    spp_require_csrf('admin_forum');
    $stmt = $forumPdo->prepare("UPDATE f_forums SET disp_position=? WHERE forum_id=? LIMIT 1");
    foreach($_POST['forumorder'] as $fid=>$order){
        $stmt->execute([(int)$order, (int)$fid]);
    }
    redirect($_SERVER['HTTP_REFERER'],1);
}elseif($_GET['action']=='newcat'){
    spp_require_csrf('admin_forum');
    $data = spp_admin_forum_filter_category_fields($_POST);
    $setClause = implode(',', array_map(function($k) { return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?'; }, array_keys($data)));
    if (!empty($data)) {
        $stmt = $forumPdo->prepare("INSERT INTO f_categories SET $setClause");
        $stmt->execute(array_values($data));
    }
    redirect($_SERVER['HTTP_REFERER'],1);
}elseif($_GET['action']=='renamecat'){
    spp_require_csrf('admin_forum');
    $catId = (int)($_POST['cat_id'] ?? 0);
    $catName = trim((string)($_POST['cat_name'] ?? ''));
    if ($catId > 0 && $catName !== '') {
        $stmt = $forumPdo->prepare("UPDATE f_categories SET cat_name=? WHERE cat_id=? LIMIT 1");
        $stmt->execute([$catName, $catId]);
    }
    redirect($_SERVER['HTTP_REFERER'],1);
}elseif($_GET['action']=='newforum'){
    spp_require_csrf('admin_forum');
    $data = spp_admin_forum_filter_forum_fields($_POST);
    $setClause = implode(',', array_map(function($k) { return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?'; }, array_keys($data)));
    if (!empty($data)) {
        $stmt = $forumPdo->prepare("INSERT INTO f_forums SET $setClause");
        $stmt->execute(array_values($data));
    }
    redirect($_SERVER['HTTP_REFERER'],1);
}elseif($_GET['action']=='renameforum'){
    spp_require_csrf('admin_forum');
    $forumId = (int)($_POST['forum_id'] ?? 0);
    $forumName = trim((string)($_POST['forum_name'] ?? ''));
    if ($forumId > 0 && $forumName !== '') {
        $stmt = $forumPdo->prepare("UPDATE f_forums SET forum_name=? WHERE forum_id=? LIMIT 1");
        $stmt->execute([$forumName, $forumId]);
    }
    redirect($_SERVER['HTTP_REFERER'],1);
}elseif($_GET['action']=='recount'){
    spp_require_csrf('admin_forum');
    recount($_GET['forum_id']);
    redirect($_SERVER['HTTP_REFERER'],1);
}elseif($_GET['action']=='deleteforum'){
    spp_require_csrf('admin_forum');
    delete_forum($_GET['forum_id']);
    redirect($_SERVER['HTTP_REFERER'],1);
}elseif($_GET['action']=='deletecat'){
    spp_require_csrf('admin_forum');
    delete_cat($_GET['cat_id']);
    redirect($_SERVER['HTTP_REFERER'],1); exit;
}elseif($_GET['action']=='deletetopic'){
    spp_require_csrf('admin_forum');
    $tid = (int)$_GET['topic_id'];
    $fid = (int)$_GET['forum_id'];
    $stmt = $forumPdo->prepare("SELECT num_replies FROM f_topics WHERE topic_id=? LIMIT 1");
    $stmt->execute([$tid]);
    $num_replies = (int)$stmt->fetchColumn();
    $stmt = $forumPdo->prepare("DELETE FROM f_posts WHERE topic_id=?");
    $stmt->execute([$tid]);
    $stmt = $forumPdo->prepare("DELETE FROM f_topics WHERE topic_id=? LIMIT 1");
    $stmt->execute([$tid]);
    $stmt = $forumPdo->prepare("UPDATE f_forums SET num_topics=GREATEST(0,num_topics-1), num_posts=GREATEST(0,num_posts-(?+1)) WHERE forum_id=? LIMIT 1");
    $stmt->execute([$num_replies, $fid]);
    redirect('index.php?n=admin&sub=forum&forum_id=' . $fid, 1); exit;
}elseif($_GET['action']=='deletepost'){
    spp_require_csrf('admin_forum');
    $pid = (int)$_GET['post_id'];
    $tid = (int)$_GET['topic_id'];
    $fid = (int)$_GET['forum_id'];
    $stmt = $forumPdo->prepare("DELETE FROM f_posts WHERE post_id=? LIMIT 1");
    $stmt->execute([$pid]);
    $stmt = $forumPdo->prepare("UPDATE f_topics SET num_replies=GREATEST(0,num_replies-1) WHERE topic_id=? LIMIT 1");
    $stmt->execute([$tid]);
    $stmt = $forumPdo->prepare("UPDATE f_forums SET num_posts=GREATEST(0,num_posts-1) WHERE forum_id=? LIMIT 1");
    $stmt->execute([$fid]);
    redirect('index.php?n=admin&sub=forum&forum_id=' . $fid . '&topic_id=' . $tid, 1); exit;
}


function recount($fid){
    global $realmDbMap;
    $pdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
    $stmt = $pdo->prepare("SELECT count(*) FROM f_topics WHERE forum_id=?");
    $stmt->execute([(int)$fid]);
    $c_topics = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT count(*) FROM f_topics RIGHT JOIN f_posts ON f_topics.topic_id=f_posts.topic_id WHERE forum_id=?");
    $stmt->execute([(int)$fid]);
    $c_posts = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT topic_id FROM f_topics WHERE forum_id=? ORDER BY last_post DESC LIMIT 1");
    $stmt->execute([(int)$fid]);
    $last_topic_id = $stmt->fetchColumn();
    $stmt = $pdo->prepare("UPDATE f_forums SET num_topics=?,num_posts=?,last_topic_id=? WHERE forum_id=? LIMIT 1");
    $stmt->execute([$c_topics, $c_posts, $last_topic_id, (int)$fid]);
}
function move_topic($topic_id,$from_fid,$to_fid){
    global $realmDbMap;
    $pdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
    $stmt = $pdo->prepare("SELECT * FROM f_topics WHERE topic_id=?");
    $stmt->execute([(int)$topic_id]);
    $this_topic = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("UPDATE f_topics SET forum_id=? WHERE topic_id=? LIMIT 1");
    $stmt->execute([(int)$to_fid, (int)$topic_id]);
    $stmt = $pdo->prepare("UPDATE f_forums SET num_topics=num_topics-1,num_posts=num_posts-? WHERE forum_id=? LIMIT 1");
    $stmt->execute([(int)$this_topic['num_replies'], (int)$from_fid]);
    $stmt = $pdo->prepare("UPDATE f_forums SET num_topics=num_topics+1,num_posts=num_posts+? WHERE forum_id=? LIMIT 1");
    $stmt->execute([(int)$this_topic['num_replies'], (int)$from_fid]);
}
function moveup($cat_id,$forum_id=0){
    global $realmDbMap;
    $pdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
    if($forum_id>0){
        $stmt = $pdo->prepare("SELECT disp_position FROM f_forums WHERE forum_id=?");
        $stmt->execute([(int)$forum_id]);
        $cur_pos = $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT * FROM f_forums WHERE disp_position<? AND forum_id=? ORDER BY disp_position DESC LIMIT 1");
        $stmt->execute([$cur_pos, (int)$forum_id]);
        $target_pos = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("UPDATE f_forums SET disp_position=? WHERE forum_id=? LIMIT 1");
        $stmt->execute([$target_pos['disp_position'], (int)$forum_id]);
        $stmt->execute([$cur_pos, (int)$target_pos['forum_id']]);
    }else{
        $stmt = $pdo->prepare("SELECT cat_disp_position FROM f_categories WHERE cat_id=?");
        $stmt->execute([(int)$cat_id]);
        $cur_pos = $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT * FROM f_categories WHERE cat_disp_position<? ORDER BY cat_disp_position DESC LIMIT 1");
        $stmt->execute([$cur_pos]);
        $target_pos = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("UPDATE f_categories SET cat_disp_position=? WHERE cat_id=? LIMIT 1");
        $stmt->execute([$target_pos['cat_disp_position'], (int)$cat_id]);
        $stmt->execute([$cur_pos, (int)$target_pos['cat_id']]);
    }
}
function movedown($cat_id,$forum_id=0){
    global $realmDbMap;
    $pdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
    if($forum_id>0){
        $stmt = $pdo->prepare("SELECT disp_position FROM f_forums WHERE forum_id=?");
        $stmt->execute([(int)$forum_id]);
        $cur_pos = $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT * FROM f_forums WHERE disp_position>? AND forum_id=? ORDER BY disp_position ASC LIMIT 1");
        $stmt->execute([$cur_pos, (int)$forum_id]);
        $target_pos = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("UPDATE f_forums SET disp_position=? WHERE forum_id=? LIMIT 1");
        $stmt->execute([$target_pos['disp_position'], (int)$forum_id]);
        $stmt->execute([$cur_pos, (int)$target_pos['forum_id']]);
    }else{
        $stmt = $pdo->prepare("SELECT cat_disp_position FROM f_categories WHERE cat_id=?");
        $stmt->execute([(int)$cat_id]);
        $cur_pos = $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT * FROM f_categories WHERE cat_disp_position>? ORDER BY cat_disp_position ASC LIMIT 1");
        $stmt->execute([$cur_pos]);
        $target_pos = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("UPDATE f_categories SET cat_disp_position=? WHERE cat_id=? LIMIT 1");
        $stmt->execute([$target_pos['cat_disp_position'], (int)$cat_id]);
        $stmt->execute([$cur_pos, (int)$target_pos['cat_id']]);
    }
}
function delete_cat($cat_id){
    global $realmDbMap;
    $pdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
    $stmt = $pdo->prepare("SELECT forum_id FROM f_forums WHERE cat_id=?");
    $stmt->execute([(int)$cat_id]);
    $cat_forums = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ($cat_forums as $forum_id) {
        delete_forum($forum_id);
    }
    $stmt = $pdo->prepare("DELETE FROM f_categories WHERE cat_id=?");
    $stmt->execute([(int)$cat_id]);
}
function delete_forum($forum_id){
    global $realmDbMap;
    $pdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
    $stmt = $pdo->prepare("SELECT topic_id FROM f_topics WHERE forum_id=?");
    $stmt->execute([(int)$forum_id]);
    $forum_topics = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!empty($forum_topics)) {
        $placeholders = implode(',', array_fill(0, count($forum_topics), '?'));
        $stmt = $pdo->prepare("DELETE FROM f_posts WHERE topic_id IN ($placeholders)");
        $stmt->execute(array_map('intval', $forum_topics));
    }
    $stmt = $pdo->prepare("DELETE FROM f_topics WHERE forum_id=?");
    $stmt->execute([(int)$forum_id]);
    $stmt = $pdo->prepare("DELETE FROM f_forums WHERE forum_id=?");
    $stmt->execute([(int)$forum_id]);
}
?>
