<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_bots_handle_action(PDO $masterPdo): array
{
    $result = array(
        'flash' => array(),
        'refresh_status' => false,
        'manual_notice' => '',
        'manual_command' => '',
    );

    $helperConfig = spp_admin_bots_helper_config();

    if (!empty($_GET['refresh_helper'])) {
        $result['manual_notice'] = 'Run this status script from PowerShell, Command Prompt, or the host shell. This page follows the same one-script-per-action pattern as the identity backfills:';
        $result['manual_command'] = spp_admin_bots_build_manual_command('status', array());
        return $result;
    }

    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        return $result;
    }

    $action = trim((string)($_POST['bots_action'] ?? ''));
    if ($action === '') {
        return $result;
    }

    spp_require_csrf('admin_bots', 'The bot maintenance form expired. Please refresh the page and try again.');

    $supportedActions = array(
        'reset_forum_realm' => 'Reset Selected Realm Forums',
        'fresh_reset' => 'Fresh Bot World Reset',
        'clear_realm_character_state' => 'Clear Realm Character State',
        'rebuild_site_layers' => 'Rebuild Bot Website Layers',
        'status' => 'Refresh Helper Status',
    );

    if (!isset($supportedActions[$action])) {
        $result['flash'] = array(
            'type' => 'error',
            'message' => 'That bot maintenance action is not recognized.',
        );
        return $result;
    }

    $realmDbMap = $GLOBALS['realmDbMap'] ?? array();
    $selectedRealmId = spp_resolve_realm_id(is_array($realmDbMap) ? $realmDbMap : array(), isset($_POST['realm']) ? (int)$_POST['realm'] : null);
    $selectedRealmName = (string)(function_exists('spp_get_armory_realm_name') ? (spp_get_armory_realm_name($selectedRealmId) ?? ('Realm ' . $selectedRealmId)) : ('Realm ' . $selectedRealmId));

    $payload = array(
        'requested_by' => (string)($GLOBALS['user']['username'] ?? 'admin'),
        'realm_id' => $selectedRealmId,
        'realm_name' => $selectedRealmName,
        'execute' => in_array($action, array('reset_forum_realm', 'fresh_reset', 'clear_realm_character_state', 'rebuild_site_layers'), true),
        'dry_run' => false,
        'preserve' => array(
            'player_accounts' => true,
            'player_characters' => true,
            'gm_accounts' => true,
            'website_users' => true,
        ),
        'bot_scope' => array(
            'account_prefix' => 'rndbot',
            'forum_reset_scope' => 'selected_realm_only',
            'forum_reset_included_in_fresh_reset' => true,
            'preserve_forum_authors' => array('SPP Team', 'web Team'),
            'preserve_zero_owner_forum_seed_posts' => true,
        ),
        'preview' => array(
            'bot_accounts' => spp_admin_bots_account_counts($masterPdo)['bot_accounts'] ?? 0,
            'website_bot_events' => spp_admin_identity_health_table_exists($masterPdo, 'website_bot_events')
                ? spp_admin_identity_health_scalar($masterPdo, "SELECT COUNT(*) FROM `website_bot_events`")
                : 0,
        ),
    );

    $result['manual_notice'] = 'Run this script from PowerShell, Command Prompt, or the host shell. Like the identity backfills, the page records the result after the script writes its status file:';
    $result['manual_command'] = spp_admin_bots_build_manual_command($action, $payload);
    $result['flash'] = array(
        'type' => 'success',
        'message' => 'This action uses the manual script workflow. Run the command directly, or add --dry-run if you only want a preview, then refresh the page to pick up the updated state file.',
    );

    return $result;
}
