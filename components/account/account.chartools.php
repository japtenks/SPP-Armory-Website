<?php
if(INCLUDED!==true)exit;
require_once(__DIR__ . '/account.chartools.helpers.php');
// ==================== //
$pathway_info[] = array('title'=>$lang['char_manage'],'link'=>'');
// ==================== //

$MANG = new Mangos;

// Here we chack to see if user is logged in, if not, then redirect to account login screen
if($user['id']<=0){
    redirect('index.php?n=account&sub=login',1);
}else{
}
?>
<?php
$chartoolsState = spp_account_chartools_build_state($user, $MW->getConfig);

$show_rename = $chartoolsState['show_rename'];
$show_custom = $chartoolsState['show_custom'];
$show_changer = $chartoolsState['show_changer'];
$allow_faction_change = $chartoolsState['allow_faction_change'];
$account_id = (int)$chartoolsState['account_id'];
$char_rename_points = (int)$chartoolsState['char_rename_points'];
$char_custom_points = (int)$chartoolsState['char_custom_points'];
$char_faction_points = (int)$chartoolsState['char_faction_points'];
$realmPdoCt = $chartoolsState['realm_pdo'];
$charPdoCt = $chartoolsState['char_pdo'];
$your_points = (int)$chartoolsState['your_points'];
$chartoolsCharacters = $chartoolsState['characters'];

// Functions
function check_if_online($name, $charPdo)
{
    $stmt = $charPdo->prepare("SELECT `online` FROM `characters` WHERE `name` = ?");
    $stmt->execute([$name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return -1;
    return (int)$row['online'] === 1 ? 1 : 0;
}
function check_if_name_exist($newname, $charPdo)
{
    $stmt = $charPdo->prepare("SELECT COUNT(*) FROM `characters` WHERE `name` = ?");
    $stmt->execute([$newname]);
    return (int)$stmt->fetchColumn() === 0 ? 0 : 1;
}
function change_name($name, $newname, $account_id, $charPdo)
{
    $stmt = $charPdo->prepare("UPDATE `characters` SET `name`=? WHERE `name`=? AND `account`=?");
    $stmt->execute([$newname, $name, (int)$account_id]);
}

// Here is WHERE the re-customization scripts start
function customize($name, $charPdo, $account_id)
{
    $stmt = $charPdo->prepare("UPDATE `characters` SET `at_login`=8 WHERE `name`=? AND `account`=?");
    $stmt->execute([$name, (int)$account_id]);
}

// Here is where the "Race changer / Faction changer" scripts start
function check_guild($guid, $charPdo)
{
    $stmt = $charPdo->prepare("SELECT `guildid` FROM `guild_member` WHERE guid=?");
    $stmt->execute([(int)$guid]);
    return $stmt->fetch() ? 1 : 0;
}

// Here is my borrowed scripts from "dp92" for the race changer.
function isAlliance($race) {
         if ($race == 1 || $race == 3 || $race == 4 || $race == 7 || $race == 11) {
            return true;
         }
         return false;
}
function isGood($race,$class) {
         switch ($race) {
                case 1:
                     if ($class == 1 || $class == 2 || $class == 4 || $class == 5 || $class == 6 || $class == 8 || $class == 9) { return true; }
                     break;
                case 2:
                     if ($class == 1 || $class == 3 || $class == 4 || $class == 6 || $class == 7 || $class == 9) { return true; }
                     break;
                case 3:
                     if ($class == 1 || $class == 2 || $class == 3 || $class == 4 || $class == 5 || $class == 6) { return true; }
                     break;
                case 4:
                     if ($class == 1 || $class == 3 || $class == 4 || $class == 5 || $class == 6 || $class == 11) { return true; }
                     break;
                case 5:
                     if ($class == 1 || $class == 4 || $class == 5 || $class == 6 || $class == 8 || $class == 9) { return true; }
                     break;
                case 6:
                     if ($class == 1 || $class == 3 || $class == 6 || $class == 7 || $class == 11) { return true; }
                     break;
                case 7:
                     if ($class == 1 || $class == 4 || $class == 6 || $class == 8 || $class == 9) { return true; }
                     break;
                case 8:
                     if ($class == 1 || $class == 3 || $class == 4 || $class == 5 || $class == 6 || $class == 7 || $class == 8) { return true; }
                     break;
                case 10:
                     if ($class == 2 || $class == 3 || $class == 4 || $class == 5 || $class == 6 || $class == 8 || $class == 9) { return true; }
                     break;
                case 11:
                     if ($class == 1 || $class == 2 || $class == 3 || $class == 5 || $class == 6 || $class == 7 || $class == 8) { return true; }
                     break;
         }
         return false;

}
function rep($race) {
         switch ($race) {
                case 1:
                     return 72;
                     break;
                case 2:
                     return 76;
                     break;
                case 3:
                     return 47;
                     break;
                case 4:
                     return 69;
                     break;
                case 5:
                     return 68;
                     break;
                case 6:
                     return 81;
                     break;
                case 7:
                     return 54;
                     break;
                case 8:
                     return 530;
                     break;
                case 10:
                     return 911;
                     break;
                case 11:
                     return 930;
                     break;
         }
}
function delMounts($guid, $race, $charPdo) {
    $guid = (int)$guid;
         switch ($race) {
                case 1:
                     $charPdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=472 or spell=6648 or spell=458 or spell=470 or spell=23229 or spell=23228 or spell=23227 or spell=63232 or spell=65640)");
                     $charPdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=472 or spell=6648 or spell=458 or spell=470 or spell=23229 or spell=23228 or spell=23227 or spell=63232 or spell=65640)");
                     $charPdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=2414 or item_template=5655 or item_template=5656 or item_template=2411 or item_template=18777 or item_template=18778 or item_template=18776 or item_template=45125 or item_template=46752)");
                     break;
                case 2:
                     $charPdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=580 or spell=6653 or spell=6654 or spell=64658 or spell=23250 or spell=23252 or spell=23251 or spell=63640 or spell=65646)");
                     $charPdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=580 or spell=6653 or spell=6654 or spell=64658 or spell=23250 or spell=23252 or spell=23251 or spell=63640 or spell=65646)");
                     $charPdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=1132 or item_template=5665 or item_template=5668 or item_template=46099 or item_template=18796 or item_template=18798 or item_template=18797 or item_template=45595 or item_template=46749)");
                     break;
                case 3:
                     $charPdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=6777 or spell=6898 or spell=6899 or spell=23239 or spell=23240 or spell=23238 or spell=63636 or spell=65643)");
                     $charPdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=6777 or spell=6898 or spell=6899 or spell=23239 or spell=23240 or spell=23238 or spell=63636 or spell=65643)");
                     $charPdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=5864 or item_template=5873 or item_template=5872 or item_template=18787 or item_template=18785 or item_template=18786 or item_template=45586 or item_template=46748)");
                     break;
                case 4:
                     $charPdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=8394 or spell=10789 or spell=10793 or spell=66847 or spell=23338 or spell=23219 or spell=23221 or spell=63637 or spell=65638)");
                     $charPdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=8394 or spell=10789 or spell=10793 or spell=66847 or spell=23338 or spell=23219 or spell=23221 or spell=63637 or spell=65638)");
                     $charPdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=8631 or item_template=8632 or item_template=8629 or item_template=47100 or item_template=18902 or item_template=18767 or item_template=18766 or item_template=45591 or item_template=46744)");
                     break;
                case 5:
                     $charPdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=64977 or spell=17464 or spell=17463 or spell=17462 or spell=17465 or spell=23246 or spell=66846 or spell=63643 or spell=65645)");
                     $charPdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=64977 or spell=17464 or spell=17463 or spell=17462 or spell=17465 or spell=23246 or spell=66846 or spell=63643 or spell=65645)");
                     $charPdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=46308 or item_template=13333 or item_template=13332 or item_template=13331 or item_template=13334 or item_template=18791 or item_template=47101 or item_template=45597 or item_template=46746)");
                     break;
                case 6:
                     $charPdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=18990 or spell=18989 or spell=64657 or spell=23249 or spell=23248 or spell=23247 or spell=63641 or spell=65641)");
                     $charPdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=18990 or spell=18989 or spell=64657 or spell=23249 or spell=23248 or spell=23247 or spell=63641 or spell=65641)");
                     $charPdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=15290 or item_template=15277 or item_template=46100 or item_template=18794 or item_template=18795 or item_template=18793 or item_template=45592 or item_template=46750)");
                     break;
                case 7:
                     $charPdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=10969 or spell=17453 or spell=10873 or spell=17454 or spell=23225 or spell=23223 or spell=23222 or spell=63638 or spell=65642)");
                     $charPdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=10969 or spell=17453 or spell=10873 or spell=17454 or spell=23225 or spell=23223 or spell=23222 or spell=63638 or spell=65642)");
                     $charPdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=8595 or item_template=13321 or item_template=8563 or item_template=13322 or item_template=18772 or item_template=18773 or item_template=18774 or item_template=45589 or item_template=46747)");
                     break;
                case 8:
                     $charPdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=8395 or spell=10796 or spell=10799 or spell=23241 or spell=23242 or spell=23243 or spell=63635 or spell=65644)");
                     $charPdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=8395 or spell=10796 or spell=10799 or spell=23241 or spell=23242 or spell=23243 or spell=63635 or spell=65644)");
                     $charPdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=8588 or item_template=8591 or item_template=8592 or item_template=18788 or item_template=18789 or item_template=18790 or item_template=45593 or item_template=46743)");
                     break;
                case 10:
                     $charPdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=35022 or spell=35020 or spell=34795 or spell=35018 or spell=35025 or spell=35027 or spell=33660 or spell=63642 or spell=65639)");
                     $charPdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=35022 or spell=35020 or spell=34795 or spell=35018 or spell=35025 or spell=35027 or spell=33660 or spell=63642 or spell=65639)");
                     $charPdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=29221 or item_template=29220 or item_template=28927 or item_template=29222 or item_template=29223 or item_template=29224 or item_template=28936 or item_template=45596 or item_template=46751)");
                     break;
                case 11:
                     $charPdo->exec("DELETE FROM character_spell WHERE guid='$guid' AND (spell=34406 or spell=35710 or spell=35711 or spell=35713 or spell=35712 or spell=35714 or spell=63639 or spell=65637)");
                     $charPdo->exec("DELETE FROM character_aura WHERE guid='$guid' AND (spell=34406 or spell=35710 or spell=35711 or spell=35713 or spell=35712 or spell=35714 or spell=63639 or spell=65637)");
                     $charPdo->exec("DELETE FROM character_inventory WHERE guid='$guid' AND (item_template=28481 or item_template=29744 or item_template=29743 or item_template=29745 or item_template=29746 or item_template=29747 or item_template=45590 or item_template=46745)");
                     break;
         }
}
function addMounts($guid, $race, $charPdo) {
    $guid = (int)$guid;
         switch ($race) {
                case 1:
                     $mount1 = 472;
                     $mount2 = 23229;
					 break;
                case 2:
                     $mount1 = 580;
                     $mount2 = 23250;
                     break;
                case 3:
                     $mount1 = 6777;
                     $mount2 = 23239;
                     break;
                case 4:
                     $mount1 = 8394;
                     $mount2 = 23338;
                     break;
                case 5:
                     $mount1 = 64977;
                     $mount2 = 23246;
                     break;
                case 6:
                     $mount1 = 18990;
                     $mount2 = 23249;
                     break;
                case 7:
                     $mount1 = 10969;
                     $mount2 = 23225;
                     break;
                case 8:
                     $mount1 = 8395;
                     $mount2 = 23241;
                     break;
                case 10:
                     $mount1 = 35022;
                     $mount2 = 35025;
                     break;
                case 11:
                     $mount1 = 34406;
                     $mount2 = 35713;
                     break;
         }
         $pop = $charPdo->query("SELECT * FROM character_spell WHERE guid='$guid' AND spell=33388")->fetchAll();
         if (count($pop) > 0) {
            $charPdo->exec("INSERT INTO character_spell (guid,spell) VALUES ('$guid','$mount1')");
         }
         $pep = $charPdo->query("SELECT * FROM character_spell WHERE guid='$guid' AND (spell=33391 or spell=34090 or spell=34091)")->fetchAll();
         if (count($pep) > 0) {
            $charPdo->exec("INSERT INTO character_spell (guid,spell) VALUES ('$guid','$mount1')");
            $charPdo->exec("INSERT INTO character_spell (guid,spell) VALUES ('$guid','$mount2')");
         }
	}

require_once(__DIR__ . '/account.chartools.actions.php');

$chartoolsCsrfToken = spp_csrf_token('account_chartools');
$chartoolsActionState = spp_account_chartools_handle_actions(array(
    'MANG' => $MANG,
    'account_id' => $account_id,
    'realm_pdo' => $realmPdoCt,
    'char_pdo' => $charPdoCt,
    'allow_faction_change' => $allow_faction_change,
    'char_rename_points' => $char_rename_points,
    'char_custom_points' => $char_custom_points,
    'char_faction_points' => $char_faction_points,
    'your_points' => $your_points,
));

$chartoolsUnstuckMessage = (string)$chartoolsActionState['unstuck_message'];
$chartoolsRenameMessage = (string)$chartoolsActionState['rename_message'];
$chartoolsCustomizeMessage = (string)$chartoolsActionState['customize_message'];
$chartoolsRaceMessage = (string)$chartoolsActionState['race_message'];
$chartoolsRaceStep = (int)$chartoolsActionState['race_step'];
$chartoolsRaceContext = is_array($chartoolsActionState['race_context']) ? $chartoolsActionState['race_context'] : array();
?>
