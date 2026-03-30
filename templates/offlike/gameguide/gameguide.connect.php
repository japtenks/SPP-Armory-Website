<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

$connectRealmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
$connectRealmId = 1;
if (function_exists('spp_resolve_realm_id') && is_array($connectRealmMap) && !empty($connectRealmMap)) {
    $connectRealmId = (int)spp_resolve_realm_id($connectRealmMap);
}
if ($connectRealmId <= 0) $connectRealmId = 1;

$connectRealmName = 'This Server';
$connectRealmlistHost = '';
if (function_exists('spp_get_pdo')) {
    try {
        $connectRealmPdo = spp_get_pdo('realmd', $connectRealmId);
        $connectRealmStmt = $connectRealmPdo->prepare('SELECT `name`, `address` FROM `realmlist` WHERE `id` = ? LIMIT 1');
        $connectRealmStmt->execute(array($connectRealmId));
        $connectRealmRow = $connectRealmStmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($connectRealmRow['name'])) {
            $connectRealmName = (string)$connectRealmRow['name'];
        }
        if (!empty($connectRealmRow['address'])) {
            $connectRealmlistHost = (string)$connectRealmRow['address'];
        }
    } catch (Throwable $e) {
        $connectRealmName = 'This Server';
    }
}
if ($connectRealmlistHost === '' && !empty($clientConnectionHost)) {
    $connectRealmlistHost = trim((string)$clientConnectionHost);
}
if ($connectRealmlistHost === '' && !empty($_SERVER['HTTP_HOST'])) {
    $connectRealmlistHost = preg_replace('/:\d+$/', '', (string)$_SERVER['HTTP_HOST']);
}
if ($connectRealmlistHost === '') {
    $connectRealmlistHost = (string)($_SERVER['SERVER_ADDR'] ?? '127.0.0.1');
}

$createAccountUrl = function_exists('mw_url')
    ? mw_url('account', 'register', null, false)
    : 'index.php?n=account&sub=register';
$downloadRealmlistUrl = 'download-realmlist.php?realm=' . $connectRealmId;
$isLoggedIn = !empty($user['id']) && (int)$user['id'] > 0;
?>
<?php builddiv_start(1, $lang['howtoplay']); ?>
<div class="modern-content">
<?php header_image_gif('game_guide'); ?>
<style>
.connect-guide{display:grid;gap:22px;color:#f4ead0}
.connect-hero,.connect-panel{border-radius:18px;border:1px solid rgba(255,196,0,.18);background:rgba(7,10,18,.78);box-shadow:0 18px 40px rgba(0,0,0,.28)}
.connect-hero{padding:26px 28px;background:radial-gradient(circle at top right,rgba(91,170,255,.14),transparent 34%),radial-gradient(circle at left center,rgba(255,196,0,.12),transparent 28%),linear-gradient(180deg,rgba(11,16,28,.96),rgba(6,8,14,.98))}
.connect-eyebrow{margin:0 0 8px;color:#c7b07b;font-size:.8rem;letter-spacing:.12em;text-transform:uppercase}
.connect-title{margin:0;color:#fff1bd;font-size:2.1rem;line-height:1.05}
.connect-copy{margin:12px 0 0;color:#d9d1ba;font-size:1rem;line-height:1.65;max-width:70ch}
.connect-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:18px}
.connect-button{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 18px;border-radius:999px;border:1px solid rgba(255,204,72,.24);background:rgba(255,255,255,.04);color:#f6e3ad;text-decoration:none;font-weight:800}
.connect-button.is-primary{background:linear-gradient(180deg,#ffd87a,#d9a63d);border-color:rgba(255,204,72,.5);color:#201300}
.connect-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:22px}
.connect-panel{padding:22px 24px}
.connect-panel h3{margin:0 0 10px;color:#fff1bd;font-size:1.25rem}
.connect-panel p{margin:0 0 14px;color:#d9d1ba;line-height:1.65}
.connect-note{margin:0 0 16px;padding:12px 14px;border-radius:14px;border:1px solid rgba(255,204,72,.14);background:rgba(255,204,72,.05);color:#f0dfb0}
.connect-list{margin:0;padding-left:18px;color:#f4ead0}
.connect-list li{margin:0 0 10px}
.connect-inline-code{display:inline-block;padding:3px 8px;border-radius:999px;background:rgba(255,204,72,.08);border:1px solid rgba(255,204,72,.12);color:#ffe39a;font-family:Consolas,'Courier New',monospace;font-size:.92rem}
.connect-option-list{display:grid;gap:14px}
.connect-option{padding:16px 18px;border-radius:16px;border:1px solid rgba(255,204,72,.14);background:linear-gradient(180deg,rgba(15,20,34,.94),rgba(7,10,18,.94))}
.connect-option-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 8px}
.connect-option-title strong{color:#fff1bd;font-size:1.02rem}
.connect-option-badge{display:inline-flex;align-items:center;justify-content:center;padding:5px 10px;border-radius:999px;background:rgba(255,204,72,.08);border:1px solid rgba(255,204,72,.12);color:#c7b07b;font-size:.76rem;letter-spacing:.08em;text-transform:uppercase}
.connect-link{color:#7fc7ff;text-decoration:none;font-weight:700}
.connect-link:hover,.connect-button:hover{text-decoration:underline}
.connect-step-grid{display:grid;grid-template-columns:1fr;gap:12px;align-items:start}
.connect-step{padding:14px 16px;border-radius:16px;border:1px solid rgba(255,204,72,.14);background:rgba(255,255,255,.03)}
.connect-step strong{display:block;margin-bottom:6px;color:#fff1bd}
@media (max-width:900px){.connect-grid{grid-template-columns:1fr}}
@media (max-width:640px){.connect-hero,.connect-panel{padding:18px}.connect-title{font-size:1.7rem}.connect-actions{flex-direction:column}.connect-button{width:100%}}
</style>

<div class="connect-guide">
  <section class="connect-hero">
    <p class="connect-eyebrow"><?php echo htmlspecialchars($connectRealmName); ?> Setup</p>
    <h2 class="connect-title"><?php echo $isLoggedIn ? 'Choose a client and connect in a few minutes.' : 'Choose a client, create an account, and connect in a few minutes.'; ?></h2>
    <p class="connect-copy">
      The player will need to source their own WoW client. You can connect with a<br><br><strong>Vanilla (1.12.1) client</strong>, optionally pair it with <strong>Project Reforged</strong> for HD visuals<br><strong>Classic (1.14.2) with HermesProxy</strong><br><strong>WoWee</strong>, the open source C++ client.<br><br> <?php echo $isLoggedIn ? 'Then use this server\'s <code>realmlist.wtf</code> to connect.' : 'Create your account, then use this server\'s <code>realmlist.wtf</code> to connect.'; ?>
    </p>
    <div class="connect-actions">
      <?php if (!$isLoggedIn) { ?>
      <a class="connect-button is-primary" href="<?php echo htmlspecialchars($createAccountUrl); ?>">Create Account</a>
      <?php } ?>
      <a class="connect-button" href="<?php echo htmlspecialchars($downloadRealmlistUrl); ?>">Download realmlist.wtf</a>
    </div>
  </section>

  <div class="connect-grid">
    <section class="connect-panel">
      <h3>Install Options</h3>
      <p>Pick whichever setup fits how you want to play.</p>
      <p class="connect-note">You will need to provide your own game client install. The options below cover the supported ways to connect to this server.</p>
      <div class="connect-option-list">
        <article class="connect-option">
          <div class="connect-option-title">
            <strong>HermesProxy 3.8.c</strong>
            <span class="connect-option-badge">Launcher Option</span>
          </div>
          <p>Use HermesProxy if you want to play through the <strong>Classic 1.14.2</strong> client path.</p>
          <a class="connect-link" href="https://github.com/celguar/HermesProxy/releases/tag/3.8.c" target="_blank" rel="noopener noreferrer">Open HermesProxy 3.8.c release</a>
        </article>
        <article class="connect-option">
          <div class="connect-option-title">
            <strong>Project Reforged</strong>
            <span class="connect-option-badge">HD Visual Mod</span>
          </div>
          <p>Project Reforged is an <strong>optional HD visual mod</strong> layered on top of a <strong>Vanilla 1.12.1</strong> client.</p>
          <a class="connect-link" href="https://projectreforged.github.io/" target="_blank" rel="noopener noreferrer">Open Project Reforged</a>
        </article>
        <article class="connect-option">
          <div class="connect-option-title">
            <strong>WoWee</strong>
            <span class="connect-option-badge">Open Source Client</span>
          </div>
          <p>WoWee is an <strong>open source C++ client</strong> option if you want an alternative to the original Blizzard clients.</p>
          <a class="connect-link" href="https://github.com/Kelsidavis/WoWee" target="_blank" rel="noopener noreferrer">Open WoWee on GitHub</a>
        </article>
      </div>
    </section>

    <section class="connect-panel">
      <h3>Quick Start</h3>
      <div class="connect-step-grid">
        <div class="connect-step">
          <strong>1. Install your version of choice</strong>
          Source your own client, then choose<br> <strong>-Vanilla (1.12.1)</strong><strong>-Classic (1.14.2) with HermesProxy</strong><strong>-WoWee</strong>
        </div>
        <?php if (!$isLoggedIn) { ?>
        <div class="connect-step">
          <strong>2. Create your account</strong>
          Use the website registration page here:
          <a class="connect-link" href="<?php echo htmlspecialchars($createAccountUrl); ?>">Create Account</a>
        </div>
        <?php } else { ?>
        <div class="connect-step">
          <strong>2. Account ready</strong>
          You're already signed in on the website, so you can move straight to the client connection steps below.
        </div>
        <?php } ?>
        <div class="connect-step">
          <strong>3. Get this server's realmlist</strong>
          Download the file directly here:
          <a class="connect-link" href="<?php echo htmlspecialchars($downloadRealmlistUrl); ?>">realmlist.wtf for <?php echo htmlspecialchars($connectRealmName); ?></a>
        </div>
        <div class="connect-step">
          <strong>4. Manual fallback if needed</strong>
          If you need to edit it by hand, your file should contain:
          <span class="connect-inline-code">set realmlist <?php echo htmlspecialchars($connectRealmlistHost); ?></span>
        </div>
      </div>
    </section>
  </div>

</div>
</div>
<?php builddiv_end(); ?>
