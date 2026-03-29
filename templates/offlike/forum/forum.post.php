<style>
.reply-context {
  max-width: 980px;
  margin: 24px auto 18px;
}
.reply-context h3 {
  color: #ffcc66;
  font-size: 1.05rem;
  margin: 0 0 12px;
}
.reply-posts {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.reply-post {
  display: flex;
  background: #101010;
  border: 1px solid #302719;
  border-radius: 10px;
  padding: 12px 14px;
  box-shadow: 0 0 10px rgba(0,0,0,0.45);
}
.reply-post-avatar {
  width: 94px;
  flex: 0 0 94px;
  text-align: center;
}
.reply-post-avatar img {
  width: 64px;
  height: 64px;
  border-radius: 8px;
  border: 2px solid #3b2a10;
  object-fit: cover;
}
.reply-post-user {
  margin-top: 6px;
  color: #ffcc66;
  font-weight: bold;
}
.reply-post-guild {
  color: #b8b8b8;
  font-size: 0.84rem;
  margin-top: 6px;
}
.reply-post-level,
.reply-post-count {
  color: #9b9b9b;
  font-size: 0.82rem;
}
.reply-post-level {
  margin-top: 8px;
}
.reply-post-count {
  margin-top: 8px;
}
.reply-post-body {
  flex: 1;
  min-width: 0;
  padding-left: 14px;
}
.reply-post-meta {
  font-size: 0.82rem;
  color: #999;
  margin-bottom: 8px;
}
.reply-post-message {
  color: #d6d6d6;
  line-height: 1.5;
  font-size: 0.95rem;
  word-break: break-word;
}
.reply-panel {
  background: #0f0f0f;
  border: 1px solid #3b2a10;
  border-radius: 10px;
  padding: 18px 20px;
  margin: 18px auto 30px;
  max-width: 980px;
  box-shadow: 0 0 12px rgba(0,0,0,0.6), inset 0 0 8px rgba(255,204,102,0.05);
  color: #ccc;
  font-family: "Trebuchet MS", sans-serif;
}
.reply-panel h2 {
  color: #ffcc66;
  font-size: 1.2rem;
  margin: 0 0 14px;
  font-weight: bold;
}
.reply-nav {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin: 0 auto 14px;
  max-width: 980px;
}
.reply-nav .btn {
  padding: 7px 14px;
  font-weight: bold;
  border-radius: 6px;
  border: 1px solid #444;
  text-decoration: none;
}
.reply-nav .btn.secondary {
  background: #222;
  color: #ccc;
}
.reply-nav .btn.secondary:hover {
  background: #333;
  color: #ffcc66;
}
.reply-panel label {
  font-weight: bold;
  color: #ffcc66;
  display: block;
  margin-bottom: 8px;
}
.subject-input {
  width: 100%;
  background: #111;
  border: 1px solid #333;
  color: #eee;
  border-radius: 6px;
  padding: 8px;
  margin-bottom: 14px;
  font-size: 0.95rem;
}
.subject-input:focus,
textarea.editor:focus {
  border-color: #ffcc66;
  box-shadow: 0 0 6px rgba(255,204,102,0.25);
  outline: none;
}
.editor-toolbar {
  background: #1a1a1a;
  border: 1px solid #333;
  border-bottom: none;
  border-radius: 8px 8px 0 0;
  padding: 6px 8px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.editor-toolbar button {
  background: #222;
  border: 1px solid #444;
  color: #ccc;
  font-size: 0.9rem;
  padding: 3px 6px;
  border-radius: 4px;
  cursor: pointer;
}
.editor-toolbar button:hover {
  background: #333;
  color: #ffcc66;
}
textarea.editor {
  width: 100%;
  min-height: 180px;
  background: #111;
  border: 1px solid #333;
  color: #eee;
  border-radius: 0 0 8px 8px;
  padding: 10px;
  resize: vertical;
  font-family: "Trebuchet MS", sans-serif;
}
.reply-actions {
  margin-top: 16px;
  text-align: right;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
.reply-actions .btn {
  padding: 8px 18px;
  font-weight: bold;
  border-radius: 6px;
  border: 1px solid transparent;
  cursor: pointer;
}
.reply-actions .btn.primary {
  background: linear-gradient(to bottom, #ffcc66, #b88a30);
  color: #111;
  border-color: #b88a30;
}
.reply-actions .btn.primary:disabled {
  background: #55451c;
  color: #8d8d8d;
  cursor: not-allowed;
}
.reply-actions .btn.secondary {
  background: #222;
  color: #ccc;
  border-color: #444;
}
.reply-actions .btn:hover {
  opacity: 0.92;
}
.subscribe-row {
  margin-top: 10px;
  display: flex;
  align-items: center;
  gap: 6px;
  color: #aaa;
  font-size: 0.9rem;
}
@media (max-width: 720px) {
  .reply-post {
    flex-direction: column;
  }
  .reply-post-avatar {
    width: auto;
    flex: none;
    margin-bottom: 10px;
  }
  .reply-post-body {
    padding-left: 0;
  }
}
</style>
<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;
$action = $_GET['action'] ?? '';
$is_newtopic = ($action === 'newtopic');
$pageTitle = $is_newtopic ? 'Create New Topic' : 'Reply to Thread';
$forumId = $this_forum['forum_id'] ?? 0;
$topicId = $this_topic['topic_id'] ?? 0;
$forumUrl = 'index.php?n=forum&sub=viewforum&fid=' . $forumId;
$indexUrl = 'index.php?n=forum';
$topicUrl = $topicId > 0 ? 'index.php?n=forum&sub=viewtopic&tid=' . $topicId : $forumUrl;
$formAction = $is_newtopic
    ? 'index.php?n=forum&sub=post&action=donewtopic&f=' . $forumId
    : 'index.php?n=forum&sub=post&action=donewpost&t=' . $topicId . '&f=' . $forumId;
?>

<?php builddiv_start(1, $pageTitle); ?>

<div class="reply-nav">
  <?php if (!$is_newtopic && $topicId > 0): ?>
    <a href="<?php echo $topicUrl; ?>" class="btn secondary">Back to Thread</a>
  <?php endif; ?>
  <a href="<?php echo $forumUrl; ?>" class="btn secondary">Back to Forum</a>
  <a href="<?php echo $indexUrl; ?>" class="btn secondary">Forum Index</a>
</div>

<?php if (!$is_newtopic && !empty($posts)): ?>
<section class="reply-context">
  <h3>Thread Context</h3>
  <div class="reply-posts">
    <?php foreach ($posts as $post): ?>
      <article class="reply-post">
        <div class="reply-post-avatar">
          <img src="<?php echo $post['avatar']; ?>" alt="avatar" />
          <div class="reply-post-user"><?php echo htmlspecialchars($post['poster']); ?></div>
          <?php if (!empty($post['guild'])): ?>
            <div class="reply-post-guild">&lt;<?php echo htmlspecialchars($post['guild']); ?>&gt;</div>
          <?php endif; ?>
          <div class="reply-post-level">Lvl <?php echo (int)$post['level']; ?></div>
          <div class="reply-post-count">Post count: <?php echo (int)($post['forum_post_count'] ?? 0); ?></div>
        </div>
        <div class="reply-post-body">
          <div class="reply-post-meta">#<?php echo (int)$post['pos_num']; ?> · <?php echo htmlspecialchars((string)$post['posted']); ?></div>
          <div class="reply-post-message"><?php echo $post['rendered_message'] ?? ''; ?></div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="reply-panel">
  <h2><?php echo $is_newtopic ? 'Start a New Discussion' : 'Write Your Reply'; ?></h2>
  <?php if (!empty($posting_block_reason)): ?>
    <div style="margin-bottom: 14px; padding: 12px 14px; border: 1px solid #7a2f2f; border-radius: 8px; background: rgba(122, 47, 47, 0.2); color: #ffb3b3;">
      <?php echo htmlspecialchars($posting_block_reason); ?>
    </div>
  <?php endif; ?>
  <form method="post" action="<?php echo $formAction; ?>" enctype="multipart/form-data">
    <?php if ($is_newtopic): ?>
      <label for="subject">Subject:</label>
      <input type="text" id="subject" name="subject" maxlength="80" placeholder="Enter your topic title..." class="subject-input" <?php echo !$canPost ? 'disabled' : ''; ?> />
    <?php endif; ?>

    <label for="message">Message:</label>

    <div class="editor-toolbar">
      <button type="button" onclick="insertTag('b')"><b>B</b></button>
      <button type="button" onclick="insertTag('i')"><i>I</i></button>
      <button type="button" onclick="insertTag('u')">U</button>
      <button type="button" onclick="insertTag('url')">Link</button>
      <button type="button" onclick="insertTag('img')">Img</button>
      <button type="button" onclick="insertTag('quote')">Quote</button>
      <button type="button" onclick="insertTag('color=red')">Color</button>
    </div>

    <textarea id="message" name="text" class="editor" placeholder="Write your message..." <?php echo !$canPost ? 'disabled' : ''; ?>></textarea>

    <div class="subscribe-row">
      <input type="checkbox" id="subscribe" name="subscribe" <?php echo !$canPost ? 'disabled' : ''; ?> />
      <label for="subscribe">Subscribe to this topic</label>
    </div>

    <div class="reply-actions">
      <button type="submit" class="btn primary" <?php echo !$canPost ? 'disabled' : ''; ?>><?php echo $is_newtopic ? 'Post Topic' : 'Add Reply'; ?></button>
      <button type="reset" class="btn secondary">Clear</button>
    </div>
  </form>
</section>

<?php builddiv_end(); ?>

<script>
function insertTag(tag) {
  const textarea = document.getElementById('message');
  if (!textarea || textarea.disabled) {
    return;
  }
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const selected = textarea.value.substring(start, end);
  const tagParts = tag.split('=');
  const open = `[${tag}]`;
  const close = `[/${tagParts[0]}]`;
  textarea.setRangeText(open + selected + close, start, end, 'end');
  textarea.focus();
}
</script>
