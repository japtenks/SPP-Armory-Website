<?php

$mainnav_links = array(

  /* ------------------ 1. NEWS ------------------ */
  '1-menuNews' => array(
    0 => array('forum_news',    'index.php',                            ''),
    1 => array('forum_archive', 'index.php?n=forum&sub=viewforum&fid=1',''),
  ),

  /* ------------------ 2. ACCOUNT ------------------ */
  '2-menuAccount' => array(
    0 => array('account_manage',    mw_url('account', 'manage'),    'g_is_supadmin'),
    1 => array('personal_messages', mw_url('account', 'pms'),       'g_view_profile'),
    2 => array('account_create',    mw_url('account', 'register'),  '!g_view_profile'),
    3 => array('retrieve_pass',     mw_url('account', 'restore'),   '!g_view_profile'),
    4 => array('account_activate',  mw_url('account', 'activate'),  '!g_view_profile'),
    5 => array('charcreate',        mw_url('account', 'charcreate'),''),
    6 => array('char_manage',       mw_url('account', 'chartools'), ''),
    7 => array('userlist',          mw_url('account', 'userlist'),  'g_is_admin'),
    8 => array('rules',             mw_url('server',  'rules'),     'g_is_admin'),
    9 => array('admin_panel',       'index.php?n=admin',            'g_is_supadmin'),
  ),

  /* ------------------ 3. GAME GUIDE ------------------ */
  '3-menuGameGuide' => array(
    0 => array('howtoplay',   mw_url('gameguide', 'connect'), ''),
    1 => array('commands',    mw_url('server', 'commands'),   ''),
    2 => array('botcommands', mw_url('server', 'botcommands'),''),
  ),

  /* ------------------ 4. WORKSHOP ------------------ */
  '4-menuWorkshop' => array(
    0 => array('realms_status', mw_url('server', 'realmstatus'), ''),
    1 => array('chars',         mw_url('server', 'chars'),        ''),
    2 => array('playermap',     mw_url('server', 'playermap'),    ''),
    3 => array('statistic',     mw_url('server', 'statistic'),    ''),
    4 => array('module_ah',     mw_url('server', 'ah'),           ''),
    5 => array('armorsets',     'index.php?n=server&sub=armorsets',''),
    6 => array('worldsets',     'index.php?n=server&sub=worldsets',''),
    7 => array('pvpsets',       'index.php?n=server&sub=pvpsets', ''),
    8 => array('addons',        'index.php?n=server&sub=addons',  ''), // new addons page
  ),

  /* ------------------ 5. MEDIA ------------------ */
  '5-menuMedia' => array(
    0 => array('wallp',    mw_url('media', 'wallp'),        ''),
    1 => array('screen',   mw_url('media', 'screen'),       ''),
    2 => array('UScreen',  mw_url('media', 'addgalscreen'), 'g_view_profile'),
    3 => array('UWallp',   mw_url('media', 'addgalwallp'),  'g_view_profile'),
  ),

  /* ------------------ 6. FORUMS ------------------ */
  '6-menuForums' => array(
    0 => array('spp_forum',     'index.php?n=forum',                    ''),
    1 => array('forum_archive', 'index.php?n=forum&sub=viewforum&fid=1',''),
  ),

  /* ------------------ 7. ARMORY ------------------ */
  '7-menuArmory' => array(
    0 => array('honor',             '/armory/index.php?searchType=honor', ''),
    1 => array('armory',            '/armory/',                           ''),
    2 => array('talent_calculator', '/armory/index.php#0-0-0',            ''),
    3 => array('talents',           '#',                                  ''),
  ),

  /* ------------------ 8. SUPPORT ------------------ */
  '8-menuSupport' => array(
    0 => array('bugs',         'index.php?n=forum&sub=viewforum&fid=2', ''),
    1 => array('spp_discord',  'https://discord.gg/TpxqWWT',            ''),
    2 => array('bots_discord', 'https://discord.gg/s4JGKG2BUW',         ''),
  ),
);

/* ------------------ ALLOWED EXTENSIONS ------------------ */
$allowed_ext = array(
  'account', 'admin', 'ajax', 'forum', 'frontpage',
  'html', 'server', 'whoisonline', 'community', 'media', 'armory', 'gameguide'
);

/* ------------------ DYNAMIC TALENTS LINK ------------------ */
$talentMenuIndex = 3;
if (isset($mainnav_links['7-menuArmory'][$talentMenuIndex])) {
    $talentCharacter = isset($user['character_name']) ? trim($user['character_name']) : '';
    if ($talentCharacter !== '') {
        $talentRealmName = '';
        if (!empty($user['cur_selected_realmd'])) {
            $selectedRealm = get_realm_byid($user['cur_selected_realmd']);
            if (!empty($selectedRealm['name'])) $talentRealmName = $selectedRealm['name'];
        }
        if ($talentRealmName === '' && isset($MW->getConfig->generic_values->realm_info->default_realm_id)) {
            $defaultRealmId = (int)$MW->getConfig->generic_values->realm_info->default_realm_id;
            if ($defaultRealmId > 0) {
                $defaultRealm = get_realm_byid($defaultRealmId);
                if (!empty($defaultRealm['name'])) $talentRealmName = $defaultRealm['name'];
            }
        }
        $talentLink = '/armory/index.php?searchType=profile&charPage=talents&character=' . rawurlencode($talentCharacter);
        if ($talentRealmName !== '') $talentLink .= '&realm=' . rawurlencode($talentRealmName);
        $mainnav_links['7-menuArmory'][$talentMenuIndex][1] = $talentLink;
    }
}

/* ------------------ BUG TRACKER / EXTERNAL FORUM ------------------ */
if ((int)$MW->getConfig->generic_values->forum->frame_forum ||
    (int)$MW->getConfig->generic_values->forum->externalforum) {
    $mainnav_links['6-menuForums'][0][1] =
        (string)$MW->getConfig->generic_values->forum->forum_external_link;
}

/* bug tracker dynamic link */
$mainnav_links['8-menuSupport'][0][1] =
    mw_url('forum', 'viewforum', array('fid' => (int)$MW->getConfig->generic_values->forum->bugs_forum_id));

if (!((int)$MW->getConfig->generic_values->forum->frame_bugstracker) &&
    !((int)$MW->getConfig->generic_values->forum->externalbugstracker)) {
    // internal bug tracker
} else {
    $mainnav_links['8-menuSupport'][0][1] =
        (string)$MW->getConfig->generic_values->forum->bugstracker_external_link;
}

/* ------------------ VISIBILITY TOGGLES ------------------ */

/* ====== Visibility toggles ====== */
/* Account */
if ((int)$MW->getConfig->components->left_section->retrieve_pass == 0)   unset($mainnav_links['2-menuAccount'][3]);
if ((int)$MW->getConfig->components->left_section->Activate_account == 0)unset($mainnav_links['2-menuAccount'][4]);
if ((int)$MW->getConfig->components->left_section->Character_copy == 0) unset($mainnav_links['2-menuAccount'][5]);
if ((int)$MW->getConfig->components->left_section->Character_tools == 0)unset($mainnav_links['2-menuAccount'][6]);

/* Game Guide */
if ((int)$MW->getConfig->components->left_section->Armor_sets == 0)   	unset($mainnav_links['3-menuGameGuide'][1]);		
if ((int)$MW->getConfig->components->left_section->world_sets == 0)   	unset($mainnav_links['3-menuGameGuide'][2]);			if ((int)$MW->getConfig->components->left_section->wow_bc == 0)     	unset($mainnav_links['3-menuGameGuide'][3]);
if ((int)$MW->getConfig->components->left_section->wow_wrath == 0)  	unset($mainnav_links['3-menuGameGuide'][4]);
if ((int)$MW->getConfig->components->left_section->pvp_sets == 0)   	unset($mainnav_links['3-menuGameGuide'][5]);

/* Workshop (Interactive) */
if ((int)$MW->getConfig->components->left_section->Realms_status == 0)          unset($mainnav_links['4-menuInteractive'][0]);
if ((int)$MW->getConfig->components->left_section->Honor == 0)                   unset($mainnav_links['4-menuInteractive'][1]);
if ((int)$MW->getConfig->components->left_section->Characters == 0)              unset($mainnav_links['4-menuInteractive'][3]);
if ((int)$MW->getConfig->components->left_section->Playermap == 0)               unset($mainnav_links['4-menuInteractive'][4]);
if ((int)$MW->getConfig->components->left_section->Statistic == 0)               unset($mainnav_links['4-menuInteractive'][5]);
if ((int)$MW->getConfig->components->left_section->ah_system == 0)               unset($mainnav_links['4-menuInteractive'][6]);
if ((int)$MW->getConfig->components->left_section->Armory == 0)                  unset($mainnav_links['4-menuInteractive'][7]);
if ((int)$MW->getConfig->components->left_section->Talent_calc == 0)             unset($mainnav_links['4-menuInteractive'][8]);
if ((int)$MW->getConfig->components->left_section->Interactive_world_atlas == 0) unset($mainnav_links['4-menuInteractive'][10]);
if ((int)$MW->getConfig->components->left_section->talents == 0 || empty($user['character_name']))
    unset($mainnav_links['4-menuInteractive'][11]);

/* Media */
if ((int)$MW->getConfig->components->left_section->Screenshots == 0)        unset($mainnav_links['5-menuMedia'][1]);
if ((int)$MW->getConfig->components->left_section->Wallpapers == 0)         unset($mainnav_links['5-menuMedia'][0]);
if ((int)$MW->getConfig->components->left_section->Upload_Screenshot == 0)  unset($mainnav_links['5-menuMedia'][2]);
if ((int)$MW->getConfig->components->left_section->Upload_Wallpaper == 0)   unset($mainnav_links['5-menuMedia'][3]);

/* Community */
if ((int)$MW->getConfig->components->left_section->Teamspeak == 0) unset($mainnav_links['7-menuCommunity'][0]);
if ((int)$MW->getConfig->components->left_section->donate == 0)    unset($mainnav_links['7-menuCommunity'][1]);
if ((int)$MW->getConfig->components->left_section->vote == 0)      unset($mainnav_links['7-menuCommunity'][2]);
if ((int)$MW->getConfig->components->left_section->chat == 0)      unset($mainnav_links['7-menuCommunity'][3]);



/* Support */
if ((int)$MW->getConfig->components->left_section->Commands == 0)          unset($mainnav_links['8-menuSupport'][0]);
if ((int)$MW->getConfig->components->left_section->Bug_tracker == 0)       unset($mainnav_links['8-menuSupport'][1]);
if ((int)$MW->getConfig->components->left_section->In_Game_Support == 0)   unset($mainnav_links['8-menuSupport'][2]);
if ((int)$MW->getConfig->components->left_section->Online_GMs == 0)        unset($mainnav_links['8-menuSupport'][3]);
if ((int)$MW->getConfig->components->left_section->botcommands == 1)         unset($mainnav_links['8-menuSupport'][4]);



?>
