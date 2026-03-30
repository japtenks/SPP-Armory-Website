<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_cleanup_table_exists(PDO $pdo, $tableName)
{
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([(string)$tableName]);
    return (bool)$stmt->fetchColumn();
}

function spp_admin_cleanup_table_count(PDO $pdo, $tableName)
{
    if (!spp_admin_cleanup_table_exists($pdo, $tableName)) {
        return 0;
    }
    $stmt = $pdo->query("SELECT COUNT(*) FROM `$tableName`");
    return (int)$stmt->fetchColumn();
}

function spp_admin_cleanup_realm_name(PDO $pdo, $realmId)
{
    $stmt = $pdo->prepare("SELECT name FROM realmlist WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$realmId]);
    $realmName = $stmt->fetchColumn();
    return $realmName ? (string)$realmName : ('Realm ' . (int)$realmId);
}

function spp_admin_cleanup_empty_preview(int $realmId, string $realmName)
{
    return array(
        'realm_id' => $realmId,
        'realm_name' => $realmName,
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
}
