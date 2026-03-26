<?php
if(INCLUDED!==true)exit;
// ==================== //
$pathway_info[] = array('title'=>$lang['realms_manage'],'link'=>'index.php?n=admin&sub=realms');
// ==================== //
$realm_type_def = array(
    0 => 'Normal',
    1 => 'PVP',
    4 => 'Normal',
    6 => 'RP',
    8 => 'RPPVP',
    16 => 'FFA_PVP'
);
$realm_timezone_def = array(
     0 => 'Unknown',
     1 => 'Development',
     2 => 'United States',
     3 => 'Oceanic',
     4 => 'Latin America',
     5 => 'Tournament',
     6 => 'Korea',
     7 => 'Tournament',
     8 => 'English',
     9 => 'German',
    10 => 'French',
    11 => 'Spanish',
    12 => 'Russian',
    13 => 'Tournament',
    14 => 'Taiwan',
    15 => 'Tournament',
    16 => 'China',
    17 => 'CN1',
    18 => 'CN2',
    19 => 'CN3',
    20 => 'CN4',
    21 => 'CN5',
    22 => 'CN6',
    23 => 'CN7',
    24 => 'CN8',
    25 => 'Tournament',
    26 => 'Test Server',
    27 => 'Tournament',
    28 => 'QA Server',
    29 => 'CN9',
);

$realmsPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));

if(!$_GET['action']){

    $items = $realmsPdo->query("SELECT * FROM realmlist ORDER BY `name`")->fetchAll(PDO::FETCH_ASSOC);

}elseif($_GET['action']=='edit' && $_GET['id']){
    $pathway_info[] = array('title'=>$lang['editing'],'link'=>'');
    $stmtEr = $realmsPdo->prepare("SELECT * FROM realmlist WHERE `id`=?");
    $stmtEr->execute([(int)$_GET['id']]);
    $item = $stmtEr->fetch(PDO::FETCH_ASSOC);
}elseif($_GET['action']=='update' && $_GET['id']){
    $stmtUr = $realmsPdo->prepare("UPDATE realmlist SET name=?,address=?,port=?,icon=?,timezone=?,ra_address=?,ra_port=?,ra_user=?,ra_pass=?,soap_address=?,soap_port=?,soap_user=?,soap_pass=?,dbinfo=? WHERE id=? LIMIT 1");
    $stmtUr->execute([
        $_POST['name'] ?? '',
        $_POST['address'] ?? '',
        (int)($_POST['port'] ?? 0),
        (int)($_POST['icon'] ?? 0),
        (int)($_POST['timezone'] ?? 0),
        $_POST['ra_address'] ?? '',
        (int)($_POST['ra_port'] ?? 0),
        $_POST['ra_user'] ?? '',
        $_POST['ra_pass'] ?? '',
        $_POST['soap_address'] ?? '127.0.0.1',
        (int)($_POST['soap_port'] ?? 7878),
        $_POST['soap_user'] ?? '',
        $_POST['soap_pass'] ?? '',
        $_POST['dbinfo'] ?? '',
        (int)$_GET['id'],
    ]);
    redirect('index.php?n=admin&sub=realms',1);
}elseif($_GET['action']=='create'){
    $stmtCr = $realmsPdo->prepare("INSERT INTO realmlist (name,address,port,icon,timezone,ra_address,ra_port,ra_user,ra_pass,soap_address,soap_port,soap_user,soap_pass,dbinfo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmtCr->execute([
        $_POST['name'] ?? '',
        $_POST['address'] ?? '',
        (int)($_POST['port'] ?? 0),
        (int)($_POST['icon'] ?? 0),
        (int)($_POST['timezone'] ?? 0),
        $_POST['ra_address'] ?? '',
        (int)($_POST['ra_port'] ?? 0),
        $_POST['ra_user'] ?? '',
        $_POST['ra_pass'] ?? '',
        $_POST['soap_address'] ?? '127.0.0.1',
        (int)($_POST['soap_port'] ?? 7878),
        $_POST['soap_user'] ?? '',
        $_POST['soap_pass'] ?? '',
        $_POST['dbinfo'] ?? '',
    ]);
    redirect('index.php?n=admin&sub=realms',1);
}elseif($_GET['action']=='delete' && $_GET['id']){
    $stmtDr = $realmsPdo->prepare("DELETE FROM realmlist WHERE id=? LIMIT 1");
    $stmtDr->execute([(int)$_GET['id']]);
    redirect('index.php?n=admin&sub=realms',1);
}

?>
