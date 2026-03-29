<br>
<?php builddiv_start(1, $lang['admin_panel']) ?>
<style type="text/css">
.admin-home { color: #f4efe2; }
.admin-home__intro {
    margin-bottom: 24px;
    padding: 18px 20px;
    border: 1px solid rgba(230, 193, 90, 0.22);
    border-radius: 14px;
    background: rgba(10, 12, 18, 0.56);
}
.admin-home__eyebrow {
    margin: 0 0 8px;
    color: #c9a45a;
    font-size: 12px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.admin-home__title {
    margin: 0 0 6px;
    color: #ffca5a;
    font-size: 20px;
    font-weight: 700;
}
.admin-home__body {
    margin: 0;
    color: #d6d0c4;
    line-height: 1.6;
}
.admin-home__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
}
.admin-home__card {
    padding: 18px 18px 16px;
    border: 1px solid rgba(230, 193, 90, 0.2);
    border-radius: 16px;
    background: linear-gradient(180deg, rgba(20, 24, 34, 0.82), rgba(10, 12, 18, 0.9));
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}
.admin-home__card h3 {
    margin: 0 0 12px;
    color: #ffca5a;
    font-size: 15px;
}
.admin-home__links {
    margin: 0;
    padding: 0;
    list-style: none;
}
.admin-home__links li + li { margin-top: 10px; }
.admin-home__links a {
    display: block;
    padding: 10px 12px;
    border: 1px solid rgba(230, 193, 90, 0.14);
    border-radius: 10px;
    background: rgba(255, 198, 87, 0.06);
    color: #f5f1e7;
    text-decoration: none;
    font-weight: 600;
}
.admin-home__links a:hover {
    background: rgba(255, 198, 87, 0.12);
    border-color: rgba(230, 193, 90, 0.28);
}
</style>
<div class="admin-home">
  <div class="admin-home__intro">
    <p class="admin-home__eyebrow">Control Center</p>
    <h2 class="admin-home__title">MangosWeb Enhanced Admin</h2>
    <p class="admin-home__body">
      Use these tools to manage members, forums, realms, and bot-driven site systems.
    </p>
  </div>

  <div class="admin-home__grid">
    <section class="admin-home__card">
      <h3>Site Management</h3>
      <ul class="admin-home__links">
        <li><a href="index.php?n=admin&amp;sub=members"><?php echo $lang['users_manage']; ?></a></li>
        <li><a href="index.php?n=admin&amp;sub=realms"><?php echo $lang['realms_manage']; ?></a></li>
      </ul>
    </section>

    <section class="admin-home__card">
      <h3>Character Tools</h3>
      <ul class="admin-home__links">
        <li><a href="index.php?n=admin&amp;sub=chartools">Character Rename</a></li>
        <li><a href="index.php?n=admin&amp;sub=chartransfer">Character Transfer</a></li>
      </ul>
    </section>

    <section class="admin-home__card">
      <h3>Forum & Content</h3>
      <ul class="admin-home__links">
        <li><a href="index.php?n=admin&amp;sub=forum"><?php echo $lang['admin_forum']; ?> Admin</a></li>
        <li><a href="index.php?n=admin&amp;sub=botevents">Bot Events Pipeline</a></li>
        <li><a href="index.php?n=admin&amp;sub=botrotation">Bot Rotation Health</a></li>
      </ul>
    </section>
  </div>
</div>
<?php builddiv_end() ?>
