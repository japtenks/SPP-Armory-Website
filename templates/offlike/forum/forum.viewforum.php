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
</style>


<?php builddiv_start(1, $lang['spp_forum']); ?>
<div class="modern-content">
  <img src="<?php echo $currtmp; ?>/images/forum_top.png" alt="<?php echo $lang['forums'] ?? ''; ?>" class="forum-header"/>

  <section class="forum-section">
    <header class="forum-header">
      <h1><?php echo htmlspecialchars($this_forum['forum_name']); ?></h1>

      <?php if($user['id']>0): ?>
        <div class="forum-actions">
          <?php if(($user['g_post_new_topics']==1 && $this_forum['closed']!=1) || $user['g_forum_moderate']==1): ?>
            <a href="<?php echo $this_forum['linktonewtopic'];?>" class="btn primary"><?php echo $lang['newtopic'];?></a>
          <?php endif; ?>
          <a href="<?php echo $this_forum['linktomarkread'];?>" class="btn secondary"><?php echo $lang['markread'];?></a>
        </div>
      <?php endif; ?>

      <div class="pagination">
        <?php echo paginate($this_forum['pnum'],$p,"index.php?n=forum&sub=viewforum&fid=".$this_forum['forum_id']); ?>
      </div>
    </header>

    <div class="forum-list">
      <div class="forum-list-head">
        <span class="col-status">⚑</span>
        <span class="col-subject"><?php echo $lang['subject'];?></span>
        <span class="col-author"><?php echo $lang['author'];?></span>
        <span class="col-replies"><?php echo $lang['replies'];?></span>
        <span class="col-views"><?php echo $lang['views'];?></span>
        <span class="col-last"><?php echo $lang['lastpost'];?></span>
      </div>

      <?php foreach($topics as $topic): ?>
        <div class="forum-row">
          <span class="col-status">
            <?php if($topic['sticky']==1): ?><img src="<?php echo $currtmp; ?>/images/sticky.gif" alt="<?php echo $lang['sticky'];?>"/><?php endif; ?>
            <?php if($topic['closed']==1): ?><img src="<?php echo $currtmp; ?>/images/lock-icon.gif" alt="<?php echo $lang['postclose'];?>"/><?php endif; ?>
          </span>

          <span class="col-subject">
            <a href="<?php echo $topic['linktothis']; ?>"><?php echo htmlspecialchars($topic['topic_name']); ?></a>
            <?php if($topic['isnew']): ?><span class="new-tag"><?php echo $lang['newmessages']; ?></span><?php endif; ?>
            <?php if($topic['pnum']>1): ?><small class="pages">[<?php echo $lang['post_pages']; ?>: <?php echo $topic['pages_str']; ?>]</small><?php endif; ?>
          </span>

          <span class="col-author"><?php echo htmlspecialchars($topic['topic_poster']); ?></span>
          <span class="col-replies"><?php echo $topic['num_replies']; ?></span>
          <span class="col-views"><?php echo $topic['num_views']; ?></span>
          <span class="col-last">
            <a href="<?php echo $topic['linktolastpost']; ?>">
              <?php echo htmlspecialchars($topic['last_poster']); ?> – <?php echo $topic['last_post']; ?>
            </a>
          </span>
        </div>
      <?php endforeach; ?>
    </div>

    <footer class="forum-footer">
      <div class="pagination">
        <?php echo paginate($this_forum['pnum'],$p,"index.php?n=forum&sub=viewforum&fid=".$this_forum['forum_id']); ?>
      </div>

      <div class="forum-legend">
        <span><img src="<?php echo $currtmp; ?>/images/square-new.gif"/> <?php echo $lang['newsubject']; ?></span>
        <span><img src="<?php echo $currtmp; ?>/images/square.gif"/> <?php echo $lang['readsubject']; ?></span>
        <span><img src="<?php echo $currtmp; ?>/images/square-grey.gif"/> <?php echo $lang['notreadsubject']; ?></span>
      </div>
    </footer>
  </section>
</div>
<?php builddiv_end(); ?>


<script>
// Optional: Future table sorting or interactivity
</script>
