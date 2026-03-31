<br>
<?php builddiv_start(1, 'Site Cleanup') ?>
<style>
.admin-cleanup {
  color: #f4efe2;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.admin-cleanup__panel,
.admin-cleanup__grid > section {
  padding: 18px 20px;
  border: 1px solid rgba(230, 193, 90, 0.22);
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(20, 24, 34, 0.82), rgba(10, 12, 18, 0.9));
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22);
}

.admin-cleanup__eyebrow {
  margin: 0 0 10px;
  color: #c9a45a;
  font-size: 12px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.admin-cleanup__title {
  margin: 0 0 8px;
  color: #ffca5a;
  font-size: 1.4rem;
}

.admin-cleanup__body,
.admin-cleanup__note,
.admin-cleanup__list {
  margin: 0;
  color: #d6d0c4;
  line-height: 1.6;
}

.admin-cleanup__grid {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.admin-cleanup__metric {
  margin: 0 0 6px;
  color: #ffcc66;
  font-size: 2rem;
  font-weight: 700;
}

.admin-cleanup__label {
  margin: 0 0 12px;
  color: #f5f1e7;
  font-weight: 600;
}

.admin-cleanup__subgrid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px 14px;
  margin-top: 14px;
}

.admin-cleanup__mini {
  padding: 12px 14px;
  border-radius: 10px;
  background: rgba(255, 198, 87, 0.05);
  border: 1px solid rgba(230, 193, 90, 0.12);
}

.admin-cleanup__mini strong {
  display: block;
  color: #ffcc66;
  font-size: 1.15rem;
}

.admin-cleanup__mini span {
  display: block;
  color: #d6d0c4;
  font-size: 0.92rem;
}

.admin-cleanup__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
}

.admin-cleanup__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 9px 16px;
  border-radius: 10px;
  border: 1px solid rgba(255, 206, 102, 0.35);
  background: rgba(255, 193, 72, 0.08);
  color: #ffd27a;
  text-decoration: none;
  font-weight: bold;
}

.admin-cleanup__btn:hover {
  color: #fff6dc;
  background: rgba(255, 193, 72, 0.18);
  box-shadow: 0 0 10px rgba(255, 193, 72, 0.18);
}

.admin-cleanup__actions form {
  margin: 0;
}

.admin-cleanup__btn-input {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 9px 16px;
  border-radius: 10px;
  border: 1px solid rgba(255, 206, 102, 0.35);
  background: rgba(255, 193, 72, 0.08);
  color: #ffd27a;
  text-decoration: none;
  font-weight: bold;
  cursor: pointer;
}

.admin-cleanup__btn-input:hover {
  color: #fff6dc;
  background: rgba(255, 193, 72, 0.18);
  box-shadow: 0 0 10px rgba(255, 193, 72, 0.18);
}

.admin-cleanup__select {
  min-width: 220px;
  box-sizing: border-box;
  border-radius: 10px;
  border: 1px solid rgba(255, 206, 102, 0.2);
  background: rgba(12, 12, 12, 0.72);
  color: #f1f1f1;
  padding: 10px 12px;
}

.admin-cleanup__btn[aria-disabled="true"] {
  pointer-events: none;
  opacity: 0.55;
}

@media (max-width: 720px) {
  .admin-cleanup__subgrid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="admin-cleanup">
  <section class="admin-cleanup__panel">
    <p class="admin-cleanup__eyebrow">Maintenance</p>
    <h2 class="admin-cleanup__title">Site-Wide Cleanup And Reset Planning</h2>
    <p class="admin-cleanup__body">
      This page now focuses on structural cleanup and reset work instead of generic inactivity pruning. Each section below is a preview-first maintenance bucket so we can see imbalance, stale website data, forum reset scope, bot footprint, and active-realm reset size before we turn on destructive actions.
    </p>
  </section>

  <div class="admin-cleanup__grid">
    <section>
      <p class="admin-cleanup__eyebrow">Orphaned Website Accounts</p>
      <p class="admin-cleanup__metric"><?php echo (int)$cleanupPreview['orphans']['website_only_accounts']; ?></p>
      <p class="admin-cleanup__label">Website-linked accounts with no characters on any configured realm</p>
      <p class="admin-cleanup__note">This is the first place to look for profile imbalance at the site level. It catches website accounts that still exist, but do not currently own any characters anywhere in the configured realm set.</p>
      <div class="admin-cleanup__subgrid">
        <div class="admin-cleanup__mini">
          <strong><?php echo (int)$cleanupPreview['orphans']['invalid_selected_character']; ?></strong>
          <span>Selected character pointers that no longer resolve</span>
        </div>
        <div class="admin-cleanup__mini">
          <strong><?php echo (int)$cleanupPreview['orphans']['missing_account_rows']; ?></strong>
          <span>`website_accounts` rows with no matching `account` row</span>
        </div>
      </div>
      <div class="admin-cleanup__actions">
        <form action="index.php?n=admin&amp;sub=cleanup" method="post">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$cleanupCsrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="cleanup_realm_id" value="<?php echo (int)$cleanupPreview['realm_id']; ?>" />
          <input type="hidden" name="action" value="clear_invalid_selected_character" />
          <input class="admin-cleanup__btn-input" type="submit" value="Clear Invalid Selected Character" />
        </form>
        <form action="index.php?n=admin&amp;sub=cleanup" method="post">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$cleanupCsrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="cleanup_realm_id" value="<?php echo (int)$cleanupPreview['realm_id']; ?>" />
          <input type="hidden" name="action" value="remove_missing_account_rows" />
          <input class="admin-cleanup__btn-input" type="submit" value="Remove Orphaned Website Account Rows" />
        </form>
      </div>
    </section>

    <section>
      <p class="admin-cleanup__eyebrow">Forum Reset</p>
      <p class="admin-cleanup__metric"><?php echo number_format((int)$cleanupPreview['forum']['posts']); ?></p>
      <p class="admin-cleanup__label">Forum posts currently in the clean-slate reset scope</p>
      <p class="admin-cleanup__note">This is the clean-slate forum bucket. A full forum reset would clear the conversation layer and linked social identity data, then rebuild the default forum structure from SQL.</p>
      <div class="admin-cleanup__subgrid">
        <div class="admin-cleanup__mini">
          <strong><?php echo number_format((int)$cleanupPreview['forum']['topics']); ?></strong>
          <span>Topics</span>
        </div>
        <div class="admin-cleanup__mini">
          <strong><?php echo number_format((int)$cleanupPreview['forum']['pms']); ?></strong>
          <span>Private messages</span>
        </div>
        <div class="admin-cleanup__mini">
          <strong><?php echo number_format((int)$cleanupPreview['forum']['identities']); ?></strong>
          <span>Forum identities</span>
        </div>
        <div class="admin-cleanup__mini">
          <strong><?php echo number_format((int)$cleanupPreview['forum']['identity_profiles']); ?></strong>
          <span>Identity profile rows</span>
        </div>
      </div>
      <div class="admin-cleanup__actions">
        <a class="admin-cleanup__btn" href="#" aria-disabled="true"><?php echo $cleanupPreview['forum']['reset_sql_available'] ? 'Blank-State Reset SQL Ready' : 'Need Blank-State Reset SQL'; ?></a>
        <a class="admin-cleanup__btn" href="#" aria-disabled="true"><?php echo !empty($cleanupPreview['forum']['seed_sql_available']) ? 'Seed SQL Ready' : 'Need Seed SQL'; ?></a>
      </div>
    </section>

    <section>
      <p class="admin-cleanup__eyebrow">Bot Cleanup</p>
      <p class="admin-cleanup__metric"><?php echo number_format((int)$cleanupPreview['bots']['accounts']); ?></p>
      <p class="admin-cleanup__label">`rndbot` accounts currently on the site</p>
      <p class="admin-cleanup__note">This is the bucket for wiping or rebuilding the bot layer. It helps answer how much forum, account, and character state is bot-owned before a cleanup or repopulation pass.</p>
      <div class="admin-cleanup__subgrid">
        <div class="admin-cleanup__mini">
          <strong><?php echo number_format((int)$cleanupPreview['bots']['characters']); ?></strong>
          <span>Bot characters on the active realm</span>
        </div>
        <div class="admin-cleanup__mini">
          <strong><?php echo number_format((int)$cleanupPreview['bots']['identities']); ?></strong>
          <span>Bot forum identities</span>
        </div>
        <div class="admin-cleanup__mini">
          <strong><?php echo number_format((int)$cleanupPreview['bots']['signatures']); ?></strong>
          <span>Bot signatures currently stored</span>
        </div>
      </div>
      <div class="admin-cleanup__actions">
        <a class="admin-cleanup__btn" href="#" aria-disabled="true">Plan Bot Wipe</a>
      </div>
    </section>

    <section>
      <p class="admin-cleanup__eyebrow">Realm Reset Tools</p>
      <form action="index.php?n=admin&amp;sub=cleanup" method="get" class="admin-cleanup__actions" style="margin-top:0;margin-bottom:16px;">
        <input type="hidden" name="n" value="admin" />
        <input type="hidden" name="sub" value="cleanup" />
        <select class="admin-cleanup__select" name="cleanup_realm_id" onchange="this.form.submit()">
          <?php foreach ($cleanupRealmOptions as $cleanupRealmId => $cleanupRealmName) { ?>
            <option value="<?php echo (int)$cleanupRealmId; ?>"<?php if ((int)$cleanupRealmId === (int)$cleanupPreview['realm_id']) echo ' selected'; ?>>
              <?php echo htmlspecialchars($cleanupRealmName); ?>
            </option>
          <?php } ?>
        </select>
      </form>
      <p class="admin-cleanup__metric"><?php echo htmlspecialchars($cleanupPreview['realm_name']); ?></p>
      <p class="admin-cleanup__label">Selected realm reset footprint preview</p>
      <p class="admin-cleanup__note">For heavier resets like world DB, char DB, or full bot removal, the safer pattern is targeted PHP tools that run explicit SQL under guardrails. We can also wire known SQL scripts when the reset shape is fixed and repeatable.</p>
      <div class="admin-cleanup__subgrid">
        <div class="admin-cleanup__mini">
          <strong><?php echo number_format((int)$cleanupPreview['realm_reset']['characters']); ?></strong>
          <span>Characters</span>
        </div>
        <div class="admin-cleanup__mini">
          <strong><?php echo number_format((int)$cleanupPreview['realm_reset']['guilds']); ?></strong>
          <span>Guilds</span>
        </div>
        <div class="admin-cleanup__mini">
          <strong><?php echo number_format((int)$cleanupPreview['realm_reset']['items']); ?></strong>
          <span>Item instances</span>
        </div>
        <div class="admin-cleanup__mini">
          <strong><?php echo number_format((int)$cleanupPreview['realm_reset']['mail']); ?></strong>
          <span>Mail rows</span>
        </div>
        <div class="admin-cleanup__mini">
          <strong><?php echo number_format((int)$cleanupPreview['realm_reset']['auctions']); ?></strong>
          <span>Auction rows</span>
        </div>
      </div>
      <div class="admin-cleanup__actions">
        <a class="admin-cleanup__btn" href="#" aria-disabled="true">Design Reset Action</a>
      </div>
    </section>
  </div>

  <section class="admin-cleanup__panel">
    <p class="admin-cleanup__eyebrow">Execution Model</p>
    <p class="admin-cleanup__body">
      For this kind of maintenance, direct PHP admin tools are usually the better fit because they can preview counts, require confirmations, and target the right realm DB safely. SQL files still make sense for known one-shot resets, like <code>DB Updates/reset_web_forums_blank_state.sql</code> for the destructive wipe-and-rebuild path and <code>DB Updates/seed_web_forums_default_state.sql</code> for non-destructive forum seeding.
    </p>
  </section>
</div>
<?php builddiv_end() ?>

