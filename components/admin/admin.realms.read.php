<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_realms_build_view(PDO $realmsPdo, array $lang)
{
    $view = array(
        'pathway_info' => array(
            array('title' => $lang['realms_manage'], 'link' => 'index.php?n=admin&sub=realms'),
        ),
        'items' => array(),
        'item' => null,
    );

    $action = (string)($_GET['action'] ?? '');
    $realmId = (int)($_GET['id'] ?? 0);

    if ($action === 'edit' && $realmId > 0) {
        $view['pathway_info'][] = array('title' => $lang['editing'], 'link' => '');
        $stmt = $realmsPdo->prepare("SELECT * FROM realmlist WHERE `id`=?");
        $stmt->execute([$realmId]);
        $view['item'] = $stmt->fetch(PDO::FETCH_ASSOC);
        return $view;
    }

    $stmt = $realmsPdo->query("SELECT * FROM realmlist ORDER BY `name`");
    $view['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $view;
}
