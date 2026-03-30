<?php
if(INCLUDED!==true)exit;
$_GET['nobody'] = 1;
require_once(__DIR__ . '/forum.func.php');

if (!function_exists('spp_attachment_storage_dir')) {
    function spp_attachment_storage_dir($basePath, $memberId) {
        return rtrim((string)$basePath, '/\\') . '/' . (int)$memberId . '/';
    }
}

if (!function_exists('spp_attachment_safe_filename')) {
    function spp_attachment_safe_filename($originalName, $extension, $targetDir) {
        $pathInfo = pathinfo((string)$originalName);
        $baseName = trim((string)($pathInfo['filename'] ?? 'attachment'));
        $baseName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $baseName);
        $baseName = trim($baseName, '._-');
        if ($baseName === '') {
            $baseName = 'attachment';
        }

        $safeExtension = strtolower((string)$extension);
        $candidate = $baseName . '.' . $safeExtension;
        while (file_exists(rtrim((string)$targetDir, '/\\') . '/' . $candidate)) {
            $candidate = $baseName . '-' . bin2hex(random_bytes(4)) . '.' . $safeExtension;
        }

        return $candidate;
    }
}

if (!function_exists('spp_attachment_file_path')) {
    function spp_attachment_file_path($basePath, array $attachment) {
        return spp_attachment_storage_dir($basePath, (int)($attachment['attach_member_id'] ?? 0)) . basename((string)($attachment['attach_file'] ?? ''));
    }
}

if (!function_exists('spp_user_can_access_attachment')) {
    function spp_user_can_access_attachment(PDO $pdo, array $attachment, array $user) {
        if (empty($attachment['attach_id'])) {
            return false;
        }

        if ((int)($user['g_forum_moderate'] ?? 0) === 1 || (int)($user['id'] ?? 0) === (int)($attachment['attach_member_id'] ?? 0)) {
            return true;
        }

        $topicId = (int)($attachment['attach_tid'] ?? 0);
        if ($topicId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare("
            SELECT f_topics.topic_id, f_forums.*
            FROM f_topics
            JOIN f_forums ON f_forums.forum_id = f_topics.forum_id
            WHERE f_topics.topic_id = ?
            LIMIT 1
        ");
        $stmt->execute([$topicId]);
        $topic = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$topic) {
            return false;
        }

        if ((int)($topic['hidden'] ?? 0) === 1) {
            return false;
        }

        $realmMap = $GLOBALS['realmDbMap'] ?? array();
        $realmId = is_array($realmMap) && !empty($realmMap) ? spp_resolve_realm_id($realmMap) : 1;

        return check_forum_scope($topic, $realmId);
    }
}

if($user['g_use_attach']==1){
    $all_attachs_size = 0;
    $all_attachs_count = 0;
    $attachs = array();
    $user_attach_dir = spp_attachment_storage_dir((string)$MW->getConfig->generic->attachs_path, (int)$user['id']);
    if(!file_exists($user_attach_dir))mkdir($user_attach_dir, 0775, true);

    $attachPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
    $stmtAt = $attachPdo->prepare("SELECT * FROM f_attachs WHERE attach_member_id=? ORDER BY attach_date DESC");
    $stmtAt->execute([(int)$user['id']]);
    $attachs = $stmtAt->fetchAll(PDO::FETCH_ASSOC);
    foreach($attachs as $i=>$attach){
        $attachs[$i]['ext'] = strtolower(substr(strrchr($attach['attach_file'],'.'), 1));
        $attachs[$i]['goodsize'] = return_good_size($attach['attach_filesize']);
        $all_attachs_size += $attach['attach_filesize'];
        $all_attachs_count++;
    }

    if($all_attachs_size <= (int)$MW->getConfig->generic->max_attachs_size)$this['allowupload'] = true;else $this['allowupload'] = false;
    $this['goodsize'] = return_good_size($all_attachs_size);
    $this['maxfilesize'] = return_good_size((int)$MW->getConfig->generic->max_attachs_size-$all_attachs_size);
    $this['forum_csrf_token'] = spp_forum_csrf_token();

    if($_GET['action']=='upload' && $this['allowupload']===true){
        spp_forum_require_csrf();
        $tmpattach = $_FILES['attach'];
        $allowed_ext = explode('|',(string)$MW->getConfig->generic->allowed_attachs);
        if(is_uploaded_file($tmpattach['tmp_name'])){
            if($tmpattach['size'] <= (int)$MW->getConfig->generic->max_attachs_size-$all_attachs_size){
                $ext = strtolower(substr(strrchr($tmpattach['name'],'.'), 1));
                if(in_array($ext,$allowed_ext)){
                    $storedFilename = spp_attachment_safe_filename($tmpattach['name'], $ext, $user_attach_dir);
                    if(@move_uploaded_file($tmpattach['tmp_name'], $user_attach_dir.$storedFilename)){
                        $stmtIns = $attachPdo->prepare("INSERT INTO f_attachs (attach_file,attach_location,attach_date,attach_tid,attach_member_id,attach_filesize) VALUES (?,?,?,?,?,?)");
                        $stmtIns->execute([
                            $storedFilename,
                            $user_attach_dir,
                            time(),
                            (int)($_GET['tid'] ?? 0),
                            (int)$user['id'],
                            (int)$tmpattach['size'],
                        ]);
                        redirect($MW->getConfig->temp->base_href.'index.php?n=forum&sub=attach&tid='.$_GET['tid'],1);
                    }
                }
            }
        }
    }elseif($_GET['action']=='download' && $_GET['attid']){
        $stmtDl = $attachPdo->prepare("SELECT * FROM f_attachs WHERE attach_id=?");
        $stmtDl->execute([(int)$_GET['attid']]);
        $thisattach = $stmtDl->fetch(PDO::FETCH_ASSOC);
        if($thisattach['attach_id'] && spp_user_can_access_attachment($attachPdo, $thisattach, $user)){
            $stmtHit = $attachPdo->prepare("UPDATE f_attachs SET attach_hits=attach_hits+1 WHERE attach_id=?");
            $stmtHit->execute([(int)$thisattach['attach_id']]);
            redirect(spp_attachment_file_path((string)$MW->getConfig->generic->attachs_path, $thisattach),1);
        }
        redirect($MW->getConfig->temp->base_href.'index.php?n=forum',1);
    }elseif($_GET['action']=='delete' && $_GET['attid']){
        spp_forum_require_csrf();
        $stmtDel = $attachPdo->prepare("SELECT * FROM f_attachs WHERE attach_id=?");
        $stmtDel->execute([(int)$_GET['attid']]);
        $thisattach = $stmtDel->fetch(PDO::FETCH_ASSOC);
        if($user['id']==$thisattach['attach_member_id'] && $thisattach['attach_id']){
            @unlink(spp_attachment_file_path((string)$MW->getConfig->generic->attachs_path, $thisattach));
            $stmtDlq = $attachPdo->prepare("DELETE FROM f_attachs WHERE attach_id=? LIMIT 1");
            $stmtDlq->execute([(int)$thisattach['attach_id']]);
        }
        redirect($MW->getConfig->temp->base_href.'index.php?n=forum&sub=attach&tid='.$_GET['tid'],1);
    }
}
?>
