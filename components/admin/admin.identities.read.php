<?php
if (INCLUDED !== true) {
    exit;
}

if (!function_exists('spp_admin_identity_health_collect_invalid_selected_accounts')) {
    function spp_admin_identity_health_collect_invalid_selected_accounts(PDO $masterPdo, $selectedRealmId, PDO $charsPdo = null) {
        $selectedRealmId = (int)$selectedRealmId;
        if ($selectedRealmId <= 0 || $charsPdo === null) {
            return array();
        }

        $tableName = spp_account_profile_table_name();
        if (!spp_admin_identity_health_table_exists($masterPdo, $tableName)
            || !spp_admin_identity_health_column_exists($masterPdo, $tableName, 'account_id')
            || !spp_admin_identity_health_column_exists($masterPdo, $tableName, 'character_id')) {
            return array();
        }

        $hasRealmColumn = spp_admin_identity_health_column_exists($masterPdo, $tableName, 'character_realm_id');
        try {
            $sql = "SELECT `account_id`, `character_id`";
            if ($hasRealmColumn) {
                $sql .= ", `character_realm_id`";
            }
            $sql .= " FROM `" . $tableName . "` WHERE `character_id` IS NOT NULL AND `character_id` > 0";
            $stmt = $masterPdo->prepare($sql . ($hasRealmColumn ? " AND `character_realm_id` = ?" : ""));
            $stmt->execute($hasRealmColumn ? array($selectedRealmId) : array());
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            spp_admin_identity_health_log('Invalid selected-character preview failed: ' . $e->getMessage());
            return array();
        }

        $selectedCharacterIds = array();
        foreach ($rows as $row) {
            $characterId = (int)($row['character_id'] ?? 0);
            if ($characterId > 0) {
                $selectedCharacterIds[$characterId] = true;
            }
        }
        if (empty($selectedCharacterIds)) {
            return array();
        }

        $existingCharacterIds = spp_admin_identity_health_chunked_existing_ids(
            $charsPdo,
            'characters',
            'guid',
            $selectedCharacterIds,
            '',
            array(),
            'Invalid selected-character lookup failed'
        );

        $invalidAccountIds = array();
        foreach ($rows as $row) {
            $accountId = (int)($row['account_id'] ?? 0);
            $characterId = (int)($row['character_id'] ?? 0);
            if ($accountId > 0 && $characterId > 0 && !isset($existingCharacterIds[$characterId])) {
                $invalidAccountIds[$accountId] = true;
            }
        }
        return $invalidAccountIds;
    }
}

if (!function_exists('spp_admin_identity_health_collect_missing_account_rows')) {
    function spp_admin_identity_health_collect_missing_account_rows(PDO $masterPdo, array $realmPdos) {
        if (!spp_admin_identity_health_table_exists($masterPdo, 'website_accounts')) {
            return array();
        }

        $websiteAccountIds = spp_admin_identity_health_fetch_int_column(
            $masterPdo,
            "SELECT `account_id` FROM `website_accounts` WHERE `account_id` > 0",
            array(),
            'Website account row preview failed'
        );
        if (empty($websiteAccountIds)) {
            return array();
        }

        $existingAccountIds = array();
        foreach ($realmPdos as $realmId => $realmPdo) {
            if (!$realmPdo instanceof PDO || !spp_admin_identity_health_table_exists($realmPdo, 'account')) {
                continue;
            }
            $existingAccountIds += spp_admin_identity_health_chunked_existing_ids(
                $realmPdo,
                'account',
                'id',
                $websiteAccountIds,
                '',
                array(),
                'Realm account row preview failed for realm ' . (int)$realmId
            );
        }

        $missingRows = array();
        foreach ($websiteAccountIds as $accountId => $trueValue) {
            if (!isset($existingAccountIds[(int)$accountId])) {
                $missingRows[(int)$accountId] = true;
            }
        }
        return $missingRows;
    }
}

if (!function_exists('spp_admin_identity_health_collect_website_only_accounts')) {
    function spp_admin_identity_health_collect_website_only_accounts(PDO $masterPdo, array $accessibleCharsPdos) {
        if (!spp_admin_identity_health_table_exists($masterPdo, 'website_accounts')) {
            return array();
        }

        $websiteAccounts = array();
        try {
            $sql = "SELECT wa.`account_id`, COALESCE(a.`gmlevel`, 0) AS gmlevel
                    FROM `website_accounts` wa
                    LEFT JOIN `account` a ON a.`id` = wa.`account_id`
                    WHERE wa.`account_id` > 0";
            foreach ($masterPdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $accountId = (int)($row['account_id'] ?? 0);
                if ($accountId <= 0) {
                    continue;
                }
                if ((int)($row['gmlevel'] ?? 0) >= 4) {
                    continue;
                }
                $websiteAccounts[$accountId] = true;
            }
        } catch (Throwable $e) {
            spp_admin_identity_health_log('Website account orphan preview failed: ' . $e->getMessage());
            return array();
        }

        if (empty($websiteAccounts)) {
            return array();
        }

        $accountsWithCharacters = array();
        foreach ($accessibleCharsPdos as $realmId => $charsPdo) {
            if (!$charsPdo instanceof PDO || !spp_admin_identity_health_table_exists($charsPdo, 'characters')) {
                continue;
            }
            $accountsWithCharacters += spp_admin_identity_health_fetch_int_column(
                $charsPdo,
                "SELECT DISTINCT `account` FROM `characters` WHERE `account` > 0",
                array(),
                'Character ownership preview failed for realm ' . (int)$realmId
            );
        }

        $orphans = array();
        foreach ($websiteAccounts as $accountId => $trueValue) {
            if (!isset($accountsWithCharacters[(int)$accountId])) {
                $orphans[(int)$accountId] = true;
            }
        }
        return $orphans;
    }
}

if (!function_exists('spp_admin_identity_health_selected_realm_coverage_gap')) {
    function spp_admin_identity_health_selected_realm_coverage_gap(PDO $masterPdo, PDO $realmPdo = null, PDO $charsPdo = null, $selectedRealmId = 0, array $coverageRow = array()) {
        $selectedRealmId = (int)$selectedRealmId;
        $gaps = array(
            'accounts_without_identity' => 0,
            'characters_without_identity' => 0,
            'posts_without_identity' => 0,
            'topics_without_identity' => 0,
            'pms_without_identity' => 0,
        );

        if ($selectedRealmId <= 0 || $realmPdo === null) {
            return $gaps;
        }

        if (spp_admin_identity_health_table_exists($masterPdo, 'website_identities')
            && spp_admin_identity_health_table_exists($realmPdo, 'account')) {
            $realmAccountIds = spp_admin_identity_health_fetch_int_column($realmPdo, "SELECT `id` FROM `account` WHERE `id` > 0");
            $identityAccountIds = spp_admin_identity_health_fetch_int_column(
                $masterPdo,
                "SELECT `owner_account_id` FROM `website_identities`
                 WHERE `realm_id` = ? AND `identity_type` = 'account' AND `owner_account_id` IS NOT NULL",
                array($selectedRealmId)
            );
            $gaps['accounts_without_identity'] = spp_admin_identity_health_count_missing_from_set($realmAccountIds, $identityAccountIds);
        }

        if (spp_admin_identity_health_table_exists($masterPdo, 'website_identities')
            && $charsPdo !== null
            && spp_admin_identity_health_table_exists($charsPdo, 'characters')) {
            $realmCharacterIds = spp_admin_identity_health_fetch_int_column($charsPdo, "SELECT `guid` FROM `characters` WHERE `guid` > 0");
            $identityCharacterIds = spp_admin_identity_health_fetch_int_column(
                $masterPdo,
                "SELECT `character_guid` FROM `website_identities`
                 WHERE `realm_id` = ? AND `character_guid` IS NOT NULL
                   AND `identity_type` IN ('character','bot_character')",
                array($selectedRealmId)
            );
            $gaps['characters_without_identity'] = spp_admin_identity_health_count_missing_from_set($realmCharacterIds, $identityCharacterIds);
        }

        if ($realmPdo !== null && spp_admin_identity_health_table_exists($realmPdo, 'f_posts')
            && spp_admin_identity_health_column_exists($realmPdo, 'f_posts', 'poster_character_id')
            && spp_admin_identity_health_column_exists($realmPdo, 'f_posts', 'poster_identity_id')) {
            $gaps['posts_without_identity'] = spp_admin_identity_health_scalar(
                $realmPdo,
                "SELECT COUNT(*) FROM `f_posts`
                 WHERE `poster_character_id` IS NOT NULL
                   AND `poster_character_id` > 0
                   AND (`poster_identity_id` IS NULL OR `poster_identity_id` = 0)"
            );
        }

        if ($realmPdo !== null && spp_admin_identity_health_table_exists($realmPdo, 'f_topics')
            && spp_admin_identity_health_table_exists($realmPdo, 'f_posts')
            && spp_admin_identity_health_column_exists($realmPdo, 'f_topics', 'topic_poster_identity_id')
            && spp_admin_identity_health_column_exists($realmPdo, 'f_posts', 'poster_character_id')) {
            $gaps['topics_without_identity'] = spp_admin_identity_health_scalar(
                $realmPdo,
                "SELECT COUNT(*)
                 FROM `f_topics` t
                 JOIN `f_posts` p ON p.`post_id` = (
                     SELECT MIN(`post_id`) FROM `f_posts` WHERE `topic_id` = t.`topic_id`
                 )
                 WHERE (t.`topic_poster_identity_id` IS NULL OR t.`topic_poster_identity_id` = 0)
                   AND p.`poster_character_id` IS NOT NULL
                   AND p.`poster_character_id` > 0"
            );
        }

        if ($realmPdo !== null && spp_admin_identity_health_table_exists($realmPdo, 'website_pms')
            && spp_admin_identity_health_column_exists($realmPdo, 'website_pms', 'sender_identity_id')
            && spp_admin_identity_health_column_exists($realmPdo, 'website_pms', 'recipient_identity_id')) {
            $gaps['pms_without_identity'] = spp_admin_identity_health_scalar(
                $realmPdo,
                "SELECT COUNT(*)
                 FROM `website_pms`
                 WHERE `sender_identity_id` IS NULL
                    OR `sender_identity_id` = 0
                    OR `recipient_identity_id` IS NULL
                    OR `recipient_identity_id` = 0"
            );
        }

        return $gaps;
    }
}

if (!function_exists('spp_admin_identity_health_coverage_row')) {
    function spp_admin_identity_health_coverage_row(PDO $masterPdo, array $realmRow, string $siteRoot) {
        $realmId = (int)($realmRow['id'] ?? 0);
        $realmName = (string)($realmRow['name'] ?? ('Realm ' . $realmId));

        $row = array(
            'realm_id' => $realmId,
            'realm_name' => $realmName,
            'available' => false,
            'skip_reason' => '',
            'health' => 'ok',
            'account_identities' => 0,
            'character_identities' => 0,
            'bot_identities' => 0,
            'posts_total' => 0,
            'posts_covered' => 0,
            'topics_total' => 0,
            'topics_covered' => 0,
            'pms_total' => 0,
            'pms_covered' => 0,
            'commands' => [
                'identities' => spp_admin_identity_health_build_command($siteRoot . '/tools/backfill_identities.php', ['--realm=' . $realmId]),
                'posts' => spp_admin_identity_health_build_command($siteRoot . '/tools/backfill_post_identities.php', ['--realm=' . $realmId]),
                'pms' => spp_admin_identity_health_build_command($siteRoot . '/tools/backfill_pm_identities.php', ['--realm=' . $realmId]),
            ],
        );

        try {
            $realmPdo = spp_get_pdo('realmd', $realmId);
        } catch (Throwable $e) {
            $row['skip_reason'] = $e->getMessage();
            $row['health'] = 'error';
            spp_admin_identity_health_log('Skipping unavailable realm ' . $realmId . ': ' . $e->getMessage());
            return $row;
        }

        $row['available'] = true;
        if (spp_admin_identity_health_table_exists($masterPdo, 'website_identities')) {
            $row['account_identities'] = spp_admin_identity_health_scalar($masterPdo, "SELECT COUNT(*) FROM `website_identities` WHERE `realm_id` = ? AND `identity_type` = 'account'", array($realmId));
            $row['character_identities'] = spp_admin_identity_health_scalar($masterPdo, "SELECT COUNT(*) FROM `website_identities` WHERE `realm_id` = ? AND `identity_type` = 'character'", array($realmId));
            $row['bot_identities'] = spp_admin_identity_health_scalar($masterPdo, "SELECT COUNT(*) FROM `website_identities` WHERE `realm_id` = ? AND `identity_type` = 'bot_character'", array($realmId));
        }

        if (spp_admin_identity_health_table_exists($realmPdo, 'f_posts')) {
            $row['posts_total'] = spp_admin_identity_health_scalar($realmPdo, "SELECT COUNT(*) FROM `f_posts`");
            if (spp_admin_identity_health_column_exists($realmPdo, 'f_posts', 'poster_identity_id')) {
                $row['posts_covered'] = spp_admin_identity_health_scalar($realmPdo, "SELECT COUNT(*) FROM `f_posts` WHERE `poster_identity_id` IS NOT NULL AND `poster_identity_id` > 0");
            }
        }

        if (spp_admin_identity_health_table_exists($realmPdo, 'f_topics')) {
            $row['topics_total'] = spp_admin_identity_health_scalar($realmPdo, "SELECT COUNT(*) FROM `f_topics`");
            if (spp_admin_identity_health_column_exists($realmPdo, 'f_topics', 'topic_poster_identity_id')) {
                $row['topics_covered'] = spp_admin_identity_health_scalar($realmPdo, "SELECT COUNT(*) FROM `f_topics` WHERE `topic_poster_identity_id` IS NOT NULL AND `topic_poster_identity_id` > 0");
            }
        }

        if (spp_admin_identity_health_table_exists($realmPdo, 'website_pms')) {
            $row['pms_total'] = spp_admin_identity_health_scalar($realmPdo, "SELECT COUNT(*) FROM `website_pms`");
            if (spp_admin_identity_health_column_exists($realmPdo, 'website_pms', 'sender_identity_id')
                && spp_admin_identity_health_column_exists($realmPdo, 'website_pms', 'recipient_identity_id')) {
                $row['pms_covered'] = spp_admin_identity_health_scalar(
                    $realmPdo,
                    "SELECT COUNT(*) FROM `website_pms`
                     WHERE `sender_identity_id` IS NOT NULL AND `sender_identity_id` > 0
                       AND `recipient_identity_id` IS NOT NULL AND `recipient_identity_id` > 0"
                );
            }
        }

        $missingTotal =
            max(0, $row['posts_total'] - $row['posts_covered']) +
            max(0, $row['topics_total'] - $row['topics_covered']) +
            max(0, $row['pms_total'] - $row['pms_covered']);
        if ($missingTotal > 0) {
            $row['health'] = 'attention';
        }

        return $row;
    }
}

if (!function_exists('spp_admin_identity_health_build_view')) {
    function spp_admin_identity_health_build_view(PDO $masterPdo, $selectedRealmId, string $siteRoot, array $backfillState = array()) {
        $realmRows = spp_admin_identity_health_realm_rows($masterPdo);
        $realmOptions = array();
        foreach ($realmRows as $realmRow) {
            $realmOptions[(int)$realmRow['id']] = (string)$realmRow['name'];
        }

        $defaultRealmId = (int)($GLOBALS['activeRealmId'] ?? spp_resolve_realm_id($GLOBALS['realmDbMap'] ?? array(), 1));
        $selectedRealmId = spp_admin_identity_health_resolve_realm_id($realmOptions, $selectedRealmId, $defaultRealmId);

        $coverageRows = array();
        $coverageByRealm = array();
        $skippedRealms = array();
        foreach ($realmRows as $realmRow) {
            $coverageRow = spp_admin_identity_health_coverage_row($masterPdo, $realmRow, $siteRoot);
            $coverageRows[] = $coverageRow;
            $coverageByRealm[(int)$coverageRow['realm_id']] = $coverageRow;
            if (!$coverageRow['available']) {
                $skippedRealms[] = array(
                    'realm_id' => (int)$coverageRow['realm_id'],
                    'realm_name' => (string)$coverageRow['realm_name'],
                    'reason' => (string)$coverageRow['skip_reason'],
                );
            }
        }

        $selectedCoverage = $coverageByRealm[$selectedRealmId] ?? array(
            'realm_id' => $selectedRealmId,
            'realm_name' => $realmOptions[$selectedRealmId] ?? ('Realm ' . $selectedRealmId),
            'posts_total' => 0,
            'posts_covered' => 0,
            'topics_total' => 0,
            'topics_covered' => 0,
            'pms_total' => 0,
            'pms_covered' => 0,
            'account_identities' => 0,
            'character_identities' => 0,
            'bot_identities' => 0,
            'commands' => array(),
        );

        $accessibleRealmPdos = array();
        $accessibleCharsPdos = array();
        foreach ($realmRows as $realmRow) {
            $realmId = (int)($realmRow['id'] ?? 0);
            if ($realmId <= 0) {
                continue;
            }
            try {
                $accessibleRealmPdos[$realmId] = spp_get_pdo('realmd', $realmId);
            } catch (Throwable $e) {
                $accessibleRealmPdos[$realmId] = null;
            }
            try {
                $accessibleCharsPdos[$realmId] = spp_get_pdo('chars', $realmId);
            } catch (Throwable $e) {
                $accessibleCharsPdos[$realmId] = null;
            }
        }

        $selectedRealmPdo = $accessibleRealmPdos[$selectedRealmId] ?? null;
        $selectedCharsPdo = $accessibleCharsPdos[$selectedRealmId] ?? null;

        $invalidSelectedAccounts = spp_admin_identity_health_collect_invalid_selected_accounts($masterPdo, $selectedRealmId, $selectedCharsPdo instanceof PDO ? $selectedCharsPdo : null);
        $missingAccountRows = spp_admin_identity_health_collect_missing_account_rows($masterPdo, $accessibleRealmPdos);
        $websiteOnlyAccounts = spp_admin_identity_health_collect_website_only_accounts($masterPdo, $accessibleCharsPdos);
        $coverageGap = spp_admin_identity_health_selected_realm_coverage_gap($masterPdo, $selectedRealmPdo instanceof PDO ? $selectedRealmPdo : null, $selectedCharsPdo instanceof PDO ? $selectedCharsPdo : null, $selectedRealmId, $selectedCoverage);

        $forumReset = array(
            'posts' => (int)($selectedCoverage['posts_total'] ?? 0),
            'topics' => (int)($selectedCoverage['topics_total'] ?? 0),
            'pms' => (int)($selectedCoverage['pms_total'] ?? 0),
            'identities' => 0,
            'identity_profiles' => 0,
        );
        if (spp_admin_identity_health_table_exists($masterPdo, 'website_identities')) {
            $forumReset['identities'] = spp_admin_identity_health_scalar($masterPdo, "SELECT COUNT(*) FROM `website_identities` WHERE `realm_id` = ?", array($selectedRealmId));
        }
        if (spp_admin_identity_health_table_exists($masterPdo, spp_identity_profile_table_name())
            && spp_admin_identity_health_table_exists($masterPdo, 'website_identities')) {
            $forumReset['identity_profiles'] = spp_admin_identity_health_scalar(
                $masterPdo,
                "SELECT COUNT(*) FROM `" . spp_identity_profile_table_name() . "` p
                 INNER JOIN `website_identities` i ON i.`identity_id` = p.`identity_id`
                 WHERE i.`realm_id` = ?",
                array($selectedRealmId)
            );
        }

        $botCleanup = array('accounts' => 0, 'characters' => 0, 'identities' => 0, 'signatures' => 0);
        if ($selectedRealmPdo instanceof PDO && spp_admin_identity_health_table_exists($selectedRealmPdo, 'account')) {
            $botCleanup['accounts'] = spp_admin_identity_health_scalar($selectedRealmPdo, "SELECT COUNT(*) FROM `account` WHERE LOWER(`username`) LIKE 'rndbot%'");
            $botAccountIds = spp_admin_identity_health_fetch_int_column($selectedRealmPdo, "SELECT `id` FROM `account` WHERE LOWER(`username`) LIKE 'rndbot%'");
            if (!empty($botAccountIds) && $selectedCharsPdo instanceof PDO && spp_admin_identity_health_table_exists($selectedCharsPdo, 'characters')) {
                $botCleanup['characters'] = spp_admin_identity_health_scalar($selectedCharsPdo, "SELECT COUNT(*) FROM `characters` WHERE `account` IN (" . implode(',', array_map('intval', array_keys($botAccountIds))) . ")");
            }
        }
        if (spp_admin_identity_health_table_exists($masterPdo, 'website_identities')) {
            $botCleanup['identities'] = spp_admin_identity_health_scalar($masterPdo, "SELECT COUNT(*) FROM `website_identities` WHERE `realm_id` = ? AND (`identity_type` = 'bot_character' OR `is_bot` = 1)", array($selectedRealmId));
        }
        if (spp_admin_identity_health_table_exists($masterPdo, spp_identity_profile_table_name())
            && spp_admin_identity_health_table_exists($masterPdo, 'website_identities')) {
            $botCleanup['signatures'] = spp_admin_identity_health_scalar(
                $masterPdo,
                "SELECT COUNT(*) FROM `" . spp_identity_profile_table_name() . "` p
                 INNER JOIN `website_identities` i ON i.`identity_id` = p.`identity_id`
                 WHERE i.`realm_id` = ? AND (i.`identity_type` = 'bot_character' OR i.`is_bot` = 1)",
                array($selectedRealmId)
            );
        }

        $realmReset = array('characters' => 0, 'guilds' => 0, 'items' => 0, 'mail' => 0, 'auctions' => 0);
        if ($selectedCharsPdo instanceof PDO) {
            if (spp_admin_identity_health_table_exists($selectedCharsPdo, 'characters')) {
                $realmReset['characters'] = spp_admin_identity_health_scalar($selectedCharsPdo, "SELECT COUNT(*) FROM `characters`");
            }
            if (spp_admin_identity_health_table_exists($selectedCharsPdo, 'guild')) {
                $realmReset['guilds'] = spp_admin_identity_health_scalar($selectedCharsPdo, "SELECT COUNT(*) FROM `guild`");
            }
            if (spp_admin_identity_health_table_exists($selectedCharsPdo, 'item_instance')) {
                $realmReset['items'] = spp_admin_identity_health_scalar($selectedCharsPdo, "SELECT COUNT(*) FROM `item_instance`");
            }
            if (spp_admin_identity_health_table_exists($selectedCharsPdo, 'mail')) {
                $realmReset['mail'] = spp_admin_identity_health_scalar($selectedCharsPdo, "SELECT COUNT(*) FROM `mail`");
            }
            if (spp_admin_identity_health_table_exists($selectedCharsPdo, 'auction')) {
                $realmReset['auctions'] = spp_admin_identity_health_scalar($selectedCharsPdo, "SELECT COUNT(*) FROM `auction`");
            }
        }

        return array(
            'page_title' => 'Identity & Data Health',
            'canonical_url' => 'index.php?n=admin&sub=identities',
            'legacy_url' => 'index.php?n=admin&sub=cleanup',
            'realm_options' => $realmOptions,
            'selected_realm_id' => $selectedRealmId,
            'selected_realm_name' => $realmOptions[$selectedRealmId] ?? ('Realm ' . $selectedRealmId),
            'coverage_rows' => $coverageRows,
            'coverage_selected' => $selectedCoverage,
            'skipped_realms' => $skippedRealms,
            'coverage_summary' => array(
                'available_realms' => count($coverageRows) - count($skippedRealms),
                'skipped_realms' => count($skippedRealms),
                'total_account_identities' => array_sum(array_map(function ($row) { return (int)$row['account_identities']; }, $coverageRows)),
                'total_character_identities' => array_sum(array_map(function ($row) { return (int)$row['character_identities']; }, $coverageRows)),
                'total_bot_identities' => array_sum(array_map(function ($row) { return (int)$row['bot_identities']; }, $coverageRows)),
            ),
            'mismatches' => array(
                'invalid_selected_character' => count($invalidSelectedAccounts),
                'missing_account_rows' => count($missingAccountRows),
                'website_only_accounts' => count($websiteOnlyAccounts),
                'accounts_without_identity' => (int)$coverageGap['accounts_without_identity'],
                'characters_without_identity' => (int)$coverageGap['characters_without_identity'],
                'posts_without_identity' => (int)$coverageGap['posts_without_identity'],
                'topics_without_identity' => (int)$coverageGap['topics_without_identity'],
                'pms_without_identity' => (int)$coverageGap['pms_without_identity'],
            ),
            'reset_preview' => array(
                'forum' => $forumReset,
                'bots' => $botCleanup,
                'realm' => $realmReset,
            ),
            'backfill' => array(
                'output' => (string)($backfillState['identityOutput'] ?? ''),
                'error' => (string)($backfillState['identityError'] ?? ''),
                'notice' => (string)($backfillState['identityNotice'] ?? ''),
                'command' => (string)($backfillState['identityCommand'] ?? ''),
            ),
        );
    }
}
