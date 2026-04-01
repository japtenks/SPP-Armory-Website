<?php

$talentParams = array();
if (!empty($user['character_name'])) {
    $talentParams['character'] = $user['character_name'];
}
if (!empty($user['cur_selected_realmd'])) {
    $talentParams['realm'] = (int)$user['cur_selected_realmd'];
}
$talentsLink = mw_url('server', 'talents', $talentParams);
$com_content['server'] = array(
    'index' => array(
        '', // g_ option require for view     [0]
        'server', // loc name (key)                [1]
        'index.php?n=server', // Link to                 [2]
        '', // main menu name/id ('' - not show)        [3]
        0 // show in context menu (1-yes,0-no)          [4]
    ),
	'botcommands' => array(
    '', 
    '(Bot) Commands', 
    mw_url('server', 'botcommands'),
    '3-menuGameGuide',
    0
),

    'chars' => array(
        '', 
        'Characters on the server', 
        mw_url('server', 'chars'),
        '4-menuInteractive',
        0
    ),
    'character' => array(
        '',
        'Character',
        mw_url('server', 'character'),
        '',
        0
    ),
    'guilds' => array(
        '',
        'Guilds on the server',
        mw_url('server', 'guilds'),
        '4-menuInteractive',
        0
    ),
    'guild' => array(
        '',
        'Guild',
        mw_url('server', 'guild'),
        '',
        0
    ),
    'realmstatus' => array(
        '', 
        'realms_status', 
        mw_url('server', 'realmstatus'),
        '4-menuInteractive',
        0
    ),
    'honor' => array(
        '', 
        'honor', 
        mw_url('server', 'honor'),
        '4-menuInteractive',
        0
    ),
    'playersonline' => array(
        '', 
        'players_online', 
        mw_url('server', 'playersonline'),
        '4-menuInteractive',
        0
    ),
    'playermap' => array(
        '', 
        'Player Map', 
        mw_url('server', 'playermap'),
        '4-menuInteractive',
        0
    ),
    'talents' => array(
        '', 
        'Talents', 
        $talentsLink,
        '4-menuInteractive',
        0
    ),
    'items' => array(
        '',
        'items',
        mw_url('server', 'items'),
        '7-menuArmory',
        0
    ),
    'marketplace' => array(
        '',
        'Market Place',
        mw_url('server', 'marketplace'),
        '7-menuArmory',
        0
    ),
    'item' => array(
        '',
        'item',
        mw_url('server', 'item'),
        '',
        0
    ),
    'statistic' => array(
        '', 
        'statistic', 
        mw_url('server', 'statistic'),
        '4-menuInteractive',
        0
    ),
    'ah' => array(
        '',
        'ah',
        mw_url('server', 'ah'),
        '4-menuGameGuide',
        0
    ),
    'sets' => array(
        '', 
        'Armor Sets', 
        mw_url('server', 'sets'),
        '3-menuGameGuide',
        0
    ),
    'downloads' => array(
        '',
        'Downloads',
        mw_url('server', 'downloads'),
        '4-menuWorkshop',
        0
    ),
    'realmlist' => array(
        '',
        'Realm List Download',
        mw_url('server', 'realmlist'),
        '',
        0
    ),
    'itemtooltip' => array(
        '',
        'Item Tooltip',
        mw_url('server', 'itemtooltip'),
        '',
        0
    ),
    'rules' => array(
        '', 
        'rules', 
        mw_url('server', 'rules'),
        '4-menuGameGuide',
        0
    ),
);
?>













