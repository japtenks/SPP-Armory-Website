<?php

if (!function_exists('spp_ensure_website_account_row')) {
    function spp_ensure_website_account_row(PDO $pdo, $accountId) {
        $accountId = (int)$accountId;
        if ($accountId <= 0) {
            return;
        }

        $stmtEnsure = $pdo->prepare("
            INSERT INTO website_accounts (account_id)
            SELECT ?
            WHERE NOT EXISTS (
                SELECT 1 FROM website_accounts WHERE account_id = ?
            )
        ");
        $stmtEnsure->execute([$accountId, $accountId]);
    }
}

if (!function_exists('spp_account_avatar_fallback_url')) {
    function spp_account_avatar_fallback_url(PDO $charsPdo, array $profile, array $accountCharacters = []) {
        $selectedGuid = (int)($profile['character_id'] ?? 0);
        if ($selectedGuid <= 0 && !empty($accountCharacters[0]['guid'])) {
            $selectedGuid = (int)$accountCharacters[0]['guid'];
        }
        if ($selectedGuid <= 0) {
            return '';
        }

        try {
            $stmt = $charsPdo->prepare("SELECT guid, race, class, gender FROM characters WHERE guid=? AND account=? LIMIT 1");
            $stmt->execute([$selectedGuid, (int)($profile['id'] ?? 0)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return '';
            }

            if (!function_exists('get_character_portrait_path')) {
                require_once(dirname(__DIR__) . '/forum/forum.func.php');
            }

            if (function_exists('get_character_portrait_path')) {
                return (string)get_character_portrait_path(
                    (int)$row['guid'],
                    (int)$row['gender'],
                    (int)$row['race'],
                    (int)$row['class']
                );
            }
        } catch (Throwable $e) {
            error_log('[account.helpers] Avatar fallback lookup failed: ' . $e->getMessage());
        }

        return '';
    }
}

if (!function_exists('spp_manage_allowed_profile_fields')) {
    function spp_manage_allowed_profile_fields($backgroundPreferencesAvailable, $canHideProfile) {
        $allowedFields = array(
            'theme',
            'display_name',
            'fname',
            'lname',
            'city',
            'location',
            'hidelocation',
            'gmt',
            'msn',
            'icq',
            'aim',
            'yahoo',
            'skype',
            'homepage',
            'gender',
            'signature',
        );

        if ($canHideProfile) {
            $allowedFields[] = 'hideprofile';
        }

        if ($backgroundPreferencesAvailable) {
            $allowedFields[] = 'background_mode';
            $allowedFields[] = 'background_image';
        }

        return spp_allowed_field_map($allowedFields);
    }
}

if (!function_exists('spp_account_login_redirect_target')) {
    function spp_account_login_redirect_target($requestedTarget, $fallbackTarget = 'index.php') {
        $target = trim((string)$requestedTarget);
        if ($target === '') {
            $target = $fallbackTarget;
        }

        $target = str_replace(array("\r", "\n"), '', $target);
        if (preg_match('#^https?://#i', $target) || strpos($target, '//') === 0) {
            return $fallbackTarget;
        }

        if ($target !== '' && $target[0] === '/') {
            $target = ltrim($target, '/');
        }

        if ($target === '' || stripos($target, 'index.php?n=account&sub=login') !== false) {
            return $fallbackTarget;
        }

        return $target;
    }
}

if (!function_exists('spp_format_total_played')) {
    function spp_format_total_played($seconds) {
        $seconds = max(0, (int)$seconds);
        $days = (int)floor($seconds / 86400);
        $hours = (int)floor(($seconds % 86400) / 3600);
        $minutes = (int)floor(($seconds % 3600) / 60);

        $parts = array();
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0 || $days > 0) {
            $parts[] = $hours . 'h';
        }
        $parts[] = $minutes . 'm';

        return implode(' ', $parts);
    }
}

if (!function_exists('spp_account_view_avatar_fallback_url')) {
    function spp_account_view_avatar_fallback_url($profile, $realmDbMap) {
        $characterGuid = (int)($profile['character_id'] ?? 0);
        if ($characterGuid <= 0) {
            return '';
        }

        foreach ($realmDbMap as $realmId => $realmInfo) {
            try {
                $charPdo = spp_get_pdo('chars', (int)$realmId);
                $stmt = $charPdo->prepare("SELECT guid, race, class, gender FROM characters WHERE guid=? LIMIT 1");
                $stmt->execute([$characterGuid]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    continue;
                }

                if (!function_exists('get_character_portrait_path')) {
                    require_once(dirname(__DIR__) . '/forum/forum.func.php');
                }

                if (function_exists('get_character_portrait_path')) {
                    return (string)get_character_portrait_path(
                        (int)$row['guid'],
                        (int)$row['gender'],
                        (int)$row['race'],
                        (int)$row['class']
                    );
                }
            } catch (Throwable $e) {
                error_log('[account.helpers] Account view avatar fallback lookup failed: ' . $e->getMessage());
            }
        }

        return '';
    }
}

if (!function_exists('spp_account_view_open_named_pdo')) {
    function spp_account_view_open_named_pdo($dbName) {
        $db = $GLOBALS['db'] ?? null;
        if (!is_array($db) || empty($db['host']) || empty($db['user'])) {
            throw new RuntimeException('Database config not available.');
        }

        return new PDO(
            "mysql:host={$db['host']};port={$db['port']};dbname={$dbName};charset=utf8mb4",
            $db['user'],
            $db['pass'],
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            )
        );
    }
}
