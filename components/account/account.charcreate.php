<?php
/// FIX ME: Im not working perfect. I can not copy other than characters first bag's content and not others bag content!


if(INCLUDED!==true)exit;
// ==================== //
$pathway_info[] = array('title'=>$lang['charcreate'],'link'=>'');
// ==================== //
$account_id = $user['id'];
$rid = $user['cur_selected_realmd'];
$char_points = (int)$MW->getConfig->character_copy_config->points;
$realmPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
$charPdo   = spp_get_pdo('chars',  (int)$rid);
$stmtPts = $realmPdo->prepare("SELECT `points` FROM `voting_points` WHERE id=?");
$stmtPts->execute([$account_id]);
$your_points = $stmtPts->fetchColumn();
if($user['id'] > 0){
/*************** START LEVLEUP ACTION ****************/
    if(!$_GET['action'])
        {
            $profile = $auth->getprofile($user['id']);
            $profile['signature'] = str_replace('<br />','',$profile['signature']);
        }
    elseif($_GET['action']=='createchar'){
        $MANG = new Mangos;
        //Post declare:
        $class =& $_POST["createchar_class"];       //used only as a check to see if it exists
        $faction =& $_POST["createchar_faction"];   //used only as a check to see if it exists
        $character_copy_to = $_POST["character_copy_char"];
        //Main Declare.....
        $name = $checknamestring = ucfirst(strtolower(escape_string($_POST['createchar_name'])));
        $loggedin = 0;

        // Current ID + 1

        $new_guid = $MANG->mangos_newguid('character');
        $guid = $new_guid['new_guid'];
        $WE_DID_OFFSET_ID = $new_guid['incr'];

        //Checks if wanted name exsits.
        $stmtNc = $charPdo->prepare("SELECT guid FROM `characters` WHERE name=?");
        $stmtNc->execute([$name]);
        $name_check = $stmtNc->fetchColumn();
        if ($name_check == FALSE){
            $classexists = false;
        }else{
            $classexists = true;
        }


        //Checks if user is logged on
        $stmtLi = $realmPdo->prepare("SELECT online FROM account WHERE id=?");
        $stmtLi->execute([$account_id]);
        $loggedin = $stmtLi->fetchColumn();
        //Another check if user is logged on.
        if ($loggedin == '0'){
            $stmtLi2 = $charPdo->prepare("SELECT online FROM `characters` WHERE account=?");
            $stmtLi2->execute([$account_id]);
            $loggedin = $stmtLi2->fetchColumn();
        }
        //Checks if user has MAX players.
        $stmtNc2 = $realmPdo->prepare("SELECT numchars FROM realmcharacters WHERE acctid=? AND realmid=?");
        $stmtNc2->execute([$account_id, $rid]);
        $numchars = $stmtNc2->fetchColumn();


        /******** FORM CHECKS ********/
        if ($class == false){}
        elseif ($faction == false){}
        elseif ($classexists == true)
        {
            output_message('alert','<b>'.$lang['charcreate_nameinuse'].'<br/>'.$lang['redirecting_wait'].'</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=charcreate">');
        }
        elseif ($name == false)
        {
            output_message('alert','<b>'.$lang['charcreate_invalidname'].'<br/>'.$lang['redirecting_wait'].'</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=charcreate">');
        }
        elseif ($loggedin == 1)
        {
            output_message('alert','<b>'.$lang['charcreate_loggedin'].'<br/>'.$lang['redirecting_wait'].'</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=charcreate">');
        }
        elseif(check_for_symbols($checknamestring,1) == TRUE)
        {
            output_message('alert','<b>'.$lang['charcreate_nameissymbols'].'<br/>'.$lang['redirecting_wait'].'</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=charcreate">');
        }
        elseif($numchars == 9)
        {
            output_message('alert','<b>'.$lang['charcreate_tomanychars'].'<br/>'.$lang['redirecting_wait'].'</b><meta http-equiv=refresh content="3;url=index.php?n=account&sub=charcreate">');
        }
        else
        {
        //Here is the main section for character creation. You define your values.
        // You create a character in-game, look up the ID in character table.

        /*******************  MAIN COPY ******************/
        // Tables wich is going to be copied.
        #character
        #character_action
        #character_homebind
		#character_inventory
        #character_reputation
		#character_skills
        #character_spell
        #item_instance


        /*    Make array's   From copy char */
        /*Character*/
        $s = $charPdo->prepare("SELECT * FROM `characters` WHERE guid=?"); $s->execute([(int)$character_copy_to]);
        $COPY_character = $s->fetch(PDO::FETCH_ASSOC);
        /*`character_action` */
        $s = $charPdo->prepare("SELECT * FROM `character_action` WHERE guid=?"); $s->execute([(int)$character_copy_to]);
        $COPY_character_action = $s->fetchAll(PDO::FETCH_ASSOC);
        /*`character_homebind` */
        $s = $charPdo->prepare("SELECT * FROM `character_homebind` WHERE guid=?"); $s->execute([(int)$character_copy_to]);
        $COPY_character_homebind = $s->fetchAll(PDO::FETCH_ASSOC);
        /*`character_reputation` */
        $s = $charPdo->prepare("SELECT * FROM `character_reputation` WHERE guid=?"); $s->execute([(int)$character_copy_to]);
        $COPY_character_reputation = $s->fetchAll(PDO::FETCH_ASSOC);
		/*`character_skills` */
		$s = $charPdo->prepare("SELECT * FROM `character_skills` WHERE guid=?"); $s->execute([(int)$character_copy_to]);
		$COPY_character_skills = $s->fetchAll(PDO::FETCH_ASSOC);
        /*`character_spell` */
        $s = $charPdo->prepare("SELECT * FROM `character_spell` WHERE guid=?"); $s->execute([(int)$character_copy_to]);
        $COPY_character_spell = $s->fetchAll(PDO::FETCH_ASSOC);

        /*``character_inventory` ` */ /* Also ``item_instance``*/
        $s = $charPdo->prepare("SELECT * FROM `item_instance` WHERE owner_guid=?"); $s->execute([(int)$character_copy_to]);
        $MAIN_COPY_item_instance = $s->fetchAll(PDO::FETCH_ASSOC);
        $ROUNDS = 0;
        $ARRAY_INCREMENT = 0;
        $ARRAY_BAG_INCREMENT = 0;
        foreach($MAIN_COPY_item_instance as $MAIN_COPY_SUB_item_instance){

            $stmtCi = $charPdo->prepare("SELECT * FROM `character_inventory` WHERE item=?");
            $stmtCi->execute([(int)$MAIN_COPY_SUB_item_instance['guid']]);
            $character_inventory = $stmtCi->fetch(PDO::FETCH_ASSOC);
                $bag = $character_inventory['bag'];
                $slot = $character_inventory['slot'];
                $item_template = $character_inventory['item_template'];

            // First we need to check what is the lastest id
            // We use standard query SAME AS DONATION system uses.
            $ROUNDS++;
            // If this is the first loop we must check if we MUST increment with offset or if we can just apply id +1
            if ($ROUNDS == 1){
                $new_guid = $MANG->mangos_newguid('item_instance');
                $item = $new_guid['new_guid'];
                $WE_DID_OFFSET_ID = $new_guid['incr'];

            }else{
            // In this point we must incre, since we dont INSERT after each loop therefor we must take current + 1
            $item = $item+1;
            }
            // Start modify data fields to what it should be.
            $data = explode(" ", $MAIN_COPY_SUB_item_instance['data']);
            $data['0'] = $item;
            $data['6'] = $guid;
            $data['8'] = $guid;
            $update_implode_data_field = implode(" ", $data);
            


            // Make a check if this is a bag. If it is we make an array with the bag.
            // If its NOT a bag.. we will define it in our NOT bag array.
            if (count($data) > 90){
                $COPY_BAG_item_instance[$ARRAY_BAG_INCREMENT] = array(
					        'guid' => $item,
                	'owner_guid' => $guid,
                	'data' => $update_implode_data_field,
                	'old_guid' => $MAIN_COPY_SUB_item_instance['guid'],
                );
                $ARRAY_BAG_INCREMENT++;
            }else{

            // Ok this was not an bag well.. We must now basicly make the sql array's.
            $COPY_item_instance[$ARRAY_INCREMENT] = array(
                'guid' => $item,
                'owner_guid' => $guid,
                'data' => $update_implode_data_field,
                'old_guid' => $MAIN_COPY_SUB_item_instance['guid'],
            );
            }


            $COPY_character_inventory[$ARRAY_INCREMENT] = array(
                'guid' => $guid,
                'bag'  => $bag,
                'slot' => $slot,
                'item' => $item,
                'item_template' => $item_template,
            );


            // Now, we need to replace Data FOR EACH field within the new values used, as items.
            $COPY_character['data'] =  str_replace($MAIN_COPY_SUB_item_instance['guid'], $item, $COPY_character['data']);

            $ARRAY_INCREMENT++;        // TODO: We need to copy bags content into the right places .... Long process
        }// END WHILE CHARACTER_INSTANCE





        ####################### INSERT TO Database ########################
            // Filter Item_instance + Character Inventory.
            foreach($COPY_BAG_item_instance as $COPY_BAG_SUB_item_instance){

                // Filter thrue all item_instance objects and replace in bags.
          foreach($COPY_item_instance as $COPY_WORK_item_instance){

          if (strstr($COPY_BAG_SUB_item_instance['data'], $COPY_WORK_item_instance['old_guid']) == TRUE){
					$COPY_BAG_SUB_item_instance['data'] = str_replace(" ".$COPY_WORK_item_instance['old_guid']." ", " ".$COPY_WORK_item_instance['guid']." ", $COPY_BAG_SUB_item_instance['data']);
              foreach($COPY_character_inventory as $i => $check){
                  if ($check['bag'] != 0){
                  if ($check['bag'] == $COPY_BAG_SUB_item_instance['old_guid']){
                      $COPY_character_inventory[$i]['bag'] = $COPY_BAG_SUB_item_instance['guid'];
                  }
                  }
              }
          }
          
				  }

            $charPdo->exec("INSERT INTO `item_instance` (
            `guid`,
            `owner_guid`,
            `data`)
             VALUES
            ('".$COPY_BAG_SUB_item_instance['guid']."', '".$COPY_BAG_SUB_item_instance['owner_guid']."', '".$COPY_BAG_SUB_item_instance['data']."')");

            }

            foreach($COPY_item_instance as $COPY_SUB_item_instance){
            $charPdo->exec("INSERT INTO `item_instance` (
            `guid`,
            `owner_guid`,
            `data`)
             VALUES
            ('".$COPY_SUB_item_instance['guid']."', '".$COPY_SUB_item_instance['owner_guid']."', '".$COPY_SUB_item_instance['data']."')");
            }

            foreach($COPY_character_inventory as $COPY_SUB_character_inventory){
            $charPdo->exec("INSERT INTO `character_inventory` (
            `guid`,
            `bag`,
            `slot`,
            `item`,
            `item_template`)
            VALUES
            ('".$COPY_SUB_character_inventory['guid']."', '".$COPY_SUB_character_inventory['bag']."', '".$COPY_SUB_character_inventory['slot']."', '".$COPY_SUB_character_inventory['item']."', '".$COPY_SUB_character_inventory['item_template']."')");
            }

            /* character */
                // Filters
                  # $COPY_character[data]  == FILTERED!
                  $COPY_character['data'] = explode(' ', $COPY_character['data']);
                    $COPY_character['data']['0']    = $guid;
                    // NOT USE - Mangos changes field all time | $COPY_character['data']['1326'] = (int)$MW->getConfig->character_copy_config->general->Player_Start_Money;
                    // NOT USE - Mangos changes field all time | $COPY_character['data']['1420'] = (int)$MW->getConfig->character_copy_config->general->Player_Start_Level;
                    // NOT USE - Mangos changes field all time | $COPY_character['data']['1244'] = (int)$MW->getConfig->character_copy_config->general->Player_Start_Level-9;
                  $COPY_character['data'] = implode(' ', $COPY_character['data']);
            $charPdo->exec("INSERT INTO `characters` (
            `guid`,
            `account`,
            `name`,
            `race`,
            `class`,
			`gender`,
			`level`,
			`xp`,
			`money`,
			`playerBytes`,
			`playerBytes2`,
			`playerFlags`,
            `position_x`,
            `position_y`,
            `position_z`,
            `map`,
			`dungeon_difficulty`,
            `orientation`,
            `taximask`,
            `online`,
            `cinematic`,
            `totaltime`,
            `leveltime`,
            `logout_time`,
            `is_logout_resting`,
            `rest_bonus`,
            `resettalents_cost`,
            `resettalents_time`,
            `trans_x`,
            `trans_y`,
            `trans_z`,
            `trans_o`,
            `transguid`,
			`extra_flags`,
            `stable_slots`,
            `at_login`,
			`zone`,
			`death_expire_time`,
			`taxi_path`,
			`arenaPoints`,
			`totalHonorPoints`,
			`todayHonorPoints`,
			`yesterdayHonorPoints`,
			`totalKills`,
			`todayKills`,
			`yesterdayKills`,
			`chosenTitle`,
			`knownCurrencies`,
			`watchedFaction`,
			`drunk`,
			`health`,
			`power1`,
			`power2`,
			`power3`,
			`power4`,
			`power5`,
			`power6`,
			`power7`,
			`specCount`,
			`activeSpec`,
			`exploredZones`,
			`equipmentCache`,
			`ammoId`,
			`knownTitles`,
			`actionBars`)
             VALUES
            ('".$guid."', '".$account_id."', '".$name."', '".$COPY_character['race']."', '".$COPY_character['class']."', '".$COPTY_character['gender']."', '".$COPY_character['level']."', 
			'".$COPY_character['xp']."', '".$COPY_character['money']."', '".$COPY_character['playerBytes']."', '".$COPY_character['playerBytes2']."', '".$COPY_character['playerFlags']."', '".$COPY_character['position_x']."',
            '".$COPY_character['position_y']."', '".$COPY_character['position_z']."', '".$COPY_character['map']."', '".$COPY_character['dungeon_difficulty']."', '".$COPY_character['orientation']."', '".$COPY_character['taximask']."', '".$COPY_character['online']."',
            '".$COPY_character['cinematic']."', '".$COPY_character['totaltime']."', '".$COPY_character['leveltime']."', '".$COPY_character['logout_time']."', '".$COPY_character['is_logout_resting']."',
            '".$COPY_character['rest_bonus']."', '".$COPY_character['resettalents_cost']."', '".$COPY_character['resettalents_time']."', '".$COPY_character['trans_x']."', '".$COPY_character['trans_y']."',
            '".$COPY_character['trans_z']."', '".$COPY_character['trans_o']."', '".$COPY_character['transguid']."', '".$COPY_character['extra_flags']."', '".$COPY_character['stable_slots']."', 
			'".$COPY_character['at_login']."', '".$COPY_character['zone']."', '".$COPY_character['death_expire_time']."', '".$COPY_character['taxi_path']."', '".$COPY_character['arenaPoints']."', '".$COPY_character['totalHonorPoints']."', '".$COPY_character['todayHonorPoints']."', '".$COPY_character['yesterdayHonorPoints']."', '".$COPY_character['totalKills']."', '".$COPY_character['todayKills']."', 
			'".$COPY_character['yesterdayKills']."', '".$COPY_character['chosenTitle']."', '".$COPY_character['knownCurrenies']."', '".$COPY_character['watchedFaction']."', '".$COPY_character['drunk']."', 
			'".$COPY_character['health']."', '".$COPY_character['power1']."', '".$COPY_character['power2']."', '".$COPY_character['power3']."', '".$COPY_character['power4']."', '".$COPY_character['power5']."', 
			'".$COPY_character['power6']."', '".$COPY_character['power7']."', '".$COPY_character['specCount']."', '".$COPY_character['activeSpec']."','".$COPY_character['exploredZones']."', 
			'".$COPY_character['equipmentCache']."', '".$COPY_character['ammoId']."', '".$COPY_character['knownTitles']."', '".$COPY_character['actionBars']."')");

        /* Character_action */
        foreach($COPY_character_action as $COPY_SUB_character_action){
            $charPdo->exec("INSERT INTO `character_action` (
            `guid`,
			`spec`,
            `button`,
            `action`,
            `type`)
             VALUES
            ('".$guid."' , '".$COPY_SUB_character_action['spec']."', '".$COPY_SUB_character_action['button']."', '".$COPY_SUB_character_action['action']."', '".$COPY_SUB_character_action['type']."')");
        }
        /* Character_homebind */
        foreach($COPY_character_homebind as $COPY_SUB_character_homebind){
            $charPdo->exec("INSERT INTO `character_homebind` (
            `guid`,
            `map`,
            `zone`,
            `position_x`,
            `position_y`,
            `position_z`)
             VALUES
            ('".$guid."' , '".$COPY_SUB_character_homebind['map']."', '".$COPY_SUB_character_homebind['zone']."',
            '".$COPY_SUB_character_homebind['position_x']."', '".$COPY_SUB_character_homebind['position_y']."', '".$COPY_SUB_character_homebind['position_z']."')");
        }

        /* Character_reputation */
        foreach($COPY_character_reputation as $COPY_SUB_character_reputation){
            $charPdo->exec("INSERT INTO `character_reputation` (
            `guid`,
            `faction`,
            `standing`,
            `flags`)
             VALUES
            ('".$guid."', '".$COPY_SUB_character_reputation['faction']."', '".$COPY_SUB_character_reputation['standing'].", '".$COPY_SUB_character_reputation['flags']."')");
        }
        /*  Character_spell  */
        foreach($COPY_character_spell as $COPY_SUB_character_spell){
            $charPdo->exec("INSERT INTO `character_spell` (
            `guid`,
            `spell`,
            `active`,
            `disabled`)
             VALUES
            ('".$guid."', '".$COPY_SUB_character_spell['spell']."', '".$COPY_SUB_character_spell['active']."', '".$COPY_SUB_character_spell['disabled']."')");
        }

        // update points
        $stmtUpd = $realmPdo->prepare("UPDATE `voting_points` SET `points`=(`points` - ?), `points_spent`=(`points_spent` + ?) WHERE id=?");
        $stmtUpd->execute([$char_points, $char_points, $account_id]);

        //Gogo message
        output_message('notice','<b><h1>'.$lang['congratulations'].'</h1>, '.$lang['charcreate_charcreated'].'<br/>'.$lang['redirecting_wait'].'</b><meta http-equiv=refresh content="5;url=index.php?n=account&sub=charcreate">');

    }//Stops if all cases returns false, then apply data fields.
    unset($MANG);
}
/*************** STOP LEVLEUP ACTION ****************/
}
?>
