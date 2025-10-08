<?php
if (!defined('Armory')) { define('Armory', 1); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($MW->getConfig->generic->site_title ?? 'World of Warcraft') ?></title>

<link rel="icon" href="<?= $currtmp ?>/images/favicon.ico">
<link rel="stylesheet" href="<?= $currtmp ?>/css/modern.css">
<script src="<?= $currtmp ?>/js/modern.js" defer></script>

</head>
<body>

<div class="nav-container">
  <!-- Logo -->
  <div class="nav-logo">
    <a href="./">
      <img src="templates/offlike/images/Modern/wow.png" alt="WoW Logo">
    </a>
  </div>

  <!-- Mobile Toggle -->
  <div class="mobile-toggle">&#9776;</div>

  <!-- Desktop Menu -->
  <ul class="nav-menu desktop-menu">
    <?php build_main_menu(true); ?>
    <?php build_serverinfo_menu(true); ?>
    <?php build_language_menu(true); ?>
  </ul>

  <!-- Account Menu -->
  <div class="nav-right">
    <ul class="nav-menu account-dropdown">
      <?php build_account_menu(true); ?>
    </ul>
  </div>
</div>

<!-- Mobile Drawer -->
<div class="mobile-menu">
  <div class="menu-close">✖</div>
  <a href="./">
    <img 
      src="<?= ($expansion == 1)
        ? 'components/pomm/img/map_tbc/realm_on.gif'
        : 'templates/offlike/images/Modern/Logo-wow-NA.png'; ?>"
      alt="WoW Logo" class="menu-logo">
  </a>
  <ul class="mobile-main">
    <?php build_main_menu(true); ?>
    <li class="menu-spacer"><br></li>
    <?php build_serverinfo_menu(true); ?>
    <li class="menu-spacer"><br></li>
    <?php build_language_menu(true); ?>
  </ul>
</div>

<div class="menu-overlay"></div>
<div id="tooltip" class="tooltip-box"><div id="tooltiptext"></div></div>
<main class="site-main">
