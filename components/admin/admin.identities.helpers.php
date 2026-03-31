<?php
if (INCLUDED !== true) {
    exit;
}

if (!function_exists('spp_admin_identity_health_log')) {
    function spp_admin_identity_health_log($message) {
        error_log('[admin.identities] ' . (string)$message);
    }
}

if (!function_exists('spp_admin_identity_health_action_url')) {
    function spp_admin_identity_health_action_url(array $params) {
        return function_exists('spp_action_url')
            ? spp_action_url('index.php', $params, 'admin_identities')
            : ('index.php?' . http_build_query($params));
    }
}

if (!function_exists('spp_admin_identity_health_resolve_php_cli_binary')) {
    function spp_admin_identity_health_resolve_php_cli_binary(): string {
        $candidates = array_filter(array_unique([
            (string)(PHP_BINARY ?? ''),
            (defined('PHP_BINDIR') ? rtrim((string)PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php' : ''),
            '/usr/bin/php',
            '/usr/local/bin/php',
            '/opt/cpanel/ea-php82/root/usr/bin/php',
            '/opt/cpanel/ea-php81/root/usr/bin/php',
            '/opt/cpanel/ea-php80/root/usr/bin/php',
            '/opt/cpanel/ea-php74/root/usr/bin/php',
            'php',
        ]));

        foreach ($candidates as $candidate) {
            if ($candidate === 'php') {
                return $candidate;
            }
            if (@is_file($candidate) && @is_executable($candidate)) {
                return $candidate;
            }
        }

        return 'php';
    }
}

if (!function_exists('spp_admin_identity_health_run_script')) {
    function spp_admin_identity_health_run_script(string $phpBin, string $scriptPath, array $extraArgs = []): array {
        $args = array_map('escapeshellarg', $extraArgs);
        $cmd = escapeshellcmd($phpBin) . ' ' . escapeshellarg($scriptPath)
            . (empty($args) ? '' : ' ' . implode(' ', $args));

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $descriptors, $pipes, $scriptPath ? dirname($scriptPath) : null);
        if (!is_resource($proc)) {
            return ['stdout' => '', 'stderr' => 'proc_open failed - check PHP disable_functions.', 'code' => -1];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        return ['stdout' => $stdout, 'stderr' => $stderr, 'code' => $code];
    }
}

if (!function_exists('spp_admin_identity_health_build_command')) {
    function spp_admin_identity_health_build_command(string $scriptPath, array $extraArgs = []): string {
        $parts = ['php'];
        if ($scriptPath !== '') {
            $relativeScript = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $scriptPath);
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
            if (is_string($docRoot) && $docRoot !== '' && stripos($relativeScript, $docRoot) === 0) {
                $relativeScript = ltrim(substr($relativeScript, strlen($docRoot)), '/\\');
            }
            $parts[] = $relativeScript;
        }
        foreach ($extraArgs as $arg) {
            $parts[] = $arg;
        }
        return implode(' ', $parts);
    }
}

if (!function_exists('spp_admin_identity_health_realm_commands')) {
    function spp_admin_identity_health_realm_commands(string $siteRoot, int $realmId, string $type = 'all'): array {
        $realmId = (int)$realmId;
        $commandMap = [
            'identities' => [
                'script' => $siteRoot . '/tools/backfill_identities.php',
                'args' => ['--realm=' . $realmId],
            ],
            'posts' => [
                'script' => $siteRoot . '/tools/backfill_post_identities.php',
                'args' => ['--realm=' . $realmId],
            ],
            'pms' => [
                'script' => $siteRoot . '/tools/backfill_pm_identities.php',
                'args' => ['--realm=' . $realmId],
            ],
        ];

        if ($type === 'identities' || $type === 'posts' || $type === 'pms') {
            return [$commandMap[$type]];
        }

        return array_values($commandMap);
    }
}

if (!function_exists('spp_admin_identity_health_token_key')) {
    function spp_admin_identity_health_token_key($scope) {
        return 'spp_csrf_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string)$scope);
    }
}

if (!function_exists('spp_admin_identity_health_csrf_token')) {
    function spp_admin_identity_health_csrf_token($scope) {
        if (function_exists('spp_csrf_token')) {
            return spp_csrf_token($scope);
        }
        $key = spp_admin_identity_health_token_key($scope);
        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = bin2hex(random_bytes(16));
        }
        return (string)$_SESSION[$key];
    }
}

if (!function_exists('spp_admin_identity_health_csrf_check')) {
    function spp_admin_identity_health_csrf_check($scope, $token) {
        if (function_exists('spp_csrf_check')) {
            return spp_csrf_check($scope, $token);
        }
        $key = spp_admin_identity_health_token_key($scope);
        $expected = isset($_SESSION[$key]) ? (string)$_SESSION[$key] : '';
        return $expected !== '' && hash_equals($expected, (string)$token);
    }
}

if (!function_exists('spp_admin_identity_health_table_exists')) {
    function spp_admin_identity_health_table_exists(PDO $pdo, $tableName) {
        static $cache = array();
        $cacheKey = spl_object_hash($pdo) . ':' . $tableName;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }
        try {
            $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([(string)$tableName]);
            $cache[$cacheKey] = (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            $cache[$cacheKey] = false;
        }
        return $cache[$cacheKey];
    }
}

if (!function_exists('spp_admin_identity_health_column_exists')) {
    function spp_admin_identity_health_column_exists(PDO $pdo, $tableName, $columnName) {
        static $cache = array();
        $cacheKey = spl_object_hash($pdo) . ':' . $tableName . ':' . $columnName;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }
        if (!spp_admin_identity_health_table_exists($pdo, $tableName)) {
            $cache[$cacheKey] = false;
            return false;
        }
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `" . str_replace('`', '``', (string)$tableName) . "` LIKE ?");
            $stmt->execute([(string)$columnName]);
            $cache[$cacheKey] = (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            $cache[$cacheKey] = false;
        }
        return $cache[$cacheKey];
    }
}

if (!function_exists('spp_admin_identity_health_scalar')) {
    function spp_admin_identity_health_scalar(PDO $pdo, $sql, array $params = array(), $logContext = '') {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            if ($logContext !== '') {
                spp_admin_identity_health_log($logContext . ': ' . $e->getMessage());
            }
            return 0;
        }
    }
}

if (!function_exists('spp_admin_identity_health_fetch_int_column')) {
    function spp_admin_identity_health_fetch_int_column(PDO $pdo, $sql, array $params = array(), $logContext = '') {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $values = array();
            while (($value = $stmt->fetchColumn()) !== false) {
                $value = (int)$value;
                if ($value > 0) {
                    $values[$value] = true;
                }
            }
            return $values;
        } catch (Throwable $e) {
            if ($logContext !== '') {
                spp_admin_identity_health_log($logContext . ': ' . $e->getMessage());
            }
            return array();
        }
    }
}

if (!function_exists('spp_admin_identity_health_chunked_existing_ids')) {
    function spp_admin_identity_health_chunked_existing_ids(PDO $pdo, $tableName, $idColumn, array $ids, $extraWhere = '', array $extraParams = array(), $logContext = '') {
        $existing = array();
        if (empty($ids) || !spp_admin_identity_health_table_exists($pdo, $tableName)) {
            return $existing;
        }
        $tableName = str_replace('`', '``', (string)$tableName);
        $idColumn = str_replace('`', '``', (string)$idColumn);
        foreach (array_chunk(array_values(array_unique(array_map('intval', array_keys($ids)))), 500) as $chunk) {
            try {
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $sql = "SELECT `" . $idColumn . "` FROM `" . $tableName . "` WHERE `" . $idColumn . "` IN (" . $placeholders . ")";
                if ($extraWhere !== '') {
                    $sql .= ' AND ' . $extraWhere;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_merge($chunk, $extraParams));
                while (($value = $stmt->fetchColumn()) !== false) {
                    $existing[(int)$value] = true;
                }
            } catch (Throwable $e) {
                if ($logContext !== '') {
                    spp_admin_identity_health_log($logContext . ': ' . $e->getMessage());
                }
                return $existing;
            }
        }
        return $existing;
    }
}

if (!function_exists('spp_admin_identity_health_count_missing_from_set')) {
    function spp_admin_identity_health_count_missing_from_set(array $allIds, array $existingIds) {
        $missing = 0;
        foreach ($allIds as $id => $trueValue) {
            if (!isset($existingIds[(int)$id])) {
                $missing++;
            }
        }
        return $missing;
    }
}

if (!function_exists('spp_admin_identity_health_realm_rows')) {
    function spp_admin_identity_health_realm_rows(PDO $masterPdo) {
        try {
            return $masterPdo->query("SELECT `id`, `name` FROM `realmlist` ORDER BY `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            spp_admin_identity_health_log('Failed loading realm rows: ' . $e->getMessage());
            return array();
        }
    }
}

if (!function_exists('spp_admin_identity_health_resolve_realm_id')) {
    function spp_admin_identity_health_resolve_realm_id(array $realmOptions, $requestedRealmId, $defaultRealmId) {
        $requestedRealmId = (int)$requestedRealmId;
        $defaultRealmId = (int)$defaultRealmId;
        if ($requestedRealmId > 0 && isset($realmOptions[$requestedRealmId])) {
            return $requestedRealmId;
        }
        if ($defaultRealmId > 0 && isset($realmOptions[$defaultRealmId])) {
            return $defaultRealmId;
        }
        if (!empty($realmOptions)) {
            return (int)spp_array_first_key($realmOptions);
        }
        return 0;
    }
}

if (!function_exists('spp_admin_identity_health_format_percent')) {
    function spp_admin_identity_health_format_percent($covered, $total) {
        $covered = (int)$covered;
        $total = (int)$total;
        if ($total <= 0) {
            return 'n/a';
        }
        return number_format(($covered / $total) * 100, 0) . '%';
    }
}
