<?php
if (INCLUDED !== true) exit;

require_once(__DIR__ . '/account.helpers.php');
require_once(__DIR__ . '/account.register.actions.php');

$pathway_info[] = array('title' => $lang['register'], 'link' => '');

if ((int)($user['id'] ?? 0) > 0) {
    redirect('index.php?n=account&sub=manage', 1);
}

$registerState = spp_account_register_build_state($realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()));

if ((int)$MW->getConfig->generic->site_register === 0) {
    $registerState['register_closed'] = true;
    $registerState['message_type'] = 'error';
    $registerState['message_html'] = '<strong>Registration is currently locked.</strong><br><small>Please try again later.</small>';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registerState = spp_account_register_handle_submission($registerState);
}

$registerRealmId = (int)$registerState['realm_id'];
$registerExpansion = (int)$registerState['expansion'];
$registerRealmlistHost = (string)$registerState['realmlist_host'];
$registerMessageType = (string)$registerState['message_type'];
$registerMessageHtml = (string)$registerState['message_html'];
$registerUsername = (string)$registerState['username'];
$registerCsrfToken = (string)$registerState['csrf_token'];
$registerClosed = !empty($registerState['register_closed']);
