<Br />
<?php builddiv_start(1, "Character Tools") ?>
<style>
div.errorMsg { width: 60%; height: 30px; line-height: 30px; font-size: 10pt; border: 2px solid #e03131; background: #ff9090;}
</style>
<style type="text/css">
  a.server { border-style: solid; border-width: 0px 1px 1px 0px; border-color: #D8BF95; font-weight: bold; }
  td.serverStatus1 { font-size: 0.8em; border-style: solid; border-width: 0px 1px 1px 0px; border-color: #D8BF95; }
  td.serverStatus2 { font-size: 0.8em; border-style: solid; border-width: 0px 1px 1px 0px; border-color: #D8BF95; background-color: #C3AD89; }
  td.rankingHeader { color: #C7C7C7; font-size: 10pt; font-family: arial,helvetica,sans-serif; font-weight: bold; background-color: #2E2D2B; border-style: solid; border-width: 1px; border-color: #5D5D5D #5D5D5D #1E1D1C #1E1D1C; padding: 3px;}
</style>
<!-- Character Tools Description -->
<table width = "510" cellspacing = "0" cellpadding = "0" border = "0">
<tr>

        <td>
        <span>
   <?php echo add_pictureletter("Here is where you are able to edit your characters"); ?>
        </span>
        </td>

</tr>
<tr>
	<td><center>You have <font color=blue><u><?php echo $your_points; ?></u></font> <?php echo $lang['vote_points']; ?>
</table>
<br />

<!-- Character Unstuck Tool -->
<?php write_subheader("Character Un-Stuck Tool"); ?>
<center>
<table width = "580" style = "border-width: 1px; border-style: dotted; border-color: #928058;"><tr><td><table style = "border-width: 1px; border-style: solid; border-color: black; background-image: url('<?php echo $currtmp; ?>/images/light3.jpg');"><tr><td>

<table border=0 cellspacing=0 cellpadding=4 width="580px">
<tr>
<td>
<form action="index.php?n=account&sub=chartools" method="post">
<center>
<table width="300" border="0" cellpadding="2px">
  <tr>
  <td><?php echo $lang['charname']; ?></td>
  <td>
    <select name="name">
<?php

$stmtCt1 = $charPdoCt->prepare("SELECT * FROM `characters` WHERE account=?");
$stmtCt1->execute([(int)$user['id']]);
$qray = $stmtCt1->fetchAll(PDO::FETCH_ASSOC);
foreach($qray as $c){
        echo "<option value='".htmlspecialchars($c['name'])."'>".htmlspecialchars($c['name'])."</option>";
}

?>
</select>
</td>
  </tr>
  <td colspan='2' align='center'>
        <input type='submit' name='unstuck' value='Reset Position'  />
  </td>
</table>
</center>
</form>
<?php
if (isset($_POST['unstuck'])) {
    $name = $_POST['name'];
    $stmtRace = $charPdoCt->prepare("SELECT race FROM characters WHERE name=?");
    $stmtRace->execute([$name]);
    $race = $stmtRace->fetchColumn();
    $isalliance = isAlliance($race);
    $status = check_if_online($name, $charPdoCt);
    if ($status == -1) {
        echo "<p align='center'><font color='red'>The character doesnt exsist
                </font></p>";
        exit();
    }
	if ($status == 1)
		echo "<p align='center'><font color='red'>This character is online. Please try again later</font></p>";
	else {
		if($isalliance==true) {
			$stmtUs1 = $charPdoCt->prepare("UPDATE characters SET position_x = -8913.23, position_y = 554.633, position_z = 93.7944, map = 0, zone = 1519 WHERE name=?");
			$stmtUs1->execute([$name]);
			echo "<p align='center'><font color='blue'>Success! Character " .htmlspecialchars($name)." Has been teleported to Stormwind</font></p>";
		}else{
			$stmtUs2 = $charPdoCt->prepare("UPDATE characters SET position_x = 1440.45, position_y = -4422.78, position_z = 25.4634, map = 1, zone = 1637 WHERE name=?");
			$stmtUs2->execute([$name]);
			echo "<p align='center'><font color='blue'>Success! Character " .htmlspecialchars($name)." Has been teleported to Orgrimar</font></p>";
		}
	}
}
?>
</td>
</tr>
</table>
</td></tr></table>
</td></tr></table>
</center>
<br />

<!-- Character Rename -->
<?php write_subheader("Character Re-name"); ?>
<center>
<table width = "580" style = "border-width: 1px; border-style: dotted; border-color: #928058;"><tr><td><table style = "border-width: 1px; border-style: solid; border-color: black; background-image: url('<?php echo $currtmp; ?>/images/light3.jpg');"><tr><td>

<table border=0 cellspacing=0 cellpadding=4 width="580px">
<tr>
<td>
<form action="index.php?n=account&sub=chartools" method="post">
<center>
<?php
if ($show_rename == false){
?>
<center>
<div class="errorMsg"><b><?php echo $lang['chat_disable'] ?></b></div>
</center>
<?php
}else{
?>
<table width="550" border="0" cellpadding="2px">
  <tr>
	<td><center><?php echo $lang['rename_desc1']; ?></center>
	</td>
  </tr>
</table>
<br />
<?php
		if($char_rename_points > $your_points){
			$disabledr = 1;
			echo "<font color=\"red\"><center>You do not have enough points to rename a character!!</center></font>";
		}else{
			$disabledr = 0;

		}
?>
<table width="300" border="0" cellpadding="2px">
  <tr>
  <td><?php echo $lang['charname']; ?></td>
  <td>
    <select name="name">
<?php

$stmtCt2 = $charPdoCt->prepare("SELECT * FROM `characters` WHERE account=?");
$stmtCt2->execute([(int)$user['id']]);
$qray = $stmtCt2->fetchAll(PDO::FETCH_ASSOC);
foreach($qray as $c){
        echo "<option value='".htmlspecialchars($c['name'])."'>".htmlspecialchars($c['name'])."</option>";
}

?>
</select>
</td>
  </tr>
  <tr>
    <td><?php echo $lang['desired_name']; ?></td>
    <td><input type='text' name='newname' maxlength='20' size='20'/></td>
  </tr>
  <tr>
	<td colspan='2' align='center'>Cost: <font color=blue><u><?php echo $char_rename_points; ?></u></font> <?php echo $lang['vote_points']; ?>
	</td>
  </tr>
  <?php if ($disabledr == 0){ ?>
  <td colspan='2' align='center'>
                        <input type='submit' name='rename' value='Rename'  />
  </td>
  <?php }else{ ?>
    <td colspan='2' align='center'>
                        <input type='submit' name='rename' value='Rename' disabled='disabled' />
  </td>
  <?php } ?>
</table>
</center>
<?php } ?>
</form>
<?php
if (isset($_POST['rename'])) {
    if (($_POST['name']) == '' or ($_POST['newname']) == '') {
        echo "<p align='center'><font color='red'>Please enter a New Name</font></p>";
            exit();
        }
        $name = $_POST['name'];
        $newname = ucfirst(strtolower(trim($_POST['newname'])));
        $status = check_if_online($name, $charPdoCt);
        $newname_exist = check_if_name_exist($newname, $charPdoCt);
        if ($status == -1) {
            echo "<p align='center'><font color='red'>The character doesnt exsist
                </font></p>";
            exit();
        }
        if ($newname_exist == 1) {
            echo "<p align='center'><font color='red'>The character already exsists, please choose a different name!</font></p>";
            exit();
        }
    if ($status == 1)
        echo "<p align='center'><font color='red'>This character is online. Please try again later</font></p>";
    else {
            change_name($name, $newname, $account_id, $charPdoCt);
            $stmtRnPts = $realmPdoCt->prepare("UPDATE `voting_points` SET `points`=(`points` - ?), `points_spent`=(`points_spent` + ?) WHERE id=?");
            $stmtRnPts->execute([$char_rename_points, $char_rename_points, (int)$account_id]);
        echo "<p align='center'><font color='blue'>Success! Character " .htmlspecialchars($name)." renamed to " .htmlspecialchars($newname). "</font></p>";
    }
}
?>
</td>
</tr>
</table>
</td></tr></table>
</td></tr></table>
</center>
<br />
<center>
<!-- CHARACTER RECUSOMTIZATION -->
<br />
<?php write_subheader("Character Re-Customization"); ?>
<center>
<table width = "580" style = "border-width: 1px; border-style: dotted; border-color: #928058;"><tr><td><table style = "border-width: 1px; border-style: solid; border-color: black; background-image: url('<?php echo $currtmp; ?>/images/light3.jpg');"><tr><td>
<table border=0 cellspacing=0 cellpadding=4 width="580px">
<tr>
<td>
<form action="index.php?n=account&sub=chartools" method="post">
<center>
<table width="540" border="0" cellpadding="2px">
<tr><td><center>
<?php
if ($show_custom == false){
?>
<center>
<div class="errorMsg"><b><?php echo $lang['chat_disable'] ?></b></div>
</center>
<?php
}else{
?>
<?php echo $lang['customize_desc1']; ?> <font color=red>Warning</font> <?php echo $lang['customize_desc2']; ?>
</table>
<br />
<?php
		if($char_custom_points > $your_points){
			$disabledc = 1;
			echo "<font color=\"red\"><center>You do not have enough points to re-customize a character!!</center></font>";
		}else{
			$disabledc = 0;

		}
?>
<table width="250" border="0" cellpadding="2px">
   <tr>
  <td><?php echo $lang['charname']; ?></td>
  <td>
    <select name="char_c_name">
<?php

$stmtCt3 = $charPdoCt->prepare("SELECT * FROM `characters` WHERE account=?");
$stmtCt3->execute([(int)$user['id']]);
$qray = $stmtCt3->fetchAll(PDO::FETCH_ASSOC);
foreach($qray as $c){
        echo "<option value='".htmlspecialchars($c['name'])."'>".htmlspecialchars($c['name'])."</option>";
}

?>
</select>
</td></tr>
  <tr>
	<td colspan='2' align='center'>Cost: <font color=blue><u><?php echo $char_custom_points; ?></u></font> <?php echo $lang['vote_points']; ?>
	</td>
  </tr>
<tr>
<?php if ($disabledc == 0){ ?>
  <td colspan='2' align='center'>
                        <input type='submit' name='customize' value='Customize'/>
  </td>
  <?php }else{ ?>
  <td colspan='2' align='center'>
                        <input type='submit' name='customize' value='Customize' disabled='disabled' />
  </td>
  <?php } ?>
</tr>
</center></td></tr>
</table>
</form>
<table width="300" border="0" cellpadding="2px">
<?php } ?>
<?php
if (isset($_POST['customize'])) {

        $name = $_POST['char_c_name'];
        $status = check_if_online($name, $charPdoCt);
        if ($status == -1) {
            echo "<p align='center'><font color='red'>The character doesnt exsist!
                </font></p>";
            exit();
        }
		if ($status == 1)
        echo "<p align='center'><font color='red'>This character is online. Please try again later</font></p>";
		else {
            customize($name, $charPdoCt, $account_id);
            $stmtCusPts = $realmPdoCt->prepare("UPDATE `voting_points` SET `points`=(`points` - ?), `points_spent`=(`points_spent` + ?) WHERE id=?");
            $stmtCusPts->execute([$char_custom_points, $char_custom_points, (int)$account_id]);
        echo "<p align='center'><font color='blue'>Success! You are able to customize you character at next login!</font></p>";
    }
}
?>
</table>
</center>
</td>
</tr>
</table>
</td></tr></table>
</td></tr></table>
</center>
<br />

<!-- CHARACTER RACE/FACTION CHANGER -->
<br />
<?php write_subheader("Race/Faction Changer"); ?>
<center>
<table width = "580" style = "border-width: 1px; border-style: dotted; border-color: #928058;"><tr><td><table style = "border-width: 1px; border-style: solid; border-color: black; background-image: url('<?php echo $currtmp; ?>/images/light3.jpg');"><tr><td>
<table border="0" cellspacing="0" cellpadding="4" width="580px">
<tr>
<td>
<?php
if($show_changer == true) {
	if($char_faction_points > $your_points){
		$disabledf = 1;
	}else{
		$disabledf = 0;
	}
// Step two (Step one is under step 3)
if ($_POST['step2']) {
if (!$_POST['char_f_name']) {
	die('Error!<br /><br />No character was selected. Please try again');
	}
echo "<center>Step 2/3</center>";
$name = $_POST['char_f_name'];
$stmtPre = $charPdoCt->prepare("SELECT `guid`, `race`, `class`, `gender`, `level`,`zone` FROM characters WHERE name=?");
$stmtPre->execute([$name]);
$pre = $stmtPre->fetchAll(PDO::FETCH_ASSOC);
foreach($pre as $row) {
$guid1 = $row['guid'];
$preoldrace = $row['race'];
$oldclass = $row['class'];
$oldgender = $row['gender'];
$level = $row['level'];
$pos = $MANG->get_zone_name($row['zone']);
}
?>
<br />
<?php write_metalborder_header(); ?>
    <table cellpadding='3' cellspacing='0' width='100%'>
    <tbody>
    <tr>
      <td class="rankingHeader" align="center" colspan='5' nowrap="nowrap">Current Selected Character</td>
    </tr>
    <tr>
      <td class="rankingHeader" align="center" nowrap="nowrap"><?php echo $lang['name'];?>&nbsp;</td>
      <td class="rankingHeader" align="center" nowrap="nowrap"><?php echo $lang['race'];?>&nbsp;</td>
      <td class="rankingHeader" align="center" nowrap="nowrap"><?php echo $lang['class'];?>&nbsp;</td>
      <td class="rankingHeader" align="center" nowrap="nowrap"><?php echo $lang['level_short'];?>&nbsp;</td>
      <td class="rankingHeader" align="center" nowrap="nowrap"><?php echo $lang['location'];?>&nbsp;</td>
    </tr>
	<tr>
      <td class="serverStatus1"><b style="color: rgb(35, 67, 3);"><center><?php echo $name; ?></center></b></a></td>
      <td class="serverStatus1" align="center"><small style="color: rgb(102, 13, 2);"><img onmouseover="ddrivetip('<?php echo $MANG->characterInfoByID['character_race'][$preoldrace]; ?>','#ffffff')" onmouseout="hideddrivetip()"
      src="<?php echo $currtmp; ?>/images/icon/race/<?php echo $preoldrace;?>-<?php echo $oldgender;?>.gif" height="18" width="18" alt=""/></small></td>
      <td class="serverStatus1" align="center"><small style="color: (35, 67, 3);"><img onmouseover="ddrivetip('<?php echo $MANG->characterInfoByID['character_class'][$oldclass]; ?>','#ffffff')" onmouseout="hideddrivetip()"
      src="<?php echo $currtmp; ?>/images/icon/class/<?php echo $oldclass ?>.gif" height="18" width="18" alt=""/></small></td>
      <td class="serverStatus1" align="center"><b style="color: rgb(102, 13, 2);"><?php echo $level; ?></b></td>
      <td class="serverStatus1" align="center"><b style="color: rgb(35, 67, 3);"><?php echo $pos; ?></b></td>
    </tr>
	</tbody>
    </table>
<?php write_metalborder_footer(); ?>
<br />
<form action="index.php?n=account&sub=chartools" method="post">
<table width="540" border="0" cellpadding="2px" cellspacing="5px">
<tr>
	<td align="center" colspan="2"><font color="red">Warning!</font> Make sure you select a race that goes with your current class. Failure to do so will result in
	an error. You will be returned to this screen.
	</td>
</tr>
<tr>
	<td colspan="2" align="center"><?php if ($allow_faction_change == false) echo "<font color='red'>Faction Change Disabled. Please select a race current with your faction.</font>";
	else echo "<font color='blue'>Faction Change is Enabled</font>"; ?>
	</td>
</tr>
<tr>
	<td align="right"><b style="color: rgb(102, 13, 2);">New Race:</b></td>
	<td align="left"><select name="newrace">
	<option value="1">Human</option>
	<option value="2">Orc</option>
	<option value="3">Dwarf</option>
	<option value="4">Night Elf</option>
	<option value="5">Undead</option>
	<option value="6">Tauren</option>
	<option value="7">Gnome</option>
	<option value="8">Troll</option>
	<option value="10">Blood Elf</option>
	<option value="11">Draenei</option>
	</select></td></tr>
<tr>
   <td colspan="2"><center><br />
   	<input type="hidden" name="guid" value="<?php echo $guid1; ?>" />
	<input type="hidden" name="name" value="<?php echo $name; ?>" />
	<input type="hidden" name="oldrace" value="<?php echo $preoldrace; ?>" />
	<input type="hidden" name="oldclass" value="<?php echo $oldclass; ?>" />
    <input type='submit' name='step1' value='Previous Step'> <input type='submit' name='step3' value='Next Step'>
	</center>
   </td>
</tr>
</table>
</form>
<?php }
// Step three
elseif ($_POST['step3']) {
if (!$_POST['newrace']) {
	die('Error!<br /><br />No character was selected. Please try again');
	}
if (!$_POST['oldrace']) {
	die('Error!<br /><br />Error #2. Please try again');
	}
if (!$_POST['oldclass']) {
	die('Error!<br /><br />Error #3. Please try again');
	}
if (!$_POST['guid']) {
	die('Error!<br /><br />Error #4. Please try again');
	}
echo "<center>Step 3/3</center?";
$newrace = (int)$_POST['newrace'];
$oldrace = (int)$_POST['oldrace'];
$class = (int)$_POST['oldclass'];
$name = $_POST['name'];
$guid = (int)$_POST['guid'];
echo htmlspecialchars($name);
?><br />
<table width="540" border="0" cellpadding="2px" cellspacing="5px">
<tr>
	<td>
<?php
// Start
// If the admin has faction change dis-abled, this code is produced
$online_status = check_if_online($name, $charPdoCt);
$check_guild = check_guild($guid, $charPdoCt);
if ($allow_faction_change == false) {
if ($newrace > 0 && $newrace < 12 && $newrace != 9) {
    if ($newrace != $oldrace) {
        if ((isAlliance($newrace) && isAlliance($oldrace)) || (!isAlliance($newrace) && !isAlliance($oldrace))) {
			if($online_status == 0) {
				if (isGood($newrace,$class)) {
					delMounts($guid,$oldrace, $charPdoCt);
					addMounts($guid,$newrace, $charPdoCt);
					$oldrepfunction = rep($oldrace);
					$newrepfunction = rep($newrace);
					$stmtR5 = $charPdoCt->prepare("SELECT * FROM character_reputation WHERE guid=? AND faction=?");
					$stmtR5->execute([$guid, $oldrepfunction]);
					$result5 = $stmtR5->fetchAll(PDO::FETCH_ASSOC);
					foreach($result5 as $result6) { $oldRep = $result6['standing']; }
					$stmtR7 = $charPdoCt->prepare("SELECT * FROM character_reputation WHERE guid=? AND faction=?");
					$stmtR7->execute([$guid, $newrepfunction]);
					$result7 = $stmtR7->fetchAll(PDO::FETCH_ASSOC);
					foreach($result7 as $result8) { $newRep = $result8['standing']; }
					if (isAlliance($oldrace)) {
						$stmtAp1 = $charPdoCt->prepare("UPDATE character_achievement_progress SET counter=10500 WHERE guid=? AND (criteria=2030 or criteria=2031 or criteria=2032 or criteria=2033 or criteria=2034)");
						$stmtAp1->execute([$guid]);
					} else {
						$stmtAp2 = $charPdoCt->prepare("UPDATE character_achievement_progress SET counter=10500 WHERE guid=? AND (criteria=992 or criteria=993 or criteria=994 or criteria=995 or criteria=996)");
						$stmtAp2->execute([$guid]);
					}
					$stmtRu1 = $charPdoCt->prepare("UPDATE character_reputation SET standing=? WHERE guid=? AND faction=?");
					$stmtRu1->execute([$oldRep, $guid, $newrepfunction]);
					$stmtRu2 = $charPdoCt->prepare("UPDATE character_reputation SET standing=? WHERE guid=? AND faction=?");
					$stmtRu2->execute([$newRep, $guid, $oldrepfunction]);
					$stmtRc = $charPdoCt->prepare("UPDATE characters SET race=? ,at_login=8 ,playerBytes=1 WHERE guid=?");
					$stmtRc->execute([$newrace, $guid]);
					$stmtFcPts = $realmPdoCt->prepare("UPDATE `voting_points` SET `points`=(`points` - ?), `points_spent`=(`points_spent` + ?) WHERE id=?");
					$stmtFcPts->execute([$char_faction_points, $char_faction_points, (int)$account_id]);
					echo "<font color='blue'><center>Success! Race successfully changed</center></font>";
				} else { echo "<center>Error: Your class cant be the chosen race! Please try again.</center>"; }
			} else { echo "<center>Error: This character is online. Please try again later</center>"; }
		} else { echo "<center>Error: The admin has disabled faction changes. Please select a friendly race</center>"; }
    } else { echo "<center>Error: The new race and the original race are the same</center>"; }
} else { echo "<center>Error: Race code invalid!</center>"; }

// If the admin has faction change enabled, this code is produced instead
}else{
if ($newrace > 0 && $newrace < 12 && $newrace != 9) {
    if ($newrace != $oldrace) {
		if ($online_status == 0) {
			if (isGood($newrace,$class)) {
				if((((isAlliance($newrace) && !isAlliance($oldrace)) || (!isAlliance($newrace) && isAlliance($oldrace))) && $check_guild == 0)||((isAlliance($newrace) && isAlliance($oldrace)) || (!isAlliance($newrace) && !isAlliance($oldrace)))){
					delMounts($guid,$oldrace, $charPdoCt);
					addMounts($guid,$newrace, $charPdoCt);
					$stmtRep = $charPdoCt->prepare("SELECT `standing` FROM `character_reputation` WHERE guid=? AND faction=?");
					$stmtRep->execute([$guid, 72]); $aone = $stmtRep->fetchColumn();
					$stmtRep->execute([$guid, 47]); $atwo = $stmtRep->fetchColumn();
					$stmtRep->execute([$guid, 69]); $athree = $stmtRep->fetchColumn();
					$stmtRep->execute([$guid, 54]); $afour = $stmtRep->fetchColumn();
					$stmtRep->execute([$guid, 930]); $afive = $stmtRep->fetchColumn();
					$stmtRep->execute([$guid, 76]); $hone = $stmtRep->fetchColumn();
					$stmtRep->execute([$guid, 68]); $htwo = $stmtRep->fetchColumn();
					$stmtRep->execute([$guid, 81]); $hthree = $stmtRep->fetchColumn();
					$stmtRep->execute([$guid, 530]); $hfour = $stmtRep->fetchColumn();
					$stmtRep->execute([$guid, 911]); $hfive = $stmtRep->fetchColumn();
					// If staying the same race, change main reputaion to match new race
					$oldrepfunction = rep($oldrace);
					$newrepfunction = rep($newrace);
					$stmtR5b = $charPdoCt->prepare("SELECT * FROM character_reputation WHERE guid=? AND faction=?");
					$stmtR5b->execute([$guid, $oldrepfunction]);
					$result5 = $stmtR5b->fetchAll(PDO::FETCH_ASSOC);
					foreach($result5 as $result6) { $oldRep = $result6['standing']; }
					$stmtR7b = $charPdoCt->prepare("SELECT * FROM character_reputation WHERE guid=? AND faction=?");
					$stmtR7b->execute([$guid, $newrepfunction]);
					$result7 = $stmtR7b->fetchAll(PDO::FETCH_ASSOC);
					foreach($result7 as $result8) { $newRep = $result8['standing']; }
					if (isAlliance($oldrace)) {
						$stmtAp3 = $charPdoCt->prepare("UPDATE character_achievement_progress SET counter=10500 WHERE guid=? AND (criteria=2030 or criteria=2031 or criteria=2032 or criteria=2033 or criteria=2034)");
						$stmtAp3->execute([$guid]);
					} else {
						$stmtAp4 = $charPdoCt->prepare("UPDATE character_achievement_progress SET counter=10500 WHERE guid=? AND (criteria=992 or criteria=993 or criteria=994 or criteria=995 or criteria=996)");
						$stmtAp4->execute([$guid]);
					}
					if (isAlliance($newrace) && !isAlliance($oldrace))
						{ $stmtPos1 = $charPdoCt->prepare("UPDATE characters SET position_x = -8913.23, position_y = 554.633, position_z = 93.7944, map = 0 WHERE guid=?"); $stmtPos1->execute([$guid]); }
					if (!isAlliance($newrace) && isAlliance($oldrace))
						{ $stmtPos2 = $charPdoCt->prepare("UPDATE characters SET position_x = 1440.45, position_y = -4422.78, position_z = 25.4634, map = 1 WHERE guid=?"); $stmtPos2->execute([$guid]); }
					$stmtRuu = $charPdoCt->prepare("UPDATE character_reputation SET standing=? WHERE guid=? AND faction=?");
					if ((isAlliance($newrace) && isAlliance($oldrace)) || (!isAlliance($newrace) && !isAlliance($oldrace))) {
						$stmtRuu->execute([$oldRep, $guid, $newrepfunction]);
						$stmtRuu->execute([$newRep, $guid, $oldrepfunction]);
					}else{
						if ($newrace == 1 || $newrace == 3 || $newrace == 4 || $newrace == 7 || $newrace == 11) {
							$stmtRuF = $charPdoCt->prepare("UPDATE `character_reputation` SET `standing`=?, `flags`=17 WHERE guid=? AND faction=?");
							foreach([[72,$hone],[47,$htwo],[69,$hthree],[54,$hfour],[930,$hfive]] as $r) { $stmtRuF->execute([$r[1],$guid,$r[0]]); }
							$stmtRuF6 = $charPdoCt->prepare("UPDATE `character_reputation` SET `standing`=150, `flags`=6 WHERE guid=? AND faction=?");
							foreach([76,68,81,530,911] as $f) { $stmtRuF6->execute([$guid,$f]); }
						}else{
							$stmtRuH = $charPdoCt->prepare("UPDATE `character_reputation` SET `standing`=?, `flags`=17 WHERE guid=? AND faction=?");
							foreach([[76,$aone],[68,$atwo],[81,$athree],[530,$afour],[911,$afive]] as $r) { $stmtRuH->execute([$r[1],$guid,$r[0]]); }
							$stmtRuH6 = $charPdoCt->prepare("UPDATE `character_reputation` SET `standing`=150, `flags`=6 WHERE guid=? AND faction=?");
							foreach([72,47,69,54,930] as $f) { $stmtRuH6->execute([$guid,$f]); }
						}
					}
					$stmtRcF = $charPdoCt->prepare("UPDATE characters SET race=? ,at_login=8 ,playerBytes=1 WHERE guid=?");
					$stmtRcF->execute([$newrace, $guid]);
					$stmtFcPtsF = $realmPdoCt->prepare("UPDATE `voting_points` SET `points`=(`points` - ?), `points_spent`=(`points_spent` + ?) WHERE id=?");
					$stmtFcPtsF->execute([$char_faction_points, $char_faction_points, (int)$account_id]);
					echo "<font color='blue'><center>Success! Race successfully changed</center></font>";
				} else { echo "<center>Error: When changing factions, you must first leave your guild!</center>"; }
            } else { echo "<center>Error: Your class cant be the chosen race! Please try again.</center>"; }
		} else { echo "<center>Error: This character is online. Please try again later</center>"; }
    } else { echo "<center>Error: The new race and the original race are the same</center>"; }
} else { echo "<center>Error: Race code invalid!</center>"; }
}
// End
?>
</td>
</tr>
</table>
<?php
 }else{
	echo "<center>Step 1/3</center>";
// Step one
?>
<form action="index.php?n=account&sub=chartools" method="post">
<center>
<table width="540" border="0" cellpadding="2px" cellspacing="5px">
  <tr>This is where you can change the Race <?php if($allow_faction_change == true) echo "and Faction"; ?> of your character. To start, please select a character you wish
  to change his/her race <?php if($allow_faction_change == true) echo "and/or faction"; ?>.
  <td align="right"><?php echo $lang['charname']; ?></td>
  <td align="left">
    <select name="char_f_name">
<?php
$stmtCt4 = $charPdoCt->prepare("SELECT * FROM `characters` WHERE account=?");
$stmtCt4->execute([(int)$user['id']]);
$qray = $stmtCt4->fetchAll(PDO::FETCH_ASSOC);
foreach($qray as $c){
        echo "<option value='".htmlspecialchars($c['name'])."'>".htmlspecialchars($c['name'])."</option>";
}
?>
</td>
</tr>
<tr>
   <td colspan="2"><center>
   Cost: <font color="blue"><u><?php echo $char_faction_points ?></u></font> Vote Points<br />
   <?php if ($disabledf == 0){ ?>
    <input type='submit' name='step2' value='Next Step'>
	<?php }else{ echo "<font color='red'><center>You dont have enough points to continue.</center></font>"; ?><br />
	<input type='submit' name='step2' value='Next Step' disabled='disabled'>
	<?php } ?>
	</center>
   </td>
</tr>
</table>
</form>
<?php }
}else{ ?>
<center>
<div class="errorMsg"><b><?php echo $lang['chat_disable'] ?></b></div>
</center>
<?php } ?>
<br />
<table width="250" border="0" cellpadding="2px">

</center></td></tr>
</table>
</form>
<table width="300" border="0" cellpadding="2px">

</table>
</center>
</td>
</tr>
</table>
</td></tr></table>
</td></tr></table>
<?php builddiv_end() ?>
