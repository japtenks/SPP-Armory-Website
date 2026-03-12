<?php
$siteRoot = !empty($_SERVER['DOCUMENT_ROOT'])
    ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')
    : dirname(__DIR__, 3);

require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/core/dbsimple/Generic.php');
require_once($siteRoot . '/armory/configuration/settings.php');
require_once($siteRoot . '/armory/configuration/mysql.php');
require_once($siteRoot . '/armory/configuration/defines.php');

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
        if (strcasecmp($requestedRealm, $mappedRealmInfo['label']) === 0) {
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
$talentBaseParams = 'index.php?n=server&sub=talents&realm=' . rawurlencode((string) REALM_NAME) . '&class=' . (int) $selectedClassId;
if ($selectedCharacter !== '') {
    $talentBaseParams .= '&character=' . rawurlencode($selectedCharacter);
}
echo '<script>window.tcBaseUrl = ' . json_encode($talentBaseParams) . ';</script>';
?>
<link rel="stylesheet" href="/armory/css/talents-calc.css?v=modern-server">
<script defer src="/armory/js/talents-calc.js?v=modern-server"></script>
<style>
.server-talents-shell {
  padding: 12px 10px 16px;
}
.server-talents-shell .tc-container {
  max-width: none;
  margin: 0;
}
</style>
<?php
builddiv_start(1, 'Talent Calculator', 1);
echo '<div class="server-talents-shell">';
include($siteRoot . '/armory/source/talent-calc.php');
echo '</div>';
builddiv_end();



