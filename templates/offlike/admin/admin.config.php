<br/>
<?php builddiv_start(1, "Config") ?>
<style>
.admin-config {
  color: #f4efe2;
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.admin-config__intro,
.admin-config__panel {
  padding: 18px 20px;
  border: 1px solid rgba(230, 193, 90, 0.22);
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(20, 24, 34, 0.82), rgba(10, 12, 18, 0.9));
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.22);
}
.admin-config__eyebrow {
  margin: 0 0 10px;
  color: #c9a45a;
  font-size: 12px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}
.admin-config__title {
  margin: 0 0 8px;
  color: #ffca5a;
  font-size: 1.35rem;
}
.admin-config__body {
  margin: 0;
  color: #d6d0c4;
  line-height: 1.6;
}
.admin-config__path {
  margin-top: 12px;
  padding: 10px 12px;
  border-radius: 10px;
  background: rgba(255, 198, 87, 0.05);
  border: 1px solid rgba(230, 193, 90, 0.12);
  color: #f4efe2;
  font-family: Consolas, "Courier New", monospace;
  font-size: 0.9rem;
  word-break: break-all;
}
.admin-config__meta {
  margin: 0 0 14px;
  color: #d6d0c4;
}
.admin-config__table-wrap {
  overflow: auto;
}
.admin-config__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
.admin-config__table th,
.admin-config__table td {
  padding: 9px 10px;
  border-bottom: 1px solid rgba(230, 193, 90, 0.12);
  vertical-align: top;
}
.admin-config__table th {
  color: #c9a45a;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.78rem;
  text-align: left;
}
.admin-config__table td:first-child {
  color: #f4efe2;
  white-space: nowrap;
}
.admin-config__table td:last-child {
  color: #d6d0c4;
  font-family: Consolas, "Courier New", monospace;
}
</style>

<div class="admin-config">
  <section class="admin-config__intro">
    <p class="admin-config__eyebrow">Configuration</p>
    <h2 class="admin-config__title">MangosWeb Config Reference</h2>
    <p class="admin-config__body">
      This page is a read-only view of the loaded MangosWeb configuration. To change these values, edit the config file directly on disk and redeploy or reload the site as needed.
    </p>
    <div class="admin-config__path"><?php echo htmlspecialchars($configfilepath); ?></div>
  </section>

  <section class="admin-config__panel">
    <p class="admin-config__meta"><?php echo number_format((int)$configCount); ?> flattened config values loaded.</p>
    <div class="admin-config__table-wrap">
      <table class="admin-config__table">
        <thead>
          <tr>
            <th>Setting</th>
            <th>Value</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($config as $key=>$value): ?>
          <tr>
            <td><?php echo htmlspecialchars($key); ?></td>
            <td><?php echo htmlspecialchars($value); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php builddiv_end() ?>
