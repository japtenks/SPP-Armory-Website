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


<?php builddiv_start(1, $lang['players_online']); ?>
<div class="modern-content">

  <iframe
    src="./components/pomm/playermap.php"
    class="playermap-frame"
    frameborder="0"
    scrolling="no">
  </iframe>

</div> <!-- closes .modern-content -->
<?php builddiv_end(); ?>


<script>


</script>