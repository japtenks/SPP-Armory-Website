<br>
<?php builddiv_start(1, isset($lang['media']) ? $lang['media'] : 'Media'); ?>
<div style="padding:14px 18px; line-height:1.6;">
  <p>The old screenshot and wallpaper galleries are retired and no longer part of the active site.</p>
  <p><a href="index.php?n=server&sub=downloads">Browse downloads</a></p>
  <?php if (!empty($user['id']) && !empty($user['character_name'])) { ?>
    <p><a href="index.php?n=media&sub=addgalwallp">Upload a wallpaper</a></p>
  <?php } else { ?>
    <p>Wallpaper uploads are still available from your account when you are signed in with a selected character.</p>
  <?php } ?>
</div>
<?php builddiv_end(); ?>
