<style>
.modern-content.pm-container {
  max-width: 900px;
  margin: 0 auto;
  background: rgba(10,10,10,0.92);
  border: 1px solid #6a4a00;
  border-radius: 10px;
  box-shadow: 0 0 12px rgba(0,0,0,0.7);
  padding: 30px 28px 20px;
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
  gap: 12px;
}
.pm-thread-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.pm-thread-shell {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.pm-thread-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border: 1px solid rgba(255, 204, 102, 0.16);
  border-radius: 12px;
  background: rgba(255,255,255,0.03);
}
.pm-thread-title {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.pm-thread-kicker {
  color: #c7a56a;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.72rem;
}
.pm-thread-peer {
  color: #ffcc66;
  font-size: 1.15rem;
  font-weight: 700;
}
.pm-thread-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.pm-thread-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px 14px;
  border-radius: 999px;
  text-decoration: none;
  font-weight: 700;
}
.pm-thread-link.secondary {
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.12);
  color: #f0f0f0;
}
.pm-thread-link.primary {
  background: linear-gradient(180deg, #c08a00, #7a5100);
  border: 1px solid #cda400;
  color: #fff;
}
.pm-thread-link.secondary:hover {
  background: rgba(255,255,255,0.1);
}
.pm-thread-link.primary:hover {
  box-shadow: 0 0 8px rgba(255,204,0,0.25);
}
.pm-thread-reply {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 14px;
  border: 1px solid rgba(255, 204, 102, 0.16);
  border-radius: 14px;
  background: rgba(255,255,255,0.03);
}
.pm-thread-reply textarea {
  width: 100%;
  min-height: 130px;
  box-sizing: border-box;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.12);
  background: rgba(12,12,12,0.92);
  color: #f0f0f0;
  resize: vertical;
}
.pm-thread-reply textarea:focus {
  outline: none;
  border-color: rgba(255, 206, 102, 0.45);
  box-shadow: 0 0 0 3px rgba(216, 158, 57, 0.12);
}
.pm-thread-reply-actions {
  display: flex;
  justify-content: flex-end;
}
.pm-thread-send {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 16px;
  border: 0;
  border-radius: 10px;
  background: linear-gradient(180deg, #ffd27a, #d89e39);
  color: #17120a;
  font-weight: 700;
  cursor: pointer;
}
.pm-thread-send:hover {
  filter: brightness(1.05);
}
.pm-card {
  position: relative;
  box-sizing: border-box;
  width: min(72%, 700px);
  max-width: calc(100% - 12px);
  background: rgba(20,20,20,0.9);
  border: 1px solid #333;
  border-radius: 18px;
  padding: 14px 16px 16px;
  transition: background 0.2s, border-color 0.2s, transform 0.2s;
}
.pm-card:hover { background: rgba(35,35,35,0.95); border-color: rgba(255, 204, 102, 0.2); transform: translateY(-1px); }
.pm-card.unread { border-color: #bba100; }
.pm-card.outgoing {
  border-right: 3px solid rgba(120, 200, 255, 0.5);
  align-self: flex-end;
  margin-right: 6px;
  background: linear-gradient(180deg, rgba(18, 28, 38, 0.94), rgba(10, 18, 26, 0.9));
}
.pm-card.incoming {
  border-left: 3px solid rgba(255, 204, 102, 0.55);
  align-self: flex-start;
  margin-left: 6px;
  background: linear-gradient(180deg, rgba(28, 24, 14, 0.94), rgba(18, 14, 10, 0.9));
}
.pm-row-header {
  display: flex;
  justify-content: flex-start;
  align-items: flex-start;
  font-size: 0.88rem;
  color: #ccc;
  margin-bottom: 8px;
  gap: 12px;
}
.pm-row-main {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.pm-direction {
  display: inline-flex;
  align-self: flex-start;
  padding: 2px 8px;
  border-radius: 999px;
  border: 1px solid rgba(255, 204, 102, 0.18);
  background: rgba(255,255,255,0.04);
  color: #ffcc66;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
.pm-time {
  color: #d5d5d5;
  font-size: 0.88rem;
  white-space: nowrap;
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
.pm-preview {
  margin-top: 8px;
  color: #d0d0d0;
  line-height: 1.6;
}
.pm-card-footer {
  display: flex;
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px solid rgba(255,255,255,0.06);
}
.pm-card.incoming .pm-card-footer {
  justify-content: flex-start;
}
.pm-card.outgoing .pm-card-footer {
  justify-content: flex-end;
}
.pm-card.outgoing .pm-row-header,
.pm-card.outgoing .pm-row-main {
  align-items: flex-end;
  text-align: right;
}
.pm-card.outgoing .pm-direction {
  align-self: flex-end;
  border-color: rgba(120, 200, 255, 0.2);
  color: #9fd8ff;
}
.pm-card.outgoing .pm-label {
  color: #9fd8ff;
}
.pm-empty {
  padding: 18px;
  text-align: center;
  color: #b7b7b7;
}
.pm-conversation-card {
  width: 95%;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  padding: 16px 18px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(20,20,20,0.9);
  text-decoration: none;
  color: inherit;
  transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
}
.pm-conversation-card:hover {
  transform: translateY(-1px);
  border-color: rgba(255, 204, 102, 0.18);
  background: rgba(28,28,28,0.95);
}
.pm-conversation-main {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
}
.pm-conversation-top {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.pm-conversation-name {
  color: #ffcc66;
  font-size: 1.05rem;
  font-weight: 700;
}
.pm-conversation-preview {
  color: #c8c8c8;
  line-height: 1.55;
}
.pm-conversation-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 10px;
  white-space: nowrap;
}
.pm-unread-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  padding: 0 9px;
  border-radius: 999px;
  background: rgba(255, 204, 0, 0.15);
  border: 1px solid rgba(255, 204, 0, 0.3);
  color: #ffdf7a;
  font-weight: 700;
}

.pm-view-card {
  background: rgba(20,20,20,0.9);
  border: 1px solid #333;
  border-radius: 12px;
  padding: 18px;
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
  border-radius: 10px;
  padding: 14px;
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
.pm-back-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-right: 10px;
  padding: 6px 12px;
  border-radius: 999px;
  border: 1px solid rgba(255, 206, 102, 0.25);
  background: rgba(255,255,255,0.06);
  color: #f0f0f0;
  text-decoration: none;
  font-weight: 700;
}
.pm-back-btn:hover {
  background: rgba(255,255,255,0.12);
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

@media (max-width: 760px) {
  .pm-conversation-card {
    width: 100%;
  }

  .pm-card {
    width: 100%;
    max-width: 100%;
    margin-left: 0;
    margin-right: 0;
    border-right-width: 1px;
  }

  .pm-row-header {
    flex-direction: column;
  }

  .pm-card.outgoing .pm-row-header,
  .pm-card.outgoing .pm-row-main,
  .pm-card.outgoing .pm-card-footer {
    align-items: flex-start;
    justify-content: flex-start;
    text-align: left;
  }

  .pm-card.outgoing .pm-direction {
    align-self: flex-start;
  }
}
</style>

<script>
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
    <a href="index.php?n=account&sub=pms&action=view" class="<?php echo ($_GET['action']=='view' || $_GET['action']=='viewpm'?'active':''); ?>">Messages</a>
  </nav>

  <?php if($_GET['action']=='add'): ?>
  <div class="pm-compose">
    <form method="post" action="index.php?n=account&sub=pms&action=add" onsubmit="return this.owner.value.trim() && this.message.value.trim();">
      <div class="pm-recipient-row">
        <div>
          <label for="pm-owner">Who:</label>
          <?php if(!empty($isReplyMode)): ?>
          <div class="compose-help">Reply is locked to this conversation.</div>
          <input type="text" id="pm-owner" name="owner" value="<?php echo htmlspecialchars($content['sender']); ?>" maxlength="80" readonly="readonly" required>
          <?php else: ?>
          <div class="compose-help">Type an account name, or pick one below when fewer than 20 visible members are available.</div>
          <input type="text" id="pm-owner" name="owner" value="<?php echo htmlspecialchars($content['sender']); ?>" maxlength="80" placeholder="Enter account name" required>
          <?php endif; ?>
        </div>

        <?php if(empty($isReplyMode) && !empty($pmRecipientOptions)): ?>
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

      <div class="compose-help">Plain text works best here right now.</div>

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
      <?php
        $isIncoming = (($item['latest_box'] ?? '') === 'in');
        $peerLabel = !empty($item['peer_name']) ? $item['peer_name'] : 'Unknown';
        $previewText = trim(strip_tags((string)my_preview(my_previewreverse($item['latest_message'] ?? ''))));
        if ($previewText === '') {
          $previewText = 'No message preview available.';
        } elseif (function_exists('mb_substr')) {
          $previewText = mb_substr($previewText, 0, 180) . (mb_strlen($previewText) > 180 ? '...' : '');
        } else {
          $previewText = substr($previewText, 0, 180) . (strlen($previewText) > 180 ? '...' : '');
        }
      ?>
      <a class="pm-conversation-card" href="index.php?n=account&sub=pms&action=viewpm&iid=<?php echo (int)$item['latest_id']; ?>">
        <div class="pm-conversation-main">
          <div class="pm-conversation-top">
            <span class="pm-direction"><?php echo $isIncoming ? 'Received' : 'Sent'; ?></span>
            <span class="pm-conversation-name"><?php echo htmlspecialchars($peerLabel); ?></span>
          </div>
          <div class="pm-conversation-preview"><?php echo htmlspecialchars($previewText); ?></div>
        </div>
        <div class="pm-conversation-meta">
          <div class="pm-time"><?php echo date('d-m-Y, H:i', (int)($item['latest_posted'] ?? 0)); ?></div>
          <?php if (!empty($item['unread_count'])): ?>
          <span class="pm-unread-badge"><?php echo (int)$item['unread_count']; ?></span>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="pm-card pm-empty"><em><?php echo !empty($lang['no_messages']) ? $lang['no_messages'] : 'None'; ?></em></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if($_GET['action']=='viewpm' && !empty($threadPeer)): ?>
  <div class="pm-thread-shell">
    <div class="pm-thread-head">
      <div class="pm-thread-title">
        <div class="pm-thread-kicker">Conversation</div>
        <div class="pm-thread-peer"><?php echo htmlspecialchars($threadPeer); ?></div>
      </div>
      <div class="pm-thread-actions">
        <a href="index.php?n=account&sub=pms&action=view" class="pm-thread-link secondary">All Messages</a>
      </div>
    </div>

    <div class="pm-thread-list">
      <?php foreach ($threadItems as $threadItem): ?>
      <?php
        $isIncoming = (($threadItem['pm_box'] ?? '') === 'in');
        $previewHtml = my_preview(my_previewreverse($threadItem['message'] ?? ''));
      ?>
      <div class="pm-card <?php echo !empty($threadItem['showed']) ? 'read' : 'unread'; ?> <?php echo $isIncoming ? 'incoming' : 'outgoing'; ?>">
        <div class="pm-row-header">
          <div class="pm-row-main">
            <span class="pm-direction"><?php echo $isIncoming ? 'Received' : 'Sent'; ?></span>
          </div>
        </div>
        <div class="pm-preview"><?php echo $previewHtml; ?></div>
        <div class="pm-card-footer">
          <div class="pm-time"><?php echo date('d-m-Y, H:i', (int)$threadItem['posted']); ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <form method="post" action="index.php?n=account&sub=pms&action=viewpm&iid=<?php echo (int)($_GET['iid'] ?? 0); ?>" class="pm-thread-reply">
      <div class="compose-help">Replying to <?php echo htmlspecialchars($threadPeer); ?>.</div>
      <textarea name="reply_message" placeholder="Write your reply..." required></textarea>
      <div class="pm-thread-reply-actions">
        <button type="submit" class="pm-thread-send">Reply</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

</div>
<?php endif; ?>

<?php builddiv_end(); ?>
