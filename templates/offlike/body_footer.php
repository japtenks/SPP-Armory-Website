<!--footer css-->
<style>
/* === STICKY LAYOUT === */
html, body {
  height: 100%;
  margin: 0;
  display: flex;
  flex-direction: column;
}

main {
  flex: 1;
}

/* === INLINE FOOTER === */
.site-footer {
  position: relative;
  bottom: 0;
  width: 100%;
  margin-top: auto;
  text-align: center;
  font-size: 12px;
  line-height: 1.4;

  background:
    linear-gradient(rgba(10, 10, 10, 0.82), rgba(5, 5, 5, 0.9)),
    url('../images/topnav-stone.jpg') repeat-x center #0b0b0b;

  border-top: 1px solid #3a3118;
  box-shadow: inset 0 1px 0 #3d331b, 0 -2px 8px rgba(0,0,0,0.7);
  padding: 10px 0;
  color: #d9c68b;
  backdrop-filter: blur(3px);
}

/* Wrap the logo + text inline */
.footer-inner {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 14px; /* space between logo and text */
  flex-wrap: wrap; /* keep readable on small screens */
}

/* Blizzard logo */
.footer-inner img {
  height: 36px;
  width: auto;
  opacity: 0.9;
  filter: drop-shadow(0 0 4px rgba(255, 204, 0, 0.25));
  transition: transform 0.3s ease, filter 0.3s ease;
  vertical-align: middle;
}
.footer-inner img:hover {
  transform: scale(1.05);
  filter: drop-shadow(0 0 8px #ffd700);
}

/* Footer text */
.footer-text {
  color: #e4d6a3;
  text-shadow: 0 0 4px rgba(0,0,0,0.9);
  font-size: 12.5px;
}
.footer-text a {
  color: #ffcc00;
  text-decoration: none;
  font-weight: bold;
}
.footer-text a:hover {
  color: #fff1b0;
  text-shadow: 0 0 4px #ffd700, 0 0 8px #ff9900;
}

/* Gold top edge */
.site-footer::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 6px;
  background: linear-gradient(to bottom, rgba(255,215,0,0.12), transparent);
}


</style> 
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
