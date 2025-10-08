<?php
if (!defined('Armory')) { define('Armory', 1); }
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
  <title><?php echo $MW->getConfig->generic->site_title ?? 'World of Warcraft'; ?></title>

  <link rel="shortcut icon" href="<?php echo $currtmp; ?>/images/favicon.ico"/>
  <link rel="stylesheet" href="<?php echo $currtmp; ?>/css/bundle.css" />
  <link rel="stylesheet" href="<?php echo $currtmp; ?>/css/topnav-new.css" />
  <script>/* your nav JS stays here */</script>
</head>

<body>

  <!-- ======= Top Navigation ======= -->
  <div class="nav-container">
    <div class="nav-logo">
      <a href="./">
        <img src="templates/offlike/images/Modern/wow.png" alt="WoW Logo" class="nav-logo-img" />
      </a>
    </div>

    <div class="mobile-toggle">&#9776;</div>

    <div class="mobile-menu">
      <div class="menu-close">✖</div>
      <a href="./">
        <img 
          src="<?php echo ($expansion == 1) 
            ? 'components/pomm/img/map_tbc/realm_on.gif' 
            : 'templates/offlike/images/Modern/Logo-wow-NA.png'; ?>" 
          alt="WoW Logo" 
          class="menu-logo" 
        />
      </a>
      <ul class="mobile-main">
        <?php build_main_menu(true); ?>
        <li class="menu-spacer"><br></li>
        <?php build_serverinfo_menu(true); ?>
        <li class="menu-spacer"><br></li>
        <?php build_language_menu(true); ?>
      </ul>
    </div>

    <ul class="nav-menu desktop-menu">
      <?php build_main_menu(true); ?>
      <?php build_serverinfo_menu(true); ?>
      <?php build_language_menu(true); ?>
    </ul>

    <div class="nav-right">
      <ul class="nav-menu account-dropdown">
        <?php build_account_menu(true); ?>
      </ul>
    </div>
  </div>

  <div class="menu-overlay"></div>
  <div id="tooltip" class="tooltip-box"><div id="tooltiptext"></div></div>

  <!-- ======= Begin Page Content ======= -->
  <main>
  
  <style>
  
  /* centered header image */
img[src*="armorsets.jpg"] {
  display: block;
  margin: 0 auto 12px auto;
  max-width: 100%;
  height: auto;
  border-radius: 6px;
  box-shadow: 0 0 10px rgba(0,0,0,0.6);
}
.modern-wrapper {
  max-width: 1000px;
  margin: 30px auto;
  background: #0d0d0d url('<?php echo $currtmp; ?>/images/stone-dark.jpg') repeat;
  /*	background:#1a1a1a;*/
  border: 1px solid #222;
  border-radius: 10px;
  box-shadow: 0 0 15px rgba(0,0,0,0.7);
  padding: 24px;
  position: relative;
  font-family: 'Trebuchet MS', sans-serif;
  color: #ccc;
}
.modern-header{
  background:linear-gradient(to right,#2a1b05,#111);
  color:#ffcc66;text-align:center;padding:12px;font-size:1.4rem;font-weight:bold;
  border-bottom:1px solid #2e2e2e;
}
.modern-content {padding:16px 20px;}
img.ah-banner {
  display:block;margin:0 auto 18px auto;max-width:100%;
  border-radius:6px;box-shadow:0 0 10px rgba(0,0,0,0.6);
}
.modern-desc{color:#ccc;margin-bottom:20px;line-height:1.5;}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const accBtn  = document.querySelector(".account-dropdown > a");
  const accDrop = document.querySelector(".account-dropdown");
  if (accBtn) {
    accBtn.addEventListener("click", e => {
      e.preventDefault();
      accDrop.classList.toggle("open");
    });
  }
  document.addEventListener("click", e => {
    if (accDrop && !accDrop.contains(e.target)) accDrop.classList.remove("open");
  });
  document.querySelectorAll(".account-menu li").forEach(li => {
    if (li.querySelector("ul")) li.classList.add("has-sub");
  });
  document.querySelectorAll(".account-menu").forEach(menu => {
    menu.addEventListener("click", e => {
      const link = e.target.closest("a"); if (!link) return;
      const parentLi = link.parentElement, submenu = parentLi.querySelector("ul");
      if (submenu) { e.preventDefault(); parentLi.classList.toggle("open"); }
    });
  });
  const menuBtn  = document.querySelector(".mobile-toggle");
  const menu     = document.querySelector(".mobile-menu");
  const overlay  = document.querySelector(".menu-overlay");
  const closeBtn = document.querySelector(".menu-close");
  if (menuBtn) {
    menuBtn.addEventListener("click", e => {
      e.stopPropagation(); menu.classList.toggle("open"); overlay.classList.toggle("active");
    });
  }
  overlay?.addEventListener("click", () => {
    menu.classList.remove("open"); overlay.classList.remove("active");
  });
  closeBtn?.addEventListener("click", () => {
    menu.classList.remove("open"); overlay.classList.remove("active");
  });
  menu?.querySelectorAll("li").forEach(li => {
    if (li.querySelector("ul")) li.classList.add("has-sub");
  });
  menu?.addEventListener("click", e => {
    const link = e.target.closest("a"); if (!link) return;
    const parentLi = link.parentElement, submenu  = parentLi.querySelector("ul");
    if (submenu) {
      e.preventDefault();
      menu.querySelectorAll("li.open").forEach(li => { if (li !== parentLi) li.classList.remove("open"); });
      parentLi.classList.toggle("open");
    }
  });
  const accountLi   = document.querySelector(".nav-menu li.account");
  const accountLink = accountLi?.querySelector("a");
  accountLink?.addEventListener("click", e => {
    e.preventDefault(); accountLi.classList.toggle("open");
  });
});
</script>