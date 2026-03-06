<style>
/* ---------- Modern Post Form ---------- */
.reply-panel {
  background: #0f0f0f;
  border: 1px solid #3b2a10;
  border-radius: 10px;
  padding: 18px 20px;
  margin: 30px auto;
  max-width: 880px;
  box-shadow: 0 0 12px rgba(0,0,0,0.6), inset 0 0 8px rgba(255,204,102,0.05);
  color: #ccc;
  font-family: "Trebuchet MS", sans-serif;
}

.reply-panel h2 {
  color: #ffcc66;
  font-size: 1.2rem;
  margin-bottom: 14px;
  font-weight: bold;
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
  transition: border-color 0.2s;
}
.subject-input:focus {
  border-color: #ffcc66;
  box-shadow: 0 0 6px rgba(255,204,102,0.25);
  outline: none;
}

/* Editor Toolbar */
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
textarea.editor:focus {
  border-color: #ffcc66;
  box-shadow: 0 0 6px rgba(255,204,102,0.25);
  outline: none;
}

/* Attach + options */
.attach-row {
  margin-top: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  font-size: 0.9rem;
}
.attach-row input[type="file"] {
  background: #111;
  border: 1px solid #333;
  color: #aaa;
  border-radius: 4px;
  padding: 3px;
}
.attach-row label {
  font-weight: normal;
  color: #ccc;
}

/* Buttons */
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
.reply-actions .btn.secondary {
  background: #222;
  color: #ccc;
  border-color: #444;
}
.reply-actions .btn:hover {
  opacity: 0.9;
}

/* Checkbox row */
.subscribe-row {
  margin-top: 10px;
  display: flex;
  align-items: center;
  gap: 6px;
  color: #aaa;
  font-size: 0.9rem;
}
</style>
<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;
$action = $_GET['action'] ?? '';
$is_newtopic = ($action === 'newtopic');
?>

<?php builddiv_start(1, $is_newtopic ? 'Create New Topic' : 'Reply to Thread'); ?>

<section class="reply-panel">
<form method="post"
      action="index.php?n=forum&sub=post&action=<?php 
          if ($is_newtopic) {
              echo 'donewtopic&fid=' . ($this_forum['forum_id'] ?? 0);
          } else {
              $fid = !empty($this_forum['forum_id']) ? $this_forum['forum_id'] : ($this_topic['forum_id'] ?? 0);
              echo 'donewpost&t=' . ($this_topic['topic_id'] ?? 0) . '&fid=' . $fid;
          } ?>"
      enctype="multipart/form-data">

    <?php if ($is_newtopic): ?>
      <label for="subject">Subject:</label>
      <input type="text" id="subject" name="subject" maxlength="80" placeholder="Enter your topic title..." class="subject-input"/>
    <?php endif; ?>

    <label for="message">Message:</label>

    <div class="editor-toolbar">
      <button type="button" onclick="insertTag('b')"><b>B</b></button>
      <button type="button" onclick="insertTag('i')"><i>I</i></button>
      <button type="button" onclick="insertTag('u')">U</button>
      <button type="button" onclick="insertTag('url')">Link</button>
      <button type="button" onclick="insertTag('img')">Img</button>
      <button type="button" onclick="insertTag('quote')">Quote</button>
      <button type="button" onclick="insertTag('color=red')">?</button>
    </div>

    <textarea id="message" name="text" class="editor" placeholder="Write your message..."></textarea>

    <div class="attach-row">
      <label>Attach file: <input type="file" name="file"></label>
      <span>Max file size 10 MB</span>
    </div>

    <div class="subscribe-row">
      <input type="checkbox" id="subscribe" name="subscribe">
      <label for="subscribe">Subscribe to this topic</label>
    </div>

    <div class="reply-actions">
      <button type="submit" class="btn primary"><?php echo $is_newtopic ? 'Post Topic' : 'Add Reply'; ?></button>
      <button type="reset" class="btn secondary">Clear</button>
    </div>
  </form>
</section>

<?php builddiv_end(); ?>

<script>
function insertTag(tag) {
  const textarea = document.getElementById('message');
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const selected = textarea.value.substring(start, end);
  const tagParts = tag.split('=');
  const open = tagParts.length > 1 ? `[${tag}]` : `[${tag}]`;
  const close = tagParts.length > 1 ? `[/${tagParts[0]}]` : `[/${tag}]`;
  textarea.setRangeText(open + selected + close, start, end, 'end');
  textarea.focus();
}
</script>
