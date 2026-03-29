<style>
/* ---------- Forum Header ---------- */
.forum-header {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
}
.forum-header h1 {
  font-size: 1.3rem;
  color: #ffcc66;
  margin: 0;
}
.forum-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.btn {
  padding: 6px 12px;
  border-radius: 6px;
  font-weight: bold;
  text-decoration: none;
  font-size: .7em;
  display: inline-block;
}
.btn.primary { background: #ffcc66; color: #111; }
.btn.secondary { background: #333; color: #ccc; }
.btn:hover { opacity: 0.9; }

.pagination {
  display: flex;
  justify-content: center;
  margin: 16px 0 0;
}

/* ---------- Topic Table ---------- */
.forum-list-head,
.forum-row {
  display: grid;
  grid-template-columns: 50px 1.8fr 1fr 0.5fr 0.5fr 1.2fr;
  align-items: center;
  gap: 8px;
  padding: 10px;
}
.forum-list-head {
  background: #222;
  color: #aaa;
  font-weight: bold;
  border-bottom: 1px solid #444;
}
.forum-list-head a {
  color: inherit;
  text-decoration: none;
}
.forum-list-head a:hover {
  color: #ffd97a;
}
.sort-indicator {
  margin-left: 4px;
  color: #c3a46a;
}
.forum-row {
  background: #111;
  border-bottom: 1px solid #333;
  transition: background 0.25s;
}
.forum-row:hover {
  background: rgba(255, 204, 102, 0.08);
}
.forum-row:nth-child(even) {
  background: #161616;
}

.col-subject a {
  color: #b0d0ff;
  text-decoration: none;
  transition: color 0.2s, text-shadow 0.3s;
}
.col-subject a:hover {
  color: #ffd97a;
  text-shadow: 0 0 8px rgba(255, 204, 102, 0.4);
}
.new-tag {
  background: #c22;
  color: #fff;
  font-size: 0.7rem;
  padding: 1px 5px;
  border-radius: 4px;
  margin-left: 6px;
}

/* ---------- Legend ---------- */
.forum-legend {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  font-size: 0.85rem;
  color: #bbb;
  padding: 12px 0 4px;
  border-top: 1px solid #222;
  margin-top: 10px;
}
.forum-legend img {
  vertical-align: middle;
  width: 18px;
  height: 18px;
  margin-right: 6px;
}

/* ---------- Centered Header Image ---------- */
img[src*="forum_top.png"] {
  display: block;
  margin: 0 auto 12px auto;
  max-width: 100%;
  height: auto;
  border-radius: 6px;
  box-shadow: 0 0 10px rgba(0,0,0,0.6);
}

.forum-header {
  background: linear-gradient(to bottom, rgba(50,30,0,0.9), rgba(15,10,0,0.85));
  border: 1px solid #5a4000;
  border-radius: 6px;
  padding: 8px 12px;
  margin: 12px 0;
}
.forum-header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
}
.forum-header-inner h1 {
  font-size: 1.2rem;
  color: #ffcc66;
  margin: 0;
}
.forum-actions.right {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

.forum-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.forum-page-size {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #c3a46a;
  font-size: 0.82rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.forum-page-size select {
  border-radius: 8px;
  border: 1px solid rgba(255, 206, 102, 0.28);
  background: rgba(20, 20, 20, 0.85);
  color: #f1f1f1;
  padding: 6px 10px;
}
</style>

<?php
if (INCLUDED !== true) exit;
if (empty($this_forum) || (int)$this_forum['forum_id'] <= 0) {
    output_message('alert', 'Invalid forum.');
    return;
}

$forumPageCount = max(1, (int)($this_forum['pnum'] ?? 1));
$forumItemsPerPage = (int)($this_forum['items_per_page'] ?? 25);
$forumSortField = (string)($this_forum['sort_field'] ?? 'last_reply');
$forumSortDir = (string)($this_forum['sort_dir'] ?? 'desc');
$forumPageBaseUrl = 'index.php?n=forum&sub=viewforum&fid=' . (int)$this_forum['forum_id'] . '&per_page=' . $forumItemsPerPage . '&sort=' . urlencode($forumSortField) . '&dir=' . urlencode($forumSortDir);

$forumSortUrl = function (string $field) use ($this_forum, $forumItemsPerPage, $forumSortField, $forumSortDir): string {
    $nextDir = ($forumSortField === $field && $forumSortDir === 'asc') ? 'desc' : 'asc';
    return 'index.php?n=forum&sub=viewforum&fid=' . (int)$this_forum['forum_id']
        . '&per_page=' . $forumItemsPerPage
        . '&sort=' . urlencode($field)
        . '&dir=' . urlencode($nextDir);
};

$forumSortLabel = function (string $field, string $label) use ($forumSortUrl, $forumSortField, $forumSortDir): string {
    $indicator = '';
    if ($forumSortField === $field) {
        $indicator = '<span class="sort-indicator">' . ($forumSortDir === 'asc' ? '&#9650;' : '&#9660;') . '</span>';
    }
    return '<a href="' . htmlspecialchars($forumSortUrl($field), ENT_QUOTES, 'UTF-8') . '">' . $label . $indicator . '</a>';
};
?>

<?php builddiv_start(1, $this_forum['forum_name'], 0, true, $this_forum['forum_id'], $this_forum['closed']); ?>

<img src="<?php echo $currtmp; ?>/images/forum_top.png" alt="Forums" class="forum-header"/>

<div class="modern-content forum-view">
  <?php
  $_vfNewsFid = (int)($MW->getConfig->generic_values->forum->news_forum_id ?? 0);
  $_vfIsNews  = $_vfNewsFid > 0 && (int)$this_forum['forum_id'] === $_vfNewsFid;
  $_vfCanPost = !$_vfIsNews || (int)($user['gmlevel'] ?? 0) >= 3;
  if ($_vfCanPost && !$this_forum['closed']):
  ?>
  <div style="margin-bottom:10px;">
    <a href="index.php?n=forum&sub=post&action=newtopic&fid=<?php echo (int)$this_forum['forum_id']; ?>" class="btn primary">New Topic</a>
  </div>
  <?php endif; ?>

  <div class="forum-toolbar">
    <div></div>
    <form method="get" action="index.php" class="forum-page-size">
      <input type="hidden" name="n" value="forum" />
      <input type="hidden" name="sub" value="viewforum" />
      <input type="hidden" name="fid" value="<?php echo (int)$this_forum['forum_id']; ?>" />
      <input type="hidden" name="sort" value="<?php echo htmlspecialchars($forumSortField, ENT_QUOTES, 'UTF-8'); ?>" />
      <input type="hidden" name="dir" value="<?php echo htmlspecialchars($forumSortDir, ENT_QUOTES, 'UTF-8'); ?>" />
      <label for="forum_per_page">Show</label>
      <select id="forum_per_page" name="per_page" onchange="this.form.submit()">
        <?php foreach (($this_forum['allowed_page_sizes'] ?? array(10, 25, 50)) as $pageSize): ?>
          <option value="<?php echo (int)$pageSize; ?>"<?php if ((int)$pageSize === $forumItemsPerPage) echo ' selected'; ?>><?php echo (int)$pageSize; ?></option>
        <?php endforeach; ?>
      </select>
      <span>topics</span>
    </form>
  </div>

  <div class="forum-list-head">
    <div></div>
    <div><?php echo $forumSortLabel('subject', 'Subject'); ?></div>
    <div><?php echo $forumSortLabel('author', 'Author'); ?></div>
    <div><?php echo $forumSortLabel('replies', 'Replies'); ?></div>
    <div><?php echo $forumSortLabel('views', 'Views'); ?></div>
    <div><?php echo $forumSortLabel('last_reply', 'Last Reply'); ?></div>
  </div>

  <?php if (empty($topics)): ?>
    <div class="forum-row">
      <div class="col-subject" style="grid-column: span 6;">No topics found.</div>
    </div>
  <?php else: ?>
    <?php foreach ($topics as $t): ?>
      <div class="forum-row">
        <div><img src="<?php echo $currtmp; ?>/images/<?php echo ((int)($user['id'] ?? 0) <= 0 || !empty($t['isnew'])) ? 'news-community.gif' : 'no-news-community.gif'; ?>" alt="Status"></div>
        <div class="col-subject">
          <a href="<?php echo $t['linktothis']; ?>">
            <?php echo htmlspecialchars($t['topic_name']); ?>
          </a>
          <?php if ($t['closed']): ?><span class="new-tag">Closed</span><?php endif; ?>
        </div>
        <div><?php echo htmlspecialchars($t['topic_author_display']); ?></div>
        <div><?php echo (int)$t['num_replies']; ?></div>
        <div><?php echo (int)$t['num_views']; ?></div>
        <div>
          <?php echo htmlspecialchars($t['last_poster']); ?><br>
          <?php echo $t['last_post']; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($forumPageCount > 1): ?>
  <div class="pagination">
    <?php echo default_paginate($forumPageCount, (int)$p, $forumPageBaseUrl); ?>
  </div>
  <?php endif; ?>

  <div class="forum-legend">
    <div>
      <img src="<?php echo $currtmp; ?>/images/news-community.gif" alt="New Posts"/>
      <?php echo $lang['newpost']; ?>
    </div>
    <div>
      <img src="<?php echo $currtmp; ?>/images/no-news-community.gif" alt="No New Posts"/>
      <?php echo $lang['nonewpost']; ?>
    </div>
    <div>
      <img src="<?php echo $currtmp; ?>/images/forum/icons/lock-icon.gif" alt="Forum Closed"/>
      <?php echo $lang['postclose']; ?>
    </div>
  </div>
<?php builddiv_end(); ?>
