<style>
/* ---------- Modern Forum Topic View ---------- */
.modern-wrapper {
  max-width: 950px;
  margin: 30px auto;
  background: #0d0d0d url('<?php echo $currtmp; ?>/images/stone-dark.jpg') repeat;
  border: 1px solid #222;
  border-radius: 10px;
  box-shadow: 0 0 15px rgba(0,0,0,0.7);
  overflow: hidden;
  font-family: 'Trebuchet MS', sans-serif;
  color: #ccc;
}

.modern-header {
  background: linear-gradient(to right, #2a1b05, #111);
  color: #ffcc66;
  text-align: center;
  padding: 12px;
  font-size: 1.4rem;
  font-weight: bold;
  border-bottom: 1px solid #2e2e2e;
}

.modern-content {
  background: #111;
  padding: 20px;
  color: #ddd;
}

/* ---------- Header Section ---------- */
.forum-topic-header {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
}
.forum-topic-header h1 {
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

/* ---------- Posts ---------- */
.forum-post {
  display: flex;
  gap: 16px;
  border: 1px solid #2c2c2c;
  border-radius: 8px;
  background: #1a1a1a;
  padding: 14px;
  margin-top: 18px;
  transition: background 0.25s;
}
.forum-post:hover {
  background: rgba(255,204,102,0.06);
}
/* Alternate rows */
.forum-post:nth-child(even) {
  background: #181818;
  box-shadow: inset 0 0 8px rgba(0,0,0,0.4);
}

/* Avatar column */
.forum-avatar {
  width: 90px;
  text-align: center;
}
.forum-avatar img {
  width: 64px;
  height: 64px;
  border-radius: 6px;
  border: 1px solid #444;
}
.forum-avatar-icons {
  margin-top: 6px;
  display: flex;
  justify-content: center;
  gap: 6px;
}
.forum-avatar-icons img {
  width: 20px;
  height: 20px;
  border-radius: 3px;
  border: 1px solid #333;
  background: #111;
  box-shadow: 0 0 4px rgba(255,255,255,0.1);
}

/* Post meta */
.forum-meta {
  flex: 1;
}
.forum-name {
  font-weight: bold;
  color: #ffcc66;
  font-size: 1rem;
}
.forum-guild {
  font-size: 0.85rem;
  color: #aaa;
}
.forum-message {
  margin-top: 8px;
  line-height: 1.4;
}
.forum-message blockquote {
  background: rgba(30,30,30,0.95);
  border-left: 4px solid #ffcc66;
  padding: 8px 12px;
  margin: 10px 0;
  border-radius: 4px;
  color: #ccc;
  font-style: italic;
  box-shadow: inset 0 0 6px rgba(0,0,0,0.4);
}
.forum-message blockquote strong {
  color: #ffcc66;
}
.forum-tools {
  margin-top: 10px;
  font-size: 0.8rem;
  color: #999;
}
.forum-tools a {
  color: #66aaff;
  margin-right: 8px;
  text-decoration: none;
}
.forum-tools a:hover { text-decoration: underline; }

/* ---------- Reply Form ---------- */
.reply-box {
  margin-top: 24px;
  padding: 20px;
  background: #151515;
  border: 1px solid #333;
  border-radius: 8px;
}
.reply-box h2 {
  font-size: 1.1rem;
  color: #ffcc66;
  margin-bottom: 10px;
}
.reply-box textarea {
  width: 100%;
  background: #111;
  border: 1px solid #444;
  color: #eee;
  border-radius: 6px;
  padding: 8px;
  min-height: 120px;
}
.reply-box textarea:focus {
  outline: none;
  border-color: #ffcc66;
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

<style>
/* ---------- Header Section ---------- */
.forum-topic-header {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
}
.forum-topic-header h1 {
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

/* ---------- Posts ---------- */
.forum-post {
  display: flex;
  gap: 16px;
  border: 1px solid #2c2c2c;
  border-radius: 8px;
  background: #1a1a1a;
  padding: 14px;
  margin-top: 18px;
  transition: background 0.25s;
}
.forum-post:hover { background: rgba(255,204,102,0.06); }

/* Alternate rows */
.forum-post:nth-child(even) {
  background: #181818;
  box-shadow: inset 0 0 8px rgba(0,0,0,0.4);
}

/* Avatar */
.forum-avatar { width: 90px; text-align: center; }
.forum-avatar img {
  width: 64px; height: 64px;
  border-radius: 6px; border: 1px solid #444;
}
.forum-avatar-icons {
  margin-top: 6px;
  display: flex; justify-content: center; gap: 6px;
}
.forum-avatar-icons img {
  width: 20px; height: 20px;
  border-radius: 3px; border: 1px solid #333;
  background: #111; box-shadow: 0 0 4px rgba(255,255,255,0.1);
}

/* Post Meta */
.forum-meta { flex: 1; }
.forum-name { font-weight: bold; color: #ffcc66; font-size: 1rem; }
.forum-guild { font-size: 0.85rem; color: #aaa; }
.forum-message { margin-top: 8px; line-height: 1.4; }

/* Quote boxes */
.forum-message blockquote {
  background: rgba(30,30,30,0.95);
  border-left: 4px solid #ffcc66;
  padding: 8px 12px;
  margin: 10px 0;
  border-radius: 4px;
  color: #ccc;
  font-style: italic;
  box-shadow: inset 0 0 6px rgba(0,0,0,0.4);
}
.forum-message blockquote strong { color: #ffcc66; }

.forum-tools {
  margin-top: 10px;
  font-size: 0.8rem;
  color: #999;
}
.forum-tools a {
  color: #66aaff;
  margin-right: 8px;
  text-decoration: none;
}
.forum-tools a:hover { text-decoration: underline; }

/* ---------- Reply Box ---------- */
.reply-box {
  margin-top: 24px;
  padding: 20px;
  background: #151515;
  border: 1px solid #333;
  border-radius: 8px;
}
.reply-box h2 {
  font-size: 1.1rem;
  color: #ffcc66;
  margin-bottom: 10px;
}
.reply-box textarea {
  width: 100%;
  background: #111;
  border: 1px solid #444;
  color: #eee;
  border-radius: 6px;
  padding: 8px;
  min-height: 120px;
}
.reply-box textarea:focus {
  outline: none;
  border-color: #ffcc66;
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

/* ---------- Centered Banner ---------- */
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

  <header class="forum-topic-header">
    <h1><?php echo htmlspecialchars($topic['topic_name']); ?></h1>

    <?php if($user['id']>0): ?>
      <div class="forum-actions">
        <?php if(($user['g_post_new_replies']==1 && $topic['closed']!=1) || $user['g_forum_moderate']==1): ?>
          <a href="<?php echo $topic['linktonewpost'];?>" class="btn primary"><?php echo $lang['newpost'];?></a>
        <?php endif; ?>
        <a href="<?php echo $topic['linktoreply'];?>" class="btn secondary"><?php echo $lang['reply'];?></a>
        <a href="<?php echo $topic['linktomarkread'];?>" class="btn secondary"><?php echo $lang['markread'];?></a>
      </div>
    <?php endif; ?>

    <div class="pagination">
      <?php echo paginate($topic['pnum'],$p,"index.php?n=forum&sub=viewtopic&t=".$topic['topic_id']); ?>
    </div>
  </header>

  <!-- Forum Posts -->
  <?php foreach($posts as $post): ?>
    <article class="forum-post">
      <div class="forum-avatar">
        <img src="<?php echo $dtmp.'/images/portraits/wow/'.$post['avatar']; ?>" alt="avatar"/>
        <div class="forum-avatar-icons">
          <img src="<?php echo $currtmp.'/images/icon/race/'.$post['mini_race']; ?>" alt="race"/>
          <img src="<?php echo $currtmp.'/images/icon/class/'.$post['mini_class']; ?>" alt="class"/>
          <img src="<?php echo $currtmp.'/images/icon/faction/'.$post['faction']; ?>" alt="faction"/>
        </div>
      </div>

      <div class="forum-meta">
        <div class="forum-name"><?php echo htmlspecialchars($post['poster']); ?></div>
        <?php if($post['guild']): ?>
          <div class="forum-guild">&lt;<?php echo htmlspecialchars($post['guild']); ?>&gt;</div>
        <?php endif; ?>

        <div class="forum-message"><?php echo $post['message']; ?></div>

        <div class="forum-tools">
          <?php echo $lang['posted'];?>: <?php echo $post['posted']; ?> |
          <a href="<?php echo $post['linktoquote']; ?>">Quote</a>
          <?php if($user['id']==$post['poster_id']): ?>
            <a href="<?php echo $post['linktoedit']; ?>">Edit</a>
            <a href="<?php echo $post['linktodelete']; ?>">Delete</a>
          <?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>

  <!-- Reply Box -->
  <?php if($user['id']>0 && $topic['closed']!=1): ?>
    <section class="reply-box">
      <h2><?php echo $lang['quickreply']; ?></h2>
      <form method="post" action="index.php?n=forum&sub=post&action=donewpost&t=<?php echo $topic['topic_id']; ?>">
        <textarea name="text" placeholder="<?php echo $lang['editor_message'] ?? 'Write your reply...'; ?>"></textarea>
        <div class="form-actions" style="margin-top:10px;">
          <button type="submit" class="btn primary"><?php echo $lang['editor_send'];?></button>
          <button type="reset" class="btn secondary"><?php echo $lang['editor_clear'];?></button>
        </div>
      </form>
    </section>
  <?php endif; ?>

  <!-- Footer Legend -->
  <footer class="forum-footer">
    <div class="pagination">
      <?php echo paginate($topic['pnum'],$p,"index.php?n=forum&sub=viewtopic&t=".$topic['topic_id']); ?>
    </div>
    <div class="forum-legend">
      <span><img src="<?php echo $currtmp; ?>/images/square-new.gif"/> <?php echo $lang['newsubject']; ?></span>
      <span><img src="<?php echo $currtmp; ?>/images/square.gif"/> <?php echo $lang['readsubject']; ?></span>
      <span><img src="<?php echo $currtmp; ?>/images/lock-icon.gif"/> <?php echo $lang['postclose']; ?></span>
    </div>
  </footer>

</div> <!-- closes .modern-content -->
<?php builddiv_end(); ?>


<script>
// (Optional JS hooks here)
</script>

