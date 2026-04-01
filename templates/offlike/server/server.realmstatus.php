
<?php
if (INCLUDED !== true) exit;

/* ---------- Helpers ---------- */
function parse_time($n){
  return [
    'd'=>intval($n/86400),
    'h'=>intval(($n%86400)/3600),
    'm'=>intval(($n%3600)/60),
    's'=>$n%60
  ];
}
function print_time($t){
  global $lang;
  $out=[];
  if($t['d']>0)$out[]=$t['d'].$lang['rs_days'];
  if($t['h']>0)$out[]=$t['h'].$lang['rs_hours'];
  if($t['m']>0)$out[]=$t['m'].$lang['rs_minutes'];
  if($t['s']>0)$out[]=$t['s'].$lang['rs_seconds'];
  echo implode(', ',$out);
}
function format_time($n){
  if (!is_numeric($n) || (int)$n <= 0) {
    return '-';
  }
  $t = parse_time((int)$n);
  $out = [];
  global $lang;
  if($t['d']>0)$out[]=$t['d'].$lang['rs_days'];
  if($t['h']>0)$out[]=$t['h'].$lang['rs_hours'];
  if($t['m']>0)$out[]=$t['m'].$lang['rs_minutes'];
  if($t['s']>0)$out[]=$t['s'].$lang['rs_seconds'];
  return empty($out) ? '-' : implode(', ',$out);
}

function connect_realm_db($target, $realmId){
  if (!function_exists('spp_get_db_config')) {
    return null;
  }

  try {
    $cfg = spp_get_db_config($target, $realmId);
  } catch (Throwable $e) {
    return null;
  }

  try {
    return new PDO(
      'mysql:host='.$cfg['host'].';port='.(int)$cfg['port'].';dbname='.$cfg['name'].';charset=utf8mb4',
      $cfg['user'],
      $cfg['pass'],
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
  } catch (PDOException $e) {
    return null;
  }
}

function realmstatus_probe_realm($host, $port, $timeout = 0.75){
  $host = trim((string)$host);
  $port = (int)$port;
  if ($host === '' || $port <= 0) {
    return false;
  }

  $errno = 0;
  $errstr = '';
  $socket = @fsockopen($host, $port, $errno, $errstr, (float)$timeout);
  if (!$socket) {
    return false;
  }

  fclose($socket);
  return true;
}

function realmstatus_fetch_uptime_stats(PDO $realmPdo, $realmId){
  $stats = [
    'starttime' => 0,
    'uptime' => 0,
    'avg_uptime' => 0,
    'median_uptime' => 0,
    'stable_avg_uptime' => 0,
    'stable_runs' => 0,
    'restart_count' => 0,
    'short_restarts' => 0,
    'recent' => false,
  ];

  $stmtLatest = $realmPdo->prepare("
    SELECT `starttime`, `uptime`
    FROM `uptime`
    WHERE `realmid` = ?
    ORDER BY `starttime` DESC
    LIMIT 1
  ");
  $stmtLatest->execute([(int)$realmId]);
  $latest = $stmtLatest->fetch(PDO::FETCH_ASSOC);

  if (is_array($latest) && !empty($latest['starttime'])) {
    $stats['starttime'] = (int)$latest['starttime'];
    $storedUptime = isset($latest['uptime']) ? (int)$latest['uptime'] : 0;
    $calculatedUptime = max(0, time() - $stats['starttime']);
    $stats['uptime'] = max($storedUptime, $calculatedUptime);
    $stats['recent'] = ($calculatedUptime <= 300);
  }

  $stmtAvg = $realmPdo->prepare("
    SELECT ROUND(AVG(`uptime`) / 3600, 2)
    FROM `uptime`
    WHERE `realmid` = ?
      AND `starttime` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))
  ");
  $stmtAvg->execute([(int)$realmId]);
  $avgUptime = $stmtAvg->fetchColumn();
  $stats['avg_uptime'] = $avgUptime !== false ? (float)$avgUptime : 0;

  $stmtRuns = $realmPdo->prepare("
    SELECT `uptime`
    FROM `uptime`
    WHERE `realmid` = ?
      AND `starttime` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))
    ORDER BY `starttime` DESC
  ");
  $stmtRuns->execute([(int)$realmId]);
  $runRows = $stmtRuns->fetchAll(PDO::FETCH_COLUMN) ?: [];
  $allRuns = [];
  $stableRuns = [];
  foreach ($runRows as $uptimeValue) {
    $uptimeSec = (int)$uptimeValue;
    if ($uptimeSec <= 0) {
      $stats['short_restarts']++;
      continue;
    }
    $allRuns[] = $uptimeSec;
    if ($uptimeSec < 900) {
      $stats['short_restarts']++;
    } else {
      $stableRuns[] = $uptimeSec;
    }
  }
  if (!empty($allRuns)) {
    sort($allRuns, SORT_NUMERIC);
    $mid = (int)floor(count($allRuns) / 2);
    $stats['median_uptime'] = count($allRuns) % 2 === 0
      ? (int)round(($allRuns[$mid - 1] + $allRuns[$mid]) / 2)
      : (int)$allRuns[$mid];
  }
  if (!empty($stableRuns)) {
    $stats['stable_runs'] = count($stableRuns);
    $stats['stable_avg_uptime'] = round((array_sum($stableRuns) / count($stableRuns)) / 3600, 2);
  }

  $stmtRestarts = $realmPdo->prepare("
    SELECT COUNT(*)
    FROM `uptime`
    WHERE `realmid` = ?
      AND `starttime` >= UNIX_TIMESTAMP(CURDATE())
  ");
  $stmtRestarts->execute([(int)$realmId]);
  $restartCount = $stmtRestarts->fetchColumn();
  $stats['restart_count'] = $restartCount !== false ? (int)$restartCount : 0;

  return $stats;
}

$realmstatusDebug = !empty($_GET['debug']);

/* ---------- Realm Data Build ---------- */
$realm_flags_def=[0=>"Normal",1=>"PvP",4=>"RP",8=>"RPPvP"];
$items=[];
$realmPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
$realms=$realmPdo->query("SELECT * FROM `realmlist` ORDER BY `id` ASC")->fetchAll(PDO::FETCH_ASSOC);

foreach($realms as $r){
  $realm_id=(int)$r['id'];
  $realm_name=$r['name'];
  $realm_type=$realm_flags_def[$r['realmflags']&0x0F]??"Normal";
  $build_ver=trim($r['realmbuilds']);

  // Determine expansion by build version
  $exp="classic";
  if(preg_match('/8[6-9][0-9]{2}/',$build_ver))$exp="tbc";
  elseif(preg_match('/[12][0-9]{4}/',$build_ver))$exp="wotlk";

  $charCfg = null;
  $worldCfg = null;
  try {
    $charCfg = spp_get_db_config('chars', $realm_id);
  } catch (Throwable $e) {}
  try {
    $worldCfg = spp_get_db_config('world', $realm_id);
  } catch (Throwable $e) {}

  $charDbName = $charCfg['name'] ?? '';
  $worldDbName = $worldCfg['name'] ?? '';
  $dbHost = $charCfg['host'] ?? ($worldCfg['host'] ?? '');
  $dbPort = (int)($charCfg['port'] ?? ($worldCfg['port'] ?? 0));
  $realmHost = trim((string)($r['address'] ?? ''));
  $realmPort = (int)($r['port'] ?? 0);

  $CHDB_EXTRA = connect_realm_db('chars', $realm_id);
  $WSDB_EXTRA = connect_realm_db('world', $realm_id);
  $hasCharData = $CHDB_EXTRA ? 1 : 0;
  $hasWorldData = $WSDB_EXTRA ? 1 : 0;

  // The realm socket is the best up/down signal; DB access is only supporting data.
  $realmReachable = realmstatus_probe_realm($realmHost, $realmPort);
  $uptimeStats = realmstatus_fetch_uptime_stats($realmPdo, $realm_id);
  $hasRecentUptime = !empty($uptimeStats['recent']);
  $is_online = $realmReachable;
  $res_color = $is_online ? 1 : 0;
  $res_label = $is_online ? 'up' : 'down';
  $res_img = $is_online
    ? 'templates/offlike/images/modern/status/uparrow2.gif'
    : 'templates/offlike/images/modern/status/downarrow2.gif';

  // uptime stats
  $uptime = (int)$uptimeStats['uptime'];
  $avg_uptime = (float)$uptimeStats['avg_uptime'];
  $median_uptime = (int)($uptimeStats['median_uptime'] ?? 0);
  $stable_avg_uptime = (float)($uptimeStats['stable_avg_uptime'] ?? 0);
  $restart_count = (int)$uptimeStats['restart_count'];
  $short_restarts = (int)($uptimeStats['short_restarts'] ?? 0);
  if($restart_count==0 && ($is_online || $hasRecentUptime))$restart_count=1;
 

  // player/faction/progression
  $pop=0;$online=0;$alli=0;$horde=0;$avg_lvl=0;$max_lvl=0;$avg_ilvl=0;
  $progress=['MC','Ony','BWL','ZG','AQ','Naxx','Kara','SSC','TK','BT','SWP','Naxx25','Ulduar','ICC'];
  foreach($progress as $p)$state[$p]='uncleared';

    if($CHDB_EXTRA){
      $pop=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `characters` WHERE xp > 0 AND level >= 1")->fetchColumn();
      $online=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `characters` WHERE online=1")->fetchColumn();
      $alli=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `characters` WHERE online=1 AND race IN (1,3,4,7,11)")->fetchColumn();
      $horde=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `characters` WHERE online=1 AND race IN (2,5,6,8,10)")->fetchColumn();
      $avg_lvl=$CHDB_EXTRA->query("SELECT ROUND(AVG(level),1) FROM `characters` WHERE online=1")->fetchColumn();
      $max_lvl=(int)$CHDB_EXTRA->query("SELECT MAX(level) FROM `characters`")->fetchColumn();
      if($worldDbName !== ''){
        try {
          $avg_ilvl = $CHDB_EXTRA->query("
            SELECT ROUND(AVG(char_avg.avg_item_level), 1)
            FROM (
              SELECT
                ci.guid,
                AVG(it.ItemLevel) AS avg_item_level
              FROM `character_inventory` ci
              INNER JOIN `characters` c ON c.guid = ci.guid
              INNER JOIN `".$worldDbName."`.`item_template` it ON it.entry = ci.item_template
              WHERE c.online = 1
                AND ci.bag = 0
                AND ci.slot BETWEEN 0 AND 18
                AND ci.slot NOT IN (3, 18)
                AND ci.item_template > 0
              GROUP BY ci.guid
            ) AS char_avg
          ")->fetchColumn();
        } catch (Throwable $e) {
          $avg_ilvl = 0;
        }
      }

      // ---- Progression checks per expansion ----
      if($exp=="classic"){
        $state['MC']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (16866,16854,16867,16868,16865,16863,16861,16862)")->fetchColumn()>3?'cleared':'uncleared';
        $state['Ony']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (16963,16964,16965,16966,16967,16968,16969,16970)")->fetchColumn()>3?'cleared':'uncleared';
        $state['BWL']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (16911,16924,16932,16940,16945,16953,16961,16968)")->fetchColumn()>3?'partial':'uncleared';
        $state['ZG']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (19802,19854,19822,19862,19848,19910)")->fetchColumn()>3?'cleared':'uncleared';
        $state['AQ']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (21329,21330,21331,21332,21333,21220)")->fetchColumn()>3?'partial':'uncleared';
        $state['Naxx']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (22416,22417,22418,22419,22420,22421,22422,22423)")->fetchColumn()>2?'partial':'uncleared';
      }
      elseif($exp=="tbc"){
        $state['Kara']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (29066,29067,29068,29069,29070)")->fetchColumn()>3?'cleared':'uncleared';
        $state['SSC']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (30245,30246,30247,30248,30249)")->fetchColumn()>3?'partial':'uncleared';
        $state['TK']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (30233,30234,30235,30236,30237)")->fetchColumn()>3?'partial':'uncleared';
        $state['BT']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (30969,30970,30971,30972,30974)")->fetchColumn()>3?'partial':'uncleared';
        $state['SWP']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (34332,34333,34334,34335,34336)")->fetchColumn()>2?'partial':'uncleared';
      }
      elseif($exp=="wotlk"){
        $state['Naxx25']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (40554,40557,40559,40560,40562)")->fetchColumn()>3?'cleared':'uncleared';
        $state['Ulduar']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (45340,45341,45342,45343,45344)")->fetchColumn()>3?'partial':'uncleared';
        $state['ICC']=(int)$CHDB_EXTRA->query("SELECT COUNT(*) FROM `item_instance` WHERE itemEntry IN (51155,51156,51157,51158,51159)")->fetchColumn()>3?'partial':'uncleared';
      }
    }

  unset($CHDB_EXTRA, $WSDB_EXTRA);
  

  $items[]=[
    'id'=>$realm_id,'name'=>$realm_name,'type'=>$realm_type,'build'=>$build_ver,
    'exp'=>$exp,'pop'=>$pop,'online'=>$online,'alli'=>$alli,'horde'=>$horde,'uptime'=>$uptime,
    'avg_up'=>$avg_uptime,'median_up'=>$median_uptime,'stable_avg_up'=>$stable_avg_uptime,'stable_runs'=>(int)($uptimeStats['stable_runs'] ?? 0),'short_restarts'=>$short_restarts,'restarts'=>$restart_count,'avg_lvl'=>$avg_lvl,'max_lvl'=>$max_lvl,'avg_ilvl'=>$avg_ilvl,
    'state'=>$state,'res_color'=>$res_color,'status_label'=>$res_label,'img'=>$res_img,
    'debug'=>[
      'realm_host' => (string)$dbHost,
      'realm_port' => $dbPort,
      'realm_socket_host' => $realmHost,
      'realm_socket_port' => $realmPort,
      'realm_reachable' => $realmReachable ? 1 : 0,
      'recent_uptime' => $hasRecentUptime ? 1 : 0,
      'char_db' => (string)$charDbName,
      'world_db' => (string)$worldDbName,
      'char_ok' => $hasCharData,
      'world_ok' => $hasWorldData,
    ],
    'has_char_data' => $hasCharData,
  ];
}
?>

<style>
.progression .avg-ilvl{
  display:inline-block;
  margin-left:14px;
  color:#d8c99d;
}
</style>

<?php builddiv_start(1,$lang['realm_status']); ?>
<div class="modern-content">
  <?php header_image("realm"); ?>
  <div class="modern-desc">
    <?php
        $up   = '<span class="status up">▲ '.$lang['up'].'</span>';
        $down = '<span class="status down">▼ '.$lang['down'].'</span>';
      $link='<a href="index.php?n=forum">'.$lang['realm_status_forum'].'</a>';
      $up = '<span class="status up">&#9650; '.$lang['up'].'</span>';
      $down = '<span class="status down">&#9660; '.$lang['down'].'</span>';
      $desc=str_replace(['[up]','[down]'],[$up,$down],$lang['realmstatus_desc']);
      echo $desc;
    ?>
  </div>

  <div class="legend">
    <span>&#128994; <span class="cleared">Cleared</span></span>
    <span>&#128993; <span class="partial">Partial</span></span>
    <span>&#128308; <span class="uncleared">Uncleared</span></span>
  </div>

  <div class="realm-list">
    <?php foreach($items as $r): ?>
    <div class="realm-card <?php echo $r['res_color']==1?'online':'offline'; ?>">
      <div class="realm-header">
        <img src="<?php echo htmlspecialchars($r['img']); ?>" alt="<?php echo htmlspecialchars($r['status_label']); ?>" class="realm-icon"/>
        <span class="realm-name"><?php echo htmlspecialchars($r['name']); ?></span>
        <span style="color:#888;font-size:.9rem;">(Build: <?php echo htmlspecialchars($r['build']); ?>)</span>
      </div>

      <div class="realm-body">
        <div><strong><?php echo $lang['uptime']; ?>:</strong> 
          <?php if($r['uptime']>1)print_time(parse_time($r['uptime']));else echo '-'; ?>
        </div>
        <div><strong>Stable Avg Uptime (7d):</strong> <?php echo !empty($r['stable_avg_up']) ? $r['stable_avg_up'].' hrs' : '-'; ?></div>
        <div><strong>Median Uptime (7d):</strong> <?php echo htmlspecialchars(format_time($r['median_up'])); ?></div>
        <div><strong>Short Restarts (7d):</strong> <?php echo (int)$r['short_restarts']; ?><?php echo !empty($r['stable_runs']) ? ' (' . (int)$r['stable_runs'] . ' stable runs kept)' : ''; ?></div>
        <div><strong>Restarts Today:</strong> <?php echo $r['restarts']; ?></div>
        <div><strong><?php echo $lang['si_type']; ?>:</strong> <?php echo htmlspecialchars($r['type']); ?></div>
        <div><strong><?php echo $lang['si_pop']; ?>:</strong> 
          <?php if(!empty($r['has_char_data']))echo $r['pop']." (".population_view($r['pop']).")";else echo '-'; ?>
        </div>
        <div><strong>Online:</strong> <?php echo !empty($r['has_char_data']) ? (int)$r['online'] : '-'; ?></div>

          <?php if(!empty($r['has_char_data']) && $r['alli']+$r['horde']>0):
            $a=round(($r['alli']/max(1,($r['alli']+$r['horde'])))*100);
            $h=100-$a;
            $balanceClass='balanced';
            $balanceText='Balanced Population';
            if($a>60){$balanceClass='alliance';$balanceText='Alliance Favored';}
            elseif($h>60){$balanceClass='horde';$balanceText='Horde Favored';}
          ?>
            <div class="faction-labels">
              <span class="alliance">Alliance (<?php echo $r['alli']; ?>)</span>
              <span class="horde">Horde (<?php echo $r['horde']; ?>)</span>
            </div>
            <div class="faction-bar">
              <div class="alliance" style="width:<?php echo $a; ?>%"></div>
              <div class="horde" style="width:<?php echo $h; ?>%"></div>
            </div>
            <div class="faction-balance <?php echo $balanceClass; ?>">
              <?php echo $balanceText; ?>
            </div>
          <?php endif; ?>
        </div>

        <div><strong>Avg / Max Level:</strong> 
          <?php echo ($r['avg_lvl']?$r['avg_lvl']:'-').' / '.($r['max_lvl']?$r['max_lvl']:'-'); ?>
        </div>

        <div class="progression">
          <strong>Progression:</strong>
          <?php if($r['exp']=='classic'){ ?>
            <span class="<?php echo $r['state']['MC']; ?>">MC</span>
            <span class="<?php echo $r['state']['Ony']; ?>">Ony</span>
            <span class="<?php echo $r['state']['BWL']; ?>">BWL</span>
            <span class="<?php echo $r['state']['ZG']; ?>">ZG</span>
            <span class="<?php echo $r['state']['AQ']; ?>">AQ</span>
            <span class="<?php echo $r['state']['Naxx']; ?>">Naxx</span>
          <?php } elseif($r['exp']=='tbc'){ ?>
            <span class="<?php echo $r['state']['Kara']; ?>">Kara</span>
            <span class="<?php echo $r['state']['SSC']; ?>">SSC</span>
            <span class="<?php echo $r['state']['TK']; ?>">TK</span>
            <span class="<?php echo $r['state']['BT']; ?>">BT</span>
            <span class="<?php echo $r['state']['SWP']; ?>">SWP</span>
          <?php } elseif($r['exp']=='wotlk'){ ?>
            <span class="<?php echo $r['state']['Naxx25']; ?>">Naxx</span>
            <span class="<?php echo $r['state']['Ulduar']; ?>">Uld</span>
            <span class="<?php echo $r['state']['ICC']; ?>">ICC</span>
          <?php } ?>
          <span class="avg-ilvl"><strong>Avg iLvl:</strong> <?php echo !empty($r['avg_ilvl']) ? number_format((float)$r['avg_ilvl'], 1) : '-'; ?></span>
        </div>

        <?php if($realmstatusDebug): ?>
        <div class="progression" style="margin-top:10px;">
          <strong>Debug:</strong>
          <div style="margin-top:6px;color:#cdb88a;font-size:.92rem;line-height:1.55;">
            host=<?php echo htmlspecialchars($r['debug']['realm_host'] !== '' ? $r['debug']['realm_host'] : '(empty)'); ?>
            |
            port=<?php echo (int)$r['debug']['realm_port']; ?>
            |
            socket=<?php echo htmlspecialchars($r['debug']['realm_socket_host'] !== '' ? $r['debug']['realm_socket_host'] : '(empty)'); ?>:<?php echo (int)$r['debug']['realm_socket_port']; ?>
            (<?php echo $r['debug']['realm_reachable'] ? 'up' : 'down'; ?>)
            |
            recent-uptime=<?php echo $r['debug']['recent_uptime'] ? 'yes' : 'no'; ?>
            |
            chars=<?php echo htmlspecialchars($r['debug']['char_db'] !== '' ? $r['debug']['char_db'] : '(empty)'); ?>
            (<?php echo $r['debug']['char_ok'] ? 'ok' : 'fail'; ?>)
            |
            world=<?php echo htmlspecialchars($r['debug']['world_db'] !== '' ? $r['debug']['world_db'] : '(empty)'); ?>
            (<?php echo $r['debug']['world_ok'] ? 'ok' : 'fail'; ?>)
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php builddiv_end(); ?>
