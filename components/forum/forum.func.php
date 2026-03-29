<?php


function get_forum_byid($id){
    $realmId = isset($GLOBALS['realmDbMap']) ? spp_resolve_realm_id($GLOBALS['realmDbMap']) : 1;
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_forums WHERE forum_id=?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function get_topic_byid($id){
    $realmId = isset($GLOBALS['realmDbMap']) ? spp_resolve_realm_id($GLOBALS['realmDbMap']) : 1;
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_topics WHERE topic_id=?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function get_post_byid($id){
    $realmId = isset($GLOBALS['realmDbMap']) ? spp_resolve_realm_id($GLOBALS['realmDbMap']) : 1;
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_posts WHERE post_id=?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function get_last_forum_topic($id){
    $realmId = isset($GLOBALS['realmDbMap']) ? spp_resolve_realm_id($GLOBALS['realmDbMap']) : 1;
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_topics WHERE forum_id=? ORDER BY last_post DESC LIMIT 1");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function get_last_topic_post($id){
    $realmId = isset($GLOBALS['realmDbMap']) ? spp_resolve_realm_id($GLOBALS['realmDbMap']) : 1;
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT * FROM f_posts WHERE topic_id=? ORDER BY posted DESC LIMIT 1");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function get_post_pos($tid,$pid){
    $realmId = isset($GLOBALS['realmDbMap']) ? spp_resolve_realm_id($GLOBALS['realmDbMap']) : 1;
    $pdo  = spp_get_pdo('realmd', $realmId);
    $stmt = $pdo->prepare("SELECT count(*) FROM f_posts WHERE topic_id=? AND post_id<? ORDER BY posted");
    $stmt->execute([(int)$tid, (int)$pid]);
    return $stmt->fetchColumn();
}

function spp_increment_forum_unread(PDO $pdo, int $forumId, int $excludeMemberId = 0): void
{
    $stmt = $pdo->prepare("
        UPDATE f_markread
        SET marker_unread = marker_unread + 1,
            marker_last_update = ?
        WHERE marker_forum_id = ?
          AND (? = 0 OR marker_member_id <> ?)
    ");
    $stmt->execute([
        (int)$_SERVER['REQUEST_TIME'],
        $forumId,
        $excludeMemberId,
        $excludeMemberId,
    ]);
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

// Expansion slug map — realm_id → expansion string used in scope_value.
function spp_realm_to_expansion(int $realmId): string {
    $map = [1 => 'classic', 2 => 'tbc', 3 => 'wotlk'];
    return $map[$realmId] ?? '';
}

// Returns true if the character's current realm is allowed to post in $forum.
// $forum is the row from f_forums (must include scope_type / scope_value).
// $realmId is the character's active realm.
function check_forum_scope(array $forum, int $realmId): bool {
    $scopeType = $forum['scope_type'] ?? 'all';
    if (!$scopeType || $scopeType === 'all') {
        return true;
    }

    $scopeValue = (string)($forum['scope_value'] ?? '');

    if ($scopeType === 'realm') {
        return ((string)$realmId === $scopeValue);
    }

    if ($scopeType === 'expansion') {
        return (spp_realm_to_expansion($realmId) === $scopeValue);
    }

    if ($scopeType === 'guild_recruitment') {
        $user = $GLOBALS['user'] ?? [];
        $charGuid  = (int)($user['character_id'] ?? 0);
        $accountId = (int)($user['id'] ?? 0);
        return get_char_recruitment_guild($realmId, $charGuid, $accountId) !== null;
    }

    // event_feed — reserved for Phase 5 (bot-only writes).
    return true;
}

// MaNGOS guild rank rights bit for INVITE (can recruit members).
define('GUILD_RIGHT_INVITE', 0x10);

// Returns guild info for a character if they are allowed to post in a
// guild_recruitment forum (guild leader or member with invite rights).
// Returns null if the character is not in a guild or lacks permission.
//
// Return shape: ['guildid' => int, 'name' => str, 'rank' => int, 'is_leader' => bool]
function get_char_recruitment_guild(int $realmId, int $charGuid, int $accountId): ?array {
    if ($charGuid <= 0 || $accountId <= 0) {
        return null;
    }

    try {
        $charsPdo = spp_get_pdo('chars', $realmId);

        // Verify the character belongs to this account and fetch guild membership.
        $stmt = $charsPdo->prepare("
            SELECT g.guildid, g.name AS guild_name, g.leaderguid,
                   gm.rank,
                   COALESCE(gr.rights, 0) AS rank_rights
            FROM characters c
            JOIN guild_member gm ON gm.guid = c.guid
            JOIN guild g ON g.guildid = gm.guildid
            LEFT JOIN guild_rank gr ON gr.guildid = gm.guildid AND gr.rid = gm.rank
            WHERE c.guid = ? AND c.account = ?
            LIMIT 1
        ");
        $stmt->execute([$charGuid, $accountId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null; // not in a guild or wrong account
        }

        $isLeader   = ((int)$row['leaderguid'] === $charGuid) || ((int)$row['rank'] === 0);
        $canRecruit = $isLeader || (((int)$row['rank_rights'] & GUILD_RIGHT_INVITE) !== 0);

        if (!$canRecruit) {
            return null;
        }

        return [
            'guildid'   => (int)$row['guildid'],
            'name'      => (string)$row['guild_name'],
            'rank'      => (int)$row['rank'],
            'is_leader' => $isLeader,
        ];
    } catch (Throwable $e) {
        error_log('[forum.func] get_char_recruitment_guild failed: ' . $e->getMessage());
        return null;
    }
}

// Returns the topic_id of an existing active recruitment thread for $guildId
// in $forumId, or null if none exists. Pass $excludeTopicId to ignore a
// specific topic (e.g., when editing the thread itself).
function find_active_recruitment_thread(int $realmId, int $forumId, int $guildId, int $excludeTopicId = 0): ?int {
    try {
        $pdo  = spp_get_pdo('realmd', $realmId);
        $stmt = $pdo->prepare("
            SELECT topic_id FROM f_topics
            WHERE forum_id = ? AND guild_id = ? AND recruitment_status = 'active'
              AND (? = 0 OR topic_id <> ?)
            LIMIT 1
        ");
        $stmt->execute([$forumId, $guildId, $excludeTopicId, $excludeTopicId]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    } catch (Throwable $e) {
        error_log('[forum.func] find_active_recruitment_thread failed: ' . $e->getMessage());
        return null;
    }
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

    return false;
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

function get_forum_staff_avatar_data_uri()
{
    static $uri = null;
    if ($uri !== null) {
        return $uri;
    }

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#2b1a06"/>
      <stop offset="100%" stop-color="#6f4a12"/>
    </linearGradient>
    <linearGradient id="trim" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#ffd57c"/>
      <stop offset="100%" stop-color="#b7862c"/>
    </linearGradient>
  </defs>
  <rect x="4" y="4" width="88" height="88" rx="14" fill="url(#bg)" stroke="url(#trim)" stroke-width="4"/>
  <circle cx="48" cy="36" r="15" fill="#e8d0a0"/>
  <path d="M26 73c4-13 15-21 22-21s18 8 22 21" fill="#1e2f3e"/>
  <path d="M48 20l4 8 9 1-7 6 2 9-8-5-8 5 2-9-7-6 9-1z" fill="#ffd57c"/>
  <text x="48" y="85" text-anchor="middle" font-family="Trebuchet MS, Arial, sans-serif" font-size="14" font-weight="bold" fill="#ffd57c">SPP</text>
</svg>
SVG;

    $uri = 'data:image/svg+xml;base64,' . base64_encode($svg);
    return $uri;
}

function get_forum_avatar_fallback($posterName = '')
{
    $normalized = strtolower(trim((string)$posterName));
    if ($normalized === 'web team' || $normalized === 'spp team') {
        return get_forum_staff_avatar_data_uri();
    }

    return '/templates/offlike/images/forum/icons/lock-icon.gif';
}

$yesterday_ts = mktime(0, 0, 0, date("m")  , date("d")-1, date("Y"));

?>
