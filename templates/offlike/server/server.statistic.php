<style>

.faction-columns {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  gap: 24px;
  flex-wrap: wrap;
}

.faction-col {
  position: relative;
  flex: 1 1 300px;
  max-width: 260px;
  min-width: 260px;
  min-height: 360px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 24px 16px;
  border-radius: 8px;
  border: 1px solid #333;
  background: rgba(0,0,0,0.5);
  box-shadow: inset 0 0 12px rgba(0,0,0,0.7);
}

.faction-bg {
  position: absolute;
  inset: 0;
  background-size: 100%;
  background-position: center;
  background-repeat: no-repeat;
  opacity: 0.1;
  filter: saturate(150%) brightness(1.3);
  z-index: 0;
}

.faction-text {
  font-size: 1.2rem;
  font-weight: 700;
  margin-bottom: 10px;
  position: relative;
  z-index: 1;
}

.faction-col.alliance .faction-text {
  color: #ffcc66;
  text-shadow: 0 0 6px rgba(255,220,100,0.4);
}

.faction-col.horde .faction-text {
  color: #ff4444;
  text-shadow: 0 0 6px rgba(255,60,60,0.4);
}

.race-line {
  display: flex;
  justify-content:left;
  align-items: center;
  gap: 8px;
  margin: 6px auto;
  width: fit-content;
}

.race-line img {
  width: 42px;
  height: auto;
  border-radius: 4px;
  border: 1px solid #444;
  box-shadow: 0 0 4px rgba(0,0,0,0.4);
}

.race-line span {
  font-size: 0.95rem;
  color: #ddd;
  text-align: left;
  min-width: 100px;
}

.neutral-dk {
  margin-top: 20px;
  text-align: center;
  color: #c41f3b;
  font-weight: bold;
}

@media (max-width: 860px) {
  .faction-wrapper {
    max-width: 500px;
  }
  .faction-col {
    max-width: 90%;
   
  }
}

</style>




<?php builddiv_start(1, $lang['statistic']); ?>

<div class="modern-content">
  <img src="<?php echo $currtmp; ?>/images/banner1.jpg" alt="Auction House" class="ah-banner"/>

  <?php if ($num_chars == 0): ?>
    <p class="no-chars">0 <?php echo $lang['characters'] ?? 'Characters'; ?></p>
  <?php else: ?>
  
    <?php
      $allianceMap = [1 => 'human', 3 => 'dwarf', 4 => 'nightelf', 7 => 'gnome', 11 => 'dranei'];
      $hordeMap    = [2 => 'orc', 5 => 'undead', 6 => 'tauren', 8 => 'troll', 10 => 'be'];

      $allianceRaces = [];
      $hordeRaces    = [];

      foreach ($allianceMap as $id => $key)
        $allianceRaces[$id] = ['count' => $rc[$id] ?? 0, 'pc' => ${'pc_'.$key} ?? 0];

      foreach ($hordeMap as $id => $key)
        $hordeRaces[$id] = ['count' => $rc[$id] ?? 0, 'pc' => ${'pc_'.$key} ?? 0];

      $hasDK = !empty($rc[12]);
    ?>

    <div class="faction-columns">
      <!-- Alliance -->
      <div class="faction-col alliance">
        <div class="faction-bg" style="background-image:url('<?php echo $currtmp; ?>/images/icon/faction/alliance.png');"></div>
        <div class="faction-text">
          Alliance: <strong><?php echo $num_ally; ?></strong> (<?php echo $pc_ally; ?>%)
        </div>
        <?php foreach ($allianceRaces as $id => $data): ?>
          <div class="race-line">
            <img src="<?php echo $currtmp; ?>/images/icon/race/<?php echo $id; ?>-0.jpg" alt="">
            <span><?php echo $data['count']; ?> (<?php echo $data['pc']; ?>%)</span>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Horde -->
      <div class="faction-col horde">
        <div class="faction-bg" style="background-image:url('<?php echo $currtmp; ?>/images/icon/faction/horde.png');"></div>
        <div class="faction-text">
          Horde: <strong><?php echo $num_horde; ?></strong> (<?php echo $pc_horde; ?>%)
        </div>
        <?php foreach ($hordeRaces as $id => $data): ?>
          <div class="race-line">
            <img src="<?php echo $currtmp; ?>/images/icon/race/<?php echo $id; ?>-0.jpg" alt="">
            <span><?php echo $data['count']; ?> (<?php echo $data['pc']; ?>%)</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($hasDK): ?>
      <div class="neutral-dk">
        <img src="<?php echo $currtmp; ?>/images/stat/12-0.gif" alt="Death Knight">
        <span>
          Death Knights:
          <strong><?php echo $rc[12]; ?></strong>
          (<?php echo ${'pc_dk'} ?? number_format(($rc[12] / $num_chars) * 100, 2); ?>%)
        </span>
      </div>
    <?php endif; ?> 
	<?php endif; ?>
</div> <!-- closes .modern-content -->
<?php builddiv_end(); ?>







