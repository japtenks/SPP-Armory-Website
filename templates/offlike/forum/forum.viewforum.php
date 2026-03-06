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
  text-align: center;
  color: #aaa;
  margin: 10px 0;
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

</style>

<?php
if (INCLUDED !== true) exit;

// ========================================================
// Validate and fetch forum info
// ========================================================
$forumId = isset($_GET['fid']) ? (int)$_GET['fid'] : 0;
if ($forumId <= 0) {
    output_message('alert', 'Invalid forum.');
    return;
}

$forum = $DB->selectRow("
    SELECT f.forum_id, f.forum_name, f.forum_desc, f.closed, f.hidden, c.cat_name
    FROM tbcrealmd.f_forums AS f
    LEFT JOIN tbcrealmd.f_categories AS c ON f.cat_id = c.cat_id
    WHERE f.forum_id = ?d AND f.hidden = 0
    LIMIT 1
", $forumId);

if (!$forum) {
    output_message('alert', 'Invalid forum.');
    return;
}

// Pathway
$pathway_info[] = ['title' => $lang['forum'], 'link' => 'index.php?n=forum'];
$pathway_info[] = ['title' => $forum['forum_name'], 'link' => ''];

// ========================================================
// Fetch topics
// ========================================================
$topics = $DB->select("
    SELECT 
        t.topic_id, t.topic_name, t.topic_poster, t.num_replies, t.num_views,
        t.last_post, t.last_post_id, t.last_poster, t.closed,
        p.posted AS last_posted_time
    FROM tbcrealmd.f_topics AS t
    LEFT JOIN tbcrealmd.f_posts AS p ON p.post_id = t.last_post_id
    WHERE t.forum_id = ?d
    ORDER BY t.sticky DESC, t.last_post DESC
", $forumId);
?>

<?php builddiv_start(1, $forum['forum_name'], 0, true, $forumId, $forum['closed']); ?>


 <img src="<?php echo $currtmp; ?>/images/forum_top.png" alt="Forums" class="forum-header"/>

<div class="modern-content forum-view">

  <div class="forum-list-head">
    <div></div>
    <div>Subject</div>
    <div>Author</div>
    <div>Replies</div>
    <div>Views</div>
    <div>Last Reply</div>
  </div>

  <?php if (empty($topics)): ?>
    <div class="forum-row">
      <div class="col-subject" style="grid-column: span 6;">No topics found.</div>
    </div>
  <?php else: ?>
    <?php foreach ($topics as $t): ?>
      <div class="forum-row">
        <div><img src="<?php echo $currtmp; ?>/images/<?= $t['closed'] ? 'news-community.gif' : 'no-news-community.gif'; ?>" alt="bean status"></div>
        <div class="col-subject">
          <a href="index.php?n=forum&sub=viewtopic&tid=<?= $t['topic_id'] ?>">
            <?= htmlspecialchars($t['topic_name']); ?>
          </a>
          <?php if ($t['closed']): ?><span class="new-tag">Closed</span><?php endif; ?>
        </div>
        <div><?= htmlspecialchars($t['topic_poster']); ?></div>
        <div><?= (int)$t['num_replies']; ?></div>
        <div><?= (int)$t['num_views']; ?></div>
        <div>
          <?= htmlspecialchars($t['last_poster']); ?><br>
          <?= $t['last_posted_time'] ? date('d-m-Y, H:i', $t['last_posted_time']) : ''; ?>
        </div>
      </div>
    <?php endforeach; ?>
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
        <img src="<?php echo $currtmp; ?>/images/lock-icon.gif" alt="Fourm Closed"/> 
        <?php echo $lang['postclose']; ?>
      </div>
    </div>
<?php builddiv_end(); ?>
