<?php

if (!function_exists('spp_csrf_token')) {
    function spp_csrf_token($formName = 'default') {
        if (!isset($_SESSION['spp_csrf_tokens']) || !is_array($_SESSION['spp_csrf_tokens'])) {
            $_SESSION['spp_csrf_tokens'] = array();
        }

        if (empty($_SESSION['spp_csrf_tokens'][$formName])) {
            $_SESSION['spp_csrf_tokens'][$formName] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION['spp_csrf_tokens'][$formName];
    }
}

if (!function_exists('spp_require_csrf')) {
    function spp_require_csrf($formName = 'default', $failureMessage = 'Security check failed. Please refresh the page and try again.', $redirectUrl = '') {
        $submittedToken = (string)($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
        $sessionToken = (string)($_SESSION['spp_csrf_tokens'][$formName] ?? '');

        if ($submittedToken !== '' && $sessionToken !== '' && hash_equals($sessionToken, $submittedToken)) {
            return true;
        }

        if (function_exists('output_message')) {
            $message = '<b>' . $failureMessage . '</b>';
            if ($redirectUrl !== '') {
                $message .= '<meta http-equiv=refresh content="2;url=' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">';
            }
            output_message('alert', $message);
        }

        exit;
    }
}

if (!function_exists('spp_action_url')) {
    function spp_action_url($path, array $params = array(), $formName = 'default') {
        $params['csrf_token'] = spp_csrf_token($formName);
        return $path . (strpos($path, '?') === false ? '?' : '&') . http_build_query($params);
    }
}
