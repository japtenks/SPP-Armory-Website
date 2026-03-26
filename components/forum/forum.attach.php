<?php
if(INCLUDED!==true)exit;
$_GET['nobody'] = 1;

if($user['g_use_attach']==1){
    $all_attachs_size = 0;
    $all_attachs_count = 0;
    $attachs = array();
    $user_attach_dir = (string)$MW->getConfig->generic->attachs_path.$user['id'].'/';
    if(!file_exists($user_attach_dir))mkdir($user_attach_dir);

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

    if($_GET['action']=='upload' && $this['allowupload']===true){
        $tmpattach = $_FILES['attach'];
        $allowed_ext = explode('|',(string)$MW->getConfig->generic->allowed_attachs);
        if(is_uploaded_file($tmpattach['tmp_name'])){
            if($tmpattach['size'] <= (int)$MW->getConfig->generic->max_attachs_size-$all_attachs_size){
                $ext = strtolower(substr(strrchr($tmpattach['name'],'.'), 1));
                if(in_array($ext,$allowed_ext)){
                    if(@move_uploaded_file($tmpattach['tmp_name'], $user_attach_dir.$tmpattach['name'])){
                        $stmtIns = $attachPdo->prepare("INSERT INTO f_attachs (attach_file,attach_location,attach_date,attach_tid,attach_member_id,attach_filesize) VALUES (?,?,?,?,?,?)");
                        $stmtIns->execute([
                            $tmpattach['name'],
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
        if($thisattach['attach_id']){
            $stmtHit = $attachPdo->prepare("UPDATE f_attachs SET attach_hits=attach_hits+1 WHERE attach_id=?");
            $stmtHit->execute([(int)$thisattach['attach_id']]);
        }
        redirect($thisattach['attach_location'].$thisattach['attach_file'],1);
    }elseif($_GET['action']=='delete' && $_GET['attid']){
        $stmtDel = $attachPdo->prepare("SELECT * FROM f_attachs WHERE attach_id=?");
        $stmtDel->execute([(int)$_GET['attid']]);
        $thisattach = $stmtDel->fetch(PDO::FETCH_ASSOC);
        if($user['id']==$thisattach['attach_member_id'] && $thisattach['attach_id']){
            @unlink($thisattach['attach_location'].$thisattach['attach_file']);
            $stmtDlq = $attachPdo->prepare("DELETE FROM f_attachs WHERE attach_id=? LIMIT 1");
            $stmtDlq->execute([(int)$thisattach['attach_id']]);
        }
        redirect($MW->getConfig->temp->base_href.'index.php?n=forum&sub=attach&tid='.$_GET['tid'],1);
    }
}
?>
