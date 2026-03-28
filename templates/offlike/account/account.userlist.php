<br>
<?php builddiv_start(1, $lang['userlist']); ?>

<?php if ($user['id'] <= 0): ?>
  <center>
    <div class="alert-denied">
      <strong><?php echo $lang['access_denied']; ?></strong>
    </div>
  </center>

<?php else: ?>
<div class="modern-content userlist">

  <div class="userlist-header">
    <div class="userlist-titleblock">
      <div class="userlist-kicker">Community</div>
      <div class="pagination">
        <?php echo $lang['post_pages']; ?>: <?php echo $pages_str; ?>
      </div>
    </div>
    <div class="alphabet-filter">
      <a href="index.php?n=account&sub=userlist"><?php echo $lang['all']; ?></a>
      <?php foreach (range('A','Z') as $letter): ?>
        <a href="index.php?n=account&sub=userlist&char=<?php echo strtolower($letter); ?>"><?php echo $letter; ?></a>
      <?php endforeach; ?>
    </div>
  </div>

    <div class="userlist-table">
      <div class="userlist-row header">
        <div class="col icon"></div>
        <div class="col name"><?php echo $lang['user_name']; ?></div>
        <div class="col action">Profile</div>
      </div>

    <?php if (is_array($items)): ?>
      <?php foreach ($items as $item): ?>
        <div class="userlist-row">
          <div class="col icon">
            <a href="index.php?n=account&sub=pms&action=add&to=<?php echo $item['username']; ?>" 
               title="<?php echo $lang['personal_message']; ?>">
               <img src="<?php echo $currtmp; ?>/images/icons/email.gif" alt="PM">
            </a>
          </div>
          <div class="col name">
            <a href="index.php?n=account&sub=view&action=find&name=<?php echo $item['username']; ?>">
              <?php echo $item['username']; ?>
            </a>
          </div>
          <div class="col action">
            <a class="profile-link" href="index.php?n=account&sub=view&action=find&name=<?php echo $item['username']; ?>">
              View
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="userlist-empty">No members found for this filter.</div>
    <?php endif; ?>
  </div>

  <div class="pagination bottom">
    <?php echo $lang['post_pages']; ?>: <?php echo $pages_str; ?>
  </div>
</div>
<?php endif; ?>

<?php builddiv_end(); ?>


<style>
.alert-denied {
  background-color: #800;
  border: 2px solid #d22;
  color: #fff;
  padding: 8px 12px;
  border-radius: 6px;
  margin: 10px;
  text-align: center;
}

.userlist {
  font-family: "Trebuchet MS", sans-serif;
  color: #ddd;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.userlist-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  background: linear-gradient(180deg, rgba(18,18,18,0.92), rgba(9,9,9,0.9));
  border: 1px solid rgba(223, 168, 70, 0.5);
  border-radius: 12px;
  padding: 14px 16px;
  gap: 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.24);
}

.userlist-titleblock {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.userlist-kicker {
  color: #c3a46a;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  font-size: 0.72rem;
}

.userlist-header .alphabet-filter {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.userlist-header .alphabet-filter a {
  color: gold;
  text-decoration: none;
  font-weight: bold;
  padding: 4px 0;
}
.userlist-header .alphabet-filter a:hover {
  text-shadow: 0 0 6px #ffcc00;
}

.userlist-table {
  width: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(14,14,14,0.78), rgba(8,8,8,0.68));
  backdrop-filter: blur(3px);
}

.userlist-row {
  display: grid;
  grid-template-columns: 48px minmax(220px, 1fr) 100px;
  align-items: center;
  background: rgba(15, 15, 15, 0.45);
  border-bottom: 1px solid rgba(255,255,255,0.05);
  padding: 10px 10px;
}
.userlist-row.header {
  background: linear-gradient(to bottom, rgba(84, 56, 10, 0.9), rgba(37, 24, 5, 0.95));
  font-weight: bold;
  color: gold;
  text-transform: uppercase;
  border-bottom: 1px solid rgba(255, 210, 102, 0.25);
  letter-spacing: 0.04em;
}
.userlist-row:hover {
  background: rgba(57, 39, 6, 0.42);
}

.userlist-row .col {
  padding: 4px 8px;
  text-align: center;
}
.userlist-row .col.name {
  text-align: left;
}
.userlist-row .col.name a {
  color: #ffcc66;
  text-decoration: none;
}
.userlist-row .col.name a:hover {
  color: #fff;
  text-shadow: 0 0 6px #ffcc00;
}

.userlist-row .col.icon img {
  width: 18px;
  height: 18px;
}

.profile-link {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 999px;
  border: 1px solid rgba(255, 206, 102, 0.4);
  color: #ffd27a;
  text-decoration: none;
  font-weight: bold;
  background: rgba(255, 193, 72, 0.08);
}

.profile-link:hover {
  color: #fff6dc;
  background: rgba(255, 193, 72, 0.18);
  box-shadow: 0 0 10px rgba(255, 193, 72, 0.18);
}

.userlist-empty {
  padding: 24px 18px;
  text-align: center;
  color: #b8b8b8;
}

.pagination {
  color: #cfcfcf;
}

.pagination.bottom {
  text-align: right;
  color: #aaa;
  font-size: 0.9em;
  padding: 0 4px;
}

@media (max-width: 900px) {
  .userlist-header {
    flex-direction: column;
    align-items: stretch;
  }

  .userlist-header .alphabet-filter {
    justify-content: flex-start;
  }

  .userlist-row {
    grid-template-columns: 42px minmax(140px, 1fr) 88px;
  }
}
</style>
