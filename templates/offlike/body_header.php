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
<body style="background: url('<?php echo $randomBg; ?>') no-repeat center center fixed; 
             background-size: cover;">

  <link rel="shortcut icon" href="<?php echo $currtmp; ?>/images/favicon.ico"/>

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
.banner {
  display: block;
  margin: 0 auto 12px auto;
   margin-top: 30px;
  max-width: 100%;
  height: auto;
  border-radius: 6px;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.6);
}

.modern-wrapper {
  position: relative;
  overflow: hidden;
  max-width: 1000px;
  margin: 30px auto;
  border: 1px solid #222;
  border-radius: 10px;
  box-shadow: 0 0 15px rgba(0,0,0,0.7);
  padding: 24px;
  font-family: 'Trebuchet MS', sans-serif;
  color: #ccc;
  background: url('<?php echo $currtmp; ?>/images/stone-dark.jpg') repeat center;
}

.modern-wrapper::before {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(13,13,13,0.75); /* transparency overlay */
  z-index: 0;
}

.modern-header{
  background:linear-gradient(to right,#2a1b05,#111);
  color:#ffcc66;text-align:center;padding:12px;font-size:1.4rem;font-weight:bold;
  border-bottom:1px solid #2e2e2e;
}
.modern-content,
.modern-header {
  position: relative;
  z-index: 1; /* keeps text above the overlay */
}

.modern-desc{color:#ccc;margin-bottom:20px;line-height:1.5;}



</style>
<style>
/* ======================================================
   SHARED WOW TABLE — used by both AH and Character tables
   ====================================================== */
.wow-table {
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 1000px;
  margin: 20px auto;
  border: 1px solid #222;
  border-radius: 10px;
  box-sizing: border-box;
  font-family: 'Trebuchet MS', sans-serif;
  color: #ccc;
  background: url('<?php echo $currtmp; ?>/images/stone-dark.jpg') repeat center;
}

/* Transparent stone overlay */
.wow-table::before {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(13,13,13,0.80);
  z-index: 0;
}

.wow-table .header,
.wow-table .row {
  position: relative;
  z-index: 1;
  display: grid;
  align-items: center;
  text-align: center;
  padding: 8px 0;
  border-bottom: 1px solid #222;
}

/* Header Look */
.wow-table .header {
  background: linear-gradient(to bottom, #1a1a1a, #101010);
  color: #ffcc66;
  font-weight: bold;
  text-transform: uppercase;
}

/* Rows: alternating + hover */
.wow-table .row:nth-child(even) { background: rgba(255,255,255,0.04); }
.wow-table .row:hover { background: rgba(255,255,255,0.08); }

/* Generic link and text colors */
.wow-table a {
  color: #ccc;
  text-decoration: none;
  transition: color .2s, text-shadow .2s;
}
.wow-table a:hover {
  color: #fff3a0;
  text-shadow: 0 0 8px #ffcc66;
}

/* Quality colors (used in AH) */
a.iqual0 { color:#9e9e9e; }
a.iqual1 { color:#eee; }
a.iqual2 { color:#00ff10; }
a.iqual3 { color:#0070dd; }
a.iqual4 { color:#a335ee; }
a.iqual5 { color:#ff8000; }
a.iqual6 { color:#e60000; }
a[class^="iqual"]:hover { color:#fff; }

/* Shared hover pop for icons */
.wow-table img.circle,
.wow-table .class-icon {
  transition: transform .25s ease, box-shadow .25s ease;
}
.wow-table img.circle:hover,
.wow-table .class-icon:hover {
  transform: scale(1.08);
  box-shadow: 0 0 12px currentColor, 0 0 24px rgba(255,255,255,0.2);
}

/* Shared pagination + filters */
.wow-table .pagination-controls { text-align:center; margin-top:12px; color:#ccc; }
.wow-table .filter-bar { text-align:center; margin:10px 0 20px; }
.wow-table .filter {
  color:#aaa; text-decoration:none; margin:0 8px; font-weight:bold;
}
.wow-table .filter.is-active,
.wow-table .filter:hover { color:#ffcc66; }

/* Dropdown menu (AH filter style) */
.wow-table .has-dropdown { position:relative; cursor:pointer; }
.wow-table .dropdown {
  display:none; position:absolute; top:100%; left:0;
  background:#181818; border:1px solid #333; border-radius:4px;
  min-width:160px; z-index:20;
}
.wow-table .has-dropdown:hover .dropdown { display:block; }
.wow-table .option {
  padding:5px 10px; color:#ddd; white-space:nowrap;
}
.wow-table .option:hover {
  background:#333; color:#ffcc66;
}

/* ======================================================
   CLASS COLORS (shared across all tables)
   ====================================================== */
.class-warrior { --class-color:#C79C6E; }
.class-mage { --class-color:#69CCF0; }
.class-priest { --class-color:#FFFFFF; }
.class-hunter { --class-color:#ABD473; }
.class-rogue { --class-color:#FFF569; }
.class-warlock { --class-color:#9482C9; }
.class-paladin { --class-color:#F58CBA; }
.class-druid { --class-color:#FF7D0A; }
.class-shaman { --class-color:#0070DE; }
.class-deathknight { --class-color:#C41F3B; }

/* Accent usage */
[class*="class-"] .portrait,
[class*="class-"] .class-icon {
  color: var(--class-color);
  border-color: var(--class-color);
  box-shadow: 0 0 4px var(--class-color, #888);
  transition: box-shadow 0.25s ease, border-color 0.25s ease;
}
[class*="class-"]:hover .portrait,
[class*="class-"]:hover .class-icon {
  box-shadow: 0 0 10px var(--class-color, #aaa);
  filter: brightness(1.1);
}

/* Class-colored name links */
[class*="class-"] a {
  color: var(--class-color);
  text-shadow: none;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* ======================================================
   FACTION / RACE ICON BORDERS
   ====================================================== */

/* Alliance */
.col img[title="Alliance"],
img.circle[alt="race"][src*="race/1-"],
img.circle[alt="race"][src*="race/3-"],
img.circle[alt="race"][src*="race/4-"],
img.circle[alt="race"][src*="race/7-"],
img.circle[alt="race"][src*="race/11-"] {
  border: 1px solid #0055ff;
  box-shadow: 0 0 4px #0055ff;
  border-radius: 50%;
}

/* Horde */
.col img[title="Horde"],
img.circle[alt="race"][src*="race/2-"],
img.circle[alt="race"][src*="race/5-"],
img.circle[alt="race"][src*="race/6-"],
img.circle[alt="race"][src*="race/8-"],
img.circle[alt="race"][src*="race/10-"] {
  border: 1px solid #aa0000;
  box-shadow: 0 0 4px #aa0000;
  border-radius: 50%;
}

/* ======================================================
   RESPONSIVE BEHAVIOR
   ====================================================== */
@media (max-width: 750px) {
  .wow-table .header, .wow-table .row { font-size: 0.9rem; }
}
</style>
<!--armor set shared-->
<style>
/* === CLASS BAR === */
.class-bar {
  display: flex;
  justify-content: center;      /* center all icons horizontally */
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  margin: 12px auto 20px;       /* centers block itself */
  padding: 8px 0;
  background: transparent;      /* removes parchment look */
  border: none;
  border-radius: 0;
  backdrop-filter: none;
  box-shadow: none;
}

/* === CLASS TOKEN ICONS === */
.class-token {
  --ring:#888;
  --glow:rgba(136,136,136,.55);
  position: relative;
  width: 40px;
  height: 40px;
  border-radius: 999px;
  background: transparent;
  cursor: pointer;
  box-shadow:
    0 0 0 2px rgba(0,0,0,.45) inset,
    0 0 0 2px rgba(255,255,255,.2);
  transition: transform .12s, box-shadow .12s, filter .12s;
}
.class-token img {
  width: 100%;
  height: 100%;
  border-radius: 999px;
}

/* Hover / Active Effects */
.class-token:hover,
.class-token:focus {
  transform: translateY(-1px);
  filter: brightness(1.05);
  box-shadow:
    0 0 0 2px rgba(0,0,0,.6) inset,
    0 0 0 2px var(--ring),
    0 0 16px var(--glow);
}
.class-token.is-active {
  box-shadow:
    0 0 0 2px rgba(0,0,0,.7) inset,
    0 0 0 2px var(--ring),
    0 0 18px var(--glow);
}

/* === TOOLTIP (class name on hover) === */
.class-token::after {
  content: attr(data-name);
  position: absolute;
  bottom: -30px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(
    to bottom,
    rgba(0, 0, 0, 0.92),
    rgba(0, 0, 0, 0.85)
  );
  color: var(--ring, #ffd700);
  font-size: 12px;
  font-weight: bold;
  padding: 4px 8px;
  border-radius: 4px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
  box-shadow:
    0 0 4px rgba(0,0,0,0.6),
    0 0 10px var(--glow, rgba(255,255,255,0.25));
  border: 1px solid var(--ring, rgba(255,255,255,0.2));
  z-index: 25;
}
.class-token:hover::after {
  opacity: 1;
  transform: translateX(-50%) translateY(2px);
  box-shadow:
    0 0 6px var(--glow, rgba(255,255,255,0.25)),
    0 0 12px var(--glow, rgba(255,255,255,0.25));
}

/* === CLASS COLOR VARIABLES === */
.is-warrior {--ring:#C79C6E;--glow:rgba(199,156,110,.6);}
.is-paladin {--ring:#F58CBA;--glow:rgba(245,140,186,.6);}
.is-hunter  {--ring:#ABD473;--glow:rgba(171,212,115,.6);}
.is-rogue   {--ring:#FFF569;--glow:rgba(255,245,105,.55);}
.is-priest  {--ring:#FFFFFF;--glow:rgba(255,255,255,.55);}
.is-shaman  {--ring:#0070DE;--glow:rgba(0,112,222,.55);}
.is-mage    {--ring:#40C7EB;--glow:rgba(64,199,235,.55);}
.is-warlock {--ring:#8787ED;--glow:rgba(135,135,237,.55);}
.is-druid   {--ring:#FF7D0A;--glow:rgba(255,125,10,.55);}
.is-dk      {--ring:#C41F3B;--glow:rgba(196,31,59,.55);}

/* === CLASS COLOR TEXT (for inline <b> names) === */
.is-warrior  b { color:#C79C6E; }
.is-paladin  b { color:#F58CBA; }
.is-hunter   b { color:#ABD473; }
.is-rogue    b { color:#FFF569; }
.is-priest   b { color:#FFFFFF; }
.is-shaman   b { color:#0070DE; }
.is-mage     b { color:#40C7EB; }
.is-warlock  b { color:#8787ED; }
.is-druid    b { color:#FF7D0A; }
.is-dk       b { color:#C41F3B; }

/* === SET GROUPING === */
.set-group   { margin: 20px 0 10px; }
.set-title   { font-size: 20px; font-weight: 700; color: #6b2d1f; margin: 14px 0 6px; }
.set-subtitle{ font-weight: 700; color: #7a3f28; }
.set-desc    { margin: 2px 0 10px; }
.set-note    { color: #7b6a52; display: block; margin-top: 2px; }

/* === SET ITEMS === */
.set-item {
  display:inline-block;
  padding:1px 6px;
  margin:0 3px;
  border-radius:7px;
  background:rgba(0,0,0,.06);
  box-shadow:
    0 1px 0 rgba(255,255,255,.2) inset,
    0 1px 2px rgba(0,0,0,.08);
  font-weight:700;
  font-size:12px;
  white-space:nowrap;
}
.set-item img {
  width:14px;
  height:14px;
  margin-right:4px;
  border-radius:3px;
  vertical-align:-2px;
}
.set-item.ghost {
  background:rgba(0,0,0,.05);
  color:#7b6a52;
  opacity:.95;
}

/* === TOOLTIP POPUP FOR ITEMS === */
.talent-tt {
  position:fixed;
  z-index:9999;
  min-width:220px;
  max-width:360px;
  padding:14px;
  background:rgba(16,24,48,.78);
  border:1px solid rgba(200,220,255,.18);
  border-radius:10px;
  color:#e9eefb;
  font:14px/1.45 "Trebuchet MS",Arial,sans-serif;
  pointer-events:none;
  backdrop-filter:blur(2px);
}
.talent-tt h5 {
  margin:0 0 6px;
  font-size:18px;
  font-weight:800;
  color:#f1f6ff;
}
.talent-tt .tt-subtle {
  font-size:13px;
  opacity:.9;
}

/* === SET ROWS === */
.set-row {
  display:flex;
  align-items:center;
  margin:6px 0;
}
.set-name {
  flex:0 0 240px;
  font-weight:bold;
}
.set-icons {
  display:flex;
  gap:6px;
  margin-left:10px;
}
.set-icons img {
  border-radius:4px;
  box-shadow:
    0 0 0 1px rgba(255,255,255,.2),
    0 1px 2px rgba(0,0,0,.4);
  transition:transform .12s,box-shadow .12s;
}
.set-icons img:hover {
  transform:translateY(-2px) scale(1.08);
  box-shadow:
    0 0 0 1px rgba(255,255,255,.35),
    0 2px 6px rgba(0,0,0,.6);
}

</style>

<!--Pagination CSS Block -->
<style>
/* === Pagination Wrapper === */
.pagination-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  padding: 12px 18px;
  background: #111;
  border: 1px solid #222;
  border-radius: 8px;
  color: #ccc;
  width: 100%;
  max-width: 1000px;
  box-sizing: border-box;
  margin-left: auto;
  margin-right: auto;
}


/* === Page Links === */
.page-links {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
  align-items: center;
}

.page-links a,
.page-links span {
  color: #ffcc66;
  text-decoration: none;
  padding: 6px 10px;
  border-radius: 6px;
  font-weight: bold;
  transition: background 0.2s, color 0.2s;
}

.page-links a:hover {
  background: #ffcc66;
  color: #000;
}

.page-links .current {
  background: #ffcc66;
  color: #000;
  font-weight: bold;
  box-shadow: 0 0 6px #ffcc66;
}

.dots {
  color: #ffcc66;
  padding: 0 6px;
  user-select: none;
}

/* === Page Buttons === */
.page-btn {
  color: #ffcc66;
  background: transparent;
  border: 1px solid transparent;
  padding: 4px 8px;
  border-radius: 6px;
  transition: all 0.2s;
  font-weight: bold;
  text-decoration: none;
}

.page-btn:hover {
  background: #ffcc66;
  color: #000;
  border-color: #ffcc66;
}

.page-btn.active {
  background: #ffcc66;
  color: #000;
  border-color: #ffcc66;
  cursor: default;
}

.page-btn.disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* === Per Page Form === */
.page-size-form {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #ccc;
  font-family: 'Trebuchet MS', sans-serif;
  font-size: 14px;
}

.page-size-form label {
  color: #ffcc66;
  font-weight: bold;
}

.page-size-form select {
  background: #1a1a1a;
  color: #ffcc66;
  border: 1px solid #444;
  border-radius: 4px;
  padding: 4px 8px;
  cursor: pointer;
  font-weight: bold;
  transition: all 0.2s ease;
}

.page-size-form select:hover {
  border-color: #ffcc66;
  box-shadow: 0 0 6px #ffcc66;
}

.page-size-form span {
  color: #aaa;
}

.pagination-controls,
.character-table {
  max-width: 1000px;
  margin: 0 auto;
}

.character-table {
  width: 100%;
  border-collapse: collapse;
}

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

