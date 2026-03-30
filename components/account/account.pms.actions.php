<?php

function spp_account_pms_handle_action(array $ctx): bool
{
    $action = (string)($ctx['action'] ?? '');

    if (
        $action === 'delete'
        && in_array(($ctx['dir'] ?? ''), array('in', 'out'), true)
        && isset($_POST['deletem'])
        && is_array($_POST['checkpm'])
    ) {
        spp_account_pms_delete_messages($ctx);
        return true;
    }

    if ($action === 'markread' && ($ctx['dir'] ?? '') === 'in' && !empty($_GET['iid'])) {
        spp_account_pms_mark_read($ctx);
        return true;
    }

    if ($action === 'viewpm' && isset($_GET['iid']) && !empty($_POST['reply_message'])) {
        spp_account_pms_reply_in_thread($ctx);
        return true;
    }

    if ($action === 'add' && !empty($_POST['owner']) && !empty($_POST['message'])) {
        spp_account_pms_send_new_message($ctx);
        return true;
    }

    return false;
}

function spp_account_pms_delete_messages(array $ctx): void
{
    spp_require_csrf('account_pms');

    $pmsPdo = $ctx['pmsPdo'];
    $user = $ctx['user'];
    $dir = $ctx['dir'];
    $ids = array_map('intval', (array)$_POST['checkpm']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    if ($dir === 'in') {
        $stmt = $pmsPdo->prepare("DELETE FROM website_pms WHERE owner_id = ? AND id IN ($placeholders)");
        $stmt->execute(array_merge([(int)$user['id']], $ids));
    } else {
        $stmt = $pmsPdo->prepare("DELETE FROM website_pms WHERE sender_id = ? AND id IN ($placeholders)");
        $stmt->execute(array_merge([(int)$user['id']], $ids));
    }

    redirect('index.php?n=account&sub=pms&action=view&dir=' . $dir, 1);
    exit;
}

function spp_account_pms_mark_read(array $ctx): void
{
    spp_require_csrf('account_pms');

    $pmsPdo = $ctx['pmsPdo'];
    $user = $ctx['user'];
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

function spp_account_pms_reply_in_thread(array $ctx): void
{
    spp_require_csrf('account_pms');

    $pmsPdo = $ctx['pmsPdo'];
    $user = $ctx['user'];
    $realmDbMap = $ctx['realmDbMap'];

    $stmt = $pmsPdo->prepare("
        SELECT
            pms.*,
            CASE
                WHEN pms.owner_id = ? THEN 'in'
                ELSE 'out'
            END AS pm_box
        FROM website_pms AS pms
        WHERE (pms.owner_id = ? OR pms.sender_id = ?)
          AND pms.id = ?
        LIMIT 1
    ");
    $stmt->execute([(int)$user['id'], (int)$user['id'], (int)$user['id'], (int)$_GET['iid']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        return;
    }

    $threadPeerId = ((string)($item['pm_box'] ?? '') === 'in')
        ? (int)($item['sender_id'] ?? 0)
        : (int)($item['owner_id'] ?? 0);

    $replyMessage = trim((string)$_POST['reply_message']);
    if ($threadPeerId <= 0 || $replyMessage === '') {
        return;
    }

    $replyRealmId = spp_resolve_realm_id($realmDbMap);
    $replySenderIdentId = null;
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

function spp_account_pms_send_new_message(array $ctx): void
{
    global $lang;
    spp_require_csrf('account_pms');

    $pmsPdo = $ctx['pmsPdo'];
    $user = $ctx['user'];
    $realmDbMap = $ctx['realmDbMap'];

    $message = trim((string)$_POST['message']);
    $sender_id = $user['id'];
    $sender_ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $pmsPdo->prepare("SELECT id FROM account WHERE username = ? LIMIT 1");
    $stmt->execute([$_POST['owner']]);
    $owner_id = (int)$stmt->fetchColumn();

    if ($owner_id > 0) {
        $sendRealmId = spp_resolve_realm_id($realmDbMap);
        $pmSenderIdentId = null;
        $pmRecipientIdentId = null;
        if (function_exists('spp_ensure_account_identity')) {
            $pmSenderIdentId = spp_ensure_account_identity($sendRealmId, (int)$sender_id, $user['username']) ?: null;
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
    }

    output_message('alert', $lang['no_such_addr']);
}
