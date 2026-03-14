<?php


function get_forum_byid($id){
  global $DB;
  $result = $DB->selectRow("SELECT * FROM f_forums WHERE forum_id=?d",$id);
  return $result;
}
function get_topic_byid($id){
  global $DB;
  $result = $DB->selectRow("SELECT * FROM f_topics WHERE topic_id=?d",$id);
  return $result;
}
function get_post_byid($id){
  global $DB;
  $result = $DB->selectRow("SELECT * FROM f_posts WHERE post_id=?d",$id);
  return $result;
}
function get_last_forum_topic($id){
  global $DB;
  $result = $DB->selectRow("SELECT * FROM f_topics WHERE forum_id=?d ORDER BY last_post DESC LIMIT 1",$id);
  return $result;
}
function get_last_topic_post($id){
  global $DB;
  $result = $DB->selectRow("SELECT * FROM f_posts WHERE topic_id=?d ORDER BY posted DESC LIMIT 1",$id);
  return $result;
}
function get_post_pos($tid,$pid){
  global $DB;
  $result = $DB->selectCell("SELECT count(*) FROM f_posts WHERE topic_id=?d AND post_id<?d ORDER BY posted",$tid,$pid);
    /*
  foreach($result as $result_id){
    $post_c++;
        if($result_id==$pid)return $post_c;
  }
    */
  return $result;
}

function declension($int, $expressions)
{
    /*
     * Choost russion word declension based on numeric.
     */ 
    if (count($expressions) < 3) $expressions[2] = $expressions[1];
    settype($int, "integer");
    $count = $int % 100;
    if ($count >= 5 && $count <= 20) {
        $result = $expressions['2'];
    } else {
        $count = $count % 10;
        if ($count == 1) {
            $result = $expressions['0'];
        } elseif ($count >= 2 && $count <= 4) {
            $result = $expressions['1'];
        } else {
            $result = $expressions['2'];
        }
    }
    return $result;
}

function isValidChar($user, $realmId = null)
{
    if(!isset($user['character_id']) || empty($user['character_id']) ||
       !isset($user['character_name']) || empty($user['character_name']))
    {
        return false;
    }

    if ($realmId !== null && function_exists('spp_get_pdo')) {
        try {
            $charsPdo = spp_get_pdo('chars', (int)$realmId);
            $stmt = $charsPdo->prepare('SELECT COUNT(1) FROM `characters` WHERE `guid` = :guid AND `name` = :name AND `account` = :account');
            $stmt->execute([
                ':guid' => (int)$user['character_id'],
                ':name' => (string)$user['character_name'],
                ':account' => (int)$user['id'],
            ]);
            return ((int)$stmt->fetchColumn() === 1);
        } catch (Throwable $e) {
            error_log('[forum.isValidChar] Realm character validation failed: ' . $e->getMessage());
        }
    }

    return ($GLOBALS['CHDB']->selectCell('SELECT COUNT(1) AS cnt FROM `characters` WHERE `guid`=?d AND name=? AND account=?d',
                                         $user['character_id'], $user['character_name'], $user['id']) == 1);
}

function resolve_forum_character_for_realm(array $user, int $realmId)
{
    $characters = $GLOBALS['characters'] ?? [];
    if (!is_array($characters) || empty($characters) || empty($user['id'])) {
        return null;
    }

    $cookieCharacterId = (int)($_COOKIE['cur_selected_character'] ?? 0);
    $cookieRealmId = (int)($_COOKIE['cur_selected_realmd'] ?? ($_COOKIE['cur_selected_realm'] ?? 0));

    if ($cookieCharacterId > 0 && $cookieRealmId > 0) {
        foreach ($characters as $character) {
            if (
                (int)($character['realm_id'] ?? 0) === $cookieRealmId &&
                (int)($character['guid'] ?? 0) === $cookieCharacterId
            ) {
                return $character;
            }
        }
    }

    foreach ($characters as $character) {
        if ((int)($character['realm_id'] ?? 0) === $realmId && (int)$character['guid'] === $cookieCharacterId) {
            return $character;
        }
    }

    if (!empty($user['character_id']) && !empty($user['character_name'])) {
        foreach ($characters as $character) {
            if (
                (int)($character['guid'] ?? 0) === (int)$user['character_id'] &&
                (string)($character['name'] ?? '') === (string)$user['character_name']
            ) {
                return $character;
            }
        }
    }

    foreach ($characters as $character) {
        if ((int)($character['realm_id'] ?? 0) === $realmId && !empty($character['guid']) && !empty($character['name'])) {
            return $character;
        }
    }

    return null;
}

function get_character_portrait_path($guid, $gender, $race, $class)
{
    $portraitDir = "templates/offlike/images/portraits/wow-70/";
    $cacheDir = "templates/offlike/cache/portraits/";
    $cacheFile = $cacheDir . "portrait_{$guid}.gif";

    if (file_exists($cacheFile)) {
        return $cacheFile;
    }

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    $matches = glob(sprintf("%s%d-%d-%d*.gif", $portraitDir, $gender, $race, $class));
    if (!empty($matches)) {
        sort($matches, SORT_NATURAL);
        copy($matches[0], $cacheFile);
        return $cacheFile;
    }

    return "/templates/offlike/images/icons/race/" . $race . '-' . $gender . ".gif";
}

$yesterday_ts = mktime(0, 0, 0, date("m")  , date("d")-1, date("Y"));

?>

