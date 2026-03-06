
<style>
/* ---------- Search Box ---------- */
#commandSearch {
  width: 100%;
  padding: 10px 12px;
  margin: 240px 0 16px;
  border: 1px solid #444;
  border-radius: 6px;
  background: #0f0f0f;
  color: #eee;
  font-size: 1rem;
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
<?php

$realmId = isset($_GET['realm']) ? (int)$_GET['realm'] : 1;
switch ($realmId) {
    case 1:
        $db = [
            'host' => '192.168.1.13',
            'port' => 3310,
            'user' => 'root',
            'pass' => 'eltnub',
            'name' => 'classiccharacters'
        ];
        $world_db  = 'classicmangos';
        $tpl       = 'classic';
        $realmName = 'Classic';
        break;

    case 2:
        $db = [
            'host' => '192.168.1.13',
            'port' => 3310,
            'user' => 'root',
            'pass' => 'eltnub',
            'name' => 'tbccharacters'
        ];
        $world_db  = 'tbcmangos';
        $tpl       = 'tbc';
        $realmName = 'The Burning Crusade';
        break;

    case 4:
        $db = [
            'host' => '192.168.1.13',
            'port' => 3310,
            'user' => 'root',
            'pass' => 'eltnub',
            'name' => 'wotlkcharacters'
        ];
        $world_db  = 'wotlkmangos';
        $tpl       = 'wotlk';
        $realmName = 'Wrath of the Lich King';
        break;

    default:
        die("Invalid realm ID");
}

// ---------------------------------------------------------
// 2. Set up shared variables
// ---------------------------------------------------------


// ---------------------------------------------------------
// 3. Establish PDO connection
// ---------------------------------------------------------
try {
    $pdo = new PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
        $db['user'],
        $db['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("<b>Database connection failed:</b> " . htmlspecialchars($e->getMessage()));
}

// ---------------------------------------------------------
// 4. Example Query (you can remove or modify)
// ---------------------------------------------------------
/*
$example_sql = "
    SELECT a.id, a.houseid, i.name, i.Quality
    FROM {$db['name']}.auction a
    JOIN {$world_db}.item_template i ON i.entry = a.item_template
    LIMIT 10";
$stmt = $pdo->query($example_sql);
$example_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<pre>';
print_r($example_rows);
echo '</pre>';
*/

// ---------------------------------------------------------
// 6. Export key variables
// ---------------------------------------------------------
// Available globals after include:
//   $pdo          ? active PDO connection
//   $realmId      ? numeric realm (1, 2, 3)
//   $realmName    ? readable name (Classic, TBC, WotLK)
//   $world_db     ? corresponding world DB name
//   $icon_path    ? template path for icons
//   render_realm_selector($realmId, $realmName) ? render selector UI
/* ---------- Fetch GM Commands ---------- */
try {
    $sql = "
        SELECT name, security, help
        FROM `{$world_db}`.`command`
        ORDER BY security ASC, name ASC";
    $stmt = $pdo->query($sql);
    $gmCommands = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $gmCommands = [];
    echo "<div style='color:red;padding:10px;'>Query failed: "
       . htmlspecialchars($e->getMessage()) . "</div>";
}

?>



<?php builddiv_start(1, $lang['commands'],1); ?>
<div class="modern-content">

  <input type="text" id="commandSearch" onkeyup="filterCommands()" placeholder="Search GM commands...">

  <table id="commandTable">
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
<?php builddiv_end(); ?>






<script>
function filterCommands() {
  const input = document.getElementById("commandSearch");
  const filter = input.value.toUpperCase();
  const rows = document.querySelectorAll("#commandTable tbody tr");

  rows.forEach(row => {
    const name = row.querySelector("summary").textContent.toUpperCase();
    row.style.display = name.includes(filter) ? "" : "none";
  });
}
</script>
