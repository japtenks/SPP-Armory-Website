<style>
.status.up{color:#9f6;}
.status.down{color:#f66;}
.realm-list{display:flex;flex-direction:column;gap:16px;}
.realm-card{
  background:#111;
  border:1px solid #333;
  border-radius:8px;
  padding:14px;
  box-shadow:0 1px 4px rgba(0,0,0,.5);
  transition:background .25s;
}
.realm-card.online:hover{background:rgba(0,255,100,.05);}
.realm-card.offline:hover{background:rgba(255,60,60,.05);}
.realm-header{display:flex;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap;}
.realm-icon{width:20px;height:20px;}
.realm-name{font-weight:bold;color:#ffcc66;font-size:1.1rem;}
.realm-body{color:#ccc;line-height:1.4;}
.realm-body strong{color:#ffcc66;}

.faction-labels {
  display:flex;
  justify-content:space-between;
  font-size:0.9rem;
  margin:3px 0 2px 0;
  font-weight:bold;
}
.faction-labels .alliance{color:#2b5eff;}
.faction-labels .horde{color:#c11b1b;}

.faction-bar {
  display:flex;
  height:8px;
  border-radius:4px;
  overflow:hidden;
  margin-bottom:4px;
}
.faction-bar .alliance{background:#2b5eff;}
.faction-bar .horde{background:#c11b1b;}

.faction-balance {
  text-align:center;
  font-size:0.85rem;
  margin-top:2px;
  font-weight:bold;
}
.faction-balance.alliance{color:#2b5eff;}
.faction-balance.horde{color:#c11b1b;}
.faction-balance.balanced{color:#aaa;}

.progression{margin-top:6px;font-size:.9rem;}
.progression span{margin-right:8px;}
.progression .cleared{color:#9f6;}
.progression .partial{color:#ffcc66;}
.progression .uncleared{color:#f66;}
.legend{font-size:.9rem;color:#aaa;margin-bottom:6px;}
.legend span{margin-right:10px;}
</style>

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

/* ---------- Realm Data Build ---------- */
$realm_flags_def=[0=>"Normal",1=>"PvP",4=>"RP",8=>"RPPvP"];
$items=[];
$realms=$DB->select("SELECT * FROM `realmlist` ORDER BY `id` ASC");

foreach($realms as $r){
  $realm_id=(int)$r['id'];
  $realm_name=$r['name'];
  $realm_type=$realm_flags_def[$r['realmflags']&0x0F]??"Normal";
  /* $is_online=($r['realmflags']!=1&&($r['realmflags']&2)!=2); */
  
  $last=$DB->selectRow("SELECT starttime,uptime FROM uptime WHERE realmid=?d ORDER BY starttime DESC LIMIT 1",$realm_id);
$is_online=($last && ($last['starttime']+$last['uptime'])>=time()-60);

/*   $res_color=($is_online?1:0); */
$res_color = $is_online ? 1 : 0;
  $build_ver=trim($r['realmbuilds']);

  // Determine expansion by build version
  $exp="classic";
  if(preg_match('/8[6-9][0-9]{2}/',$build_ver))$exp="tbc";
  elseif(preg_match('/[12][0-9]{4}/',$build_ver))$exp="wotlk";

  // uptime stats
  $uptime=0;$avg_uptime=0;$restart_count=0;
  if($is_online){
    $uptimeStart=$DB->selectCell("SELECT starttime FROM uptime WHERE realmid=?d ORDER BY starttime DESC LIMIT 1",$realm_id);
    if($uptimeStart)$uptime=time()-$uptimeStart;
    $avg_uptime=$DB->selectCell("SELECT ROUND(AVG(uptime)/3600,2) FROM uptime WHERE realmid=?d AND starttime>UNIX_TIMESTAMP(DATE_SUB(NOW(),INTERVAL 7 DAY))",$realm_id);
    $restart_count=$DB->selectCell("SELECT COUNT(*) FROM uptime WHERE realmid=?d AND starttime>=UNIX_TIMESTAMP(CURDATE())",$realm_id);
    if($restart_count==0 && $is_online)$restart_count=1; // current session counts as one
  }

  // player/faction/progression
  $pop=0;$alli=0;$horde=0;$avg_lvl=0;$max_lvl=0;
  $progress=['MC','Ony','BWL','ZG','AQ','Naxx','Kara','SSC','TK','BT','SWP','Naxx25','Ulduar','ICC'];
  foreach($progress as $p)$state[$p]='uncleared';

  if($is_online){
    if($realm_id==1)$charDB="classiccharacters";
    elseif($realm_id==2)$charDB="tbccharacters";
    elseif($realm_id==3)$charDB="wotlkcharacters";
    else $charDB="";

    if($charDB){
      $pop=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.characters WHERE online=1");
      $alli=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.characters WHERE online=1 AND race IN (1,3,4,7,11)");
      $horde=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.characters WHERE online=1 AND race IN (2,5,6,8,10)");
      $avg_lvl=$DB->selectCell("SELECT ROUND(AVG(level),1) FROM {$charDB}.characters WHERE online=1");
      $max_lvl=$DB->selectCell("SELECT MAX(level) FROM {$charDB}.characters");

      // ---- Progression checks per expansion ----
      if($exp=="classic"){
        $state['MC']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (16866,16854,16867,16868,16865,16863,16861,16862)")>3?'cleared':'uncleared';
        $state['Ony']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (16963,16964,16965,16966,16967,16968,16969,16970)")>3?'cleared':'uncleared';
        $state['BWL']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (16911,16924,16932,16940,16945,16953,16961,16968)")>3?'partial':'uncleared';
        $state['ZG']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (19802,19854,19822,19862,19848,19910)")>3?'cleared':'uncleared';
        $state['AQ']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (21329,21330,21331,21332,21333,21220)")>3?'partial':'uncleared';
        $state['Naxx']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (22416,22417,22418,22419,22420,22421,22422,22423)")>2?'partial':'uncleared';
      }
      elseif($exp=="tbc"){
        $state['Kara']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (29066,29067,29068,29069,29070)")>3?'cleared':'uncleared';
        $state['SSC']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (30245,30246,30247,30248,30249)")>3?'partial':'uncleared';
        $state['TK']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (30233,30234,30235,30236,30237)")>3?'partial':'uncleared';
        $state['BT']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (30969,30970,30971,30972,30974)")>3?'partial':'uncleared';
        $state['SWP']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (34332,34333,34334,34335,34336)")>2?'partial':'uncleared';
      }
      elseif($exp=="wotlk"){
        $state['Naxx25']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (40554,40557,40559,40560,40562)")>3?'cleared':'uncleared';
        $state['Ulduar']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (45340,45341,45342,45343,45344)")>3?'partial':'uncleared';
        $state['ICC']=$DB->selectCell("SELECT COUNT(*) FROM {$charDB}.item_instance WHERE itemEntry IN (51155,51156,51157,51158,51159)")>3?'partial':'uncleared';
      }
    }
  }

  $items[]=[
    'id'=>$realm_id,'name'=>$realm_name,'type'=>$realm_type,'build'=>$build_ver,
    'exp'=>$exp,'pop'=>$pop,'alli'=>$alli,'horde'=>$horde,'uptime'=>$uptime,
    'avg_up'=>$avg_uptime,'restarts'=>$restart_count,'avg_lvl'=>$avg_lvl,'max_lvl'=>$max_lvl,
    'state'=>$state,'res_color'=>$res_color,'img'=>$res_img
  ];
}
?>

<?php builddiv_start(1,$lang['realm_status']); ?>
<div class="modern-content">
  <?php header_image("realm"); ?>
  <div class="modern-desc">
    <?php
        $up   = '<span class="status up">▲ '.$lang['up'].'</span>';
        $down = '<span class="status down">▼ '.$lang['down'].'</span>';
      $link='<a href="index.php?n=forum">'.$lang['realm_status_forum'].'</a>';
      $desc=str_replace(['[up]','[down]','[realm_status_forum]'],[$up,$down,$link],$lang['realmstatus_desc']);
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
        <img src="<?php echo $r['img']; ?>" alt="status" class="realm-icon"/>
        <span class="realm-name"><?php echo htmlspecialchars($r['name']); ?></span>
        <span style="color:#888;font-size:.9rem;">(Build: <?php echo htmlspecialchars($r['build']); ?>)</span>
      </div>

      <div class="realm-body">
        <div><strong><?php echo $lang['uptime']; ?>:</strong> 
          <?php if($r['uptime']>1)print_time(parse_time($r['uptime']));else echo '-'; ?>
        </div>
        <div><strong>Avg Uptime (7d):</strong> <?php echo $r['avg_up']?$r['avg_up'].' hrs':'-'; ?></div>
        <div><strong>Restarts Today:</strong> <?php echo $r['restarts']; ?></div>
        <div><strong><?php echo $lang['si_type']; ?>:</strong> <?php echo htmlspecialchars($r['type']); ?></div>
        <div><strong><?php echo $lang['si_pop']; ?>:</strong> 
          <?php if($r['uptime']!=0)echo $r['pop']." (".population_view($r['pop']).")";else echo '-'; ?>

          <?php if($r['alli']+$r['horde']>0):
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
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php builddiv_end(); ?>
