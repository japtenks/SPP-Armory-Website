<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_realms_handle_action(PDO $realmsPdo)
{
    $action = (string)($_GET['action'] ?? '');
    $realmId = (int)($_GET['id'] ?? 0);

    if ($action === '' || $action === '0' || $action === 'edit') {
        return;
    }

    if ($action === 'update' && $realmId > 0) {
        spp_require_csrf('admin_realms');
        $data = spp_admin_realms_normalize_fields($_POST);
        $stmt = $realmsPdo->prepare("UPDATE realmlist SET name=?,address=?,port=?,icon=?,timezone=?,ra_address=?,ra_port=?,ra_user=?,ra_pass=?,soap_address=?,soap_port=?,soap_user=?,soap_pass=?,dbinfo=? WHERE id=? LIMIT 1");
        $stmt->execute([
            $data['name'],
            $data['address'],
            $data['port'],
            $data['icon'],
            $data['timezone'],
            $data['ra_address'],
            $data['ra_port'],
            $data['ra_user'],
            $data['ra_pass'],
            $data['soap_address'],
            $data['soap_port'],
            $data['soap_user'],
            $data['soap_pass'],
            $data['dbinfo'],
            $realmId,
        ]);
        redirect('index.php?n=admin&sub=realms', 1);
        exit;
    }

    if ($action === 'create') {
        spp_require_csrf('admin_realms');
        $data = spp_admin_realms_normalize_fields($_POST);
        $stmt = $realmsPdo->prepare("INSERT INTO realmlist (name,address,port,icon,timezone,ra_address,ra_port,ra_user,ra_pass,soap_address,soap_port,soap_user,soap_pass,dbinfo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $data['name'],
            $data['address'],
            $data['port'],
            $data['icon'],
            $data['timezone'],
            $data['ra_address'],
            $data['ra_port'],
            $data['ra_user'],
            $data['ra_pass'],
            $data['soap_address'],
            $data['soap_port'],
            $data['soap_user'],
            $data['soap_pass'],
            $data['dbinfo'],
        ]);
        redirect('index.php?n=admin&sub=realms', 1);
        exit;
    }

    if ($action === 'delete' && $realmId > 0) {
        spp_require_csrf('admin_realms');
        $stmt = $realmsPdo->prepare("DELETE FROM realmlist WHERE id=? LIMIT 1");
        $stmt->execute([$realmId]);
        redirect('index.php?n=admin&sub=realms', 1);
        exit;
    }
}
