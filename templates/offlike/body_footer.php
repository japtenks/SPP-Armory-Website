<!--footer css-->
<!-- Page content here -->
  </main>

<footer class="site-footer">
  <div class="footer-inner">
    <img src="<?php echo $currtmp; ?>/images/bot-blizzlogo.gif" alt="Blizzard.com" />
    <div class="footer-text">
      Page generated in <?php echo round($exec_time,4); ?> sec.
      Query's: (RDB: <?php echo $DB->_statistics['count']; ?>,
      WSDB: <?php echo $WSDB->_statistics['count']; ?>,
      CHDB: <?php echo $CHDB->_statistics['count']; ?>)<br/>
      &copy; <?php echo (string)$MW->getConfig->generic->copyright; ?><br/>
      <a href="index.php?n=html&amp;text=license">GNU GPL Licence</a>
    </div>
  </div>
</footer>

<!--look into replacement--><script src="js/wz_tooltip.js"></script>
</body>
</html>
