


<?php builddiv_start(1, $lang['players_online'],1); ?>


<div class="modern-content">
<?php
$realm_id = isset($_GET['realm']) ? (int)$_GET['realm'] : 1;
?>

<iframe
  src="./components/pomm/playermap.php?realm=<?php echo $realm_id; ?>"
  class="playermap-frame"
  frameborder="0"
  scrolling="no">
</iframe>

</div> <!-- closes .modern-content -->
<?php builddiv_end(); ?>
