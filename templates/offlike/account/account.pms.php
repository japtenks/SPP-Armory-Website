<style>
.modern-content.pm-container {
  max-width: 900px;
  margin: 0 auto;
  background: rgba(10,10,10,0.92);
  border: 1px solid #6a4a00;
  border-radius: 10px;
  box-shadow: 0 0 12px rgba(0,0,0,0.7);
  padding: 20px 28px;
  color: #ddd;
  font-family: "Trebuchet MS", sans-serif;
}

.pm-nav {
  display: flex;
  gap: 12px;
  background: linear-gradient(to bottom, rgba(60,40,0,0.9), rgba(20,10,0,0.9));
  border: 1px solid #654321;
  border-radius: 6px;
  padding: 6px;
  margin-bottom: 14px;
}
.pm-nav a {
  flex: 1;
  color: #ccc;
  text-decoration: none;
  padding: 6px 10px;
  border-radius: 4px;
  background: rgba(25,25,25,0.8);
  border: 1px solid #3a2a00;
  text-align: center;
  transition: 0.2s;
}
.pm-nav a:hover { background:#2c2c2c; color:#fff; }
.pm-nav a.active {
  background:linear-gradient(to bottom,#c08a00,#7a5100);
  border:1px solid #cda400;
  box-shadow:0 0 8px rgba(255,204,0,0.4);
  color:#fff;
  font-weight:bold;
}

.pm-view-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.pm-card {
  background: rgba(20,20,20,0.9);
  border: 1px solid #333;
  border-radius: 6px;
  padding: 10px 12px;
  transition: background 0.2s;
}
.pm-card:hover { background: rgba(35,35,35,0.95); }
.pm-card.unread { border-color: #bba100; }
.pm-row-header {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
  color: #ccc;
  margin-bottom: 4px;
  gap: 12px;
}
.pm-row-meta {
  display: flex;
  align-items: center;
  gap: 12px;
}
.pm-inline-reply {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(255, 204, 0, 0.12);
  border: 1px solid rgba(255, 204, 0, 0.28);
  color: #ffd45f;
  font-size: 0.82rem;
  font-weight: 700;
  text-decoration: none;
}
.pm-inline-reply:hover {
  background: rgba(255, 204, 0, 0.2);
  color: #fff2b8;
}
.pm-inline-mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(120, 200, 160, 0.12);
  border: 1px solid rgba(120, 200, 160, 0.28);
  color: #9be0bb;
  font-size: 0.82rem;
  font-weight: 700;
  text-decoration: none;
}
.pm-inline-mark:hover {
  background: rgba(120, 200, 160, 0.2);
  color: #d6ffea;
}
.pm-subject {
  color: #ffcc00;
  font-weight: bold;
  text-decoration: none;
}
.pm-subject:hover { text-decoration: underline; }

.pm-view-card {
  background: rgba(20,20,20,0.9);
  border: 1px solid #333;
  border-radius: 8px;
  padding: 12px;
}
.pm-view-header {
  display: flex;
  justify-content: space-between;
  border-bottom: 1px solid #333;
  padding-bottom: 6px;
  margin-bottom: 10px;
}
.pm-view-fromto div { color: #ccc; font-size: 0.9rem; }
.pm-label { color: #ffcc66; font-weight: 600; }
.pm-view-subject {
  font-weight: bold;
  color: #ffcc00;
  margin-bottom: 8px;
}
.pm-view-body {
  background: #111;
  border-radius: 4px;
  padding: 10px;
  color: #eee;
  white-space: pre-wrap;
  min-height: 100px;
}
.pm-view-footer {
  text-align: right;
  margin-top: 12px;
}
.pm-reply-btn {
  background: #b58300;
  border: 1px solid #ffcc33;
  color: #fff;
  padding: 6px 12px;
  border-radius: 4px;
  text-decoration: none;
  font-weight: bold;
}
.pm-reply-btn:hover {
  background: #dca800;
  box-shadow: 0 0 6px #ffcc00;
}

.pm-compose label {
  display:block;
  margin:6px 0 4px 2px;
  color:#ffcc66;
  font-weight:600;
}
.pm-compose .compose-help {
  margin: -2px 0 10px 2px;
  color: #a8a8a8;
  font-size: 0.92rem;
}
.pm-compose input[type=text],
.pm-compose select,
.pm-compose textarea {
  width:100%;
  background:rgba(20,20,20,0.95);
  border:1px solid #444;
  color:#f0f0f0;
  border-radius:4px;
  padding:8px 10px;
  margin-bottom:10px;
  box-sizing:border-box;
}
.pm-compose textarea {
  min-height:200px;
  resize:vertical;
}
.pm-recipient-row {
  display: grid;
  gap: 12px;
  margin-bottom: 8px;
}
.pm-recipient-quickpick {
  background: rgba(15,15,15,0.96);
  border: 1px solid rgba(255,200,80,0.2);
  border-radius: 8px;
  padding: 10px 12px;
}
.pm-recipient-quickpick label {
  margin-top: 0;
}
.pm-recipient-quickpick select {
  margin-bottom: 0;
}
.pm-buttons {
  display:flex;
  gap:10px;
  justify-content:flex-end;
}
.btn-primary {
  background:#2c6ac8;
  color:#fff;
  border:none;
  border-radius:4px;
  padding:6px 14px;
  cursor:pointer;
}
.btn-primary:hover { background:#3d7cff; }

.editor-tools {
  background:rgba(15,15,15,0.98);
  border:1px solid rgba(255,200,80,0.25);
  border-radius:6px;
  padding:8px 10px 10px;
  margin-bottom:12px;
  box-shadow:inset 0 0 6px rgba(255,204,0,0.05);
}
.bbcode-toolbar {
  display:flex;
  flex-wrap:wrap;
  gap:6px;
  margin-bottom:6px;
}
.bbcode-toolbar button {
  background:#1a1a1a;
  border:1px solid #444;
  color:#ffcc66;
  font-weight:600;
  padding:4px 10px;
  border-radius:4px;
  cursor:pointer;
  transition:0.15s;
}
.bbcode-toolbar button:hover {
  background:#333;
  color:#fff;
  box-shadow:0 0 5px #ffcc00;
}

.suggestion-box {
  position: absolute;
  background: rgba(10, 10, 10, 0.95);
  border: 1px solid #654321;
  border-radius: 6px;
  max-height: 180px;
  overflow-y: auto;
  width: 100%;
  z-index: 999;
  margin-top: 2px;
}
.suggestion-item {
  padding: 6px 8px;
  color: #ffc;
  cursor: pointer;
  font-family: "Trebuchet MS", sans-serif;
}
.suggestion-item:hover {
  background: #2c2c2c;
  color: #fff;
}
</style>

<script>
function insertBBCode(tagStart, tagEnd){
  var t=document.getElementById('input_comment');
  if(!t)return;
  var s=t.selectionStart,e=t.selectionEnd;
  var v=t.value;
  t.value=v.substring(0,s)+tagStart+v.substring(s,e)+tagEnd+v.substring(e);
  t.focus();
  t.selectionStart=s+tagStart.length;
  t.selectionEnd=e+tagStart.length;
}
document.addEventListener('DOMContentLoaded', function () {
  var input = document.querySelector("input[name='owner']");
  if (!input) return;
  var picker = document.getElementById('pm-owner-picker');
  if (picker) {
    picker.addEventListener('change', function () {
      if (picker.value) {
        input.value = picker.value;
      }
    });
  }
  var box = document.createElement('div');
  box.className = 'suggestion-box';
  input.parentNode.style.position = 'relative';
  input.parentNode.appendChild(box);
  var timer = null;
  input.addEventListener('input', function () {
    clearTimeout(timer);
    var val = input.value.trim();
    if (val.length < 2) {
      box.innerHTML = '';
      return;
    }
    timer = setTimeout(function () {
      fetch('modules/account/pm_user_search.php?q=' + encodeURIComponent(val))
        .then(function (r) { return r.json(); })
        .then(function (names) {
          box.innerHTML = names.map(function (n) {
            return '<div class="suggestion-item">' + n + '</div>';
          }).join('');
          box.querySelectorAll('.suggestion-item').forEach(function (el) {
            el.onclick = function () {
              input.value = el.textContent;
              if (picker) {
                picker.value = el.textContent;
              }
              box.innerHTML = '';
            };
          });
        })
        .catch(function () {
          box.innerHTML = '';
        });
    }, 250);
  });
  document.addEventListener('click', function (e) {
    if (!box.contains(e.target) && e.target !== input) box.innerHTML = '';
  });
});
</script>

<?php builddiv_start(1,$lang['personal_messages']); ?>

<?php if($user['id']>0): ?>
<div class="modern-content pm-container">

  <nav class="pm-nav">
    <a href="index.php?n=account&sub=pms&action=add" class="<?php echo ($_GET['action']=='add'?'active':''); ?>"><?php echo $lang['write']; ?></a>
    <a href="index.php?n=account&sub=pms&action=view&dir=in" class="<?php echo ($_GET['dir']=='in'?'active':''); ?>"><?php echo $lang['inbox']; ?></a>
    <a href="index.php?n=account&sub=pms&action=view&dir=out" class="<?php echo ($_GET['dir']=='out'?'active':''); ?>"><?php echo $lang['outbox']; ?></a>
  </nav>

  <?php if($_GET['action']=='add'): ?>
  <div class="pm-compose">
    <form method="post" action="index.php?n=account&sub=pms&action=add" onsubmit="return this.owner.value.trim() && this.title.value.trim() && this.message.value.trim();">
      <div class="pm-recipient-row">
        <div>
          <label for="pm-owner">Who:</label>
          <div class="compose-help">Type an account name, or pick one below when fewer than 20 visible members are available.</div>
          <input type="text" id="pm-owner" name="owner" value="<?php echo htmlspecialchars($content['sender']); ?>" maxlength="80" placeholder="Enter account name" required>
        </div>

        <?php if(!empty($pmRecipientOptions)): ?>
        <div class="pm-recipient-quickpick">
          <label for="pm-owner-picker">Choose a member</label>
          <select id="pm-owner-picker">
            <option value="">Select a recipient</option>
            <?php foreach($pmRecipientOptions as $pmRecipient): ?>
              <option value="<?php echo htmlspecialchars($pmRecipient); ?>"><?php echo htmlspecialchars($pmRecipient); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
      </div>

      <label><?php echo $lang['post_subj']; ?>:</label>
      <input type="text" name="title" value="<?php echo htmlspecialchars($content['subject']); ?>" maxlength="80" required>

      <div class="editor-tools">
        <div class="bbcode-toolbar">
          <button type="button" onclick="insertBBCode('[b]','[/b]')">Bold</button>
          <button type="button" onclick="insertBBCode('[i]','[/i]')">Italic</button>
          <button type="button" onclick="insertBBCode('[u]','[/u]')">Underline</button>
          <button type="button" onclick="insertBBCode('[url]','[/url]')">Link</button>
          <button type="button" onclick="insertBBCode('[img]','[/img]')">Image</button>
          <button type="button" onclick="insertBBCode('[quote]','[/quote]')">Quote</button>
          <button type="button" onclick="insertBBCode('[color=red]','[/color]')">Red</button>
          <button type="button" onclick="insertBBCode('[color=green]','[/color]')">Green</button>
          <button type="button" onclick="insertBBCode('[color=blue]','[/color]')">Blue</button>
        </div>
      </div>

      <textarea name="message" id="input_comment" required><?php echo htmlspecialchars($content['message']); ?></textarea>

      <div class="pm-buttons">
        <button type="submit" class="btn-primary"><?php echo $lang['editor_send']; ?></button>
        <button type="reset"><?php echo $lang['editor_clear']; ?></button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <?php if($_GET['action']=='view'): ?>
  <div class="pm-view-list">
    <?php if (!empty($items)): ?>
      <?php foreach ($items as $item): ?>
      <div class="pm-card <?php echo ($item['showed'] ? 'read' : 'unread'); ?>">
        <div class="pm-row-header">
          <div>
            <?php if ($_GET['dir']=='in'): ?>
              <span class="pm-label"><?php echo $lang['post_from']; ?>:</span>
              <?php echo htmlspecialchars($item['sender']); ?>
            <?php else: ?>
              <span class="pm-label"><?php echo $lang['post_for']; ?>:</span>
              <?php echo htmlspecialchars($item['for']); ?>
            <?php endif; ?>
          </div>
          <div class="pm-row-meta">
            <?php if ($_GET['dir']=='in'): ?>
            <?php if (empty($item['showed'])): ?>
            <a class="pm-inline-mark" href="index.php?n=account&sub=pms&action=markread&dir=in&iid=<?php echo $item['id']; ?>">Mark Read</a>
            <?php endif; ?>
            <a class="pm-inline-reply" href="index.php?n=account&sub=pms&action=add&reply=<?php echo $item['id']; ?>">Reply</a>
            <?php endif; ?>
            <div class="pm-time"><?php echo date('d-m-Y, H:i', $item['posted']); ?></div>
          </div>
        </div>
        <a class="pm-subject" href="index.php?n=account&sub=pms&action=viewpm&dir=<?php echo $_GET['dir']; ?>&iid=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['subject']); ?></a>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="pm-card"><em><?php echo !empty($lang['no_messages']) ? $lang['no_messages'] : 'None'; ?></em></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if($_GET['action']=='viewpm' && isset($item)): ?>
  <div class="pm-view-card">
    <div class="pm-view-header">
      <div class="pm-view-fromto">
        <div><span class="pm-label"><?php echo $lang['post_from']; ?>:</span> <?php echo htmlspecialchars($item['sender']); ?></div>
        <div><span class="pm-label"><?php echo $lang['post_for']; ?>:</span> <?php echo htmlspecialchars($item['receiver']); ?></div>
      </div>
      <div class="pm-view-time"><?php echo date('d-m-Y, H:i', $item['posted']); ?></div>
    </div>

    <div class="pm-view-subject"><?php echo htmlspecialchars($item['subject']); ?></div>
    <div class="pm-view-body"><?php echo nl2br($item['message']); ?></div>

    <div class="pm-view-footer">
      <a href="index.php?n=account&sub=pms&action=add&reply=<?php echo $item['id']; ?>" class="pm-reply-btn"><?php echo $lang['post_reply']; ?></a>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php endif; ?>

<?php builddiv_end(); ?>
