<?php

function spp_account_view_build_profile(array $profile, int $uid, array $currentUser, array $realmDbMap): array
{
    $viewPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
    $stmtExt = $viewPdo->prepare("SELECT avatar, signature FROM website_accounts WHERE account_id=? LIMIT 1");
    $stmtExt->execute([(int)$uid]);
    $profileExtend = $stmtExt->fetch(PDO::FETCH_ASSOC);

    if (!empty($profileExtend['avatar'])) {
        $profile['avatar'] = $profileExtend['avatar'];
    }
    if (!empty($profileExtend['signature'])) {
        $profile['signature'] = $profileExtend['signature'];
    }

    $profile['avatar_fallback_url'] = '';
    if (empty($profile['avatar'])) {
        $profile['avatar_fallback_url'] = spp_account_view_avatar_fallback_url($profile, $realmDbMap);
    }

    $expansionMap = array(
        0 => 'Classic',
        1 => 'TBC',
        2 => 'WotLK',
    );
    $profile['expansion_label'] = $expansionMap[(int)($profile['expansion'] ?? 0)] ?? 'Classic';
    $profile['is_own_profile'] = ((int)($currentUser['id'] ?? 0) === (int)$uid);
    $profile['forum_posts'] = 0;
    $profile['total_played_seconds'] = 0;
    $profile['total_played_label'] = '0m';
    $profile['character_count'] = 0;
    $profile['grouped_characters'] = array(
        'Classic' => array(),
        'TBC' => array(),
        'WotLK' => array(),
    );
    $profile['selected_forum_character'] = array();
    $profile['is_human_account'] = (stripos((string)($profile['username'] ?? ''), 'rndbot') !== 0);
    $profileRealmId = null;
    $profile['character_summary'] = array();

    try {
        $stmtForumPosts = $viewPdo->prepare("SELECT COUNT(*) FROM f_posts WHERE poster_id = ?");
        $stmtForumPosts->execute([(int)$uid]);
        $profile['forum_posts'] = (int)$stmtForumPosts->fetchColumn();
    } catch (Throwable $e) {
        error_log('[account.view.read] Forum post count lookup failed: ' . $e->getMessage());
    }

    if (!empty($profile['character_id'])) {
        foreach ($realmDbMap as $realmId => $realmInfo) {
            try {
                $charPdo = spp_get_pdo('chars', (int)$realmId);
                $stmtChar = $charPdo->prepare(
                    "SELECT c.name, c.level, g.name AS guild_name
                     FROM characters c
                     LEFT JOIN guild_member gm ON c.guid = gm.guid
                     LEFT JOIN guild g ON gm.guildid = g.guildid
                     WHERE c.guid = ? LIMIT 1"
                );
                $stmtChar->execute([(int)$profile['character_id']]);
                $charInfo = $stmtChar->fetch(PDO::FETCH_ASSOC);

                if ($charInfo) {
                    $profileRealmId = (int)$realmId;
                    $realmLabel = 'Realm ' . $realmId;
                    try {
                        $realmPdo = spp_get_pdo('realmd', (int)$realmId);
                        $stmtRealmName = $realmPdo->prepare("SELECT name FROM realmlist WHERE id = ? LIMIT 1");
                        $stmtRealmName->execute([(int)$realmId]);
                        $realmName = $stmtRealmName->fetchColumn();
                        if (!empty($realmName)) {
                            $realmLabel = (string)$realmName;
                        }
                    } catch (Throwable $e) {
                        error_log('[account.view.read] Realm name lookup failed: ' . $e->getMessage());
                    }

                    $profile['character_summary'] = array(
                        'name' => $charInfo['name'],
                        'level' => $charInfo['level'],
                        'guild' => $charInfo['guild_name'],
                        'realm' => $realmLabel,
                    );
                    break;
                }
            } catch (Throwable $e) {
                error_log('[account.view.read] Character lookup failed: ' . $e->getMessage());
            }
        }
    }

    if ($profileRealmId === null) {
        $profileRealmId = (int)($_COOKIE['cur_selected_realmd'] ?? $_COOKIE['cur_selected_realm'] ?? spp_resolve_realm_id($realmDbMap));
        if (!isset($realmDbMap[$profileRealmId])) {
            $profileRealmId = (int)spp_resolve_realm_id($realmDbMap);
        }
    }

    if (!empty($profile['character_id']) && !empty($profileRealmId)) {
        try {
            $characterIdentity = spp_get_char_identity((int)$profileRealmId, (int)$profile['character_id']);
            if (!empty($characterIdentity['identity_id'])) {
                $identitySignature = spp_get_identity_signature((int)$characterIdentity['identity_id']);
                if ($identitySignature !== '') {
                    $profile['signature'] = $identitySignature;
                }
            }
        } catch (Throwable $e) {
            error_log('[account.view.read] Identity signature lookup failed: ' . $e->getMessage());
        }
    }

    try {
        foreach ($realmDbMap as $realmId => $realmInfo) {
            $realmAccountId = null;
            try {
                $realmProfilePdo = spp_account_view_open_named_pdo((string)$realmInfo['realmd']);
                $stmtRealmAccount = $realmProfilePdo->prepare("SELECT id FROM account WHERE username = ? LIMIT 1");
                $stmtRealmAccount->execute([(string)$profile['username']]);
                $realmAccountId = $stmtRealmAccount->fetchColumn();
            } catch (Throwable $e) {
                error_log('[account.view.read] Realm account lookup failed: ' . $e->getMessage());
            }

            if (empty($realmAccountId)) {
                continue;
            }

            $summaryCharPdo = spp_account_view_open_named_pdo((string)$realmInfo['chars']);
            $stmtChars = $summaryCharPdo->prepare("
                SELECT c.guid, c.name, c.level, g.name AS guild_name
                FROM characters c
                LEFT JOIN guild_member gm ON c.guid = gm.guid
                LEFT JOIN guild g ON gm.guildid = g.guildid
                WHERE c.account = ?
                ORDER BY c.level DESC, c.name ASC
            ");
            $stmtChars->execute([(int)$realmAccountId]);
            $realmChars = $stmtChars->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($realmChars)) {
                $realmLabel = $expansionMap[max(0, (int)$realmId - 1)] ?? ('Realm ' . (int)$realmId);
                foreach ($realmChars as $realmChar) {
                    $profile['grouped_characters'][$realmLabel][] = array(
                        'guid' => (int)($realmChar['guid'] ?? 0),
                        'name' => (string)($realmChar['name'] ?? ''),
                        'level' => (int)($realmChar['level'] ?? 0),
                        'guild' => (string)($realmChar['guild_name'] ?? ''),
                    );
                    $profile['character_count']++;
                }
            }

            $stmtPlayed = $summaryCharPdo->prepare("SELECT COALESCE(SUM(totaltime), 0) FROM characters WHERE account = ?");
            $stmtPlayed->execute([(int)$realmAccountId]);
            $profile['total_played_seconds'] += (int)$stmtPlayed->fetchColumn();
        }
    } catch (Throwable $e) {
        error_log('[account.view.read] Summary lookup failed: ' . $e->getMessage());
    }

    $profile['total_played_label'] = spp_format_total_played($profile['total_played_seconds']);

    if (empty($profile['character_summary']) && !empty($profile['character_name'])) {
        $profile['character_summary'] = array(
            'name' => $profile['character_name'],
            'level' => null,
            'guild' => '',
            'realm' => '',
        );
    }

    if (!empty($profile['character_summary'])) {
        $profile['selected_forum_character'] = $profile['character_summary'];
    }

    return $profile;
}
