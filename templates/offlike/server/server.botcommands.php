<?php
require_once(dirname(__FILE__, 4).'/core/xfer/com_db.php');
require_once(dirname(__FILE__, 4).'/core/xfer/com_search.php');
$botCommands = loadCommands($pdo,$world_db,'bot'); 
?>


<?php builddiv_start(1, $lang['botcommands']); ?>

<div class="modern-content">
  <!--<img src="<?php //echo $currtmp; ?>/images/banner1.jpg" alt="Command Banner" class="ah-banner" />-->

<input type="text" id="commandSearch" onkeyup="filterTable('commandSearch','commandTable')" placeholder="Search commands...">

<table id="commandTable" class="sortable">
    <thead>
      <tr>
        <th><?php echo $lang['command_name'] ?? 'Command Name'; ?></th>
        <th><?php echo $lang['category'] ?? 'Category'; ?></th>
        <th><?php echo $lang['subcategory'] ?? 'Subcategory'; ?></th>
        <th><?php echo $lang['security_level'] ?? 'Security'; ?></th>
      </tr>
    </thead>
<tbody>
  <?php foreach($botCommands as $topic): 
    $catClass = strtolower(str_replace([' ', '/'], '-', $topic['category']));
    $subClass = strtolower(str_replace([' ', '/'], '-', $topic['subcategory'] ?: 'none'));
  ?>
  <tr class="cat-<?php echo $catClass; ?> sub-<?php echo $subClass; ?>">
    <td>
      <details>
        <summary><?php echo htmlspecialchars($topic['name']); ?></summary>
        <p><?php echo nl2br(htmlspecialchars($topic['help'])); ?></p>
      </details>
    </td>
    <td><?php echo htmlspecialchars($topic['category']); ?></td>
    <td><?php echo htmlspecialchars($topic['subcategory'] ?: '-'); ?></td>
    <td align="center"><b><?php echo htmlspecialchars($topic['security']); ?></b></td>
  </tr>
  <?php endforeach; ?>
        <?php if (empty($botCommands)): ?>
      <tr><td colspan="2" style="text-align:center;color:#888;">No Bot commands found :(.</td></tr>
      <?php endif; ?>
</tbody>

  </table>
</div>
<?php builddiv_end(); ?>
<script src="/templates/offlike/js/commands.js"></script>




