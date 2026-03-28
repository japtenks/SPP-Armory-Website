<br>
<?php
$GLOBALS['builddiv_header_actions'] = '<a href="index.php?n=account&sub=userlist" class="btn secondary">User List</a>';
builddiv_start(1, $lang['si_acc']);
?>
<?php if($user['id']>0 && $profile){ ?>
<div class="modern-content member-profile">
    <div class="member-hero">
        <div class="member-identity">
            <div class="member-avatar">
                <?php if(!empty($profile['avatar'])) { ?>
                    <img src="images/avatars/<?php echo htmlspecialchars($profile['avatar']); ?>" alt="<?php echo htmlspecialchars($profile['username']); ?>">
                <?php } elseif(!empty($profile['avatar_fallback_url'])) { ?>
                    <img src="<?php echo htmlspecialchars($profile['avatar_fallback_url']); ?>" alt="<?php echo htmlspecialchars($profile['username']); ?>">
                <?php } else { ?>
                    <div class="member-avatar-placeholder"><?php echo strtoupper(substr($profile['username'], 0, 1)); ?></div>
                <?php } ?>
            </div>
            <div class="member-copy">
                <div class="member-kicker">Member Profile</div>
                <h2><?php echo htmlspecialchars($profile['username']); ?></h2>
                <div class="member-subline">
                    <span><?php echo htmlspecialchars($profile['expansion_label']); ?></span>
                </div>
            </div>
        </div>
        <div class="member-actions">
            <?php if(!empty($profile['is_own_profile'])): ?>
            <a class="member-action primary" href="index.php?n=account&sub=manage">Edit Profile</a>
            <?php else: ?>
            <a class="member-action primary" href="index.php?n=account&sub=pms&action=add&to=<?php echo urlencode($profile['username']); ?>">
                <?php echo $lang['personal_message'];?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="member-grid">
        <section class="member-panel">
            <div class="panel-label">Account Snapshot</div>
            <div class="stat-list">
                <div class="stat-item">
                    <span class="stat-label">Registered</span>
                    <span class="stat-value"><?php echo htmlspecialchars($profile['joindate'] ?? '-'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Forum Posts</span>
                    <span class="stat-value"><?php echo (int)($profile['forum_posts'] ?? 0); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Played</span>
                    <span class="stat-value"><?php echo htmlspecialchars($profile['total_played_label'] ?? '0m'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Characters</span>
                    <span class="stat-value"><?php echo (int)($profile['character_count'] ?? 0); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Game Access</span>
                    <span class="stat-value"><?php echo htmlspecialchars($profile['expansion_label']); ?></span>
                </div>
            </div>
        </section>

        <section class="member-panel">
            <div class="panel-label">Forum Character</div>
            <?php if(!empty($profile['character_summary'])): ?>
                <div class="character-card">
                    <div class="character-name"><?php echo htmlspecialchars($profile['character_summary']['name']); ?></div>
                    <?php if(!empty($profile['character_summary']['level'])): ?>
                        <div class="character-meta">Level <?php echo (int)$profile['character_summary']['level']; ?></div>
                    <?php endif; ?>
                    <?php if(!empty($profile['character_summary']['guild'])): ?>
                        <div class="character-meta"><?php echo htmlspecialchars($profile['character_summary']['guild']); ?></div>
                    <?php endif; ?>
                    <?php if(!empty($profile['character_summary']['realm'])): ?>
                        <div class="character-meta"><?php echo htmlspecialchars($profile['character_summary']['realm']); ?></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-copy">No forum character selected yet.</div>
            <?php endif; ?>
        </section>
    </div>

    <?php if(!empty(trim((string)($profile['signature'] ?? '')))): ?>
    <section class="member-panel signature-panel">
        <div class="panel-label"><?php echo $lang['signature']; ?></div>
        <div class="signature-copy"><?php echo my_preview($profile['signature']);?></div>
    </section>
    <?php endif; ?>
</div>
<?php } ?>
<?php builddiv_end() ?>

<style>
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

.member-profile {
  color: #ddd;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.member-hero {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 18px 20px;
  border: 1px solid rgba(223, 168, 70, 0.45);
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(18,18,18,0.9), rgba(8,8,8,0.82));
  box-shadow: 0 12px 28px rgba(0,0,0,0.24);
}

.member-identity {
  display: flex;
  align-items: center;
  gap: 16px;
}

.member-avatar img,
.member-avatar-placeholder {
  width: 92px;
  height: 92px;
  border-radius: 16px;
  border: 1px solid rgba(255, 204, 102, 0.35);
  object-fit: cover;
  background: rgba(255,255,255,0.04);
}

.member-avatar-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffd27a;
  font-size: 2rem;
  font-weight: bold;
}

.member-kicker,
.panel-label {
  color: #c7a56a;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.72rem;
}

.member-copy h2 {
  margin: 4px 0 6px;
  color: #ffcc66;
  font-size: 2rem;
}

.member-subline {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  color: #cfcfcf;
}

.member-subline span {
  padding: 5px 10px;
  border-radius: 999px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.06);
}

.member-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
}

.member-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 16px;
  border-radius: 10px;
  text-decoration: none;
  font-weight: bold;
  transition: 0.18s ease;
}

.member-action.primary {
  background: linear-gradient(180deg, #ffd27a, #dca443);
  color: #17120a;
}

.member-action.primary:hover {
  filter: brightness(1.05);
  box-shadow: 0 0 10px rgba(255, 204, 102, 0.2);
}

.member-action.secondary {
  background: rgba(36, 40, 46, 0.95);
  border: 1px solid rgba(255,255,255,0.08);
  color: #f0f0f0;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
}

.member-action.secondary:hover {
  background: rgba(50, 55, 62, 0.98);
}

.member-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.member-panel {
  padding: 16px 18px;
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(16,16,16,0.82), rgba(8,8,8,0.72));
  border: 1px solid rgba(255,255,255,0.08);
}

.stat-list {
  display: grid;
  gap: 10px;
  margin-top: 12px;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.stat-item:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}

.stat-label {
  color: #a9a9a9;
  font-weight: 600;
  min-width: 140px;
}

.stat-value,
.character-name,
.signature-copy {
  color: #f0f0f0;
}

.character-card {
  margin-top: 12px;
  padding: 14px;
  border-radius: 12px;
  background: rgba(255, 204, 102, 0.05);
  border: 1px solid rgba(255, 204, 102, 0.12);
}

.character-name {
  font-size: 1.2rem;
  color: #ffcc66;
  margin-bottom: 6px;
}

.character-meta,
.empty-copy {
  color: #c7c7c7;
  margin-top: 4px;
}

.signature-panel {
  grid-column: 1 / -1;
}

.signature-copy {
  margin-top: 12px;
  line-height: 1.6;
}

@media (max-width: 760px) {
  .member-hero {
    flex-direction: column;
    align-items: flex-start;
  }

  .member-actions {
    justify-content: flex-start;
  }

  .member-grid {
    grid-template-columns: 1fr;
  }
}
</style>
