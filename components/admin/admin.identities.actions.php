<?php
if (INCLUDED !== true) {
    exit;
}

if (!function_exists('spp_admin_identity_health_handle_backfill_action')) {
    function spp_admin_identity_health_handle_backfill_action(string $siteRoot, string $phpBin, bool $isWindowsHost, array $realmDbMap): array {
        $state = [
            'identityOutput' => '',
            'identityError' => '',
            'identityNotice' => '',
            'identityCommand' => '',
        ];

        $action = (string)($_GET['action'] ?? '');
        if ($action !== 'run_backfill') {
            return $state;
        }

        spp_require_csrf('admin_identities');

        $realmId = (int)($_GET['realm'] ?? 0);
        $type = (string)($_GET['type'] ?? 'all');
        if ($realmId <= 0 || empty($realmDbMap[$realmId])) {
            $state['identityError'] = 'That realm is not configured.';
            return $state;
        }

        $validTypes = ['identities', 'posts', 'pms', 'all'];
        if (!in_array($type, $validTypes, true)) {
            $type = 'all';
        }

        $commands = spp_admin_identity_health_realm_commands($siteRoot, $realmId, $type);
        if ($isWindowsHost) {
            $state['identityNotice'] = count($commands) > 1
                ? 'Run these commands from PowerShell or Command Prompt:'
                : 'Run this command from PowerShell or Command Prompt:';
            $state['identityCommand'] = implode("\n", array_map(function ($command) {
                return spp_admin_identity_health_build_command($command['script'], $command['args']);
            }, $commands));
            return $state;
        }

        $stdoutParts = [];
        $stderrParts = [];
        foreach ($commands as $command) {
            $result = spp_admin_identity_health_run_script($phpBin, $command['script'], $command['args']);
            $label = basename((string)$command['script']);
            $stdout = trim((string)($result['stdout'] ?? ''));
            $stderr = trim((string)($result['stderr'] ?? ''));
            if ($stdout !== '') {
                $stdoutParts[] = '[' . $label . ']' . "\n" . $stdout;
            }
            if ($stderr !== '') {
                $stderrParts[] = '[' . $label . ']' . "\n" . $stderr;
            }
        }

        $state['identityOutput'] = implode("\n\n", $stdoutParts);
        $state['identityError'] = implode("\n\n", $stderrParts);
        if ($state['identityOutput'] === '' && $state['identityError'] === '') {
            $state['identityNotice'] = 'Backfill command completed without console output.';
        }

        return $state;
    }
}

if (!function_exists('spp_admin_identity_health_handle_repair_action')) {
    function spp_admin_identity_health_handle_repair_action(PDO $masterPdo, $selectedRealmId) {
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return;
        }

        $action = trim((string)($_POST['action'] ?? ''));
        if ($action === '') {
            return;
        }

        if (!spp_admin_identity_health_csrf_check('admin_identity_health', (string)($_POST['csrf_token'] ?? ''))) {
            output_message('alert', 'The Identity & Data Health form expired. Please refresh and try again.');
            return;
        }

        $selectedRealmId = (int)$selectedRealmId;
        if ($selectedRealmId <= 0) {
            output_message('alert', 'A valid realm must be selected before running a repair action.');
            return;
        }

        $realmPdo = null;
        try {
            $realmPdo = spp_get_pdo('realmd', $selectedRealmId);
        } catch (Throwable $e) {
            if ($action === 'clear_invalid_selected_character') {
                output_message('alert', 'The selected realm is currently unavailable for repairs.');
                spp_admin_identity_health_log('Selected realm unavailable during repair action: ' . $e->getMessage());
                return;
            }
        }

        $charsPdo = null;
        try {
            $charsPdo = spp_get_pdo('chars', $selectedRealmId);
        } catch (Throwable $e) {
            $charsPdo = null;
        }

        if ($action === 'clear_invalid_selected_character') {
            $targets = spp_admin_identity_health_collect_invalid_selected_accounts($masterPdo, $selectedRealmId, $charsPdo);
            if (empty($targets)) {
                output_message('alert', 'No invalid selected-character pointers were found for the selected realm.');
                return;
            }

            $profileTable = spp_account_profile_table_name();
            if (!spp_admin_identity_health_table_exists($masterPdo, $profileTable)) {
                output_message('alert', 'The account profile table is not available, so selected-character pointers could not be repaired.');
                return;
            }

            try {
                $placeholders = implode(',', array_fill(0, count($targets), '?'));
                $sql = "UPDATE `" . $profileTable . "` SET `character_id` = NULL";
                if (spp_admin_identity_health_column_exists($masterPdo, $profileTable, 'character_name')) {
                    $sql .= ", `character_name` = NULL";
                }
                if (spp_admin_identity_health_column_exists($masterPdo, $profileTable, 'character_realm_id')) {
                    $sql .= ", `character_realm_id` = NULL";
                }
                $sql .= " WHERE `account_id` IN (" . $placeholders . ")";
                $stmt = $masterPdo->prepare($sql);
                $stmt->execute(array_keys($targets));

                if (spp_admin_identity_health_table_exists($masterPdo, 'website_accounts')
                    && spp_admin_identity_health_column_exists($masterPdo, 'website_accounts', 'character_id')) {
                    $sqlWebsite = "UPDATE `website_accounts` SET `character_id` = NULL";
                    if (spp_admin_identity_health_column_exists($masterPdo, 'website_accounts', 'character_name')) {
                        $sqlWebsite .= ", `character_name` = NULL";
                    }
                    if (spp_admin_identity_health_column_exists($masterPdo, 'website_accounts', 'character_realm_id')) {
                        $sqlWebsite .= ", `character_realm_id` = NULL";
                    }
                    $sqlWebsite .= " WHERE `account_id` IN (" . $placeholders . ")";
                    $stmtWebsite = $masterPdo->prepare($sqlWebsite);
                    $stmtWebsite->execute(array_keys($targets));
                }

                output_message('success', 'Cleared ' . count($targets) . ' invalid selected-character pointer(s).');
            } catch (Throwable $e) {
                spp_admin_identity_health_log('clear_invalid_selected_character failed: ' . $e->getMessage());
                output_message('alert', 'Selected-character repair failed. Check the error log for details.');
            }
            return;
        }

        if ($action === 'remove_missing_account_rows') {
            $realmRows = spp_admin_identity_health_realm_rows($masterPdo);
            $accessibleRealmPdos = array();
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
            }

            $targets = spp_admin_identity_health_collect_missing_account_rows($masterPdo, $accessibleRealmPdos);
            if (empty($targets)) {
                output_message('alert', 'No orphaned website account rows were found.');
                return;
            }

            try {
                $placeholders = implode(',', array_fill(0, count($targets), '?'));
                $stmt = $masterPdo->prepare("DELETE FROM `website_accounts` WHERE `account_id` IN (" . $placeholders . ")");
                $stmt->execute(array_keys($targets));

                $profileTable = spp_account_profile_table_name();
                if (spp_admin_identity_health_table_exists($masterPdo, $profileTable)
                    && spp_admin_identity_health_column_exists($masterPdo, $profileTable, 'account_id')) {
                    $stmtProfile = $masterPdo->prepare("DELETE FROM `" . $profileTable . "` WHERE `account_id` IN (" . $placeholders . ")");
                    $stmtProfile->execute(array_keys($targets));
                }

                output_message('success', 'Removed ' . count($targets) . ' orphaned website account row(s) that no longer map to a live account.');
            } catch (Throwable $e) {
                spp_admin_identity_health_log('remove_missing_account_rows failed: ' . $e->getMessage());
                output_message('alert', 'Website account cleanup failed. Check the error log for details.');
            }
            return;
        }

        output_message('alert', 'That Identity & Data Health action is not recognized.');
    }
}
