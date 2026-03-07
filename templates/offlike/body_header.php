<?php
if (!defined('Armory')) { define('Armory', 1); }

// path to your background images
$bgDir = 'templates/offlike/images/modern/bkgd/';

// gather all images in the directory
$bgImages = glob($bgDir . '*.{webp,jpg,jpeg,png,gif}', GLOB_BRACE);

// pick a random one
$randomBg = $bgImages ? $bgImages[array_rand($bgImages)] : 'templates/offlike/images/modern/bkgd/19.jpg';
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
  <title><?php echo $MW->getConfig->generic->site_title ?? 'World of Warcraft'; ?></title>


  <link rel="shortcut icon" href="<?php echo $currtmp; ?>/images/favicon.ico"/>


<link rel="stylesheet" href="/xfer/assets/css/site.css">
<script>
function setcookie(name, value, days = 30) {
  const d = new Date();
  d.setTime(d.getTime() + (days*24*60*60*1000));
  const expires = "expires=" + d.toUTCString();
  document.cookie = name + "=" + value + ";" + expires + ";path=/";
}
function changeLanguage(lang) {
  setcookie('Language', lang);
  location.reload();
}
</script>
</head>
<body style="background: url('<?php echo $randomBg; ?>') no-repeat center center fixed; 
             background-size: cover;">


  <!-- ======= Top Navigation ======= -->
  <div class="nav-container">
    
	
	<div class="nav-logo">
      <a href="./">
        <img src="templates/offlike/images/modern/wow.png" alt="WoW Logo" class="nav-logo-img" />
      </a>
    </div>

    <div class="mobile-toggle">&#9776;</div>

    <div class="mobile-menu">

     
        <img 
          src="<?php echo ($expansion == 1) 
            ? 'components/pomm/img/map_tbc/realm_on.gif' 
            : 'templates/offlike/images/Modern/Logo-wow-NA.png'; ?>" 
          alt="WoW Logo" 
          class="menu-logo" 
        />
      
      <ul class="mobile-main">
        <?php build_main_menu(true); ?>
        <li class="menu-spacer"><br></li>
        <?php build_language_menu(true); ?>
      </ul>
    </div>

    <ul class="nav-menu desktop-menu">
      <?php build_main_menu(true); ?>
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

