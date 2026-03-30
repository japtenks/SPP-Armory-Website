<?php

if (!function_exists('spp_account_register_build_state')) {
    function spp_account_register_build_state($realmDbMap) {
        $realmMap = is_array($realmDbMap) ? $realmDbMap : array();
        $realmId = !empty($realmMap) ? (int)spp_resolve_realm_id($realmMap) : 1;

        return array(
            'realm_id' => $realmId > 0 ? $realmId : 1,
            'expansion' => 2,
            'realmlist_host' => spp_account_register_resolve_realmlist_host($realmId),
            'message_html' => '',
            'message_type' => '',
            'username' => '',
            'csrf_token' => spp_csrf_token('account_register'),
            'register_closed' => false,
        );
    }
}

if (!function_exists('spp_account_register_resolve_realmlist_host')) {
    function spp_account_register_resolve_realmlist_host($realmId) {
        $host = preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            $host = (string)($_SERVER['SERVER_ADDR'] ?? '127.0.0.1');
        }

        if (!function_exists('spp_get_pdo')) {
            return $host;
        }

        try {
            $realmdPdo = spp_get_pdo('realmd', (int)$realmId);
            $stmt = $realmdPdo->prepare('SELECT `address` FROM `realmlist` WHERE `id` = ? LIMIT 1');
            $stmt->execute(array((int)$realmId));
            $realmInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($realmInfo['address'])) {
                return (string)$realmInfo['address'];
            }
        } catch (Throwable $e) {
            error_log('[account.register] Realmlist host lookup failed: ' . $e->getMessage());
        }

        return $host;
    }
}

if (!function_exists('spp_account_register_handle_submission')) {
    function spp_account_register_handle_submission(array $state) {
        global $auth, $MW;

        $state['username'] = trim((string)($_POST['username'] ?? ''));

        if ((int)$MW->getConfig->generic->site_register === 0) {
            return spp_account_register_error_state($state, 'Registration is currently locked.');
        }

        if (!spp_account_register_has_valid_csrf()) {
            return spp_account_register_error_state($state, 'Security check failed. Please refresh the page and try again.');
        }

        $username = strtoupper($state['username']);
        $password = trim((string)($_POST['password'] ?? ''));
        $verify = trim((string)($_POST['verify'] ?? ''));

        if (strlen($username) < 3 || strlen($username) > 16 || !preg_match('/^[A-Z0-9_]+$/', $username)) {
            return spp_account_register_error_state($state, 'Username must be 3-16 characters and use only letters, numbers, or underscores.');
        }

        if (strlen($password) < 4 || strlen($password) > 16) {
            return spp_account_register_error_state($state, 'Password must be 4-16 characters long.');
        }

        if ($password !== $verify) {
            return spp_account_register_error_state($state, 'Password fields do not match.');
        }

        try {
            $realmdPdo = spp_get_pdo('realmd', (int)$state['realm_id']);

            $maxAccountsPerIp = (int)$MW->getConfig->generic->max_accounts_per_ip;
            if ($maxAccountsPerIp > 0) {
                $stmtIp = $realmdPdo->prepare('SELECT count(*) FROM website_accounts WHERE registration_ip = ?');
                $stmtIp->execute(array((string)($_SERVER['REMOTE_ADDR'] ?? '')));
                $countIp = (int)$stmtIp->fetchColumn();
                if ($countIp >= $maxAccountsPerIp) {
                    return spp_account_register_error_state(
                        $state,
                        'This connection has reached the account creation limit. Please contact an administrator if you need help.'
                    );
                }
            }

            $stmtExisting = $realmdPdo->prepare('SELECT id FROM account WHERE LOWER(username) = LOWER(?) LIMIT 1');
            $stmtExisting->execute(array($username));
            if ($stmtExisting->fetchColumn()) {
                return spp_account_register_error_state($state, 'Username already exists. Please choose another.');
            }

            $result = $auth->register(
                array(
                    'username' => $username,
                    'password' => $password,
                    'expansion' => (int)$state['expansion'],
                ),
                false
            );

            if ($result !== true) {
                return spp_account_register_error_state(
                    $state,
                    'Account creation failed. Please check your details and try again. If it keeps happening, contact an administrator.'
                );
            }

            $autoLoginNotice = '';
            if ((int)$MW->getConfig->generic->req_reg_act === 0 && !$auth->login(array('username' => $username, 'password' => $password))) {
                $autoLoginNotice = '<br><small>Your account was created, but automatic login did not complete. Please sign in with your new account.</small>';
            }

            $state['username'] = '';
            $state['message_type'] = 'success';
            $state['message_html'] = '<strong>Account <b>' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</b> created successfully.</strong>'
                . '<br>Next step: open your game client and log in to create your first character.'
                . '<br>Set your realmlist to: <code>set realmlist ' . htmlspecialchars((string)$state['realmlist_host'], ENT_QUOTES, 'UTF-8') . '</code>'
                . $autoLoginNotice;

            return $state;
        } catch (Throwable $e) {
            error_log('[account.register] Registration request failed: ' . $e->getMessage());

            return spp_account_register_error_state(
                $state,
                'The server could not complete your request. Please try again in a moment.'
            );
        }
    }
}

if (!function_exists('spp_account_register_has_valid_csrf')) {
    function spp_account_register_has_valid_csrf() {
        $submittedToken = (string)($_POST['csrf_token'] ?? '');
        $sessionToken = (string)($_SESSION['spp_csrf_tokens']['account_register'] ?? '');

        return $submittedToken !== '' && $sessionToken !== '' && hash_equals($sessionToken, $submittedToken);
    }
}

if (!function_exists('spp_account_register_error_state')) {
    function spp_account_register_error_state(array $state, $message) {
        $state['message_type'] = 'error';
        $state['message_html'] = '<strong>Account creation failed.</strong><br><small>'
            . htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8')
            . '</small>';

        return $state;
    }
}
