<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_cleanup_build_preview(PDO $cleanupPdo, PDO $cleanupCharsPdo, int $cleanupActiveRealmId)
{
    $cleanupPreview = spp_admin_cleanup_empty_preview(
        $cleanupActiveRealmId,
        spp_admin_cleanup_realm_name($cleanupPdo, $cleanupActiveRealmId)
    );
    $realmDbMap = $GLOBALS['realmDbMap'] ?? array();
    $realmCharsPdos = spp_admin_cleanup_realm_char_pdos(is_array($realmDbMap) ? $realmDbMap : array());

    try {
        $stmtWebsiteAccounts = $cleanupPdo->query("
            SELECT wa.account_id
            FROM website_accounts wa
            JOIN account a ON a.id = wa.account_id
            ORDER BY wa.account_id ASC
        ");
        $websiteAccountIds = $stmtWebsiteAccounts ? array_map('intval', $stmtWebsiteAccounts->fetchAll(PDO::FETCH_COLUMN, 0)) : array();
        if (!empty($websiteAccountIds)) {
            $accountsWithChars = array();
            $acctPlaceholders = implode(',', array_fill(0, count($websiteAccountIds), '?'));
            foreach ($realmCharsPdos as $realmCharsPdo) {
                $stmtAccountsWithChars = $realmCharsPdo->prepare("
                    SELECT DISTINCT account
                    FROM characters
                    WHERE account IN ($acctPlaceholders)
                ");
                $stmtAccountsWithChars->execute($websiteAccountIds);
                $accountsWithChars = array_merge(
                    $accountsWithChars,
                    array_map('intval', $stmtAccountsWithChars->fetchAll(PDO::FETCH_COLUMN, 0))
                );
            }
            $accountsWithChars = array_values(array_unique($accountsWithChars));
            $cleanupPreview['orphans']['website_only_accounts'] = count(array_diff($websiteAccountIds, $accountsWithChars));
        }
    } catch (Throwable $e) {
        error_log('[admin.cleanup] Website-only account preview failed: ' . $e->getMessage());
    }

    try {
        $stmtInvalidSelected = $cleanupPdo->query("
            SELECT wa.account_id, wa.character_id, wa.character_realm_id
            FROM website_accounts wa
            WHERE wa.character_id IS NOT NULL
              AND wa.character_id > 0
        ");
        $selectedRows = $stmtInvalidSelected ? $stmtInvalidSelected->fetchAll(PDO::FETCH_ASSOC) : array();
        $invalidSelected = 0;
        foreach ($selectedRows as $selectedRow) {
            $accountId = (int)($selectedRow['account_id'] ?? 0);
            $characterId = (int)($selectedRow['character_id'] ?? 0);
            $characterRealmId = (int)($selectedRow['character_realm_id'] ?? 0);
            $resolved = false;

            if ($characterRealmId > 0 && isset($realmCharsPdos[$characterRealmId])) {
                $stmtCharacter = $realmCharsPdos[$characterRealmId]->prepare("
                    SELECT COUNT(*)
                    FROM characters
                    WHERE guid = ?
                      AND account = ?
                ");
                $stmtCharacter->execute(array($characterId, $accountId));
                $resolved = ((int)$stmtCharacter->fetchColumn() > 0);
            } else {
                foreach ($realmCharsPdos as $realmCharsPdo) {
                    $stmtCharacter = $realmCharsPdo->prepare("
                        SELECT COUNT(*)
                        FROM characters
                        WHERE guid = ?
                          AND account = ?
                    ");
                    $stmtCharacter->execute(array($characterId, $accountId));
                    if ((int)$stmtCharacter->fetchColumn() > 0) {
                        $resolved = true;
                        break;
                    }
                }
            }

            if (!$resolved) {
                $invalidSelected++;
            }
        }
        $cleanupPreview['orphans']['invalid_selected_character'] = $invalidSelected;
    } catch (Throwable $e) {
        error_log('[admin.cleanup] Invalid selected-character preview failed: ' . $e->getMessage());
    }

    try {
        $stmtMissingAccounts = $cleanupPdo->query("
            SELECT COUNT(*)
            FROM website_accounts wa
            LEFT JOIN account a ON a.id = wa.account_id
            WHERE a.id IS NULL
        ");
        $cleanupPreview['orphans']['missing_account_rows'] = (int)$stmtMissingAccounts->fetchColumn();
    } catch (Throwable $e) {
        error_log('[admin.cleanup] Missing account-row preview failed: ' . $e->getMessage());
    }

    try {
        $cleanupPreview['forum']['forums'] = spp_admin_cleanup_table_count($cleanupPdo, 'f_forums');
        $cleanupPreview['forum']['topics'] = spp_admin_cleanup_table_count($cleanupPdo, 'f_topics');
        $cleanupPreview['forum']['posts'] = spp_admin_cleanup_table_count($cleanupPdo, 'f_posts');
        $cleanupPreview['forum']['pms'] = spp_admin_cleanup_table_count($cleanupPdo, 'website_pms');
        $cleanupPreview['forum']['identities'] = spp_admin_cleanup_table_count($cleanupPdo, 'website_identities');
        $cleanupPreview['forum']['identity_profiles'] = spp_admin_cleanup_table_count($cleanupPdo, 'website_identity_profiles');
        $cleanupPreview['forum']['reset_sql_available'] = is_file(dirname(__DIR__, 2) . '/DB Updates/reset_web_forums_blank_state.sql');
        $cleanupPreview['forum']['seed_sql_available'] = is_file(dirname(__DIR__, 2) . '/DB Updates/seed_web_forums_default_state.sql');
    } catch (Throwable $e) {
        error_log('[admin.cleanup] Forum preview failed: ' . $e->getMessage());
    }

    try {
        $stmtBotAccounts = $cleanupPdo->query("SELECT COUNT(*) FROM account WHERE LOWER(username) LIKE 'rndbot%'");
        $cleanupPreview['bots']['accounts'] = (int)$stmtBotAccounts->fetchColumn();

        $stmtBotAccountIds = $cleanupPdo->query("SELECT id FROM account WHERE LOWER(username) LIKE 'rndbot%'");
        $botAccountIds = $stmtBotAccountIds ? array_map('intval', $stmtBotAccountIds->fetchAll(PDO::FETCH_COLUMN, 0)) : array();

        if (!empty($botAccountIds)) {
            $botPlaceholders = implode(',', array_fill(0, count($botAccountIds), '?'));
            $stmtBotCharacters = $cleanupCharsPdo->prepare("
                SELECT COUNT(*)
                FROM characters
                WHERE account IN ($botPlaceholders)
            ");
            $stmtBotCharacters->execute($botAccountIds);
            $cleanupPreview['bots']['characters'] = (int)$stmtBotCharacters->fetchColumn();
        }

        if (spp_admin_cleanup_table_exists($cleanupPdo, 'website_identities')) {
            $stmtBotIdentities = $cleanupPdo->query("
                SELECT COUNT(*)
                FROM website_identities
                WHERE is_bot = 1 OR identity_type = 'bot_character'
            ");
            $cleanupPreview['bots']['identities'] = (int)$stmtBotIdentities->fetchColumn();
        }

        if (spp_admin_cleanup_table_exists($cleanupPdo, 'website_identity_profiles')) {
            $stmtBotSignatures = $cleanupPdo->query("
                SELECT COUNT(*)
                FROM website_identity_profiles ip
                JOIN website_identities wi ON wi.identity_id = ip.identity_id
                WHERE (wi.is_bot = 1 OR wi.identity_type = 'bot_character')
                  AND ip.signature IS NOT NULL
                  AND TRIM(ip.signature) <> ''
            ");
            $cleanupPreview['bots']['signatures'] = (int)$stmtBotSignatures->fetchColumn();
        }
    } catch (Throwable $e) {
        error_log('[admin.cleanup] Bot preview failed: ' . $e->getMessage());
    }

    try {
        $cleanupPreview['realm_reset']['characters'] = spp_admin_cleanup_table_count($cleanupCharsPdo, 'characters');
        $cleanupPreview['realm_reset']['guilds'] = spp_admin_cleanup_table_count($cleanupCharsPdo, 'guild');
        $cleanupPreview['realm_reset']['items'] = spp_admin_cleanup_table_count($cleanupCharsPdo, 'item_instance');
        $cleanupPreview['realm_reset']['mail'] = spp_admin_cleanup_table_count($cleanupCharsPdo, 'mail');
        $cleanupPreview['realm_reset']['auctions'] = spp_admin_cleanup_table_count($cleanupCharsPdo, 'auction');
    } catch (Throwable $e) {
        error_log('[admin.cleanup] Realm reset preview failed: ' . $e->getMessage());
    }

    return $cleanupPreview;
}
