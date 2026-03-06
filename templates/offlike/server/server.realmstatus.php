

<?php

/* ---------- Helpers ---------- */
function parse_time($n) {
  return [
    'd' => intval($n/86400),
    'h' => intval(($n % 86400)/3600),
    'm' => intval(($n % 3600)/60),
    's' => $n % 60
  ];
}
function print_time($t) {
  global $lang;
  $out=[];
  if($t['d']>0) $out[]=$t['d'].$lang['rs_days'];
  if($t['h']>0) $out[]=$t['h'].$lang['rs_hours'];
  if($t['m']>0) $out[]=$t['m'].$lang['rs_minutes'];
  if($t['s']>0) $out[]=$t['s'].$lang['rs_seconds'];
  echo implode(', ', $out);
}
?>

<?php builddiv_start(1, $lang['realm_status']); ?>
<div class="modern-content">
 <?php header_image("realm");?>


 
    <div class="modern-desc">
      <?php
        $up   = '<span class="status up">▲ '.$lang['up'].'</span>';
        $down = '<span class="status down">▼ '.$lang['down'].'</span>';
        $link = '<a href="index.php?n=forum">'.$lang['realm_status_forum'].'</a>';
        $desc = str_replace(['[up]','[down]','[realm_status_forum]'], [$up,$down,$link], $lang['realmstatus_desc']);
        echo $desc;
      ?>
    </div>

    <div class="realm-list">
      <?php foreach($items as $r): ?>
      <div class="realm-card <?php echo $r['res_color']==1?'online':'offline'; ?>">
        <div class="realm-header">
          <img src="<?php echo $r['img']; ?>" alt="status" class="realm-icon"/>
          <span class="realm-name"><?php echo htmlspecialchars($r['name']); ?></span>
        </div>
        <div class="realm-body">
          <div><strong><?php echo $lang['uptime']; ?>:</strong> 
            <?php if($r['uptime']>1) print_time(parse_time($r['uptime'])); else echo '-'; ?>
          </div>
          <div><strong><?php echo $lang['si_type']; ?>:</strong> <?php echo htmlspecialchars($r['type']); ?></div>
          <div><strong><?php echo $lang['si_pop']; ?>:</strong>
            <?php 
              if($r['uptime']!=0)
                echo $r['id']==1 ? $r['pop']." (".population_view($r['pop']).")" : population_view($r['pop']);
            ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  
</div> <!-- closes .modern-content -->
<?php builddiv_end(); ?>


