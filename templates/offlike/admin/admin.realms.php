<br>
<?php builddiv_start(1, "Realms") ?>
<style type="text/css">
.realm-admin { color: #f4efe2; }
.realm-admin__intro {
    margin-bottom: 18px;
    padding: 16px 18px;
    border: 1px solid rgba(230, 193, 90, 0.18);
    border-radius: 14px;
    background: rgba(10, 12, 18, 0.55);
    line-height: 1.6;
}
.realm-admin__intro strong { color: #ffca5a; }
.realm-admin__table-wrap {
    overflow-x: auto;
    border: 1px solid rgba(230, 193, 90, 0.16);
    border-radius: 16px;
    background: rgba(8, 10, 16, 0.7);
    margin-bottom: 18px;
}
.realm-admin__table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1080px;
}
.realm-admin__table th,
.realm-admin__table td {
    padding: 12px 10px;
    border-bottom: 1px solid rgba(230, 193, 90, 0.12);
    text-align: left;
    vertical-align: top;
}
.realm-admin__table th {
    color: #c9a45a;
    font-size: 12px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    background: rgba(255, 198, 87, 0.05);
}
.realm-admin__table tr:last-child td { border-bottom: none; }
.realm-admin__name {
    color: #6fb2ff;
    font-weight: 700;
    text-decoration: none;
}
.realm-admin__muted { color: #b8b0a0; }
.realm-admin__secret { color: #b8b0a0; letter-spacing: 0.08em; }
.realm-admin__card {
    padding: 18px;
    border: 1px solid rgba(230, 193, 90, 0.18);
    border-radius: 16px;
    background: linear-gradient(180deg, rgba(20, 24, 34, 0.82), rgba(10, 12, 18, 0.9));
}
.realm-admin__card h3 {
    margin: 0 0 6px;
    color: #ffca5a;
    font-size: 18px;
}
.realm-admin__card p {
    margin: 0 0 16px;
    color: #cfc7b8;
}
.realm-admin__form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px 16px;
}
.realm-admin__field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.realm-admin__field label {
    color: #c9a45a;
    font-size: 12px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.realm-admin__field input,
.realm-admin__field select {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 12px;
    border: 1px solid rgba(230, 193, 90, 0.2);
    border-radius: 10px;
    background: rgba(7, 10, 16, 0.85);
    color: #f4efe2;
}
.realm-admin__advanced {
    grid-column: 1 / -1;
    margin-top: 4px;
    padding-top: 14px;
    border-top: 1px solid rgba(230, 193, 90, 0.12);
}
.realm-admin__advanced-title {
    margin: 0 0 12px;
    color: #c9a45a;
    font-size: 12px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.realm-admin__actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 18px;
}
.realm-admin__button,
.realm-admin__button:visited {
    display: inline-block;
    padding: 10px 14px;
    border: 1px solid rgba(230, 193, 90, 0.24);
    border-radius: 10px;
    background: rgba(255, 198, 87, 0.12);
    color: #f6f0e5;
    text-decoration: none;
    font-weight: 700;
}
.realm-admin__button--danger {
    border-color: rgba(210, 82, 82, 0.35);
    background: rgba(176, 47, 47, 0.18);
}
@media (max-width: 980px) {
    .realm-admin__form-grid { grid-template-columns: 1fr; }
}
</style>
<div class="realm-admin">
<?php if (empty($_GET['action'])) { ?>
  <div class="realm-admin__intro">
    <strong>Realm Directory</strong><br>
    For now, the cleanest approach is to keep realms as a straightforward registry:
    name, address, ports, type, timezone, and optional RA/SOAP endpoints.
  </div>
  <div class="realm-admin__table-wrap">
    <table class="realm-admin__table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Address</th>
          <th>Port</th>
          <th>Type</th>
          <th>Timezone</th>
          <th>RA</th>
          <th>SOAP</th>
          <th>DB Info</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $item) { ?>
        <tr>
          <td><?php echo (int)$item['id']; ?></td>
          <td><a class="realm-admin__name" href="index.php?n=admin&amp;sub=realms&amp;action=edit&amp;id=<?php echo (int)$item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a></td>
          <td><?php echo htmlspecialchars($item['address']); ?></td>
          <td><?php echo (int)$item['port']; ?></td>
          <td><?php echo htmlspecialchars($realm_type_def[$item['icon']] ?? 'Unknown'); ?></td>
          <td><?php echo htmlspecialchars($realm_timezone_def[$item['timezone']] ?? 'Unknown'); ?></td>
          <td><?php echo htmlspecialchars($item['ra_address']); ?><br><span class="realm-admin__muted"><?php echo (int)$item['ra_port']; ?> / <?php echo htmlspecialchars($item['ra_user']); ?></span></td>
          <td><?php echo htmlspecialchars((string)($item['soap_address'] ?? '')); ?><br><span class="realm-admin__muted"><?php echo (int)($item['soap_port'] ?? 0); ?> / <?php echo htmlspecialchars((string)($item['soap_user'] ?? '')); ?></span></td>
          <td class="realm-admin__secret"><?php echo !empty($item['dbinfo']) ? 'Configured' : 'Not set'; ?></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>

  <div class="realm-admin__card">
    <h3>Create New Realm</h3>
    <p>Add a realm entry without crowding the list view with every secret field.</p>
    <form action="index.php?n=admin&amp;sub=realms&amp;action=create" method="post" onsubmit="return popup_ask('<?php echo $lang['sure_q']; ?>');">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_realms_csrf_token ?? spp_csrf_token('admin_realms')); ?>">
      <div class="realm-admin__form-grid">
        <div class="realm-admin__field"><label>Name</label><input type="text" name="name"></div>
        <div class="realm-admin__field"><label>Address</label><input type="text" name="address"></div>
        <div class="realm-admin__field"><label>Port</label><input type="text" name="port"></div>
        <div class="realm-admin__field"><label>Type</label><select name="icon"><?php foreach ($realm_type_def as $tmp_id => $tmp_name) { ?><option value="<?php echo (int)$tmp_id; ?>"><?php echo htmlspecialchars($tmp_name); ?></option><?php } ?></select></div>
        <div class="realm-admin__field"><label>Timezone</label><select name="timezone"><?php foreach ($realm_timezone_def as $tmp_id => $tmp_name) { ?><option value="<?php echo (int)$tmp_id; ?>"><?php echo htmlspecialchars($tmp_name); ?></option><?php } ?></select></div>
        <div class="realm-admin__field"><label>DB Info</label><input type="text" name="dbinfo"></div>
        <div class="realm-admin__advanced">
          <p class="realm-admin__advanced-title">Connection Details</p>
          <div class="realm-admin__form-grid">
            <div class="realm-admin__field"><label>RA Address</label><input type="text" name="ra_address"></div>
            <div class="realm-admin__field"><label>RA Port</label><input type="text" name="ra_port"></div>
            <div class="realm-admin__field"><label>RA Username</label><input type="text" name="ra_user"></div>
            <div class="realm-admin__field"><label>RA Password</label><input type="text" name="ra_pass"></div>
            <div class="realm-admin__field"><label>SOAP Address</label><input type="text" name="soap_address" value="127.0.0.1"></div>
            <div class="realm-admin__field"><label>SOAP Port</label><input type="text" name="soap_port" value="7878"></div>
            <div class="realm-admin__field"><label>SOAP Username</label><input type="text" name="soap_user"></div>
            <div class="realm-admin__field"><label>SOAP Password</label><input type="text" name="soap_pass"></div>
          </div>
        </div>
      </div>
      <div class="realm-admin__actions"><input class="realm-admin__button" type="submit" value="Create Realm"></div>
    </form>
  </div>
<?php } elseif ($_GET['action'] == 'edit') { ?>
  <div class="realm-admin__card">
    <h3>Edit Realm</h3>
    <p>Update the public listing and the optional RA/SOAP connection settings together.</p>
    <form action="index.php?n=admin&amp;sub=realms&amp;action=update&amp;id=<?php echo (int)$item['id']; ?>" method="post" onsubmit="return confirm('<?php echo $lang['sure_q']; ?>');">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_realms_csrf_token ?? spp_csrf_token('admin_realms')); ?>">
      <div class="realm-admin__form-grid">
        <div class="realm-admin__field"><label>Name</label><input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>"></div>
        <div class="realm-admin__field"><label>Address</label><input type="text" name="address" value="<?php echo htmlspecialchars($item['address']); ?>"></div>
        <div class="realm-admin__field"><label>Port</label><input type="text" name="port" value="<?php echo (int)$item['port']; ?>"></div>
        <div class="realm-admin__field"><label>Type</label><select name="icon"><?php foreach ($realm_type_def as $tmp_id => $tmp_name) { ?><option value="<?php echo (int)$tmp_id; ?>"<?php if ((int)$item['icon'] === (int)$tmp_id) echo ' selected'; ?>><?php echo htmlspecialchars($tmp_name); ?></option><?php } ?></select></div>
        <div class="realm-admin__field"><label>Timezone</label><select name="timezone"><?php foreach ($realm_timezone_def as $tmp_id => $tmp_name) { ?><option value="<?php echo (int)$tmp_id; ?>"<?php if ((int)$item['timezone'] === (int)$tmp_id) echo ' selected'; ?>><?php echo htmlspecialchars($tmp_name); ?></option><?php } ?></select></div>
        <div class="realm-admin__field"><label>DB Info</label><input type="text" name="dbinfo" value="<?php echo htmlspecialchars((string)$item['dbinfo']); ?>"></div>
        <div class="realm-admin__advanced">
          <p class="realm-admin__advanced-title">Connection Details</p>
          <div class="realm-admin__form-grid">
            <div class="realm-admin__field"><label>RA Address</label><input type="text" name="ra_address" value="<?php echo htmlspecialchars((string)$item['ra_address']); ?>"></div>
            <div class="realm-admin__field"><label>RA Port</label><input type="text" name="ra_port" value="<?php echo (int)$item['ra_port']; ?>"></div>
            <div class="realm-admin__field"><label>RA Username</label><input type="text" name="ra_user" value="<?php echo htmlspecialchars((string)$item['ra_user']); ?>"></div>
            <div class="realm-admin__field"><label>RA Password</label><input type="password" name="ra_pass" value="<?php echo htmlspecialchars((string)$item['ra_pass']); ?>"></div>
            <div class="realm-admin__field"><label>SOAP Address</label><input type="text" name="soap_address" value="<?php echo htmlspecialchars((string)($item['soap_address'] ?? '127.0.0.1')); ?>"></div>
            <div class="realm-admin__field"><label>SOAP Port</label><input type="text" name="soap_port" value="<?php echo (int)($item['soap_port'] ?? 7878); ?>"></div>
            <div class="realm-admin__field"><label>SOAP Username</label><input type="text" name="soap_user" value="<?php echo htmlspecialchars((string)($item['soap_user'] ?? '')); ?>"></div>
            <div class="realm-admin__field"><label>SOAP Password</label><input type="password" name="soap_pass" value="<?php echo htmlspecialchars((string)($item['soap_pass'] ?? '')); ?>"></div>
          </div>
        </div>
      </div>
      <div class="realm-admin__actions">
        <a class="realm-admin__button" href="index.php?n=admin&amp;sub=realms">Back to Realms</a>
        <input class="realm-admin__button" type="submit" value="Update Realm">
        <a class="realm-admin__button realm-admin__button--danger" href="<?php echo htmlspecialchars(spp_action_url('index.php', array('n' => 'admin', 'sub' => 'realms', 'action' => 'delete', 'id' => (int)$item['id']), 'admin_realms')); ?>" onclick="return popup_ask('<?php echo $lang['sure_q']; ?>');">Delete Realm</a>
      </div>
    </form>
  </div>
<?php } ?>
</div>
<?php builddiv_end() ?>
