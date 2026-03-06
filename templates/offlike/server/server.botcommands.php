
<?php builddiv_start(1, $lang['botcommands']); ?>

<div class="modern-content">
  <!--<img src="<?php //echo $currtmp; ?>/images/banner1.jpg" alt="Command Banner" class="ah-banner" />-->

  <input type="text" id="commandSearch" onkeyup="filterCommands()" placeholder="Search commands...">

  <table id="commandTable">
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
</tbody>

  </table>
</div>
<?php builddiv_end(); ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const headers = document.querySelectorAll("#commandTable th");
  let sortStack = [];

  headers.forEach(header => {
    header.addEventListener("click", e => {
      const table = header.closest("table");
      const body = table.querySelector("tbody");
      const index = [...header.parentNode.children].indexOf(header);
      const rows = Array.from(body.querySelectorAll("tr"));

      // Reset sort stack unless Shift held
      if (!e.shiftKey) sortStack = [];
      sortStack = sortStack.filter(s => s.index !== index);

      // Cycle sort state
      let state = header.dataset.state || "none";
      state = state === "none" ? "asc" : state === "asc" ? "desc" : "none";
      if (state !== "none") sortStack.push({ index, state });
      header.dataset.state = state;

      // Update header arrows
      headers.forEach(h => {
        const s = sortStack.find(x => x.index === [...headers].indexOf(h));
        h.textContent = h.textContent.replace(/[▲▼]/g, "") + (s ? (s.state === "asc" ? " ▲" : " ▼") : "");
        h.style.color = s ? "gold" : "";
      });

      // Sort rows by stack
      rows.sort((a, b) => {
        for (const s of sortStack) {
          const asc = s.state === "asc";
          const aText = a.cells[s.index]?.innerText.trim().toLowerCase() || "";
          const bText = b.cells[s.index]?.innerText.trim().toLowerCase() || "";
          const cmp = aText.localeCompare(bText, undefined, { numeric: true });
          if (cmp !== 0) return asc ? cmp : -cmp;
        }
        return 0;
      });

      rows.forEach(row => body.appendChild(row));
    });
  });
});
</script>
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


