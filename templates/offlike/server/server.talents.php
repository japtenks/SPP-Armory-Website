<?php
$siteRoot = dirname(__DIR__, 3);

require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/core/dbsimple/Generic.php');
require_once($siteRoot . '/config/armory/settings.php');
require_once($siteRoot . '/config/armory/mysql.php');
require_once($siteRoot . '/config/armory/defines.php');
// statisticshandler.php omitted — character-profile stats functions not needed for talent calc

if (!defined('Armory')) {
    define('Armory', 1);
}
if (!defined('REQUESTED_ACTION')) {
    define('REQUESTED_ACTION', 'talentscalc');
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die('Realm DB map not loaded');
}

if (!function_exists('server_talents_resolve_armory_realm_name')) {
    function server_talents_resolve_armory_realm_name($realmId, array $realmMap, array $legacyRealms = array()): string
    {
        $realmId = (int)$realmId;

        if (function_exists('spp_get_armory_realm_name')) {
            $resolved = spp_get_armory_realm_name($realmId);
            if (is_string($resolved) && $resolved !== '') {
                return $resolved;
            }
        }

        foreach ($legacyRealms as $realmName => $realmInfo) {
            if ((int)($realmInfo[0] ?? 0) === $realmId) {
                return (string)$realmName;
            }
        }

        return 'Realm ' . $realmId;
    }
}

$requestedRealm = $_GET['realm'] ?? null;
$realmId = null;
if (is_string($requestedRealm) && $requestedRealm !== '' && !ctype_digit($requestedRealm)) {
    foreach ($realmMap as $mappedRealmId => $mappedRealmInfo) {
        $mappedArmoryRealm = server_talents_resolve_armory_realm_name((int)$mappedRealmId, $realmMap, $realms ?? array());
        if ($mappedArmoryRealm !== '' && strcasecmp($requestedRealm, $mappedArmoryRealm) === 0) {
            $realmId = (int)$mappedRealmId;
            break;
        }
    }
    if ($realmId === null) {
        foreach ($realms as $realmName => $realmInfo) {
            if (strcasecmp($requestedRealm, $realmName) === 0) {
                $realmId = (int)$realmInfo[0];
                break;
            }
        }
    }
}
if ($realmId === null) {
    $realmId = spp_resolve_realm_id($realmMap);
}

$realmConfig = $realmMap[$realmId] ?? null;
if (!is_array($realmConfig)) {
    die('Unable to resolve realm for talent calculator.');
}

$armoryRealmName = server_talents_resolve_armory_realm_name($realmId, $realmMap, $realms ?? array());

if (!function_exists('server_talents_init_db')) {
    function server_talents_init_db($connectionInfo)
    {
        $db = dbsimple_Generic::connect(
            'mysql://' . $connectionInfo[1] . ':' . $connectionInfo[2] . '@' . $connectionInfo[0] . '/' . $connectionInfo[3]
        );
        $db->setErrorHandler('databaseErrorHandler');
        $db->query('SET NAMES UTF8;');
        return $db;
    }
}

if (!function_exists('server_talents_build_url')) {
    function server_talents_build_url(int $realmId, int $classId, string $characterName = '', bool $isProfileMode = false, bool $isEmbedMode = false): string
    {
        $params = array(
            'n' => 'server',
            'sub' => 'talents',
            'realm' => (string)$realmId,
            'class' => (string)$classId,
        );

        if ($characterName !== '') {
            $params['character'] = $characterName;
        }

        if ($isProfileMode) {
            $params['mode'] = 'profile';
        }

        if ($isEmbedMode) {
            $params['embed'] = '1';
        }

        return 'index.php?' . http_build_query($params);
    }
}

$hostport = $db['host'] . ':' . $db['port'];
$DB = server_talents_init_db(array($hostport, $db['user'], $db['pass'], $realmConfig['realmd']));
$WSDB = server_talents_init_db(array($hostport, $db['user'], $db['pass'], $realmConfig['world']));
$CHDB = spp_get_pdo('chars', $realmId);
$ARDB = server_talents_init_db(array($hostport, $db['user'], $db['pass'], $realmConfig['armory']));
if (!empty($realmConfig['bots'])) {
    $PBDB = server_talents_init_db(array($hostport, $db['user'], $db['pass'], $realmConfig['bots']));
}

if (!defined('REALM_NAME')) {
    define('REALM_NAME', $armoryRealmName);
}
if (!defined('CLIENT')) {
    define('CLIENT', $ARDB->selectCell('SELECT `value` FROM `conf_client` LIMIT 1'));
}
if (!defined('LANGUAGE')) {
    define('LANGUAGE', $ARDB->selectCell('SELECT `value` FROM `conf_lang` LIMIT 1'));
}

$languageFile = $siteRoot . '/config/armory/' . LANGUAGE . '/languagearray.php';
if (is_file($languageFile)) {
    require_once($languageFile);
}

$classNameToId = [
    'warrior' => 1,
    'paladin' => 2,
    'hunter' => 3,
    'rogue' => 4,
    'priest' => 5,
    'shaman' => 7,
    'mage' => 8,
    'warlock' => 9,
    'druid' => 11,
];

$selectedCharacter = trim($_GET['character'] ?? '');
$selectedClassParam = trim($_GET['class'] ?? '');
$selectedClassId = 1;
$viewMode = strtolower(trim($_GET['mode'] ?? 'calc'));
$isProfileMode = in_array($viewMode, array('profile', 'build', 'view'), true);
$isEmbedMode = !empty($_GET['embed']);

if ($selectedClassParam !== '') {
    if (ctype_digit($selectedClassParam)) {
        $selectedClassId = (int)$selectedClassParam;
    } else {
        $selectedClassId = $classNameToId[strtolower($selectedClassParam)] ?? 1;
    }
}

$stat = [
    'guid' => 0,
    'name' => $selectedCharacter,
    'class' => $selectedClassId,
    'level' => 0,
];
if ($selectedCharacter !== '') {
    $chStmt = $CHDB->prepare('SELECT `guid`, `name`, `class`, `level` FROM `characters` WHERE `name`=? LIMIT 1');
    $chStmt->execute([$selectedCharacter]);
    $characterRow = $chStmt->fetch(PDO::FETCH_ASSOC);
    if ($characterRow) {
        $stat = array_merge($stat, $characterRow);
        if ($selectedClassParam === '') {
            $selectedClassId = (int)$characterRow['class'];
        }
    }
}

$_GET['class'] = $selectedClassId;
$_GET['realm'] = (string)$realmId;
$GLOBALS['talent_calc_realm_id'] = (int)$realmId;
$GLOBALS['talent_calc_realm_name'] = (string)REALM_NAME;
$GLOBALS['talent_calc_base_url'] = server_talents_build_url(
    (int)$realmId,
    (int)$selectedClassId,
    $selectedCharacter,
    $isProfileMode,
    $isEmbedMode
);
$GLOBALS['server_talent_calc_mode'] = !$isProfileMode;
$GLOBALS['server_talent_profile_mode'] = $isProfileMode;
$talentBaseParams = $GLOBALS['talent_calc_base_url'];
echo '<script>window.tcBaseUrl = ' . json_encode($talentBaseParams) . ';</script>';
?>
<link rel="stylesheet" href="/templates/offlike/css/talents-calc.css?v=modern-server">
<?php if (!$isProfileMode): ?>
<script defer src="/templates/offlike/js/talents-calc.js?v=modern-server"></script>
<?php endif; ?>
<style>
.server-talents-shell {
  padding: 0;
}
.server-talents-shell #tc-root {
  width: 100%;
  margin: 0;
  transform: none;
}
.server-talents-shell .tc-container {
  max-width: none;
  margin: 0;
  padding: 0;
  background: transparent;
  border-radius: 0;
  box-shadow: none;
}
.server-talents-shell .tc-header {
  width: 100%;
  margin: 0 0 12px;
}
.server-talents-shell .tc-stack {
  width: 100%;
  margin: 0;
  transform: none;
  justify-content: space-between;
}
.server-talents-shell .talent-trees {
  width: 100%;
  margin: 0;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
  align-items: start;
}
.server-talents-shell .talent-tree {
  width: 100%;
  max-width: none;
}
.server-talents-shell .talent-flex {
  width: min(100%, var(--tree-inner-w));
}
.server-talents-shell.is-profile .tc-share,
.server-talents-shell.is-profile .tc-classgrid,
.server-talents-shell.is-profile #tcResetAllBtn {
  display: none !important;
}
.server-talents-shell.is-profile .tc-header {
  display: none !important;
}
.server-talents-shell.is-profile .tc-subtitle {
  font-size: 1.2rem;
}
.server-talents-shell.is-profile .talent-cell {
  cursor: default;
}
.server-talents-shell.is-embed {
  padding: 0;
}
.server-talents-shell.is-embed .tc-container {
  padding: 0;
}
@media (max-width: 980px) {
  .server-talents-shell .talent-trees {
    grid-template-columns: 1fr;
  }
}
</style>
<?php
if (!$isEmbedMode) builddiv_start(1, $isProfileMode ? 'Talent Build' : 'Talent Calculator', 1);
echo '<div class="server-talents-shell' . ($isProfileMode ? ' is-profile' : '') . ($isEmbedMode ? ' is-embed' : '') . '">';
include($siteRoot . '/templates/offlike/server/talent-calc.php');
echo '</div>';
if (!$isEmbedMode) builddiv_end();
