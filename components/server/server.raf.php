<?php
if(INCLUDED!==true)exit;

$pathway_info[] = array('title'=>'Recruit-a-Friend', 'link' =>'');

$success = false;
$accId = 0;
if($user['id']<=0){
	redirect('index.php?n=account&sub=login',1);
}else {
	if ($_GET['rafname']) {
		$realmId  = (int)($user['cur_selected_realmd'] ?? 1);
		$charPdo  = spp_get_pdo('chars',  $realmId);
		$realmPdo = spp_get_pdo('realmd', $realmId);

		$stmt = $charPdo->prepare("SELECT account FROM characters WHERE name=?");
		$stmt->execute([$_GET['rafname']]);
		$accId = $stmt->fetchColumn();
		if ($accId) {
			$stmt = $realmPdo->prepare("SELECT referrer FROM account_raf WHERE referred=?");
			$stmt->execute([$accId]);

			$stmt = $realmPdo->prepare("DELETE FROM account_raf WHERE referrer=?");
			$stmt->execute([(int)$user['id']]);

			$stmt = $realmPdo->prepare("INSERT INTO account_raf VALUES (?, ?)");
			$stmt->execute([(int)$user['id'], $accId]);

			$success = true;
			redirect('index.php',0, 3);
		}
	}
}

?>
