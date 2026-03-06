<style>


/* ---------- Search Box ---------- */
#commandSearch {
  width: 100%;
  padding: 10px 12px;
  margin-bottom: 16px;
  border: 1px solid #444;
  border-radius: 6px;
  background: #0f0f0f;
  color: #eee;
  font-size: 1rem;
  margin-top: 240px; /*modify to liking*/
}
#commandSearch:focus {
  border-color: #ffcc66;
  outline: none;
}

/* ---------- Command Table ---------- */
#commandTable {
  width: 100%;
  border-collapse: collapse;
  color: #ddd;
}

#commandTable th {
  background: linear-gradient(to bottom, #2a2a2a, #1a1a1a);
  color: #ffcc66;
  font-weight: bold;
  text-align: left;
  padding: 8px;
  border-bottom: 1px solid #333;
  text-transform: uppercase;
}

#commandTable td {
  padding: 10px;
  border-bottom: 1px solid #222;
  vertical-align: top;
}

#commandTable tr:nth-child(even) {
  background: rgba(255,255,255,0.03);
}

#commandTable tr:hover {
  background: rgba(255,204,102,0.08);
}

/* ---------- Details + Summary ---------- */
details summary {
  cursor: pointer;
  font-weight: bold;
  color: #7abaff;
  transition: color 0.3s;
}
details summary:hover {
  color: #ffd97a;
}
details p {
  margin-top: 8px;
  color: #ccc;
  line-height: 1.4;
}

/* ---------- Security Level ---------- */
td.serverStatus b {
  color: #aefb6b;
}

/* ---------- Banner ---------- */
img.ah-banner {
  display: block;
  margin: 0 auto 16px auto;
  max-width: 100%;
  border-radius: 6px;
  box-shadow: 0 0 10px rgba(0,0,0,0.6);
}
/* ===== CATEGORY COLORS ===== */
tr.cat-chat-commands  { background: rgba(122,186,255,0.05); }
tr.cat-chat-filters   { background: rgba(102,255,153,0.05); }
tr.cat-macros         { background: rgba(255,204,102,0.05); }
tr.cat-strategies     { background: rgba(255,122,191,0.05); }
tr.cat-system-commands{ background: rgba(255,102,102,0.05); }

/* ===== CATEGORY TITLE COLORS ===== */
tr.cat-chat-commands  td:nth-child(2) { color: #7abaff; }
tr.cat-chat-filters   td:nth-child(2) { color: #66ff99; }
tr.cat-macros         td:nth-child(2) { color: #ffcc66; }
tr.cat-strategies     td:nth-child(2) { color: #ff7abf; }
tr.cat-system-commands td:nth-child(2){ color: #ff6666; }

/* ===== SUBCATEGORY COLORS ===== */
tr.sub-combat td:nth-child(3)         { color: #ff4444; }
tr.sub-group td:nth-child(3)          { color: #99ccff; }
tr.sub-guild td:nth-child(3)          { color: #ffaa33; }
tr.sub-inventory td:nth-child(3)      { color: #ffcc99; }
tr.sub-utility td:nth-child(3)        { color: #66ffcc; }
tr.sub-quest-interaction td:nth-child(3){ color: #a1ff66; }
tr.sub-training td:nth-child(3)       { color: #cc99ff; }
tr.sub-bot-management td:nth-child(3) { color: #ff9999; }
tr.sub-behavior td:nth-child(3)       { color: #ffaaee; }



</style>




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


