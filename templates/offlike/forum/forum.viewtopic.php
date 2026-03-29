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
  flex-wrap: wrap;
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
.post-user .guild {
  color: #b8b8b8;
  font-size: 0.84rem;
  margin-top: 6px;
}
.post-user .level {
  color: #aaa;
  font-size: 0.85rem;
  margin: 8px 0 0;
}
.post-user .post-count {
  color: #8f8f8f;
  font-size: 0.8rem;
  margin-top: 8px;
}

/* ---------- Post Body ---------- */
.post-body {
  flex: 1;
  padding-left: 16px;
  position: relative;
  padding-right: 118px;
  padding-bottom: 30px;
}
.post-message {
  line-height: 1.5;
  font-size: 0.95rem;
  word-break: break-word;
}
.post-number {
  position: absolute;
  top: 0;
  right: 0;
  color: #9a9a9a;
  font-size: 0.82rem;
  text-align: right;
}
.post-time {
  position: absolute;
  right: 0;
  bottom: 0;
  color: #8d8d8d;
  font-size: 0.8rem;
  text-align: right;
}
.post-edit-note {
  margin-top: 8px;
  font-size: 0.8rem;
  color: #c33;
  font-style: italic;
}
.post-signature {
  margin-top: 14px;
  padding-top: 10px;
  border-top: 1px solid #2e2e2e;
  color: #b9b9b9;
  font-size: 0.9rem;
}
@media (max-width: 720px) {
  .post {
    flex-direction: column;
  }
  .post-avatar {
    width: auto;
    margin-bottom: 10px;
  }
  .post-body {
    padding-left: 0;
    padding-right: 0;
    padding-bottom: 0;
  }
  .post-number,
  .post-time {
    position: static;
    text-align: left;
    margin-bottom: 8px;
  }
  .post-time {
    margin-top: 12px;
    margin-bottom: 0;
  }
}

/* ---------- Pagination ---------- */
.topic-pagination {
  text-align: center;
  margin: 20px 0;
}
.topic-pagination .pagination {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 4px;
  justify-content: center;
}
.topic-pagination .page-btn {
  display: inline-block;
  padding: 5px 10px;
  border-radius: 5px;
  background: #1e1e1e;
  border: 1px solid #444;
  color: #ccc;
  text-decoration: none;
  font-size: 0.85rem;
}
.topic-pagination .page-btn:hover {
  background: #2a2a2a;
  border-color: #ffcc66;
  color: #ffcc66;
}
.topic-pagination .page-btn.active {
  background: #ffcc66;
  border-color: #ffcc66;
  color: #111;
  font-weight: bold;
}
.topic-pagination .page-btn.disabled {
  opacity: 0.4;
  cursor: default;
}
.topic-pagination .dots {
  color: #666;
  padding: 5px 4px;
  font-size: 0.85rem;
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


<?php
$topicTitle = htmlspecialchars(html_entity_decode((string)$this_topic['topic_name'], ENT_QUOTES, 'UTF-8'));
$forumTitle = htmlspecialchars(html_entity_decode((string)$this_forum['forum_name'], ENT_QUOTES, 'UTF-8'));
builddiv_start(1, $forumTitle, 0, false, $this_forum['forum_id'], $this_forum['closed']);
?>

<div class="modern-content">
  <img src="<?php echo $currtmp; ?>/images/forum_top.png"
       alt="Forum Banner"
       class="forum-header" />

  <!-- Topic Header -->
  <header class="topic-header">
    <h1><?php echo $topicTitle; ?></h1>
    <p class="meta">
      Started by <strong><?php echo htmlspecialchars(html_entity_decode((string)$this_topic['topic_poster'], ENT_QUOTES, 'UTF-8')); ?></strong> ·
      <?php echo date('M d, Y H:i', $this_topic['topic_posted']); ?>
    </p>
    <div class="topic-controls">
      <?php if (!empty($this_topic['linktoreply'])): ?>
        <a href="<?php echo $this_topic['linktoreply']; ?>" class="btn primary">Reply</a>
      <?php endif; ?>
      <a href="<?php echo $this_forum['linktothis']; ?>" class="btn secondary">Back to Forums</a>
      <?php if ((int)($user['g_forum_moderate'] ?? 0) === 1): ?>
        <?php if (!empty($this_topic['sticky'])): ?>
          <a href="<?php echo $this_topic['linktounstick']; ?>" class="btn secondary">Unpin Topic</a>
        <?php else: ?>
          <a href="<?php echo $this_topic['linktostick']; ?>" class="btn secondary">Pin Topic</a>
        <?php endif; ?>
        <?php if (!empty($this_topic['closed'])): ?>
          <a href="<?php echo $this_topic['linktoopen']; ?>" class="btn secondary">Unlock Topic</a>
        <?php else: ?>
          <a href="<?php echo $this_topic['linktoclose']; ?>" class="btn secondary">Lock Topic</a>
        <?php endif; ?>
        <a href="<?php echo $this_topic['linktodelete']; ?>" class="btn secondary" onclick="return confirm('Delete this topic and all of its posts?');">Delete Topic</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- Topic Posts -->
  <section class="topic-posts">
    <?php if (!empty($posts)): ?>
      <?php foreach ($posts as $post): ?>
        <article class="post">
          <div class="post-avatar">
            <img src="<?php echo $post['avatar']; ?>" alt="avatar" />
            <div class="post-user">
              <h3><?php echo htmlspecialchars($post['poster']); ?></h3>
              <?php if (!empty($post['guild'])): ?>
                <div class="guild">&lt;<?php echo htmlspecialchars($post['guild']); ?>&gt;</div>
              <?php endif; ?>
              <p class="level">Lvl <?php echo (int)$post['level']; ?></p>
              <div class="post-count">Post count: <?php echo (int)($post['forum_post_count'] ?? 0); ?></div>
            </div>
          </div>

          <div class="post-body">
            <div class="post-number">#<?php echo (int)$post['pos_num']; ?></div>
            <div class="post-message"><?php echo $post['rendered_message']; ?></div>

            <?php if (!empty(trim((string)$post['rendered_signature']))): ?>
              <div class="post-signature">
                <?php echo $post['rendered_signature']; ?>
              </div>
            <?php endif; ?>

            <?php if ($post['edited']): ?>
              <footer class="post-edit-note">
                Edited by <?php echo htmlspecialchars($post['edited_by']); ?> on
                <?php echo date('M d, Y H:i', $post['edited']); ?>
              </footer>
            <?php endif; ?>

            <div class="post-time"><?php echo htmlspecialchars((string)$post['posted']); ?></div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="text-align:center;color:#888;">No posts yet.</p>
    <?php endif; ?>
  </section>

  <!-- Pagination -->
  <div class="topic-pagination">
    <?php echo $pages_str; ?>
  </div>
</div>

<?php builddiv_end(); ?>
