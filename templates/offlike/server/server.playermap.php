<style>
/* ---------- Playermap Frame ---------- */
.playermap-frame {
  display: block;
  margin: 20px auto;
  width: 100%;
  max-width: 900px;
  aspect-ratio: 900 / 732;
  border: 1px solid #333;
  border-radius: 6px;
  box-shadow: 0 0 10px rgba(0,0,0,0.6);
  background: #000;
}

</style>


<?php builddiv_start(1, $lang['players_online'],1); ?>


<div class="modern-content">
<?php
$realm_id = isset($_GET['realm']) ? (int)$_GET['realm'] : 1;
?>
<div style='color:lime;font-weight:bold;'>DEBUG REALMID: <?php echo $realm_id; ?></div>

<iframe
  src="./components/pomm/playermap.php?realm=<?php echo $realm_id; ?>"
  class="playermap-frame"
  frameborder="0"
  scrolling="no">
</iframe>

</div> <!-- closes .modern-content -->
<?php builddiv_end(); ?>
