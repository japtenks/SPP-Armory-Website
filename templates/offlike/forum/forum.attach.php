<?php if (!defined('Armory')) { define('Armory', 1); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="<?php echo $MW->getConfig->generic->site_encoding; ?>" />
<title><?php echo $MW->getConfig->generic->site_title . ' - Attachments'; ?></title>


<style>

/* ---------- Upload Box ---------- */
.attach-upload {
  border: 2px dashed #555;
  background: #0f0f0f;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  text-align: left;
}
.attach-upload img {
  float: right;
  height: 22px;
  cursor: pointer;
}
.attach-upload input[type=file] {
  background: #111;
  border: 1px solid #444;
  color: #ccc;
  padding: 6px;
  border-radius: 6px;
  margin-top: 6px;
}
.attach-upload input[type=submit] {
  background: #ffcc66;
  border: none;
  color: #111;
  font-weight: bold;
  border-radius: 6px;
  padding: 6px 12px;
  cursor: pointer;
  margin-left: 8px;
}
.attach-upload input[type=submit]:hover {
  background: #ffd97a;
}

/* ---------- Attachment List ---------- */
.attach-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.attach-item {
  display: grid;
  grid-template-columns: 40px 1.5fr 80px 70px 140px 40px;
  align-items: center;
  background: #1b1b1b;
  padding: 8px 10px;
  border-radius: 6px;
  border: 1px solid #222;
  transition: background 0.25s;
}
.attach-item:hover {
  background: rgba(255,204,102,0.08);
}
.attach-item img {
  height: 20px;
}
.attach-item a {
  color: #66aaff;
  text-decoration: none;
}
.attach-item a:hover {
  color: #ffd97a;
}

/* ---------- Footer ---------- */
.attach-footer {
  text-align: right;
  font-size: 0.85rem;
  color: #aaa;
  border-top: 1px solid #222;
  margin-top: 10px;
  padding-top: 8px;
}
</style>

<script>
function selectattach(id) {
  const target = opener?.document?.getElementById('input_comment');
  if (target) target.value += '[attach=' + id + ']';
  window.close();
}
</script>
</head>

<body>
<div class="modern-wrapper">
  <div class="modern-header"><?php echo $lang['attachments'] ?? 'Attachments'; ?></div>

  <div class="modern-content">

    <?php if ($user['g_use_attach'] == 1): ?>

      <?php if ($this['allowupload'] === true): ?>
      <form method="post" enctype="multipart/form-data"
            action="index.php?n=forum&sub=attach&action=upload&tid=<?php echo $_GET['tid']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($this['forum_csrf_token'] ?? '')); ?>">
        <div class="attach-upload">
          <img src="<?php echo $currtmp; ?>/images/cancel_f2.png" alt="Close" onclick="window.close();" />
          <div>
            <strong><?php Lang('file'); ?>:</strong>
            <input type="file" name="attach" size="36" />
            <input type="submit" value="<?php Lang('upload'); ?>" />
            <br><br>
            <?php Lang('max_file_size'); ?>:
            <?php echo $this['maxfilesize']; ?> &nbsp; | &nbsp;
            <?php Lang('allowed_ext'); ?>:
            <?php echo $MW->getConfig->generic->allowed_attachs; ?>
          </div>
        </div>
      </form>
      <?php endif; ?>

      <div class="attach-list">
        <?php foreach ($attachs as $attach): ?>
        <div class="attach-item">
          <img src="/templates/offlike/images/mime/<?php echo $attach['ext']; ?>.png" alt="">
          <a href="javascript:selectattach('<?php echo $attach['attach_id']; ?>');">
            <?php echo htmlspecialchars($attach['attach_file']); ?>
          </a>
          <span><?php echo $attach['goodsize']; ?></span>
          <span><?php echo $attach['attach_hits']; ?></span>
          <span><?php echo date('d-m-Y, H:i', $attach['attach_date']); ?></span>
          <a href="index.php?n=forum&sub=attach&action=delete&attid=<?php echo $attach['attach_id']; ?>&tid=<?php echo $_GET['tid']; ?>&csrf_token=<?php echo urlencode((string)($this['forum_csrf_token'] ?? '')); ?>">
            <img src="<?php echo $currtmp; ?>/images/trash.png" alt="Delete" title="Delete Attachment">
          </a>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="attach-footer">
        <?php echo $lang['files_summary']; ?>:
        <?php echo $all_attachs_count; ?> &nbsp; |
        <?php echo $lang['size_summary']; ?>:
        <?php echo $this['goodsize']; ?>
      </div>

    <?php else: ?>
      <p style="color:#c33;">You do not have permission to manage attachments.</p>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
