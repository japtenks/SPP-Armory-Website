<?php
if(INCLUDED!==true)exit;

$realmPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
$stmt = $realmPdo->prepare("SELECT * FROM f_posts WHERE post_id=?");
$stmt->execute([(int)$_REQUEST['postid']]);
$content = $stmt->fetch(PDO::FETCH_ASSOC);
echo '[blockquote="'.$content['poster'].' | '.date('d-m-Y, H:i:s',$content['posted']).'"] '.my_previewreverse($content['message']).'[/blockquote]';

?>
