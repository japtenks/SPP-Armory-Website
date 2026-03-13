<?php
$siteRoot = !empty($_SERVER['DOCUMENT_ROOT'])
    ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')
    : dirname(__DIR__, 3);

require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/core/dbsimple/Generic.php');
require_once($siteRoot . '/armory/configuration/settings.php');
require_once($siteRoot . '/armory/configuration/mysql.php');
require_once($siteRoot . '/armory/configuration/defines.php');
require_once($siteRoot . '/armory/configuration/statisticshandler.php');

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

$requestedRealm = $_GET['realm'] ?? null;
$realmId = null;
if (is_string($requestedRealm) && $requestedRealm !== '' && !ctype_digit($requestedRealm)) {
    foreach ($realmMap as $mappedRealmId => $mappedRealmInfo) {
        $mappedArmoryRealm = spp_get_armory_realm_name((int)$mappedRealmId) ?? ($mappedRealmInfo['label'] ?? '');
        if (strcasecmp($requestedRealm, $mappedRealmInfo['label']) === 0 || strcasecmp($requestedRealm, $mappedArmoryRealm) === 0) {
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

$armoryRealmName = defined('DefaultRealmName') ? DefaultRealmName : null;
foreach ($realms as $realmName => $realmInfo) {
    if ((int)$realmInfo[0] === (int)$realmId) {
        $armoryRealmName = $realmName;
        break;
    }
}
if (!$armoryRealmName) {
    die('Unable to resolve realm for talent calculator.');
}

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

$DB = server_talents_init_db($realmd_DB[$realms[$armoryRealmName][0]]);
$WSDB = server_talents_init_db($mangosd_DB[$realms[$armoryRealmName][2]]);
$CHDB = server_talents_init_db($characters_DB[$realms[$armoryRealmName][1]]);
$ARDB = server_talents_init_db($armory_DB[$realms[$armoryRealmName][3]]);

if (!defined('REALM_NAME')) {
    define('REALM_NAME', $armoryRealmName);
}
if (!defined('CLIENT')) {
    define('CLIENT', $ARDB->selectCell('SELECT `value` FROM `conf_client` LIMIT 1'));
}
if (!defined('LANGUAGE')) {
    define('LANGUAGE', $ARDB->selectCell('SELECT `value` FROM `conf_lang` LIMIT 1'));
}

$languageFile = $siteRoot . '/armory/configuration/' . LANGUAGE . '/languagearray.php';
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
    $characterRow = $CHDB->selectRow(
        'SELECT `guid`, `name`, `class`, `level` FROM `characters` WHERE `name`=? LIMIT 1',
        $selectedCharacter
    );
    if ($characterRow) {
        $stat = array_merge($stat, $characterRow);
        if ($selectedClassParam === '') {
            $selectedClassId = (int)$characterRow['class'];
        }
    }
}

$_GET['class'] = $selectedClassId;
$_GET['realm'] = REALM_NAME;
$GLOBALS['talent_calc_base_url'] = 'index.php?n=server&sub=talents';
$GLOBALS['server_talent_calc_mode'] = !$isProfileMode;
$GLOBALS['server_talent_profile_mode'] = $isProfileMode;
$talentBaseParams = 'index.php?n=server&sub=talents&realm=' . rawurlencode((string) REALM_NAME) . '&class=' . (int) $selectedClassId;
if ($selectedCharacter !== '') {
    $talentBaseParams .= '&character=' . rawurlencode($selectedCharacter);
}
if ($isProfileMode) {
    $talentBaseParams .= '&mode=profile';
}
echo '<script>window.tcBaseUrl = ' . json_encode($talentBaseParams) . ';</script>';
?>
<?php if (!$isProfileMode): ?>
<link rel="stylesheet" href="/armory/css/talents-calc.css?v=modern-server">
<script defer src="/armory/js/talents-calc.js?v=modern-server"></script>
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
  margin-bottom: 16px;
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
include($siteRoot . ($isProfileMode ? '/armory/source/character-talents.php' : '/armory/source/talent-calc.php'));
echo '</div>';
if (!$isEmbedMode) builddiv_end();




