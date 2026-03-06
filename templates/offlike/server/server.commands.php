<?php builddiv_start(1, $lang['commands']); ?>
<div class="modern-content">
  <!--uncomment to add imgage
  <img src="<?php //echo $currtmp; ?>/images/banner1.jpg" alt="Command Banner" class="ah-banner" />-->

  <input type="text" id="commandSearch" onkeyup="filterCommands()" placeholder="Search commands...">

  <table id="commandTable">
    <thead>
      <tr>
        <th><?php echo $lang['command_name'] ?? 'Command Name'; ?></th>
        <th><?php echo $lang['security_level'] ?? 'Security Level'; ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($alltopics as $topic): ?>
      <tr>
        <td>
          <details>
            <summary><?php echo htmlspecialchars($topic['name']); ?></summary>
            <p><?php echo nl2br(htmlspecialchars($topic['help'])); ?></p>
          </details>
        </td>
        <td class="serverStatus" align="center">
          <b><?php echo htmlspecialchars($topic['security']); ?></b>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php builddiv_end(); ?>

<script>
function filterCommands() {
  const input = document.getElementById("commandSearch");
  const filter = input.value.toUpperCase();
  const table = document.getElementById("commandTable");
  const tr = table.getElementsByTagName("tr");

  for (let i = 1; i < tr.length; i++) {
    const td = tr[i].getElementsByTagName("td")[0];
    if (td) {
      const txtValue = td.textContent || td.innerText;
      tr[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
    }
  }
}
</script>
