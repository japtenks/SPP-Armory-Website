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
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$chartoolsCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
<center>
<table width="300" border="0" cellpadding="2px">
  <tr>
  <td><?php echo $lang['charname']; ?></td>
  <td>
    <select name="name">
<?php
foreach($chartoolsCharacters as $c){
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
<?php echo $chartoolsUnstuckMessage; ?>
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
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$chartoolsCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
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
foreach($chartoolsCharacters as $c){
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
<?php echo $chartoolsRenameMessage; ?>
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
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$chartoolsCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
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
foreach($chartoolsCharacters as $c){
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
<?php echo $chartoolsCustomizeMessage; ?>
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
echo $chartoolsRaceMessage;
// Step two (Step one is under step 3)
if ($chartoolsRaceStep === 2 && !empty($chartoolsRaceContext)) {
echo "<center>Step 2/3</center>";
$name = $chartoolsRaceContext['name'];
$guid1 = $chartoolsRaceContext['guid'];
$preoldrace = $chartoolsRaceContext['oldrace'];
$oldclass = $chartoolsRaceContext['oldclass'];
$oldgender = $chartoolsRaceContext['oldgender'];
$level = $chartoolsRaceContext['level'];
$pos = $chartoolsRaceContext['zone_name'];
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
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$chartoolsCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
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
<?php
 }else{
	echo "<center>Step 1/3</center>";
// Step one
?>
<form action="index.php?n=account&sub=chartools" method="post">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$chartoolsCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
<center>
<table width="540" border="0" cellpadding="2px" cellspacing="5px">
  <tr>This is where you can change the Race <?php if($allow_faction_change == true) echo "and Faction"; ?> of your character. To start, please select a character you wish
  to change his/her race <?php if($allow_faction_change == true) echo "and/or faction"; ?>.
  <td align="right"><?php echo $lang['charname']; ?></td>
  <td align="left">
    <select name="char_f_name">
<?php
foreach($chartoolsCharacters as $c){
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
