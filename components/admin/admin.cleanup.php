<?php
if (INCLUDED !== true) exit;

$pathway_info[] = array('title' => 'Site Cleanup', 'link' => '');

$cleanupPdo = spp_get_pdo('realmd', 1);
$cleanupActiveRealmId = (int)($GLOBALS['activeRealmId'] ?? spp_resolve_realm_id($realmDbMap));
$cleanupCharsPdo = spp_get_pdo('chars', $cleanupActiveRealmId);

if (!function_exists('spp_admin_cleanup_table_exists')) {
    function spp_admin_cleanup_table_exists(PDO $pdo, $tableName) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([(string)$tableName]);
        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('spp_admin_cleanup_table_count')) {
    function spp_admin_cleanup_table_count(PDO $pdo, $tableName) {
        if (!spp_admin_cleanup_table_exists($pdo, $tableName)) {
            return 0;
        }
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$tableName`");
        return (int)$stmt->fetchColumn();
    }
}

if (!function_exists('spp_admin_cleanup_realm_name')) {
    function spp_admin_cleanup_realm_name(PDO $pdo, $realmId) {
        $stmt = $pdo->prepare("SELECT name FROM realmlist WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$realmId]);
        $realmName = $stmt->fetchColumn();
        return $realmName ? (string)$realmName : ('Realm ' . (int)$realmId);
    }
}

$cleanupPreview = array(
    'realm_id' => $cleanupActiveRealmId,
    'realm_name' => spp_admin_cleanup_realm_name($cleanupPdo, $cleanupActiveRealmId),
    'orphans' => array(
        'website_only_accounts' => 0,
        'invalid_selected_character' => 0,
        'missing_account_rows' => 0,
    ),
    'forum' => array(
        'forums' => 0,
        'topics' => 0,
        'posts' => 0,
        'pms' => 0,
        'identities' => 0,
        'identity_profiles' => 0,
        'reset_sql_available' => false,
    ),
    'bots' => array(
        'accounts' => 0,
        'characters' => 0,
        'identities' => 0,
        'signatures' => 0,
    ),
    'realm_reset' => array(
        'characters' => 0,
        'guilds' => 0,
        'items' => 0,
        'mail' => 0,
        'auctions' => 0,
    ),
);

try {
    $stmtWebsiteAccounts = $cleanupPdo->query("
        SELECT wa.account_id
        FROM website_accounts wa
        JOIN account a ON a.id = wa.account_id
        ORDER BY wa.account_id ASC
    ");
    $websiteAccountIds = $stmtWebsiteAccounts ? array_map('intval', $stmtWebsiteAccounts->fetchAll(PDO::FETCH_COLUMN, 0)) : array();
    if (!empty($websiteAccountIds)) {
        $acctPlaceholders = implode(',', array_fill(0, count($websiteAccountIds), '?'));
        $stmtAccountsWithChars = $cleanupCharsPdo->prepare("
            SELECT DISTINCT account
            FROM characters
            WHERE account IN ($acctPlaceholders)
        ");
        $stmtAccountsWithChars->execute($websiteAccountIds);
        $accountsWithChars = array_map('intval', $stmtAccountsWithChars->fetchAll(PDO::FETCH_COLUMN, 0));
        $cleanupPreview['orphans']['website_only_accounts'] = count(array_diff($websiteAccountIds, $accountsWithChars));
    }
} catch (Throwable $e) {
    error_log('[admin.cleanup] Website-only account preview failed: ' . $e->getMessage());
}

try {
    $stmtInvalidSelected = $cleanupCharsPdo->query("
        SELECT COUNT(*)
        FROM website_accounts wa
        LEFT JOIN characters c
            ON c.guid = wa.character_id
           AND c.account = wa.account_id
        WHERE wa.character_id IS NOT NULL
          AND wa.character_id > 0
          AND c.guid IS NULL
    ");
    $cleanupPreview['orphans']['invalid_selected_character'] = (int)$stmtInvalidSelected->fetchColumn();
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
} catch (Throwable $e) {
    error_log('[admin.cleanup] Forum preview failed: ' . $e->getMessage());
}

try {
    $stmtBotAccounts = $cleanupPdo->query("SELECT COUNT(*) FROM account WHERE LOWER(username) LIKE 'rndbot%'");
    $cleanupPreview['bots']['accounts'] = (int)$stmtBotAccounts->fetchColumn();

    $stmtBotCharacters = $cleanupCharsPdo->query("
        SELECT COUNT(*)
        FROM characters
        WHERE account IN (
            SELECT id FROM account WHERE LOWER(username) LIKE 'rndbot%'
        )
    ");
    $cleanupPreview['bots']['characters'] = (int)$stmtBotCharacters->fetchColumn();

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
?>
