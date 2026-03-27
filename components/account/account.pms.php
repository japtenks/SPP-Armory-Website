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
    $_GET['dir']    = 'in';
}

$items          = array();
$items_per_page = 16;
$page           = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit_start    = ($page - 1) * $items_per_page;
$pmsPdo         = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));

// ========================================================
// VIEW INBOX / OUTBOX
// ========================================================
if ($_GET['action'] == 'view') {

    if ($_GET['dir'] == 'in') {
        // --- INBOX ---
        $pathway_info[] = array('title' => $lang['inbox'], 'link' => '');


        $stmt = $pmsPdo->prepare("
            SELECT COUNT(1)
            FROM website_pms
            WHERE owner_id = ?
        ");
        $stmt->execute([(int)$user['id']]);
        $itemnum = $stmt->fetchColumn();
        $pnum = ceil($itemnum / $items_per_page);

        $stmt = $pmsPdo->prepare("
            SELECT pms.*, s.username AS sender
            FROM website_pms AS pms
            LEFT JOIN account AS s ON pms.sender_id = s.id
            WHERE pms.owner_id = ?
            ORDER BY posted DESC
            LIMIT " . (int)$limit_start . "," . (int)$items_per_page . "
        ");
        $stmt->execute([(int)$user['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($_GET['dir'] == 'out') {
        // --- OUTBOX ---
        $pathway_info[] = array('title' => $lang['outbox'], 'link' => '');

        $stmt = $pmsPdo->prepare("
            SELECT COUNT(1)
            FROM website_pms
            WHERE sender_id = ?
        ");
        $stmt->execute([(int)$user['id']]);
        $itemnum = $stmt->fetchColumn();
        $pnum = ceil($itemnum / $items_per_page);

        $stmt = $pmsPdo->prepare("
            SELECT pms.*, r.username AS `for`
            FROM website_pms AS pms
            LEFT JOIN account AS r ON pms.owner_id = r.id
            WHERE pms.sender_id = ?
            ORDER BY posted DESC
            LIMIT " . (int)$limit_start . "," . (int)$items_per_page . "
        ");
        $stmt->execute([(int)$user['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
// VIEW SINGLE MESSAGE
// ========================================================
elseif ($_GET['action'] == 'viewpm' && isset($_GET['iid'])) {

    if ($_GET['dir'] == 'in') {
        // --- Viewing a received message ---
        $pathway_info[] = array('title' => $lang['inbox'], 'link' => 'index.php?n=account&sub=pms&action=view&dir=in');
 

        $stmt = $pmsPdo->prepare("
            SELECT pms.*, s.username AS sender, r.username AS receiver
            FROM website_pms AS pms
            LEFT JOIN account AS s ON pms.sender_id = s.id
            LEFT JOIN account AS r ON pms.owner_id = r.id
            WHERE pms.owner_id = ? AND pms.id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$user['id'], (int)$_GET['iid']]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        // Mark as read
        if ($item && empty($item['showed'])) {
            $stmt = $pmsPdo->prepare("UPDATE website_pms SET showed = 1 WHERE id = ?");
            $stmt->execute([(int)$item['id']]);
        }

    } elseif ($_GET['dir'] == 'out') {
        // --- Viewing a sent message ---
        $pathway_info[] = array('title' => $lang['outbox'], 'link' => 'index.php?n=account&sub=pms&action=view&dir=out');

        $stmt = $pmsPdo->prepare("
            SELECT pms.*, s.username AS sender, r.username AS receiver
            FROM website_pms AS pms
            LEFT JOIN account AS s ON pms.sender_id = s.id
            LEFT JOIN account AS r ON pms.owner_id = r.id
            WHERE pms.sender_id = ? AND pms.id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$user['id'], (int)$_GET['iid']]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $pathway_info[] = array('title' => $lang['post_view'], 'link' => '');
}

// ========================================================
// ADD / SEND / REPLY
// ========================================================
elseif ($_GET['action'] == 'add') {

    $content = array('message' => '', 'subject' => '', 'sender' => '');

    if (!empty($_POST['owner']) && !empty($_POST['title']) && !empty($_POST['message'])) {

        $title     = trim($_POST['title']);
        $message   = my_preview($_POST['message']);
        $sender_id = $user['id'];
        $sender_ip = $_SERVER['REMOTE_ADDR'];

        // Lookup recipient from account table
        $stmt = $pmsPdo->prepare("SELECT id FROM account WHERE username = ? LIMIT 1");
        $stmt->execute([$_POST['owner']]);
        $owner_id = (int)$stmt->fetchColumn();

        if ($owner_id > 0) {
            $stmt = $pmsPdo->prepare("
                INSERT INTO website_pms
                    (owner_id, subject, message, sender_id, posted, sender_ip, showed)
                VALUES
                    (?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$owner_id, $title, $message, (int)$sender_id, time(), $sender_ip]);

            output_message('notice', $lang['post_sent']);
            redirect('index.php?n=account&sub=pms&action=view&dir=out', 1);
            exit;

        } else {
            output_message('alert', $lang['no_such_addr']);
        }
    }

    // --- Reply logic ---
if (!empty($_GET['reply'])) {
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
        // reply always goes to original sender
        $content['sender'] = $content['sender'];
        $content['subject'] = '[re:] ' . $content['subject'];
        $content['message'] =
          '[blockquote="' . $content['sender'] . ' | ' .
          date('d-m-Y, H:i:s', $content['posted']) . '"] ' .
          my_previewreverse($content['message']) . '[/blockquote]';
    }
}
else {
        $pathway_info[] = array('title' => $lang['newmessage'], 'link' => '');
        if (!empty($_GETVARS['to'])) $content['sender'] = RemoveXSS($_GETVARS['to']);
        if (!empty($_GETVARS['topic'])) $content['subject'] = RemoveXSS($_GETVARS['topic']);
    }
}
?>
