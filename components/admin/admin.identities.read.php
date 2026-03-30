<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_identities_should_skip_realm_error(Throwable $e): bool
{
    $message = strtolower(trim((string)$e->getMessage()));
    if ($message === '') {
        return false;
    }

    $skipFragments = array(
        'access denied for user',
        'unknown database',
        'sqlstate[hy000] [1044]',
        'sqlstate[hy000] [1049]',
    );

    foreach ($skipFragments as $fragment) {
        if (strpos($message, $fragment) !== false) {
            return true;
        }
    }

    return false;
}

function spp_admin_identities_build_view(array $realmDbMap): array
{
    $masterPdo = spp_get_pdo('realmd', 1);
    $phpBinary = spp_admin_identities_resolve_php_cli_binary();

    $view = [
        'rows' => [],
        'totals' => [
            'missing_account_identities' => 0,
            'missing_character_identities' => 0,
            'posts_missing_identity' => 0,
            'topics_missing_identity' => 0,
            'pms_missing_identity' => 0,
        ],
        'phpBinary' => $phpBinary,
        'errors' => [],
    ];

    foreach ($realmDbMap as $realmId => $realmInfo) {
        $realmId = (int)$realmId;
        $row = [
            'realm_id' => $realmId,
            'realm_name' => 'Realm ' . $realmId,
            'missing_account_identities' => 0,
            'missing_character_identities' => 0,
            'posts_missing_identity' => 0,
            'topics_missing_identity' => 0,
            'pms_missing_identity' => 0,
            'health' => 'ok',
            'error' => '',
            'commands' => [
                'identities' => spp_admin_identities_build_command($_SERVER['DOCUMENT_ROOT'] . '/tools/backfill_identities.php', ['--realm=' . $realmId]),
                'posts' => spp_admin_identities_build_command($_SERVER['DOCUMENT_ROOT'] . '/tools/backfill_post_identities.php', ['--realm=' . $realmId]),
                'pms' => spp_admin_identities_build_command($_SERVER['DOCUMENT_ROOT'] . '/tools/backfill_pm_identities.php', ['--realm=' . $realmId]),
            ],
        ];

        try {
            $realmPdo = spp_get_pdo('realmd', $realmId);
            $charPdo = spp_get_pdo('chars', $realmId);

            try {
                $stmtRealmName = $realmPdo->prepare("SELECT name FROM realmlist WHERE id = ? LIMIT 1");
                $stmtRealmName->execute([$realmId]);
                $realmName = $stmtRealmName->fetchColumn();
                if (!empty($realmName)) {
                    $row['realm_name'] = (string)$realmName;
                }
            } catch (Throwable $e) {
                error_log('[admin.identities] Realm name lookup failed: ' . $e->getMessage());
            }

            $stmtMissingAccounts = $realmPdo->prepare("
                SELECT COUNT(*)
                FROM `account` a
                LEFT JOIN `website_identities` wi
                  ON wi.identity_key = CONCAT('account:', ?, ':', a.id)
                WHERE wi.identity_id IS NULL
            ");
            $stmtMissingAccounts->execute([$realmId]);
            $row['missing_account_identities'] = (int)$stmtMissingAccounts->fetchColumn();

            $identityCharGuids = [];
            $stmtCharacterIdentities = $masterPdo->prepare("
                SELECT character_guid
                FROM `website_identities`
                WHERE realm_id = ?
                  AND identity_type IN ('character', 'bot_character')
                  AND character_guid IS NOT NULL
            ");
            $stmtCharacterIdentities->execute([$realmId]);
            foreach ($stmtCharacterIdentities->fetchAll(PDO::FETCH_COLUMN, 0) as $guid) {
                $identityCharGuids[(int)$guid] = true;
            }

            $stmtCharacterGuids = $charPdo->query("SELECT guid FROM `characters`");
            $missingCharacterCount = 0;
            foreach ($stmtCharacterGuids->fetchAll(PDO::FETCH_COLUMN, 0) as $guid) {
                if (empty($identityCharGuids[(int)$guid])) {
                    $missingCharacterCount++;
                }
            }
            $row['missing_character_identities'] = $missingCharacterCount;

            $stmtPosts = $realmPdo->query("
                SELECT COUNT(*)
                FROM `f_posts`
                WHERE poster_character_id IS NOT NULL
                  AND poster_character_id > 0
                  AND (poster_identity_id IS NULL OR poster_identity_id = 0)
            ");
            $row['posts_missing_identity'] = (int)$stmtPosts->fetchColumn();

            $stmtTopics = $realmPdo->query("
                SELECT COUNT(*)
                FROM `f_topics` t
                JOIN `f_posts` p ON p.post_id = (
                    SELECT MIN(post_id) FROM `f_posts` WHERE topic_id = t.topic_id
                )
                WHERE (t.topic_poster_identity_id IS NULL OR t.topic_poster_identity_id = 0)
                  AND p.poster_character_id IS NOT NULL
                  AND p.poster_character_id > 0
            ");
            $row['topics_missing_identity'] = (int)$stmtTopics->fetchColumn();

            $stmtPms = $realmPdo->query("
                SELECT COUNT(*)
                FROM `website_pms`
                WHERE sender_identity_id IS NULL
                   OR recipient_identity_id IS NULL
            ");
            $row['pms_missing_identity'] = (int)$stmtPms->fetchColumn();

            $missingTotal =
                $row['missing_account_identities'] +
                $row['missing_character_identities'] +
                $row['posts_missing_identity'] +
                $row['topics_missing_identity'] +
                $row['pms_missing_identity'];

            if ($missingTotal > 0) {
                $row['health'] = 'attention';
            }
        } catch (Throwable $e) {
            if (spp_admin_identities_should_skip_realm_error($e)) {
                error_log('[admin.identities] Skipping unavailable realm ' . $realmId . ': ' . $e->getMessage());
                continue;
            }

            $row['health'] = 'error';
            $row['error'] = $e->getMessage();
            $view['errors'][] = 'Realm ' . $realmId . ': ' . $e->getMessage();
            error_log('[admin.identities] Coverage scan failed for realm ' . $realmId . ': ' . $e->getMessage());
        }

        $view['totals']['missing_account_identities'] += (int)$row['missing_account_identities'];
        $view['totals']['missing_character_identities'] += (int)$row['missing_character_identities'];
        $view['totals']['posts_missing_identity'] += (int)$row['posts_missing_identity'];
        $view['totals']['topics_missing_identity'] += (int)$row['topics_missing_identity'];
        $view['totals']['pms_missing_identity'] += (int)$row['pms_missing_identity'];
        $view['rows'][] = $row;
    }

    return $view;
}
