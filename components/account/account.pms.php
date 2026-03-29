<?php
if (INCLUDED !== true) exit;

// ========================================================
// Pathway setup
// ========================================================
$pathway_info[] = array(
    'title' => $lang['personal_messages'],
    'link'  => 'index.php?n=account&sub=pms'
);

// ========================================================
// Require login
// ========================================================
if ($user['id'] <= 0) {
    redirect('index.php?n=account&sub=login', 1);
    exit;
}

// ========================================================
// Default action setup
// ========================================================
if (empty($_GET['action'])) {
    $_GET['action'] = 'view';
    $_GET['dir']    = 'all';
}

$items          = array();
$threadItems    = array();
$threadPeer     = null;
$items_per_page = 16;
$page           = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit_start    = ($page - 1) * $items_per_page;
$pmsPdo         = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));

// ========================================================
// VIEW MESSAGE TIMELINE
// ========================================================
if ($_GET['action'] == 'view') {
    $_GET['dir'] = 'all';
    $pathway_info[] = array('title' => 'Messages', 'link' => '');

    $stmt = $pmsPdo->prepare("
        SELECT
            pms.*,
            s.username AS sender,
            r.username AS receiver,
            CASE
                WHEN pms.owner_id = ? THEN 'in'
                ELSE 'out'
            END AS pm_box
        FROM website_pms AS pms
        LEFT JOIN account AS s ON pms.sender_id = s.id
        LEFT JOIN account AS r ON pms.owner_id = r.id
        WHERE pms.owner_id = ? OR pms.sender_id = ?
        ORDER BY pms.posted DESC
    ");
    $stmt->execute([(int)$user['id'], (int)$user['id'], (int)$user['id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Batch-resolve identity display names (for bot/character senders).
    $allIdentityIds = [];
    foreach ($rows as $row) {
        if (!empty($row['sender_identity_id']))    $allIdentityIds[] = (int)$row['sender_identity_id'];
        if (!empty($row['recipient_identity_id'])) $allIdentityIds[] = (int)$row['recipient_identity_id'];
    }
    $identityNames = function_exists('spp_resolve_identity_names')
        ? spp_resolve_identity_names($allIdentityIds)
        : [];

    $conversationMap = array();
    foreach ($rows as $row) {
        $isIncoming = (($row['pm_box'] ?? '') === 'in');
        $peerId = $isIncoming ? (int)($row['sender_id'] ?? 0) : (int)($row['owner_id'] ?? 0);

        // Prefer identity display_name, fall back to account username.
        if ($isIncoming) {
            $identId  = (int)($row['sender_identity_id'] ?? 0);
            $peerName = $identId && isset($identityNames[$identId])
                ? $identityNames[$identId]
                : (string)($row['sender'] ?? '');
        } else {
            $identId  = (int)($row['recipient_identity_id'] ?? 0);
            $peerName = $identId && isset($identityNames[$identId])
                ? $identityNames[$identId]
                : (string)($row['receiver'] ?? '');
        }

        if ($peerId <= 0 || $peerName === '') {
            continue;
        }

        if (!isset($conversationMap[$peerId])) {
            $conversationMap[$peerId] = array(
                'peer_id' => $peerId,
                'peer_name' => $peerName,
                'latest_id' => (int)$row['id'],
                'latest_message' => (string)($row['message'] ?? ''),
                'latest_posted' => (int)($row['posted'] ?? 0),
                'latest_box' => (string)($row['pm_box'] ?? 'in'),
                'unread_count' => 0,
            );
        }

        if ($isIncoming && empty($row['showed'])) {
            $conversationMap[$peerId]['unread_count']++;
        }
    }

    $items = array_values($conversationMap);
    $itemnum = count($items);
    $pnum = (int)ceil(max(1, $itemnum) / $items_per_page);
    if ($limit_start > 0 || $items_per_page > 0) {
        $items = array_slice($items, $limit_start, $items_per_page);
    }
}

// ========================================================
// DELETE MESSAGES
// ========================================================
elseif (
    $_GET['action'] == 'delete'
    && in_array($_GET['dir'], array('in', 'out'))
    && isset($_POST['deletem'])
    && is_array($_POST['checkpm'])
) {
    $ids = array_map('intval', (array)$_POST['checkpm']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    if ($_GET['dir'] == 'in') {
        // Delete messages RECEIVED
        $stmt = $pmsPdo->prepare("DELETE FROM website_pms WHERE owner_id = ? AND id IN ($placeholders)");
        $stmt->execute(array_merge([(int)$user['id']], $ids));
    } else {
        // Delete messages SENT
        $stmt = $pmsPdo->prepare("DELETE FROM website_pms WHERE sender_id = ? AND id IN ($placeholders)");
        $stmt->execute(array_merge([(int)$user['id']], $ids));
    }

    redirect('index.php?n=account&sub=pms&action=view&dir=' . $_GET['dir'], 1);
    exit;
}

// ========================================================
// MARK MESSAGE AS READ
// ========================================================
elseif ($_GET['action'] == 'markread' && $_GET['dir'] == 'in' && !empty($_GET['iid'])) {
    $stmt = $pmsPdo->prepare("
        UPDATE website_pms
        SET showed = 1
        WHERE owner_id = ? AND id = ?
        LIMIT 1
    ");
    $stmt->execute([(int)$user['id'], (int)$_GET['iid']]);
    redirect('index.php?n=account&sub=pms&action=view', 1);
    exit;
}

// ========================================================
// VIEW SINGLE MESSAGE
// ========================================================
elseif ($_GET['action'] == 'viewpm' && isset($_GET['iid'])) {
    $_GET['dir'] = 'all';
    $pathway_info[] = array('title' => 'Messages', 'link' => 'index.php?n=account&sub=pms&action=view');

    $stmt = $pmsPdo->prepare("
        SELECT
            pms.*,
            s.username AS sender,
            r.username AS receiver,
            CASE
                WHEN pms.owner_id = ? THEN 'in'
                ELSE 'out'
            END AS pm_box
        FROM website_pms AS pms
        LEFT JOIN account AS s ON pms.sender_id = s.id
        LEFT JOIN account AS r ON pms.owner_id = r.id
        WHERE (pms.owner_id = ? OR pms.sender_id = ?)
          AND pms.id = ?
        LIMIT 1
    ");
    $stmt->execute([(int)$user['id'], (int)$user['id'], (int)$user['id'], (int)$_GET['iid']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $threadPeerId = ((string)($item['pm_box'] ?? '') === 'in')
            ? (int)($item['sender_id'] ?? 0)
            : (int)($item['owner_id'] ?? 0);

        // Prefer identity display_name for the peer name in the thread header.
        if ((string)($item['pm_box'] ?? '') === 'in') {
            $identId = (int)($item['sender_identity_id'] ?? 0);
            $_viewPeerName = $identId && function_exists('spp_resolve_identity_names')
                ? (spp_resolve_identity_names([$identId])[$identId] ?? (string)($item['sender'] ?? ''))
                : (string)($item['sender'] ?? '');
        } else {
            $identId = (int)($item['recipient_identity_id'] ?? 0);
            $_viewPeerName = $identId && function_exists('spp_resolve_identity_names')
                ? (spp_resolve_identity_names([$identId])[$identId] ?? (string)($item['receiver'] ?? ''))
                : (string)($item['receiver'] ?? '');
        }
        $threadPeer = $_viewPeerName;

        if ($threadPeerId > 0 && !empty($_POST['reply_message'])) {
            $replyMessage = trim((string)$_POST['reply_message']);
            if ($replyMessage !== '') {
                // Resolve identity IDs for this reply.
                $replyRealmId = spp_resolve_realm_id($realmDbMap);
                $replySenderIdentId    = null;
                $replyRecipientIdentId = null;
                if (function_exists('spp_ensure_account_identity')) {
                    $replySenderIdentId = spp_ensure_account_identity($replyRealmId, (int)$user['id'], $user['username']) ?: null;
                    $stmtRpName = $pmsPdo->prepare("SELECT username FROM account WHERE id=? LIMIT 1");
                    $stmtRpName->execute([$threadPeerId]);
                    $rpUsername = (string)($stmtRpName->fetchColumn() ?: '');
                    if ($rpUsername !== '') {
                        $replyRecipientIdentId = spp_ensure_account_identity($replyRealmId, $threadPeerId, $rpUsername) ?: null;
                    }
                }

                $stmtReply = $pmsPdo->prepare("
                    INSERT INTO website_pms
                        (owner_id, subject, message, sender_id, sender_identity_id, recipient_identity_id, posted, sender_ip, showed)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, 0)
                ");
                $stmtReply->execute([
                    $threadPeerId,
                    '',
                    $replyMessage,
                    (int)$user['id'],
                    $replySenderIdentId,
                    $replyRecipientIdentId,
                    time(),
                    $_SERVER['REMOTE_ADDR'] ?? ''
                ]);

                $newPmId = (int)$pmsPdo->lastInsertId();
                if ($newPmId > 0) {
                    redirect('index.php?n=account&sub=pms&action=viewpm&iid=' . $newPmId, 1);
                    exit;
                }
                redirect('index.php?n=account&sub=pms&action=viewpm&iid=' . (int)$_GET['iid'], 1);
                exit;
            }
        }

        if ($threadPeerId > 0) {
            $stmtThread = $pmsPdo->prepare("
                SELECT
                    pms.*,
                    s.username AS sender,
                    r.username AS receiver,
                    CASE
                        WHEN pms.owner_id = ? THEN 'in'
                        ELSE 'out'
                    END AS pm_box
                FROM website_pms AS pms
                LEFT JOIN account AS s ON pms.sender_id = s.id
                LEFT JOIN account AS r ON pms.owner_id = r.id
                WHERE (pms.owner_id = ? AND pms.sender_id = ?)
                   OR (pms.sender_id = ? AND pms.owner_id = ?)
                ORDER BY pms.posted ASC, pms.id ASC
            ");
            $stmtThread->execute([
                (int)$user['id'],
                (int)$user['id'],
                $threadPeerId,
                (int)$user['id'],
                $threadPeerId
            ]);
            $threadItems = $stmtThread->fetchAll(PDO::FETCH_ASSOC);

            // Batch-resolve identity display names for this thread.
            $threadIdentityIds = [];
            foreach ($threadItems as $tRow) {
                if (!empty($tRow['sender_identity_id']))    $threadIdentityIds[] = (int)$tRow['sender_identity_id'];
                if (!empty($tRow['recipient_identity_id'])) $threadIdentityIds[] = (int)$tRow['recipient_identity_id'];
            }
            $threadIdentityNames = function_exists('spp_resolve_identity_names')
                ? spp_resolve_identity_names($threadIdentityIds)
                : [];
            foreach ($threadItems as &$tRow) {
                $sIdentId = (int)($tRow['sender_identity_id'] ?? 0);
                $tRow['sender_display'] = $sIdentId && isset($threadIdentityNames[$sIdentId])
                    ? $threadIdentityNames[$sIdentId]
                    : (string)($tRow['sender'] ?? '');
                $rIdentId = (int)($tRow['recipient_identity_id'] ?? 0);
                $tRow['receiver_display'] = $rIdentId && isset($threadIdentityNames[$rIdentId])
                    ? $threadIdentityNames[$rIdentId]
                    : (string)($tRow['receiver'] ?? '');
            }
            unset($tRow);

            $stmtMarkThreadRead = $pmsPdo->prepare("
                UPDATE website_pms
                SET showed = 1
                WHERE owner_id = ? AND sender_id = ? AND showed = 0
            ");
            $stmtMarkThreadRead->execute([(int)$user['id'], $threadPeerId]);

            foreach ($threadItems as &$threadRow) {
                if ((int)($threadRow['owner_id'] ?? 0) === (int)$user['id']) {
                    $threadRow['showed'] = 1;
                }
            }
            unset($threadRow);
        }
    }

    $pathway_info[] = array('title' => ($threadPeer ?: $lang['post_view']), 'link' => '');
}

// ========================================================
// ADD / SEND / REPLY
// ========================================================
elseif ($_GET['action'] == 'add') {

    $content = array('message' => '', 'sender' => '');
    $pmRecipientOptions = array();
    $isReplyMode = !empty($_GET['reply']);

    try {
        $stmtRecipientCount = $pmsPdo->prepare("
            SELECT COUNT(*)
            FROM account
            LEFT JOIN website_accounts ON account.id = website_accounts.account_id
            WHERE account.id <> ?
              AND LOWER(account.username) NOT LIKE 'rndbot%'
              AND (website_accounts.hideprofile IS NULL OR website_accounts.hideprofile = 0)
        ");
        $stmtRecipientCount->execute([(int)$user['id']]);
        $recipientCount = (int)$stmtRecipientCount->fetchColumn();

        if ($recipientCount > 0 && $recipientCount < 20) {
            $stmtRecipients = $pmsPdo->prepare("
                SELECT account.username
                FROM account
                LEFT JOIN website_accounts ON account.id = website_accounts.account_id
                WHERE account.id <> ?
                  AND LOWER(account.username) NOT LIKE 'rndbot%'
                  AND (website_accounts.hideprofile IS NULL OR website_accounts.hideprofile = 0)
                ORDER BY account.username ASC
            ");
            $stmtRecipients->execute([(int)$user['id']]);
            $pmRecipientOptions = $stmtRecipients->fetchAll(PDO::FETCH_COLUMN, 0);
        }
    } catch (Throwable $e) {
        error_log('[account.pms] Recipient picker lookup failed: ' . $e->getMessage());
    }

    if (!empty($_POST['owner']) && !empty($_POST['message'])) {

        $message   = trim((string)$_POST['message']);
        $sender_id = $user['id'];
        $sender_ip = $_SERVER['REMOTE_ADDR'];

        // Lookup recipient from account table
        $stmt = $pmsPdo->prepare("SELECT id FROM account WHERE username = ? LIMIT 1");
        $stmt->execute([$_POST['owner']]);
        $owner_id = (int)$stmt->fetchColumn();

        if ($owner_id > 0) {
            // Resolve (or ensure) identity IDs for sender and recipient.
            $sendRealmId = spp_resolve_realm_id($realmDbMap);
            $pmSenderIdentId    = null;
            $pmRecipientIdentId = null;
            if (function_exists('spp_ensure_account_identity')) {
                $pmSenderIdentId    = spp_ensure_account_identity($sendRealmId, (int)$sender_id, $user['username']) ?: null;
                // Look up recipient username for the identity display name.
                $stmtRName = $pmsPdo->prepare("SELECT username FROM account WHERE id=? LIMIT 1");
                $stmtRName->execute([$owner_id]);
                $recipientUsername = (string)($stmtRName->fetchColumn() ?: '');
                if ($recipientUsername !== '') {
                    $pmRecipientIdentId = spp_ensure_account_identity($sendRealmId, $owner_id, $recipientUsername) ?: null;
                }
            }

            $stmt = $pmsPdo->prepare("
                INSERT INTO website_pms
                    (owner_id, subject, message, sender_id, sender_identity_id, recipient_identity_id, posted, sender_ip, showed)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$owner_id, '', $message, (int)$sender_id, $pmSenderIdentId, $pmRecipientIdentId, time(), $sender_ip]);

            output_message('notice', $lang['post_sent']);
            redirect('index.php?n=account&sub=pms&action=view', 1);
            exit;

        } else {
            output_message('alert', $lang['no_such_addr']);
        }
    }

    // --- Reply logic ---
if ($isReplyMode) {
    $stmt = $pmsPdo->prepare("
        SELECT pms.*, s.username AS sender, r.username AS receiver
        FROM website_pms AS pms
        LEFT JOIN account AS s ON pms.sender_id = s.id
        LEFT JOIN account AS r ON pms.owner_id = r.id
        WHERE pms.id = ?
        LIMIT 1
    ");
    $stmt->execute([(int)$_GET['reply']]);
    $content = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($content) {
        if ((int)($content['owner_id'] ?? 0) === (int)$user['id'] && empty($content['showed'])) {
            $stmtMarkReplyRead = $pmsPdo->prepare("UPDATE website_pms SET showed = 1 WHERE id = ? AND owner_id = ? LIMIT 1");
            $stmtMarkReplyRead->execute([(int)$content['id'], (int)$user['id']]);
            $content['showed'] = 1;
        }
        // reply always goes to original sender
        $content['sender'] = $content['sender'];
        $content['message'] = '';
    }
}
else {
        $pathway_info[] = array('title' => $lang['newmessage'], 'link' => '');
        if (!empty($_GETVARS['to'])) $content['sender'] = RemoveXSS($_GETVARS['to']);
    }
}
?>
