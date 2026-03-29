<?php
if(INCLUDED !== true)
    exit();

$postnum = 0;
$hl = '';

if((int)$MW->getConfig->generic_values->forum->news_forum_id == 0)
    output_message('alert', 'Please define forum id for news (in config/config.xml)');

$realmId  = spp_resolve_realm_id($realmDbMap);
$realmPdo = spp_get_pdo('realmd', $realmId);

$newsFid = (int)$MW->getConfig->generic_values->forum->news_forum_id;
$thirtyDaysAgo = time() - (30 * 86400);
$stmtTopics = $realmPdo->prepare("
    SELECT f_topics.*,(SELECT message FROM f_posts WHERE f_topics.topic_id=f_posts.topic_id ORDER BY f_posts.posted LIMIT 1) as message
    FROM f_topics
    WHERE f_topics.forum_id=? AND f_topics.topic_posted >= ?
    ORDER BY topic_posted DESC");
$stmtTopics->execute([$newsFid, $thirtyDaysAgo]);
$alltopics = $stmtTopics->fetchAll(PDO::FETCH_ASSOC);
if (empty($alltopics)) {
    $stmtTopics = $realmPdo->prepare("
        SELECT f_topics.*,(SELECT message FROM f_posts WHERE f_topics.topic_id=f_posts.topic_id ORDER BY f_posts.posted LIMIT 1) as message
        FROM f_topics
        WHERE f_topics.forum_id=?
        ORDER BY topic_posted DESC
        LIMIT 1");
    $stmtTopics->execute([$newsFid]);
    $alltopics = $stmtTopics->fetchAll(PDO::FETCH_ASSOC);
}

if (!empty($alltopics)) {
    foreach ($alltopics as &$topic) {
        $rawMessage = (string)($topic['message'] ?? '');
        $normalizedMessage = str_replace(
            array('<br />', '<br/>', '<br>'),
            "\n",
            html_entity_decode($rawMessage, ENT_QUOTES, 'UTF-8')
        );
        $topic['rendered_message'] = bbcode($normalizedMessage, true, true, true, false);
    }
    unset($topic);
}

if ((int)$MW->getConfig->components->right_section->hitcounter){
    $count_my_page = "templates/offlike/hitcounter.txt";
    $hits = (int)file_get_contents($count_my_page);
    $hits++;
    file_put_contents($count_my_page, $hits);
}

$servers = array();
$multirealms = $realmPdo->query("SELECT * FROM `realmlist` ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($multirealms as $realmnow_arr){
    if((int)$MW->getConfig->components->right_section->server_information){
        $stmtData = $realmPdo->prepare("SELECT address, port, timezone, icon, name FROM realmlist WHERE id = ? LIMIT 1");
        $stmtData->execute([(int)$realmnow_arr['id']]);
        $data = $stmtData->fetch(PDO::FETCH_ASSOC);

        if (!$data)
            continue;

        $server = array();
        $server['name'] = $data['name'];
        if((int)$MW->getConfig->components->server_information->realm_status){
            $checkaddress = (int)$MW->getConfig->generic->use_local_ip_port_test ? '127.0.0.1' : $data['address'];
            $server['realm_status'] = check_port_status($checkaddress, $data['port']);
        }
        $changerealmtoparam = array("changerealm_to" => $realmnow_arr['id']);

        $_fpRealmdDb = $realmDbMap[(int)$realmnow_arr['id']]['realmd'] ?? 'classicrealmd';
        try {
            $charPdo = spp_get_pdo('chars', (int)$realmnow_arr['id']);
            if((int)$MW->getConfig->components->server_information->online){
                $server['playersonline'] = (int)$charPdo->query("SELECT count(1) FROM `characters` WHERE online=1 AND account IN (SELECT id FROM `{$_fpRealmdDb}`.`account` WHERE LOWER(username) LIKE 'rndbot%')")->fetchColumn();
                $server['onlineurl'] = mw_url('server', 'playersonline', $changerealmtoparam);
                $server['realplayersonline'] = (int)$charPdo->query("SELECT count(1) FROM `characters` WHERE online=1 AND account NOT IN (SELECT id FROM `{$_fpRealmdDb}`.`account` WHERE LOWER(username) LIKE 'rndbot%')")->fetchColumn();
            }
            if((int)$MW->getConfig->components->server_information->population){
                $server['population'] = (int)$charPdo->query("SELECT count(1) FROM `characters` WHERE online=1")->fetchColumn();
            }
            if((int)$MW->getConfig->components->server_information->characters){
                $server['characters'] = (int)$charPdo->query("SELECT count(1) FROM `characters` WHERE account NOT IN (SELECT id FROM `{$_fpRealmdDb}`.`account` WHERE LOWER(username) LIKE 'rndbot%')")->fetchColumn();
            }
        } catch (PDOException $e) { /* realm chars DB not available */ }

        if((int)$MW->getConfig->components->left_section->Playermap){
            $server['playermapurl'] = mw_url('server', 'playermap', $changerealmtoparam);
        }
        if((int)$MW->getConfig->components->server_information->server_ip){
            $server['server_ip'] = $data['address'];
        }
        if((int)$MW->getConfig->components->server_information->type){
            $server['type'] = $realm_type_def[$data['icon']];
        }
        if((int)$MW->getConfig->components->server_information->language){
            $server['language'] = $realm_timezone_def[$data['timezone']];
        }
        if((int)$MW->getConfig->components->server_information->accounts){
            $server['accounts'] = (int)$realmPdo->query("SELECT count(1) FROM `account` WHERE LOWER(username) NOT LIKE 'rndbot%'")->fetchColumn();
        }
        //updated code to current
        if((int)$MW->getConfig->components->server_information->active_accounts){
            $activeDate = date("Y-m-d H:i:s", strtotime("-2 week")) . " 00:00:00";
            $stmtAA = $realmPdo->prepare("SELECT count(DISTINCT accountId) FROM `account_logons` WHERE `loginTime` > ?");
            $stmtAA->execute([$activeDate]);
            $server['active_accounts'] = (int)$stmtAA->fetchColumn();
            $stmtAL = $realmPdo->prepare("SELECT count(*) FROM `account_logons` WHERE `loginTime` > ?");
            $stmtAL->execute([$activeDate]);
            $server['active_login'] = (int)$stmtAL->fetchColumn();
        }
        unset($data);
        $init = 'id_' . $realmnow_arr['id'];
        if((int)$MW->getConfig->components->right_section->server_rates && (string)$MW->getConfig->mangos_conf_external->$init->mangos_world_conf != ''){
            $server['rates'] = getMangosConfig($MW->getConfig->mangos_conf_external->$init->mangos_world_conf);
        }
        $server['moreinfo'] = (int)$MW->getConfig->components->server_information->more_info && (string)$MW->getConfig->mangos_conf_external->$init->mangos_world_conf != '';
        $servers[] = $server;
    }
}
unset($multirealms);

if((int)$MW->getConfig->components->right_section->users_on_homepage){
    $usersonhomepage = (int)$realmPdo->query("SELECT count(1) FROM `online`")->fetchColumn();
}
