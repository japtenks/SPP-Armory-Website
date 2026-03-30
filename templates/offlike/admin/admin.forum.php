<br>
<?php builddiv_start(0, $lang['forums']) ?>
<style type="text/css">
.forum-admin { color: #f4efe2; }
.forum-admin__intro {
    margin-bottom: 18px;
    padding: 16px 18px;
    border: 1px solid rgba(230, 193, 90, 0.18);
    border-radius: 14px;
    background: rgba(10, 12, 18, 0.55);
    line-height: 1.6;
}
.forum-admin__stack { display: grid; gap: 16px; }
.forum-admin__card {
    padding: 18px;
    border: 1px solid rgba(230, 193, 90, 0.18);
    border-radius: 16px;
    background: linear-gradient(180deg, rgba(20, 24, 34, 0.82), rgba(10, 12, 18, 0.92));
}
.forum-admin__card h3 {
    margin: 0 0 6px;
    color: #ffca5a;
    font-size: 18px;
}
.forum-admin__subtext {
    margin: 0 0 16px;
    color: #cfc7b8;
}
.forum-admin__row {
    display: flex;
    gap: 16px;
    justify-content: space-between;
    align-items: flex-start;
    padding: 14px 16px;
    border: 1px solid rgba(230, 193, 90, 0.12);
    border-radius: 14px;
    background: rgba(255, 198, 87, 0.05);
}
.forum-admin__row + .forum-admin__row { margin-top: 12px; }
.forum-admin__main { flex: 1 1 auto; }
.forum-admin__title {
    margin: 0 0 6px;
    color: #f6f0e5;
    font-weight: 700;
}
.forum-admin__title a {
    color: #6fb2ff;
    text-decoration: none;
}
.forum-admin__meta,
.forum-admin__desc {
    color: #cfc7b8;
    line-height: 1.5;
}
.forum-admin__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
    min-width: 220px;
}
.forum-admin__rename {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 10px 0 0;
    flex-wrap: wrap;
}
.forum-admin__rename input {
    box-sizing: border-box;
    min-width: 240px;
    padding: 8px 10px;
    border: 1px solid rgba(230, 193, 90, 0.2);
    border-radius: 10px;
    background: rgba(7, 10, 16, 0.85);
    color: #f4efe2;
}
.forum-admin__pill,
.forum-admin__pill:visited {
    display: inline-block;
    padding: 8px 10px;
    border: 1px solid rgba(230, 193, 90, 0.2);
    border-radius: 10px;
    background: rgba(255, 198, 87, 0.1);
    color: #f4efe2;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
}
.forum-admin__pill--danger {
    border-color: rgba(210, 82, 82, 0.35);
    background: rgba(176, 47, 47, 0.18);
}
.forum-admin__order {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
}
.forum-admin__order input,
.forum-admin__field input {
    box-sizing: border-box;
    padding: 10px 12px;
    border: 1px solid rgba(230, 193, 90, 0.2);
    border-radius: 10px;
    background: rgba(7, 10, 16, 0.85);
    color: #f4efe2;
}
.forum-admin__order input { width: 64px; }
.forum-admin__grid-form {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px 16px;
}
.forum-admin__field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.forum-admin__field label {
    color: #c9a45a;
    font-size: 12px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.forum-admin__field--wide { grid-column: span 2; }
.forum-admin__button {
    display: inline-block;
    padding: 10px 14px;
    border: 1px solid rgba(230, 193, 90, 0.24);
    border-radius: 10px;
    background: rgba(255, 198, 87, 0.12);
    color: #f6f0e5;
    font-weight: 700;
}
.forum-admin__button--compact {
    padding: 8px 12px;
    font-size: 12px;
}
.forum-admin__table {
    width: 100%;
    border-collapse: collapse;
}
.forum-admin__table th,
.forum-admin__table td {
    padding: 12px 10px;
    border-bottom: 1px solid rgba(230, 193, 90, 0.12);
    text-align: left;
    vertical-align: top;
}
.forum-admin__table th {
    color: #c9a45a;
    font-size: 12px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.forum-admin__table td:last-child,
.forum-admin__table th:last-child { text-align: right; }
@media (max-width: 980px) {
    .forum-admin__row { flex-direction: column; }
    .forum-admin__actions { justify-content: flex-start; min-width: 0; }
    .forum-admin__grid-form { grid-template-columns: 1fr; }
    .forum-admin__field--wide { grid-column: span 1; }
}
</style>
<div class="forum-admin">
<?php if (empty($_GET['action'])) { ?>
  <?php if (isset($_GET['forum_id']) && isset($_GET['topic_id'])) { ?>
    <div class="forum-admin__card">
      <h3>Topic Posts</h3>
      <p class="forum-admin__subtext">Reviewing <strong><?php echo htmlspecialchars($this_topic['topic_name']); ?></strong> inside <strong><?php echo htmlspecialchars($this_forum['forum_name']); ?></strong>.</p>
      <div class="forum-admin__actions" style="margin-bottom:16px;">
        <a class="forum-admin__pill" href="index.php?n=admin&amp;sub=forum&amp;forum_id=<?php echo (int)$_GET['forum_id']; ?>">Back to Topics</a>
        <a class="forum-admin__pill" href="index.php?n=forum&amp;sub=viewtopic&amp;tid=<?php echo (int)$_GET['topic_id']; ?>" target="_blank">View Topic</a>
        <a class="forum-admin__pill forum-admin__pill--danger" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'forum_id' => (int)$_GET['forum_id'], 'topic_id' => (int)$_GET['topic_id'], 'action' => 'deletetopic'))); ?>" onclick="return confirm('Delete entire topic and all its posts?');">Delete Topic</a>
      </div>
      <table class="forum-admin__table">
        <thead><tr><th>Post</th><th>Author</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item) { ?>
          <tr>
            <td><?php echo nl2br(htmlspecialchars($item['excerpt'])); ?><?php if (strlen($item['excerpt']) >= 120) echo '...'; ?></td>
            <td><?php echo htmlspecialchars($item['poster']); ?></td>
            <td><?php echo date('M d, Y H:i', $item['posted']); ?></td>
            <td><a class="forum-admin__pill forum-admin__pill--danger" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'forum_id' => (int)$_GET['forum_id'], 'topic_id' => (int)$_GET['topic_id'], 'post_id' => (int)$item['post_id'], 'action' => 'deletepost'))); ?>" onclick="return confirm('Delete this post?');">Delete</a></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } elseif (isset($_GET['forum_id']) && !isset($_GET['cat_id'])) { ?>
    <div class="forum-admin__card">
      <h3>Forum Topics</h3>
      <p class="forum-admin__subtext">Managing topics inside <strong><?php echo htmlspecialchars($this_forum['forum_name']); ?></strong>.</p>
      <div class="forum-admin__actions" style="margin-bottom:16px;">
        <a class="forum-admin__pill" href="index.php?n=admin&amp;sub=forum">Back to Categories</a>
      </div>
      <table class="forum-admin__table">
        <thead><tr><th>Topic</th><th>Posted By</th><th>Date</th><th>Replies</th><th>Action</th></tr></thead>
        <tbody>
        <?php if (empty($items)) { ?><tr><td colspan="5"><em>No topics yet.</em></td></tr><?php } ?>
        <?php foreach ($items as $item) { ?>
          <tr>
            <td><a class="forum-admin__title" href="index.php?n=admin&amp;sub=forum&amp;forum_id=<?php echo (int)$_GET['forum_id']; ?>&amp;topic_id=<?php echo (int)$item['topic_id']; ?>"><?php echo htmlspecialchars($item['topic_name']); ?></a></td>
            <td><?php echo htmlspecialchars($item['topic_poster']); ?></td>
            <td><?php echo date('M d, Y', $item['topic_posted']); ?></td>
            <td><?php echo (int)$item['num_replies']; ?></td>
            <td><a class="forum-admin__pill forum-admin__pill--danger" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'forum_id' => (int)$_GET['forum_id'], 'topic_id' => (int)$item['topic_id'], 'action' => 'deletetopic'))); ?>" onclick="return confirm('Delete this topic and all its posts?');">Delete</a></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } elseif (isset($_GET['cat_id'])) { ?>
    <div class="forum-admin__card">
      <h3>Forums In Section</h3>
      <p class="forum-admin__subtext">Tune ordering, visibility, and topic access from one cleaner view.</p>
      <form method="post" action="index.php?n=admin&amp;sub=forum&amp;action=updforumsorder" class="forum-admin__stack">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_admin_forum_csrf_token()); ?>">
        <?php foreach ($items as $item_c => $item) { ?>
          <div class="forum-admin__row">
            <div class="forum-admin__main">
              <p class="forum-admin__title"><a href="index.php?n=admin&amp;sub=forum&amp;forum_id=<?php echo (int)$item['forum_id']; ?>"><?php echo htmlspecialchars($item['forum_name']); ?></a></p>
              <form method="post" action="index.php?n=admin&amp;sub=forum&amp;action=renameforum" class="forum-admin__rename">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_admin_forum_csrf_token()); ?>">
                <input type="hidden" name="forum_id" value="<?php echo (int)$item['forum_id']; ?>">
                <input type="text" name="forum_name" value="<?php echo htmlspecialchars($item['forum_name']); ?>">
                <input class="forum-admin__button forum-admin__button--compact" type="submit" value="Rename">
              </form>
              <p class="forum-admin__desc"><?php echo htmlspecialchars($item['forum_desc']); ?></p>
              <div class="forum-admin__order">
                <span><?php echo $lang['order']; ?></span>
                <input type="text" name="forumorder[<?php echo (int)$item['forum_id']; ?>]" value="<?php echo (int)$item['disp_position']; ?>">
                <?php if ($item_c > 0) { ?><a class="forum-admin__pill" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'action' => 'moveup', 'cat_id' => (int)$item['cat_id'], 'forum_id' => (int)$item['forum_id']))); ?>">Move Up</a><?php } ?>
                <?php if ($item_c < count($items) - 1) { ?><a class="forum-admin__pill" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'action' => 'movedown', 'cat_id' => (int)$item['cat_id'], 'forum_id' => (int)$item['forum_id']))); ?>">Move Down</a><?php } ?>
              </div>
            </div>
            <div class="forum-admin__actions">
              <?php if ($item['closed'] == 0) { ?><a class="forum-admin__pill" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'action' => 'close', 'forum_id' => (int)$item['forum_id']))); ?>">Close</a><?php } ?>
              <?php if ($item['closed'] == 1) { ?><a class="forum-admin__pill" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'action' => 'open', 'forum_id' => (int)$item['forum_id']))); ?>">Open</a><?php } ?>
              <?php if ($item['hidden'] == 0) { ?><a class="forum-admin__pill" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'action' => 'hide', 'forum_id' => (int)$item['forum_id']))); ?>">Hide</a><?php } ?>
              <?php if ($item['hidden'] == 1) { ?><a class="forum-admin__pill" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'action' => 'show', 'forum_id' => (int)$item['forum_id']))); ?>">Show</a><?php } ?>
              <a class="forum-admin__pill" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'action' => 'recount', 'forum_id' => (int)$item['forum_id']))); ?>">Recount</a>
              <a class="forum-admin__pill" href="index.php?n=admin&amp;sub=forum&amp;forum_id=<?php echo (int)$item['forum_id']; ?>">Topics</a>
              <a class="forum-admin__pill forum-admin__pill--danger" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'action' => 'deleteforum', 'forum_id' => (int)$item['forum_id']))); ?>" onclick="return confirm('Are you sure?');">Delete</a>
            </div>
          </div>
        <?php } ?>
        <div class="forum-admin__actions"><input class="forum-admin__button" type="submit" value="<?php echo $lang['doupdate']; ?>"></div>
      </form>
    </div>

    <div class="forum-admin__card">
      <h3>Create New Forum</h3>
      <form method="post" action="index.php?n=admin&amp;sub=forum&amp;action=newforum" class="forum-admin__grid-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_admin_forum_csrf_token()); ?>">
        <input type="hidden" name="cat_id" value="<?php echo (int)$_GET['cat_id']; ?>">
        <div class="forum-admin__field"><label><?php echo $lang['l_name']; ?></label><input type="text" name="forum_name"></div>
        <div class="forum-admin__field forum-admin__field--wide"><label><?php echo $lang['l_desc']; ?></label><input type="text" name="forum_desc"></div>
        <div class="forum-admin__field"><label><?php echo $lang['order']; ?></label><input type="text" name="disp_position" value="<?php echo count($items) + 1; ?>"></div>
        <div class="forum-admin__actions" style="grid-column:1 / -1;"><input class="forum-admin__button" type="submit" value="<?php echo $lang['donewforum']; ?>"></div>
      </form>
    </div>
  <?php } else { ?>
    <div class="forum-admin__intro">
      Forum sections are your top-level buckets. Realm-specific spaces can live as scoped forums inside each section.
    </div>
    <div class="forum-admin__card">
      <h3>Forum Sections</h3>
      <div class="forum-admin__stack">
        <?php foreach ($items as $item_c => $item) { ?>
          <div class="forum-admin__row">
            <div class="forum-admin__main">
              <p class="forum-admin__title"><a href="index.php?n=admin&amp;sub=forum&amp;cat_id=<?php echo (int)$item['cat_id']; ?>"><?php echo htmlspecialchars($item['cat_name']); ?></a></p>
              <form method="post" action="index.php?n=admin&amp;sub=forum&amp;action=renamecat" class="forum-admin__rename">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_admin_forum_csrf_token()); ?>">
                <input type="hidden" name="cat_id" value="<?php echo (int)$item['cat_id']; ?>">
                <input type="text" name="cat_name" value="<?php echo htmlspecialchars($item['cat_name']); ?>">
                <input class="forum-admin__button forum-admin__button--compact" type="submit" value="Rename">
              </form>
              <div class="forum-admin__order">
                <span><?php echo $lang['order']; ?></span>
                <input type="text" value="<?php echo (int)$item['cat_disp_position']; ?>" readonly>
                <?php if ($item_c > 0) { ?><a class="forum-admin__pill" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'action' => 'moveup', 'cat_id' => (int)$item['cat_id']))); ?>">Move Up</a><?php } ?>
                <?php if ($item_c < count($items) - 1) { ?><a class="forum-admin__pill" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'action' => 'movedown', 'cat_id' => (int)$item['cat_id']))); ?>">Move Down</a><?php } ?>
              </div>
            </div>
            <div class="forum-admin__actions">
              <a class="forum-admin__pill" href="index.php?n=admin&amp;sub=forum&amp;cat_id=<?php echo (int)$item['cat_id']; ?>">Open Forums</a>
              <a class="forum-admin__pill forum-admin__pill--danger" href="<?php echo htmlspecialchars(spp_admin_forum_action_url(array('n' => 'admin', 'sub' => 'forum', 'action' => 'deletecat', 'cat_id' => (int)$item['cat_id']))); ?>" onclick="return confirm('Are you sure?');">Delete</a>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
    <div class="forum-admin__card">
      <h3>Create New Forum Section</h3>
      <form method="post" action="index.php?n=admin&amp;sub=forum&amp;action=newcat" class="forum-admin__grid-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_admin_forum_csrf_token()); ?>">
        <div class="forum-admin__field"><label><?php echo $lang['l_name']; ?></label><input type="text" name="cat_name"></div>
        <div class="forum-admin__field"><label><?php echo $lang['order']; ?></label><input type="text" name="cat_disp_position" value="<?php echo count($items) + 1; ?>"></div>
        <div class="forum-admin__actions" style="grid-column:1 / -1;"><input class="forum-admin__button" type="submit" value="Create New Forum Section"></div>
      </form>
    </div>
  <?php } ?>
<?php } ?>
</div>
<?php builddiv_end() ?>
