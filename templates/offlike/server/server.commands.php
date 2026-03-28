
<?php
require_once(dirname(__FILE__, 4).'/core/xfer/com_db.php');
require_once(dirname(__FILE__, 4).'/core/xfer/com_search.php');
$gmCommands = loadCommands($pdo,$world_db,'gm');

$userGmLevel = (int)($user['gmlevel'] ?? 0);
if (($user['id'] ?? 0) > 0) {
  $gmCommands = array_values(array_filter($gmCommands, function ($cmd) use ($userGmLevel) {
    return (int)($cmd['security'] ?? 0) <= $userGmLevel;
  }));
}
?> 


<?php builddiv_start(1, $lang['commands']); ?>
<div class="modern-content">

  <input type="text" id="commandSearch" onkeyup="filterTable('commandSearch','commandTable')" placeholder="Search GM commands...">

<table id="commandTable" class="sortable">
    <thead>
      <tr>
        <th><?php echo $lang['command_name'] ?? 'Command Name'; ?></th>
        <th><?php echo $lang['security_level'] ?? 'Security'; ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($gmCommands as $cmd): ?>
      <tr>
        <td>
          <details>
            <summary><?php echo htmlspecialchars($cmd['name']); ?></summary>
            <p><?php echo nl2br(htmlspecialchars($cmd['help'])); ?></p>
          </details>
        </td>
        <td align="center"><b><?php echo htmlspecialchars($cmd['security']); ?></b></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($gmCommands)): ?>
      <tr><td colspan="2" style="text-align:center;color:#888;">No GM commands found for this account level.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<script src="/templates/offlike/js/commands.js"></script>
<?php builddiv_end(); ?>






