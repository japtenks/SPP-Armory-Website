<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_members_build_detail_view(PDO $membersPdo, PDO $membersCharsPdo, $auth, array $lang, $comLinks, array $realmDbMap, int $accountId, int $selectedToolRealmId = 0)
{
    $profile = $auth->getprofile($accountId);
    if (!is_array($profile) || empty($profile)) {
        return array(
            'profile' => null,
            'allgroups' => array(),
            'donator' => null,
            'active' => 0,
            'act' => 0,
            'userchars' => array(),
            'onlineCharacterCount' => 0,
            'eligibleTransferAccounts' => array(),
            'txt' => array('yearlist' => '', 'monthlist' => '', 'daylist' => ''),
            'pathway_info' => array(
                array('title' => $lang['users_manage'], 'link' => is_array($comLinks) ? ($comLinks['sub_members'] ?? 'index.php?n=admin&sub=members') : 'index.php?n=admin&sub=members'),
                array('title' => 'Missing account', 'link' => ''),
            ),
        );
    }
    spp_ensure_website_account_row($membersPdo, $accountId);

    $stmt = $membersPdo->query("SELECT g_id, g_title FROM website_account_groups");
    $allgroups = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmt = $membersPdo->prepare("SELECT donator FROM website_accounts WHERE account_id=?");
    $stmt->execute([$accountId]);
    $donator = $stmt->fetchColumn();

    $stmt = $membersPdo->prepare("SELECT active FROM account_banned WHERE id=? AND active=1");
    $stmt->execute([$accountId]);
    $active = $stmt->fetchColumn();

    $stmt = $membersCharsPdo->prepare("SELECT `guid`, `name`, `race`, `class`, `level`, `online` FROM `characters` WHERE `account` = ? ORDER BY guid");
    $stmt->execute([$accountId]);
    $userchars = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $activeRealmId = (int)($GLOBALS['activeRealmId'] ?? spp_resolve_realm_id($realmDbMap));
    $allUserChars = array();
    $charactersByRealm = array();
    $onlineCharacterCount = 0;

    foreach ($realmDbMap as $candidateRealmId => $realmInfo) {
        try {
            $realmCharsPdo = spp_get_pdo('chars', (int)$candidateRealmId);
            $stmtRealmChars = $realmCharsPdo->prepare("SELECT `guid`, `name`, `race`, `class`, `level`, `online` FROM `characters` WHERE `account` = ? ORDER BY guid");
            $stmtRealmChars->execute([$accountId]);
            $realmChars = $stmtRealmChars->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('[admin.members.read] Failed loading characters for realm ' . (int)$candidateRealmId . ': ' . $e->getMessage());
            $realmChars = array();
        }

        if (empty($realmChars)) {
            continue;
        }

        $realmName = function_exists('spp_get_armory_realm_name')
            ? (spp_get_armory_realm_name((int)$candidateRealmId) ?? ('Realm ' . (int)$candidateRealmId))
            : ('Realm ' . (int)$candidateRealmId);

        foreach ($realmChars as $realmChar) {
            $realmChar['realm_id'] = (int)$candidateRealmId;
            $realmChar['realm_name'] = $realmName;
            $allUserChars[] = $realmChar;
            $charactersByRealm[(int)$candidateRealmId][] = $realmChar;
            if (!empty($realmChar['online'])) {
                $onlineCharacterCount++;
            }
        }
    }

    $stmtEligible = $membersPdo->prepare("
        SELECT id, username
        FROM account
        WHERE id <> ?
          AND LOWER(username) NOT LIKE 'rndbot%'
        ORDER BY username ASC, id ASC
    ");
    $stmtEligible->execute([$accountId]);
    $eligibleTransferAccounts = $stmtEligible->fetchAll(PDO::FETCH_ASSOC);

    if ($selectedToolRealmId <= 0 || !isset($realmDbMap[$selectedToolRealmId])) {
        $selectedToolRealmId = $activeRealmId;
    }
    if (empty($charactersByRealm[$selectedToolRealmId])) {
        $availableRealmIds = array_keys($charactersByRealm);
        if (!empty($availableRealmIds)) {
            $selectedToolRealmId = (int)$availableRealmIds[0];
        }
    }

    $profile['is_bot_account'] = stripos((string)($profile['username'] ?? ''), 'rndbot') === 0;
    $profile['character_signatures'] = array();
    if (!empty($allUserChars)) {
        foreach ($allUserChars as $char) {
            $charGuid = (int)($char['guid'] ?? 0);
            $charName = (string)($char['name'] ?? '');
            $charRealmId = (int)($char['realm_id'] ?? $activeRealmId);
            if ($charGuid <= 0 || $charName === '') {
                continue;
            }
            $identityId = spp_ensure_char_identity($charRealmId, $charGuid, $accountId, $charName);
            $profile['character_signatures'][$charRealmId . ':' . $charGuid] = $identityId > 0 ? str_replace('<br />', '', spp_get_identity_signature($identityId)) : '';
        }
    }

    $txt = array(
        'yearlist' => "\n",
        'monthlist' => "\n",
        'daylist' => "\n",
    );
    for ($i = 1; $i <= 31; $i++) {
        $txt['daylist'] .= "<option value='$i'" . ($i == $profile['bd_day'] ? ' selected' : '') . "> $i </option>\n";
    }
    for ($i = 1; $i <= 12; $i++) {
        $txt['monthlist'] .= "<option value='$i'" . ($i == $profile['bd_month'] ? ' selected' : '') . "> $i </option>\n";
    }
    for ($i = 1950; $i <= date('Y'); $i++) {
        $txt['yearlist'] .= "<option value='$i'" . ($i == $profile['bd_year'] ? ' selected' : '') . "> $i </option>\n";
    }

    $profile['signature'] = str_replace('<br />', '', $profile['signature']);

    return array(
        'profile' => $profile,
        'allgroups' => $allgroups,
        'donator' => $donator,
        'active' => $active,
        'act' => $active,
        'userchars' => $userchars,
        'all_userchars' => $allUserChars,
        'characters_by_realm' => $charactersByRealm,
        'selected_tool_realm_id' => $selectedToolRealmId,
        'tool_realm_chars' => $charactersByRealm[$selectedToolRealmId] ?? array(),
        'onlineCharacterCount' => $onlineCharacterCount,
        'activeRealmId' => $activeRealmId,
        'eligibleTransferAccounts' => $eligibleTransferAccounts,
        'txt' => $txt,
        'pathway_info' => array(
            array('title' => $lang['users_manage'], 'link' => $comLinks['sub_members']),
            array('title' => $profile['username'], 'link' => ''),
        ),
    );
}

function spp_admin_members_build_list_view(PDO $membersPdo, $mw, int $page, array $lang)
{
    $includeBots = !isset($_GET['show_bots']) || $_GET['show_bots'] === '1';
    $conditions = array();
    $filterParams = array();

    if (!$includeBots) {
        $conditions[] = "LOWER(`username`) NOT LIKE 'rndbot%'";
    }
    if (!empty($_GET['char']) && preg_match("/[a-z]/", (string)$_GET['char'])) {
        $conditions[] = '`username` LIKE ?';
        $filterParams[] = $_GET['char'] . '%';
    } elseif (isset($_GET['char']) && $_GET['char'] == 1) {
        $conditions[] = '`username` REGEXP \'^[^A-Za-z]\'';
    }

    $filter = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
    $itemsPerPage = (int)$mw->getConfig->generic->users_per_page;

    $stmt = $membersPdo->prepare("SELECT count(*) FROM account $filter");
    $stmt->execute($filterParams);
    $itemnum = $stmt->fetchColumn();
    $pnum = ceil($itemnum / $itemsPerPage);
    $pagesStr = default_paginate($pnum, $page, "index.php?n=admin&sub=members&show_bots=" . ($includeBots ? '1' : '0') . "&char=" . ($_GET['char'] ?? ''));
    $limitStart = ($page - 1) * $itemsPerPage;

    $stmt = $membersPdo->prepare("
        SELECT * FROM account
        LEFT JOIN website_accounts ON account.id=website_accounts.account_id
        $filter
        ORDER BY username
        LIMIT " . (int)$limitStart . "," . (int)$itemsPerPage);
    $stmt->execute($filterParams);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array(
        'includeBots' => $includeBots,
        'pages_str' => $pagesStr,
        'items' => $items,
        'pathway_info' => array(
            array('title' => $lang['users_manage'], 'link' => ''),
        ),
    );
}
