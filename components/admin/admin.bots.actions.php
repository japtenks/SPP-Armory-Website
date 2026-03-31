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

    $isWindowsHost = DIRECTORY_SEPARATOR === '\\';
    $helperConfig = spp_admin_bots_helper_config();

    if (!empty($_GET['refresh_helper'])) {
        if ($isWindowsHost && empty($helperConfig['configured'])) {
            $result['manual_notice'] = 'Run this helper status command from PowerShell or Command Prompt. Manual CLI mode is the default when no HTTP helper endpoint is set:';
            $result['manual_command'] = spp_admin_bots_build_manual_command('status', array());
            return $result;
        }

        $result['refresh_status'] = true;
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
        'execute' => in_array($action, array('reset_forum_realm', 'fresh_reset', 'rebuild_site_layers'), true),
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

    if ($isWindowsHost && empty($helperConfig['configured'])) {
        $result['manual_notice'] = 'Run this command from PowerShell or Command Prompt. Manual CLI mode is active because no HTTP helper endpoint is set:';
        $result['manual_command'] = spp_admin_bots_build_manual_command($action, $payload);
        $result['flash'] = array(
            'type' => 'error',
            'message' => 'This environment is using manual CLI mode, so the page is showing the exact command instead. Execution still depends on the local helper safety flag file.',
        );
        return $result;
    }

    $call = spp_admin_bots_call_helper($action, $payload);
    $state = spp_admin_bots_load_state();
    $state['last_run'] = array(
        'action' => $action,
        'label' => $supportedActions[$action],
        'ran_at' => date('c'),
        'ok' => !empty($call['ok']),
        'summary' => (string)($call['summary'] ?? ''),
        'error' => (string)($call['error'] ?? ''),
        'response' => $call['response'] ?? array(),
    );
    spp_admin_bots_save_state($state);

    $result['refresh_status'] = true;
    if (!empty($call['ok'])) {
        $message = trim((string)($call['summary'] ?? ''));
        if ($message === '') {
            $message = $supportedActions[$action] . ' was handed to the local maintenance helper.';
        }

        $result['flash'] = array(
            'type' => 'success',
            'message' => $message,
        );
        return $result;
    }

    $message = trim((string)($call['error'] ?? ''));
    if ($message === '') {
        $message = $supportedActions[$action] . ' could not reach the local maintenance helper.';
    }

    $result['flash'] = array(
        'type' => 'error',
        'message' => $message,
    );

    return $result;
}
