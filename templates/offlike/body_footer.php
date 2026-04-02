<!--footer css-->

<!-- Page content here -->
  </main>

<footer class="site-footer">
  <div class="footer-inner">
    <img src="<?php echo htmlspecialchars(spp_template_url('images/bot-blizzlogo.gif'), ENT_QUOTES); ?>" alt="Blizzard.com" />
    <div class="footer-text">
      Page generated in <?php echo round($exec_time,4); ?> sec.<br/>
      &copy; <?php echo (string)$MW->getConfig->generic->copyright; ?><br/>
      <a href="index.php?n=html&amp;text=license">GNU GPL Licence</a>
    </div>
  </div>
</footer>

<!--look into replacement--><script src="<?php echo htmlspecialchars(spp_js_asset_url('wz_tooltip.js'), ENT_QUOTES); ?>"></script>
</body>
</html>
