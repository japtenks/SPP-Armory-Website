
<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/xfer/includes/com_db.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/xfer/includes/com_search.php');
$gmCommands = loadCommands($pdo,$world_db,'gm'); 
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
      <tr><td colspan="2" style="text-align:center;color:#888;">No GM commands found for your level.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<script src="/js/commands.js"></script>
<?php builddiv_end(); ?>






