

<style>

/* Search bar */
#commandSearch {
  width: 100%;
  padding: 10px 12px;
  margin-bottom: 16px;
  border: 1px solid #444;
  border-radius: 6px;
  background: #0f0f0f;
  color: #eee;
}
#commandSearch:focus { border-color: #ffcc66; outline: none; }

/* Table */
#commandTable { width: 100%; border-collapse: collapse; }
#commandTable th {
  background: linear-gradient(to bottom,#2a2a2a,#1a1a1a);
  color: #ffcc66;
  font-weight: bold;
  text-align: left;
  padding: 8px;
  border-bottom: 1px solid #333;
}
#commandTable td {
  padding: 10px;
  border-bottom: 1px solid #222;
  vertical-align: top;
}
#commandTable tr:nth-child(even) { background: rgba(255,255,255,0.03); }
#commandTable tr:hover { background: rgba(255,204,102,0.08); }

/* Details + Summary */
details summary {
  cursor: pointer;
  font-weight: bold;
  color: #7abaff;
  transition: color 0.3s;
}
details summary:hover { color: #ffd97a; }
details p { margin-top: 8px; color: #ccc; line-height: 1.4; }

/* Security Level Badges */
.sec-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 6px;
  font-weight: bold;
  font-size: 0.85rem;
  text-align: center;
  min-width: 70px;
  text-transform: capitalize;
}
.sec-0 { background:#333; color:#ccc; }      /* Player */
.sec-1 { background:#2b542c; color:#aefb6b; }/* GM */
.sec-2 { background:#184a78; color:#9bd7ff; }/* Moderator */
.sec-3 { background:#8a5200; color:#ffd97a; }/* Admin */
.sec-4 { background:#792020; color:#ff8a8a; }/* Head GM */
.sec-5,.sec-6 { background:#500; color:#ff6666; }/* Root */

/* Banner */
img.ah-banner {
  display:block;
  margin:0 auto 16px auto;
  max-width:100%;
  border-radius:6px;
  box-shadow:0 0 10px rgba(0,0,0,0.6);
}
</style>


<?php
if (INCLUDED !== true) exit;

$pathway_info[] = array('title' => $lang['bot_commands'], 'link' => '');
$userlevel = ($user['gmlevel'] != '' ? $user['gmlevel'] : 0);

echo "<div style='background:#111;color:#0f0;padding:6px;'>[DEBUG] DB: "
   . htmlspecialchars($DB->_link_id->host_info ?? 'unknown') . "</div>";

/* ---------- Force correct schema ---------- */
$DB->select_db('tbcarmory');

/* ---------- Load bot commands ---------- */
$botCommands = $DB->select("
    SELECT name, security, help
    FROM bot_command
    WHERE security <= $userlevel
    ORDER BY name ASC
");

/* ---------- Debug Output ---------- */
if (empty($botCommands)) {
    echo "<div style='color:red;padding:8px;'>No bot commands found.</div>";
} else {
    echo "<div style='color:lime;padding:6px;'>Loaded " . count($botCommands) . " commands.</div>";
}
?>

<?php builddiv_start(1, $lang['bot_commands']); ?>
<img src="<?php echo $currtmp; ?>/images/banner1.jpg" alt="Bot Command Banner" class="ah-banner" />
<input type="text" id="commandSearch" onkeyup="filterCommands()" placeholder="Search bot commands...">

<table id="commandTable">
  <thead>
    <tr>
      <th>Command</th>
      <th>Security</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($botCommands as $cmd): ?>
      <tr>
        <td>
          <details>
            <summary><?php echo htmlspecialchars($cmd['name']); ?></summary>
            <p><?php echo nl2br(htmlspecialchars($cmd['help'])); ?></p>
          </details>
        </td>
        <td align="center"><?php echo (int)$cmd['security']; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
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
