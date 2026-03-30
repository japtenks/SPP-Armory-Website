<?php

function declension($int, $expressions)
{
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

function spp_realm_to_expansion(int $realmId): string {
    $map = [1 => 'classic', 2 => 'tbc', 3 => 'wotlk'];
    return $map[$realmId] ?? '';
}

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

    return true;
}

define('GUILD_RIGHT_INVITE', 0x10);

function get_char_recruitment_guild(int $realmId, int $charGuid, int $accountId): ?array {
    if ($charGuid <= 0 || $accountId <= 0) {
        return null;
    }

    try {
        $charsPdo = spp_get_pdo('chars', $realmId);
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
            return null;
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
        error_log('[forum.scope] get_char_recruitment_guild failed: ' . $e->getMessage());
        return null;
    }
}

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
        error_log('[forum.scope] find_active_recruitment_thread failed: ' . $e->getMessage());
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
            error_log('[forum.scope] Realm character validation failed: ' . $e->getMessage());
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
