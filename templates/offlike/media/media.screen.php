<br>
<?php builddiv_start(1, $lang['screen']) ?>
<?php
    if (isset($_POST['doadd'])){
     $img=isset($_FILES["filename"]["name"]) ? $_FILES["filename"]["name"] : '';
     $comment=isset($_POST['message']) ? $_POST['message'] : '';
     $autor=$user['character_name'];
     $date=date("Y-m-d");
     if($_FILES["filename"]["size"] > (1024*$screensize*1024) ) {
     echo $lang['Filesizes'];
	 echo " ";
	 echo $screensize;
	 echo Mb;
     exit; }
     if(!in_array($_FILES["filename"]["type"], array("image/jpeg", "image/pjpeg", "image/jpg"))) {
     echo $lang['Filetype'];
     echo ("<br/>");
     exit;
     }
     $galleryPdoSc = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
     $stmtScCheck = $galleryPdoSc->prepare("SELECT count(*) FROM `gallery` WHERE img=? AND cat='screenshot'");
     $stmtScCheck->execute([$img]);
     if($stmtScCheck->fetchColumn()){
     echo $lang['ErrorFilename'];
     exit;
     }
     if(copy($_FILES["filename"]["tmp_name"],
     "./images/screenshots/".$_FILES["filename"]["name"])) {
     $stmtScIns = $galleryPdoSc->prepare("INSERT INTO gallery (img,comment,autor,date,cat) VALUES(?,?,?,?,'screenshot')");
     $stmtScIns->execute([$img, $comment, $autor, $date]);
     } else {
     echo $lang['Uploaderror']; }
    }
?>
<?php
if (!isset($galleryPdoSc)) $galleryPdoSc = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
$gal_count = (int)$galleryPdoSc->query("SELECT count(*) FROM `gallery` WHERE cat='screenshot'")->fetchColumn();
?>


<table border = 0 width=100%>
<?php if($user['id']>=1){ ?>
<tr><td ><img src="<?php echo $currtmp; ?>/images/edit-button.gif"><a href="././index.php?n=media&sub=addgalscreen"><?php echo $lang['Addimage'];?></a></td>
<td align=right><?php echo $lang['Totalingallery'].":";?> <?php echo $gal_count; ?></td></tr>
</table>
<?php }else{ ?>
<td align=right><?php echo $lang['Totalingallery'].":";?> <?php echo $gal_count; ?></td></tr>
</table>
<style type = "text/css">
  td.serverStatus1 { border-style: solid; border-width: 0px 1px 1px 0px; border-color: #D8BF95; }
  td.serverStatus2 { border-style: solid; border-width: 0px 1px 1px 0px; border-color: #D8BF95; background-color: #C3AD89; }
  td.rankingHeader { color: #C7C7C7; font-size: 10pt; font-family: arial,helvetica,sans-serif; font-weight: bold; background-color: #2E2D2B; border-style: solid; border-width: 1px; border-color: #5D5D5D #5D5D5D #1E1D1C #1E1D1C; padding: 3px;}
</style>
<?php }
if ($gal_count) {
?>
<center>
<?php if($pnum > 1) {?>
<b>Page: <?php echo  $pages_str; ?></b>
<?php }?>
<table border=0>
<tr>
<?php
$stmtScList = $galleryPdoSc->prepare("SELECT * FROM gallery WHERE cat='screenshot' LIMIT ".(int)$limit_start.",?");
$stmtScList->execute([(int)$MW->getConfig->generic->images_per_page]);
$sql = $stmtScList->fetchAll(PDO::FETCH_ASSOC);
foreach($sql as $tablerows){
?>

<TR>
<TD ROWSPAN=3 align="center">

<table style="margin: 7px;" border="0" cellpadding="0" cellspacing="0">
<tbody>
<tr>
<td><img src="<?php echo $currtmp; ?>/images/gallery/lt.png" class="png" style="width: 9px; height: 9px;" border="0" height="9" width="9"></td>
<td background="<?php echo $currtmp; ?>/images/gallery/_t.gif"><img src="<?php echo $currtmp; ?>/images/gallery/_.gif" height="1" width="1"></td>
<td><img src="<?php echo $currtmp; ?>/images/gallery/rt.png" class="png" style="width: 11px; height: 9px;" border="0" height="9" width="11"></td>
</tr>
<tr>
<td background="<?php echo $currtmp; ?>/images/gallery/_l.gif"><img src="<?php echo $currtmp; ?>/images/gallery/_.gif" height="1" width="1"></td>
<td>
<a style="cursor: pointer;" onclick="javascript:void(window.open('./images/screenshots/<?php echo  $tablerows['img'];?>'))" target="_blank"><img style="width: 235px; height: 175px;" alt="<?php echo  $tablerows['comment'];?>"
src="show_picture.php?filename=<?php echo  $tablerows['img'];?>&gallery=screen&width=235&height=175" border="0"></a>
</td>
<td background="<?php echo $currtmp; ?>/images/gallery/_r.gif"><img src="<?php echo $currtmp; ?>/images/gallery/_.gif" height="1" width="1"></td>
</tr>
<tr>
<td><img src="<?php echo $currtmp; ?>/images/gallery/lb.png" class="png" style="width: 9px; height: 12px;" border="0" height="12" width="9"></td>
<td background="<?php echo $currtmp; ?>/images/gallery/_b.gif"><img src="<?php echo $currtmp; ?>/images/gallery/_.gif" height="1" width="1"></td>
<td><img src="<?php echo $currtmp; ?>/images/gallery/rb.png" class="png" style="width: 11px; height: 12px;" border="0" height="12" width="11"></td>
</tr>
</tbody>
</table>

</TD>
<td><?php echo  $lang['comment'].": ".$tablerows['comment'];?></td>
</TR><TR>
<td><?php echo $lang['author'].": ".$tablerows['autor'];?></td>
</TR><TR>
<td><?php echo $lang['date'].": ".$tablerows['date'];?></td>
</TR>
<TR>
<td colspan=2></td>
</TR>
<?php 
        unset($tablerows);
    }
    unset($sql);
}
else {
    echo "No Screenshots in gallery. Upload a screenshot.";
}
?>
</tr>
</table>
</center>
<?php builddiv_end() ?>
