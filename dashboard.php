<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Homelab Dashboard</title>
<style>
  body {
    background:#111; color:#eee; font-family:sans-serif;
    text-align:center; padding:30px;
  }
  table {
    margin:0 auto; border-collapse:collapse; width:90%;
    background:#222; box-shadow:0 0 10px #000;
  }
  th, td { padding:10px; border-bottom:1px solid #333; }
  button {
    background:#333; color:#eee; border:none;
    padding:6px 10px; margin:2px; border-radius:5px; cursor:pointer;
  }
  button:hover { background:#555; }
</style>
</head>
<body>
<h1>Homelab Service Control</h1>



<?php
// === CONFIG ===
$API_URL = "http://192.168.1.20:5055";   // Flask API on Proxmox host
$API_KEY = "dd6d06fb38f8dcb6183fc645c534cbc1d2bcb62eb2eafc26";       // match your key in /root/lxc_api.py

// ===Helpers ===
function get_lxc_uptime($url, $key, $cid) {
  $r = api_post("$url/lxc", ["cid"=>$cid,"action"=>"status"], $key);
  return $r["uptime"] ?? "";
}


// === Map container IDs to services ===
$services = [
  "102" => ["name" => "Loginserver", "svc" => "realmd"],
  "103" => ["name" => "TBC Realm",   "svc" => "mangosd"],
  "104" => ["name" => "Classic Realm", "svc" => "mangosd"],
  "106" => ["name" => "Database", "svc" => "mariadb"],  // MySQL container
];

// === Generic API POST ===
function api_post($endpoint, $payload, $key) {
  $opts = [
    "http" => [
      "method"  => "POST",
      "header"  => "Content-Type: application/json\r\nX-API-Key: $key\r\n",
      "content" => json_encode($payload),
      "timeout" => 6
    ]
  ];
  $ctx = stream_context_create($opts);
  $response = @file_get_contents($endpoint, false, $ctx);
  return $response ? json_decode($response, true) : ["status"=>"unreachable"];
}

// === Button actions ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $cid = $_POST["cid"];
  $service = $_POST["service"];
  $action = $_POST["action"];
  api_post("$API_URL/service/action", [
    "cid"=>$cid, "service"=>$service, "action"=>$action
  ], $API_KEY);
  header("Refresh:1");
  exit;
}

// === Get LXC state ===
function get_lxc_status($url, $key, $cid) {
  $r = api_post("$url/lxc", ["cid"=>$cid,"action"=>"status"], $key);
  return (strpos($r["result"]??"", "running")!==false) ? "running" : "stopped";
}

// === Get service state ===
function get_service_status($url, $key, $cid, $svc) {
  $r = api_post("$url/service", ["cid"=>$cid,"service"=>$svc], $key);
  return $r["status"] ?? "unknown";
}
?>

<table>
<tr><th>Service</th><th>LXC</th><th>Uptime</th><th>Process</th><th>Actions</th></tr>

<?php foreach ($services as $cid=>$data):
  $lxc = get_lxc_status($API_URL,$API_KEY,$cid);
  $svc = get_service_status($API_URL,$API_KEY,$cid,$data["svc"]);

  // color logic
  $lxc_color = ($lxc=="running") ? "lime" : "red";
  switch ($svc) {
    case "active":    $svc_color="lime"; break;
    case "inactive":  $svc_color="red";  break;
    case "failed":    $svc_color="orange"; break;
    default:          $svc_color="#aaa"; break;
  }
?>
<?php 
$lxc = get_lxc_status($API_URL,$API_KEY,$cid);
$uptime = get_lxc_uptime($API_URL,$API_KEY,$cid);
$svc = get_service_status($API_URL,$API_KEY,$cid,$data["svc"]);
$lxc_color = ($lxc=="running")?"lime":"red";
$svc_color = ($svc=="active")?"lime":(($svc=="inactive")?"red":"orange");
?>
<tr>
  <td><?= htmlspecialchars($data["name"]) ?> (LXC <?= $cid ?>)</td>
  <td style="color:<?= $lxc_color ?>"><?= $lxc ?></td>
  <td style="color:#aaa"><?= htmlspecialchars($uptime) ?></td>
  <td style="color:<?= $svc_color ?>"><?= htmlspecialchars($svc) ?></td>
  <td>  <?php if ($cid != "106"): ?>
    <form method="post" style="display:inline">
      <input type="hidden" name="cid" value="<?= $cid ?>">
      <input type="hidden" name="service" value="<?= htmlspecialchars($data["svc"]) ?>">
      <button name="action" value="start">Start</button>
      <button name="action" value="stop">Stop</button>
      <button name="action" value="restart">Restart</button>
      <button type="button" onclick="showLogs('<?= $cid ?>','<?= $data['svc'] ?>')">Logs</button>
      <button type="button" onclick="editConfig('<?= $cid ?>','<?= $data['svc'] ?>')">Config</button>
    </form>
  <?php else: ?>
    <span style="color:#888">Always on</span>
  <?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>

<script>
async function showLogs(cid, svc) {
  const res = await fetch("<?= $API_URL ?>/service/logs", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-API-Key": "<?= $API_KEY ?>"
    },
    body: JSON.stringify({ cid: cid, service: svc })
  });
  const data = await res.json();
  const output = data.log ? data.log : JSON.stringify(data);
  const w = window.open("", "_blank", "width=900,height=600,scrollbars=yes,resizable=yes");
  w.document.write("<pre style='background:#111;color:#0f0;padding:10px;font-family:monospace;white-space:pre-wrap'>" +
                   output.replace(/[<>&]/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[c])) +
                   "</pre>");
  w.document.title = svc.toUpperCase() + " Logs (LXC " + cid + ")";
}
</script>

