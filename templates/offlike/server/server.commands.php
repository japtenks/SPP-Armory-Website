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
</style>


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
