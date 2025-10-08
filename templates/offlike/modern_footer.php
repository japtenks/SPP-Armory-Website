</main>

<footer class="site-footer">
  <div id="copyright">
    <div id="blizzlogo-bot">
      <img alt="Blizzard.com" src="<?php echo $currtmp; ?>/images/bot-blizzlogo.gif">
    </div>

    <small>
      <br>
      <?php echo $lang['pagegenerated']; ?>
      <?php echo round($exec_time, 4); ?> sec.
      Query's: 
      (RDB: <?php echo $DB->_statistics['count']; ?>,
      WSDB: <?php echo $WSDB->_statistics['count']; ?>,
      CHDB: <?php echo $CHDB->_statistics['count']; ?>)
      <br>
      <b>&copy; <?php echo (string)$MW->getConfig->generic->copyright; ?></b>
      <br>
      <a href="index.php?n=html&amp;text=license">GNU GPL Licence</a>
    </small>
  </div>
</footer>

<script src="<?php echo $currtmp; ?>/js/modern.js"></script>
</body>
</html>
