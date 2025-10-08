<style>
/* ---------- Modern Forum Topic View ---------- */
.topic-header {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
}
.topic-header h1 {
  font-size: 1.4rem;
  color: #ffcc66;
  margin: 0;
}
.meta {
  color: #999;
  font-size: 0.9rem;
}
.topic-controls {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}

/* ---------- Buttons ---------- */
.btn {
  border: none;
  border-radius: 6px;
  padding: 6px 12px;
  font-weight: bold;
  cursor: pointer;
  text-decoration: none;
}
.btn.primary {
  background: #ffcc66;
  color: #111;
}
.btn.secondary {
  background: #333;
  color: #ccc;
}
.btn.primary:hover {
  background: #ffd97a;
}
.btn.secondary:hover {
  background: #444;
}

/* ---------- Posts ---------- */
.topic-posts {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.post {
  display: flex;
  background: #111;
  border: 1px solid #333;
  border-radius: 8px;
  padding: 12px;
  transition: background 0.25s;
}
.post:hover {
  background: rgba(255,204,102,0.06);
}
.post-avatar {
  text-align: center;
  width: 120px;
}
.post-avatar img {
  width: 64px;
  height: 64px;
  border-radius: 8px;
  border: 2px solid #333;
}
.post-user h3 {
  font-size: 1rem;
  margin: 6px 0 0;
  color: #ffcc66;
}
.post-user .level {
  color: #aaa;
  font-size: 0.85rem;
}

/* ---------- Post Body ---------- */
.post-body {
  flex: 1;
  padding-left: 16px;
}
.post-meta {
  font-size: 0.85rem;
  color: #999;
  margin-bottom: 8px;
}
.post-message {
  line-height: 1.5;
  font-size: 0.95rem;
}
.post-edit-note {
  margin-top: 8px;
  font-size: 0.8rem;
  color: #c33;
  font-style: italic;
}

/* ---------- Pagination ---------- */
.topic-pagination {
  text-align: center;
  margin: 20px 0;
  color: #aaa;
}

/* ---------- Header Image ---------- */
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
  <img src="<?php echo $currtmp; ?>/images/forum_top.png"
       alt="<?php echo $lang['forums'] ?? ''; ?>"
       class="forum-header" />

  <header class="topic-header">
    <h1><?php echo htmlspecialchars($this_topic['topic_name']); ?></h1>
    <p class="meta">
      <?php echo $lang['topic']; ?> ·
      <?php echo date('d/m/Y, H:i:s', $this_topic['topic_posted']); ?>
    </p>
    <div class="topic-controls">
      <?php if (($user['g_post_new_topics'] == 1 && $this_forum['closed'] != 1) || $user['g_forum_moderate'] == 1): ?>
        <a href="<?php echo $this_forum['linktonewtopic']; ?>"
           class="btn primary"><?php echo $lang['newtopic']; ?></a>
      <?php endif; ?>
      <a href="<?php echo $this_forum['linktothis']; ?>"
         class="btn secondary"><?php echo $lang['forum_index']; ?></a>
    </div>
  </header>

  <div class="topic-posts">
    <?php foreach ($posts as $post): ?>
      <article class="post <?php echo $post['bg'] ? 'alt' : ''; ?>">
        <div class="post-avatar">
          <img src="<?php echo $dtmp . "/images/portraits/wow/" . $post['avatar']; ?>"
               alt="avatar" />
          <div class="post-user">
            <h3><?php echo htmlspecialchars($post['poster']); ?></h3>
            <p class="level">Lvl <?php echo (int)$post['level']; ?></p>
          </div>
        </div>

        <div class="post-body">
          <header class="post-meta">
            <span>#<?php echo $post['pos_num']; ?></span> •
            <span><?php echo $post['posted']; ?></span>
          </header>
          <div class="post-message"><?php echo $post['message']; ?></div>
          <?php if ($post['edited']): ?>
            <footer class="post-edit-note">
              <?php
                echo $lang['post_editted_by'] . ' ' .
                     $post['edited_by'] . ' (' .
                     date('d-m-Y H:i:s', $post['edited']) . ')';
              ?>
            </footer>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="topic-pagination bottom">
    <?php echo $pages_str; ?>
  </div>
</div>
<?php builddiv_end(); ?>
