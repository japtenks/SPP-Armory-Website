<?php
if(INCLUDED!==true)exit;

$pathway_info[] = array('title'=>'Recruit-a-Friend', 'link' =>'');

global $DB, $MW, $CHDB;

$success = false;
$accId = 0;
if($user['id']<=0){
	redirect('index.php?n=account&sub=login',1);
}else {
	if ($_GET['rafname']) {
		$accId = $CHDB->selectCell("SELECT account FROM characters WHERE name=?", $_GET['rafname']);
		if ($accId) {
			$refCheck = $DB->selectCell("SELECT referrer FROM account_raf WHERE referred=?", $accId);
			$DB->query("DELETE FROM account_raf WHERE referrer=?", $user['id']);
			$refQuery = $DB->query("INSERT INTO account_raf VALUES (?, ?)", $user['id'], $accId);
			$success = true;
			redirect('index.php',0, 3);
		}
	}
}

?>
