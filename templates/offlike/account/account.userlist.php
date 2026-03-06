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
    <div class="pagination">
      <?php echo $lang['post_pages']; ?>: <?php echo $pages_str; ?>
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
      <div class="col email">Email</div>
      <div class="col home"><?php echo $lang['homepage']; ?></div>
      <div class="col icq">ICQ</div>
      <div class="col msn">MSN</div>
      <div class="col registered"><?php echo $lang['registered']; ?></div>
      <div class="col posts"><?php echo $lang['forums_posts']; ?></div>
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
          <div class="col email">
            <?php if ($item['hideemail'] != 1): ?>
              <a href="mailto:<?php echo $item['email']; ?>">
                <img src="<?php echo $currtmp; ?>/images/icons/email_open.gif" alt="Email">
              </a>
            <?php endif; ?>
          </div>
          <div class="col home">
            <?php if ($item['homepage'] && $item['homepage'] != 'http://'): ?>
              <a href="<?php echo $item['homepage']; ?>" target="_blank">
                <img src="<?php echo $currtmp; ?>/images/icons2/www.gif" alt="WWW">
              </a>
            <?php endif; ?>
          </div>
          <div class="col icq"><?php echo $item['icq'] ?? ''; ?></div>
          <div class="col msn"><?php echo $item['msn'] ?? ''; ?></div>
          <div class="col registered"><?php echo $item['registered'] ?? ''; ?></div>
          <div class="col posts"><?php echo $item['forums_posts'] ?? ''; ?></div>
        </div>
      <?php endforeach; ?>
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
}

.userlist-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(20,20,20,0.85);
  border: 1px solid #654321;
  border-radius: 6px;
  padding: 6px 10px;
  margin-bottom: 8px;
}

.userlist-header .alphabet-filter a {
  color: gold;
  text-decoration: none;
  margin: 0 2px;
  font-weight: bold;
}
.userlist-header .alphabet-filter a:hover {
  text-shadow: 0 0 6px #ffcc00;
}

.userlist-table {
  width: 100%;
  display: flex;
  flex-direction: column;
}

.userlist-row {
  display: grid;
  grid-template-columns: 40px 1fr 60px 60px 60px 60px 120px 60px;
  align-items: center;
  background: rgba(15, 15, 15, 0.7);
  border-bottom: 1px solid rgba(255,255,255,0.05);
  padding: 6px 4px;
}
.userlist-row.header {
  background: linear-gradient(to bottom, #3a2a00, #1f1600);
  font-weight: bold;
  color: gold;
  text-transform: uppercase;
  border-bottom: 2px solid #c28c00;
}
.userlist-row:hover {
  background: rgba(40, 30, 0, 0.7);
}

.userlist-row .col {
  padding: 4px;
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

.pagination.bottom {
  margin-top: 8px;
  text-align: right;
  color: #aaa;
  font-size: 0.9em;
}
</style>
