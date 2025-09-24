<?php

$mainnav_links = array(
  '1-menuNews' =>
  array(
    0 => array('forum_news',    'index.php',                            ''),
    1 => array('forum_archive', 'index.php?n=forum&sub=viewforum&fid=1',''),
  ),

  '2-menuAccount' =>
  array(
    0 => array('account_manage',   mw_url('account', 'manage'),   'g_is_supadmin'),
    1 => array('personal_messages',mw_url('account', 'pms'),      'g_view_profile'),
    2 => array('account_create',   mw_url('account', 'register'), '!g_view_profile'),
    3 => array('retrieve_pass',    mw_url('account', 'restore'),  '!g_view_profile'),
    4 => array('account_activate', mw_url('account', 'activate'), '!g_view_profile'),
    5 => array('charcreate',       mw_url('account', 'charcreate'), ''),
    6 => array('char_manage',      mw_url('account', 'chartools'),  ''),
    7 => array('userlist',         mw_url('account', 'userlist'),   'g_is_admin'),
    8 => array('rules',            mw_url('server',  'rules'),      'g_is_admin'),
    9 => array('admin_panel',      'index.php?n=admin',             'g_is_supadmin'),
  ),

  '3-menuGameGuide' =>
  array(
    0 => array('howtoplay', mw_url('gameguide', 'connect'), ''),
    1 => array('armorsets',         'index.php?n=server&sub=armorsets',           ''),
    3 => array('bc',     'http://www.worldofwarcraft.com/burningcrusade/', ''),
    4 => array('wrath',  'http://www.worldofwarcraft.com/wrath/',          ''),
    5 => array('cata',   'http://www.worldofwarcraft.com/cataclysm/',      ''),
  ),

  /* ===========================
   * 4-menuInteractive (Workshop)
   * =========================== */
  '4-menuInteractive' =>
  array(
    0  => array('realms_status',     mw_url('server', 'realmstatus'),              ''),
    1  => array('honor',             '/armory/index.php?searchType=honor',         ''),
    2  => array('players_online',    mw_url('server', 'playersonline'),            ''),
    3  => array('chars',             mw_url('server', 'chars'),                    ''),
    4  => array('playermap',         mw_url('server', 'playermap'),                ''),
    5  => array('statistic',         mw_url('server', 'statistic'),                ''),
    6  => array('module_ah',         mw_url('server', 'ah'),                       ''),
    7  => array('armory',            '/armory/',                                   ''),
    8  => array('talent_calculator', '/armory/index.php#0-0-0',                    ''), // Always available if enabled in config
   // 9  => array('armorsets',         'index.php?n=server&sub=armorsets',           ''),
    10 => array('flashmap',          '/components/tools/maps/flashmap/',           ''), // Interactive world atlas (toggle controls this)
    11 => array('talents',           '#',                                           ''), // Will be replaced below with the logged-in character talents
  ),

  '5-menuMedia' =>
  array(
    0 => array('wallp',    mw_url('media', 'wallp'),        ''),
    1 => array('screen',   mw_url('media', 'screen'),       ''),
    2 => array('UScreen',  mw_url('media', 'addgalscreen'), 'g_view_profile'),
    3 => array('UWallp',   mw_url('media', 'addgalwallp'),  'g_view_profile'),
  ),

  '6-menuForums' =>
  array(
    0 => array('spp_forum', 'index.php?n=forum', ''),
    // 1 => array('forum_general','index.php?n=forum&sub=viewforum&fid=7',''),
    // 2 => array('forum_help',   'index.php?n=forum&sub=viewforum&fid=10',''),
  ),

  '7-menuCommunity' =>
  array(
    0 => array('teamspeak',  mw_url('community', 'teamspeak'), ''),
    1 => array('donate',     mw_url('community', 'donate'),    ''),
    array('vote',            mw_url('community', 'vote'),      ''),
    array('chat',            mw_url('community', 'chat'),      ''),
    array('spp_discord',     'https://discord.gg/TpxqWWT',     ''),
    array('bots_discord',    'https://discord.gg/s4JGKG2BUW',  ''),
  ),

  '8-menuSupport' =>
  array(
    0 => array('commands',  mw_url('server', 'commands'), ''),
    1 => array('bugs',      'index.php?n=forum&sub=viewforum&fid=2', ''),
    2 => array('gmlist',    mw_url('server', 'gms'), ''),
    3 => array('gm_online', mw_url('server', 'gmonline'), ''),
  ),
);

$allowed_ext = array(
  0  => 'account',
  1  => 'admin',
  2  => 'ajax',
  3  => 'forum',
  4  => 'frontpage',
  5  => 'html',
  6  => 'server',
  7  => 'whoisonline',
  8  => 'community',
  9  => 'media',
  10 => 'armory',
  11 => 'gameguide',
);

/* Pull up active character talents into the "talents" entry (index 11) */
$talentMenuIndex = 11;
if (isset($mainnav_links['4-menuInteractive'][$talentMenuIndex])) {
    $talentCharacter = isset($user['character_name']) ? trim($user['character_name']) : '';

    if ($talentCharacter !== '') {
        $talentRealmName = '';

        if (!empty($user['cur_selected_realmd'])) {
            $selectedRealm = get_realm_byid($user['cur_selected_realmd']);
            if (!empty($selectedRealm['name'])) {
                $talentRealmName = $selectedRealm['name'];
            }
        }

        if ($talentRealmName === '' && isset($MW->getConfig->generic_values->realm_info->default_realm_id)) {
            $defaultRealmId = (int)$MW->getConfig->generic_values->realm_info->default_realm_id;
            if ($defaultRealmId > 0) {
                $defaultRealm = get_realm_byid($defaultRealmId);
                if (!empty($defaultRealm['name'])) {
                    $talentRealmName = $defaultRealm['name'];
                }
            }
        }

        $talentLink = '/armory/index.php?searchType=profile&charPage=talents&character=' . rawurlencode($talentCharacter);
        if ($talentRealmName !== '') {
            $talentLink .= '&realm=' . rawurlencode($talentRealmName);
        }

        $mainnav_links['4-menuInteractive'][$talentMenuIndex][1] = $talentLink;
    }
}


/* Main Forum Navigation Link */
if ((int)$MW->getConfig->generic_values->forum->frame_forum || (int)$MW->getConfig->generic_values->forum->externalforum) {
    $mainnav_links['6-menuForums'][0][1] = (string)$MW->getConfig->generic_values->forum->forum_external_link;
}

/* Bugs Tracker Navigation Link */
$mainnav_links['8-menuSupport'][1][1] = mw_url('forum', 'viewforum', array('fid' => (int)$MW->getConfig->generic_values->forum->bugs_forum_id));
if ((int)$MW->getConfig->generic_values->forum->frame_bugstracker || (int)$MW->getConfig->generic_values->forum->externalbugstracker == 0) {
    // keep internal
} else {
    $mainnav_links['8-menuSupport'][1][1] = (string)$MW->getConfig->generic_values->forum->bugstracker_external_link;
}

/* ====== Visibility toggles ====== */

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

/* Workshop (Interactive) */
if ((int)$MW->getConfig->components->left_section->Realms_status == 0)          unset($mainnav_links['4-menuInteractive'][0]);
if ((int)$MW->getConfig->components->left_section->Honor == 0)                   unset($mainnav_links['4-menuInteractive'][1]);
if ((int)$MW->getConfig->components->left_section->Players_online == 0)          unset($mainnav_links['4-menuInteractive'][2]);
if ((int)$MW->getConfig->components->left_section->Characters == 0)              unset($mainnav_links['4-menuInteractive'][3]);
if ((int)$MW->getConfig->components->left_section->Playermap == 0)               unset($mainnav_links['4-menuInteractive'][4]);
if ((int)$MW->getConfig->components->left_section->Statistic == 0)               unset($mainnav_links['4-menuInteractive'][5]);
if ((int)$MW->getConfig->components->left_section->ah_system == 0)               unset($mainnav_links['4-menuInteractive'][6]);
if ((int)$MW->getConfig->components->left_section->Armory == 0)                  unset($mainnav_links['4-menuInteractive'][7]);
if ((int)$MW->getConfig->components->left_section->Talent_calc == 0)             unset($mainnav_links['4-menuInteractive'][8]);  // calculator
//if ((int)$MW->getConfig->components->left_section->Armor_sets == 0)              unset($mainnav_links['4-menuInteractive'][9]);
if ((int)$MW->getConfig->components->left_section->Interactive_world_atlas == 0) unset($mainnav_links['4-menuInteractive'][10]);
if ((int)$MW->getConfig->components->left_section->talents == 0 || empty($user['character_name']))
    unset($mainnav_links['4-menuInteractive'][11]);

/* Support */
if ((int)$MW->getConfig->components->left_section->Commands == 0)          unset($mainnav_links['8-menuSupport'][0]);
if ((int)$MW->getConfig->components->left_section->Bug_tracker == 0)       unset($mainnav_links['8-menuSupport'][1]);
if ((int)$MW->getConfig->components->left_section->In_Game_Support == 0)   unset($mainnav_links['8-menuSupport'][2]);
if ((int)$MW->getConfig->components->left_section->Online_GMs == 0)        unset($mainnav_links['8-menuSupport'][3]);

/* Game Guide */
if ((int)$MW->getConfig->components->left_section->Armor_sets == 0)   unset($mainnav_links['3-menuGameGuide'][1]);				//armor sets
if ((int)$MW->getConfig->components->left_section->wow_bc == 0)     unset($mainnav_links['3-menuGameGuide'][3]);
if ((int)$MW->getConfig->components->left_section->wow_wrath == 0)  unset($mainnav_links['3-menuGameGuide'][4]);
if ((int)$MW->getConfig->components->left_section->wow_cata == 0)   unset($mainnav_links['3-menuGameGuide'][5]);

/* Account */
if ((int)$MW->getConfig->components->left_section->retrieve_pass == 0)   unset($mainnav_links['2-menuAccount'][3]);
if ((int)$MW->getConfig->components->left_section->Activate_account == 0)unset($mainnav_links['2-menuAccount'][4]);
if ((int)$MW->getConfig->components->left_section->Character_copy == 0) unset($mainnav_links['2-menuAccount'][5]);
if ((int)$MW->getConfig->components->left_section->Character_tools == 0)unset($mainnav_links['2-menuAccount'][6]);

?>
