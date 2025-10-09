<style>
.pm-nav {
  display: flex;
  gap: 12px;
  border-bottom: 2px solid #333;
  padding-bottom: 6px;
  margin-bottom: 12px;
}
.pm-nav a {
  color: #ccc;
  text-decoration: none;
  padding: 6px 10px;
  border-radius: 4px;
  background: #1a1a1a;
  transition: 0.2s;
}
.pm-nav a:hover { background: #2c2c2c; color: #fff; }
.pm-nav a.active { background: #444; color: #fff; font-weight: bold; }

.pm-controls {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  background: #151515;
  border: 1px solid #333;
  padding: 6px 10px;
  border-radius: 6px;
  color: #bbb;
  margin-bottom: 10px;
}
.pm-controls a { color: #9cf; text-decoration: none; }
.pm-controls a:hover { text-decoration: underline; }

.modern-table.pm-table {
  width: 100%;
  border-collapse: collapse;
}
.pm-table th, .pm-table td {
  padding: 8px 10px;
  border-bottom: 1px solid #2c2c2c;
  text-align: left;
  color: #ddd;
}
.pm-table tr.read { background: rgba(0,255,0,0.03); }
.pm-table tr.unread { background: rgba(255,255,0,0.05); }
.pm-table tr:hover { background: rgba(255,255,255,0.08); }

.pm-view {
  background: #1a1a1a;
  border: 1px solid #333;
  border-radius: 8px;
  padding: 12px;
}
.pm-header {
  border-bottom: 1px solid #333;
  padding-bottom: 6px;
  margin-bottom: 12px;
  font-size: 0.95rem;
  color: #ccc;
}
.pm-body {
  display: flex;
  gap: 16px;
}
.pm-avatar img {
  width: 80px;
  border-radius: 6px;
  border: 1px solid #444;
}
.pm-message {
  background: #111;
  padding: 10px;
  border-radius: 6px;
  flex: 1;
  white-space: pre-wrap;
}
.pm-footer {
  text-align: right;
  margin-top: 10px;
}
.btn-reply {
  padding: 6px 12px;
  background: #444;
  color: #fff;
  border-radius: 4px;
  text-decoration: none;
}
.btn-reply:hover { background: #666; }

.pm-compose input[type=text],
.pm-compose textarea {
  width: 100%;
  background: #111;
  color: #ddd;
  border: 1px solid #333;
  padding: 6px;
  border-radius: 4px;
  margin-bottom: 10px;
}
.pm-buttons {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}
.btn-primary {
  background: #2c6ac8;
  color: #fff;
  border: none;
  border-radius: 4px;
  padding: 6px 14px;
  cursor: pointer;
}
.btn-primary:hover { background: #3d7cff; }

</style>



<?php builddiv_start(1, $lang['personal_messages']); ?>

<?php if ($user['id'] > 0): ?>
<div class="modern-content">

  <!-- === PM Navigation Tabs === -->
  <nav class="pm-nav">
    <a href="index.php?n=account&sub=pms&action=add" class="<?php echo ($_GET['action']=='add'?'active':''); ?>"><?php echo $lang['write'];?></a>
    <a href="index.php?n=account&sub=pms&action=view&dir=in" class="<?php echo ($_GET['action']=='view' && $_GET['dir']=='in'?'active':''); ?>"><?php echo $lang['inbox'];?></a>
    <a href="index.php?n=account&sub=pms&action=view&dir=out" class="<?php echo ($_GET['action']=='view' && $_GET['dir']=='out'?'active':''); ?>"><?php echo $lang['outbox'];?></a>
  </nav>

  <?php if ($_GET['action']=='view'): ?>
    <script>
    function Check(type) {
      const fm = document.mutliact;
      for (const e of fm.elements) {
        if (e.name === 'allbox' || e.type !== 'checkbox' || e.disabled) continue;
        e.checked = (
          type === 'all' ||
          (type === 'read' && e.classList.contains('read')) ||
          (type === 'unread' && e.classList.contains('unread'))
        );
        if (type === 'none') e.checked = false;
      }
      return false;
    }
    </script>

    <form method="post" action="index.php?n=account&sub=pms&action=delete&dir=<?php echo $_GET['dir']; ?>" name="mutliact">
      <input type="hidden" name="deletem" value="deletem">

      <div class="pm-controls">
        <span><?php echo $lang['mark']; ?>:</span>
        <a href="#" onclick="return Check('all');"><?php echo $lang['post_all'];?>(<?php echo count($items); ?>)</a> |
        <a href="#" onclick="return Check('none');"><?php echo $lang['post_none'];?></a> |
        <a href="#" onclick="return Check('read');"><?php echo $lang['post_read'];?></a> |
        <a href="#" onclick="return Check('unread');"><?php echo $lang['post_unread'];?></a>
        <span class="spacer">|</span>
        <a href="#" onclick="document.forms.mutliact.submit();return false;">[<?php echo $lang['delete_marked'];?>]</a>
        <span class="pages"><b><?php echo $lang['post_pages'];?>:</b> <?php echo $pages_str = paginate($pnum, $p, 'index.php?n=account&sub=pms'); ?></span>
      </div>

      <table class="modern-table pm-table">
        <thead>
          <tr>
            <th></th>
            <th><?php echo ($_GET['dir']=='in') ? $lang['post_from'] : $lang['post_for']; ?></th>
            <th><?php echo $lang['post_subj']; ?></th>
            <th><?php echo $lang['time']; ?></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($items as $item): ?>
          <tr class="<?php echo ($item['showed']=='1'?'read':'unread'); ?>">
            <td><input type="checkbox" name="checkpm[]" value="<?php echo $item['id']; ?>" class="<?php echo ($item['showed']=='1'?'read':'unread'); ?>"></td>
            <td>
              <?php if ($_GET['dir'] == 'in'): ?>
                <a href="index.php?n=account&sub=view&action=find&name=<?php echo $item['sender']; ?>"><?php echo $item['sender']; ?></a>
              <?php else: ?>
                <a href="index.php?n=account&sub=view&action=find&name=<?php echo $item['for']; ?>"><?php echo $item['for']; ?></a>
              <?php endif; ?>
            </td>
            <td>
              <a href="index.php?n=account&sub=pms&action=viewpm&dir=<?php echo $_GET['dir']; ?>&iid=<?php echo $item['id']; ?>">
                <?php echo htmlspecialchars($item['subject']); ?>
              </a>
            </td>
            <td><?php echo date('d-m-Y, H:i',$item['posted']);?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </form>

  <?php elseif($_GET['action']=='viewpm' && $_GET['iid']): ?>
    <div class="pm-view">
      <div class="pm-header">
        <strong><?php echo $lang['post_from']; ?>:</strong>
        <a href="index.php?n=account&sub=view&action=find&name=<?php echo $senderinfo['username'];?>"><?php echo $senderinfo['username'];?></a>
        <span>→</span>
        <strong><?php echo $lang['post_for']; ?>:</strong> <?php echo $user['username']; ?>
      </div>

      <div class="pm-body">
        <div class="pm-avatar">
          <img src="<?php echo (string)$MW->getConfig->generic->avatar_path.$senderinfo['avatar'];?>" alt="avatar"/>
          <div class="pm-meta">
            <b><?php echo $senderinfo['g_title'];?></b><br/>
            <small><?php echo date('d F Y, H:i',$item['posted']);?></small>
          </div>
        </div>
        <div class="pm-message">
          <?php echo $item['message']; ?>
        </div>
      </div>

      <div class="pm-footer">
        <a href="index.php?n=account&sub=pms&action=add&reply=<?php echo $item['id']; ?>" class="btn-reply"><?php echo $lang['post_reply'];?></a>
      </div>
    </div>

  <?php elseif($_GET['action']=='add'): ?>
    <div class="pm-compose">
      <form method="post" action="index.php?n=account&sub=pms&action=add" name="mutliact" onsubmit="return this.owner.value && this.title.value;">
        <label><?php echo $lang['post_for'];?>:</label>
        <input type="text" name="owner" id="owner" value="<?php echo $content['sender'];?>" maxlength="80"/>

        <label><?php echo $lang['post_subj'];?>:</label>
        <input type="text" name="title" id="title" value="<?php echo $content['subject'];?>" maxlength="80"/>

        <?php write_form_tool(); ?>

        <textarea name="message" id="input_comment"><?php echo $content['message'];?></textarea>
        <div class="pm-buttons">
          <input type="submit" value="<?php echo $lang['editor_send'];?>" class="btn-primary" />
          <input type="reset" value="<?php echo $lang['editor_clear'];?>" />
        </div>
      </form>
    </div>
  <?php endif; ?>

</div>
<?php endif; ?>

<?php builddiv_end(); ?>
