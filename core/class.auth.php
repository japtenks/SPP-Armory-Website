<?php
class AUTH {
    var $DB;
    var $user = array(
     'id'    => -1,
     'username'  => 'Guest',
     'g_id' => 1
    );
function check()
{
    global $MW;
    if(isset($_COOKIE[((string)$MW->getConfig->generic->site_cookie)])){
        list($cookie['user_id'], $cookie['account_key']) = @unserialize(stripslashes($_COOKIE[((string)$MW->getConfig->generic->site_cookie)]));
        if($cookie['user_id'] < 1) return false;

        $stmt = $this->DB->prepare("
            SELECT * FROM account
            LEFT JOIN website_accounts ON account.id=website_accounts.account_id
            LEFT JOIN website_account_groups ON website_accounts.g_id=website_account_groups.g_id
            WHERE id = ?");
        $stmt->execute([(int)$cookie['user_id']]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if(get_banned($res['id'], 1)== TRUE){
            $this->setgroup();
            $this->logout();
            output_message('alert','Your account is currently banned');
            return false;
        }



	if (matchAccountKey($cookie['user_id'], $cookie['account_key'])){
    unset($res['sha_pass_hash']);

    // Auto-grant website admin flags based on game GM level so any GM account
    // can access the admin panel without requiring a manual DB update.
    $gmLevel = (int)($res['gmlevel'] ?? 0);
    if ($gmLevel >= 3) {
        $res['g_is_admin'] = 1;
    }
    if ($gmLevel >= 4) {
        $res['g_is_supadmin'] = 1;
    }

    $this->user = $res;

    // Always load characters once the user is authenticated
    $this->load_characters_for_user();

    // Ensure global array exists early for header/menu use
    if (!empty($GLOBALS['characters']) && is_array($GLOBALS['characters'])) {
        $GLOBALS['has_characters'] = true;
    }

    return true;
}
 else {
            $this->setgroup();
            return false;
        }
    } else {
        $this->setgroup();
        return false;
    }
}

function AUTH($DB,$confs)
{
    global $MW;

    $this->DB = spp_get_pdo('realmd', 1);

    $this->check();
    $this->user['ip'] = $_SERVER['REMOTE_ADDR'];
    if((int)$MW->getConfig->generic->onlinelist_on){
        if($this->user['id']<1)$this->onlinelist_addguest();
        else $this->onlinelist_add();
        $this->onlinelist_update();
    }
}

// === Load Characters for Logged-in User (All Realms) ===



function load_characters_for_user() {
    if (empty($this->user['id'])) {
        $GLOBALS['characters'] = [];
        return;
    }

    $realmDbMap = $GLOBALS['realmDbMap'] ?? [];
    $db         = $GLOBALS['db'] ?? [];

    if (!is_array($realmDbMap) || empty($realmDbMap) || empty($db['host'])) {
        $GLOBALS['characters'] = [];
        return;
    }

    $GLOBALS['characters'] = [];

    // Build PDO options once
    $pdoOptions = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $dsnBase = "mysql:host={$db['host']};port={$db['port']};charset=utf8mb4";

    foreach ($realmDbMap as $id => $realmInfo) {
        // Use DB names directly from the map — do NOT go through spp_get_pdo() /
        // spp_resolve_realm_id(), which would override $id with GET/cookie state
        // and cause the same realm's characters to be loaded multiple times.
        $realmdDbName = (string)($realmInfo['realmd'] ?? '');
        $charsDbName  = (string)($realmInfo['chars']  ?? '');
        if ($realmdDbName === '' || $charsDbName === '') {
            continue;
        }

        try {
            $realmdPdo = new PDO("{$dsnBase};dbname={$realmdDbName}", $db['user'], $db['pass'], $pdoOptions);
            $stmt = $realmdPdo->prepare('SELECT name FROM realmlist WHERE id=? LIMIT 1');
            $stmt->execute([(int)$id]);
            $realmName = (string)($stmt->fetchColumn() ?: '');
            if ($realmName === '') {
                $row = $realmdPdo->query("SELECT name FROM realmlist ORDER BY id ASC LIMIT 1")->fetch();
                $realmName = !empty($row['name']) ? (string)$row['name'] : 'Realm ' . $id;
            }

            $charPdo = new PDO("{$dsnBase};dbname={$charsDbName}", $db['user'], $db['pass'], $pdoOptions);
            $stmt = $charPdo->prepare("SELECT guid, name, race, class, level FROM characters WHERE account=? ORDER BY level DESC");
            $stmt->execute([(int)$this->user['id']]);
            $chars = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!is_array($chars) || empty($chars)) {
                continue;
            }

            foreach ($chars as &$char) {
                $char['realm_id']   = $id;
                $char['realm_name'] = $realmName;
            }
            unset($char);

            $GLOBALS['characters'] = array_merge($GLOBALS['characters'], $chars);

        } catch (Exception $e) {
            error_log("[AUTH] Failed loading characters for realm {$id}: " . $e->getMessage());
        }
    }
}


    function setgroup($gid=1) // 1 - guest, 5- banned
    {
        $guest_g = array($this->getgroup($gid));
        $this->user = array_merge($this->user,$guest_g);
    }

    function login($params)
    {
        global $MW;
        $success = 1;
        if (empty($params)) return false;
        if (empty($params['username'])){
            output_message('alert','You did not provide your username');
            $success = 0;
        }
        if (empty($params['password'])){
            output_message('alert','You did not provide your password');
            $success = 0;
        }
        $stmt = $this->DB->prepare("SELECT `id`,`username`,`s`,`v`,`locked` FROM `account` WHERE `username` = ?");
        $stmt->execute([strtoupper($params['username'])]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res) {
            return false;
        }

        if($res['id'] < 1){$success = 0;output_message('alert','Bad username');}
        if(get_banned($res[id], 1)== TRUE){
            output_message('alert','Your account is currently banned');
            $success = 0;
        }
        if($success!=1) return false;
        if (verifySRP6(strtoupper($params['username']), $params['password'], $res['s'], $res['v'])) {
    $this->user['id'] = $res['id'];
    $this->user['username'] = $res['username'];
    $this->user['name'] = $res['username'];

    
    // Load all realm characters for this account
    $this->load_characters_for_user();

    $generated_key = $this->generate_key();
    addOrUpdateAccountKeys($res['id'], $generated_key);

    $uservars_hash = serialize([$res['id'], $generated_key]);
    $cookie_expire_time = intval($MW->getConfig->generic->account_key_retain_length);
    if (!$cookie_expire_time) {
        $cookie_expire_time = 60 * 60 * 24 * 365; // default 1 year
    }

    $cookie_name  = (string)$MW->getConfig->generic->site_cookie;
    $cookie_href  = (string)$MW->getConfig->temp->site_href;
    $cookie_delay = time() + $cookie_expire_time;

    setcookie($cookie_name, $uservars_hash, $cookie_delay, $cookie_href);

    if ((int)$MW->getConfig->generic->onlinelist_on) {
        $this->onlinelist_delguest();
    }

    return true;

} else {
    output_message('alert', 'Your password is incorrect');
    return false;
}}

    function logout()
    {
        global $MW;
        setcookie((string)$MW->getConfig->generic->site_cookie, '', time()-3600,(string)$MW->getConfig->temp->site_href);
        removeAccountKeyForUser($this->user['id']);
        if((int)$MW->getConfig->generic->onlinelist_on)$this->onlinelist_del(); // !!
    }

    function check_pm()
    {
        $stmt = $this->DB->prepare("SELECT count(*) FROM website_pms WHERE owner_id=? AND showed=0");
        $stmt->execute([(int)$this->user['id']]);
        return $stmt->fetchColumn();
    }
    /*
    function lastvisit_update($uservars)
    {
        if($uservars['id']>0){
            if(time() - $uservars['last_visit'] > 60*10){
                $this->DB->query("UPDATE members SET last_visit=?d WHERE id=?d LIMIT 1",time(),$uservars['id']);
            }
        }
    }
    */
    function register($params, $account_extend = false)
    {
        global $MW;
        $success = 1;
        if(empty($params)) return false;
        if(empty($params['username'])){
            output_message('alert','You did not provide your username');
            $success = 0;
        }
        //if(empty($params['sha_pass_hash']) || $params['sha_pass_hash']!=$params['sha_pass_hash2']){
        //    output_message('alert','You did not provide your password or confirm pass');
        //    $success = 0;
        //}
        if(empty($params['email'])){
            //output_message('alert','You did not provide your email');
            //$success = 0;
            $params['email'] = "";
        }

        if($success!=1) return false;
        //unset($params['sha_pass_hash2']);
        $password = $params['password'];
        unset($params['password']);

        // SRP6 support
        list($salt, $verifier) = getRegistrationData(strtoupper($params['username']), $password);
        unset($params['sha_pass_hash']);
        $params['s'] = $salt;
        $params['v'] = $verifier;

        if ($params['expansion'] == '32')
            $params['expansion'] = '2';
        elseif ($params['expansion'] != '0')
            $params['expansion'] = '1';

		//$params['sha_pass_hash'] = strtoup($this->gethash($params['password']));
        //$params['sha_pass_hash'] = $this->gethash($params['password']);
        $tmp_act_key = '';
        if((int)$MW->getConfig->generic->req_reg_act){
            $tmp_act_key = $this->generate_key();
            $params['locked'] = 1;
            $setClause = implode(',', array_map(function($k) { return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?'; }, array_keys($params)));
            $stmt = $this->DB->prepare("INSERT INTO account SET $setClause");
            $stmt->execute(array_values($params));
            $acc_id = (int)$this->DB->lastInsertId();
            if($acc_id > 0){
                $stmt = $this->DB->prepare("INSERT INTO website_accounts SET account_id=?, registration_ip=?, activation_code=?");
                $stmt->execute([$acc_id, $_SERVER['REMOTE_ADDR'], $tmp_act_key]);
                if((int)$MW->getConfig->generic->use_purepass_table) {
                    $stmt = $this->DB->prepare("INSERT INTO account_pass SET id=?, username=?, password=?, email=?");
                    $stmt->execute([$acc_id, $params['username'], $password, $params['email']]);
                }
                $act_link = (string)$MW->getConfig->temp->base_href.'index.php?n=account&sub=activate&id='.$acc_id.'&key='.$tmp_act_key;
                $email_text  = '== Account activation =='."\n\n";
                $email_text .= 'Username: '.$params['username']."\n";
                $email_text .= 'Password: '.$password."\n";
                $email_text .= 'This is your activation key: '.$tmp_act_key."\n";
                $email_text .= 'CLICK HERE : '.$act_link."\n";
                send_email($params['email'],$params['username'],'== '.(string)$MW->getConfig->generic->site_title.' account activation ==',$email_text);
                return true;
            }else{
                return false;
            }
        }else{
            $setClause2 = implode(',', array_map(function($k) { return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?'; }, array_keys($params)));
            $stmt = $this->DB->prepare("INSERT INTO account SET $setClause2");
            $stmt->execute(array_values($params));
            $acc_id = (int)$this->DB->lastInsertId();
            if($acc_id > 0){
                $stmt = $this->DB->prepare("INSERT INTO website_accounts SET account_id=?, registration_ip=?, activation_code=?");
                $stmt->execute([$acc_id, $_SERVER['REMOTE_ADDR'], $tmp_act_key]);
                if((int)$MW->getConfig->generic->use_purepass_table) {
                    $stmt = $this->DB->prepare("INSERT INTO account_pass SET id=?, username=?, password=?, email=?");
                    $stmt->execute([$acc_id, $params['username'], $password, $params['email']]);
                }
                return true;
            } else {
                return false;
            }
        }
    }

    function isavailableusername($username){
        $stmt = $this->DB->prepare("SELECT count(*) FROM account WHERE username=?");
        $stmt->execute([$username]);
        return (int)$stmt->fetchColumn() < 1;
    }

    function isavailableemail($email){
        $stmt = $this->DB->prepare("SELECT count(*) FROM account WHERE email=?");
        $stmt->execute([$email]);
        return (int)$stmt->fetchColumn() < 1;
    }
    function isvalidemail($email){
        if(preg_match('#^.{1,}@.{2,}\..{2,}$#', $email)==1){
            return true; // email is valid
        }else{
            return false; // email is not valid
        }
    }
    function isvalidregkey($key){
        $stmt = $this->DB->prepare("SELECT count(*) FROM site_regkeys WHERE `key`=?");
        $stmt->execute([$key]);
        return (int)$stmt->fetchColumn() > 0;
    }
    function isvalidactkey($key){
        $stmt = $this->DB->prepare("SELECT account_id FROM website_accounts WHERE activation_code=?");
        $stmt->execute([$key]);
        $res = $stmt->fetchColumn();
        if($res > 0) return $res;
        return false;
    }
    function generate_key()
    {
        $str = microtime(1);
        return sha1(base64_encode(pack("H*", md5(utf8_encode($str)))));
    }
    function generate_keys($n)
    {
        set_time_limit(600);
        for($i=1;$i<=$n;$i++)
        {
            if($i>1000)exit;
            $keys[] = $this->generate_key();
            $slt = rand(15000, 500000);
            usleep($slt);
            //sleep(1);
        }
        return $keys;
    }
    function delete_key($key){
        $stmt = $this->DB->prepare("DELETE FROM website_regkeys WHERE `key`=?");
        $stmt->execute([$key]);
    }
    function getprofile($acct_id=false){
        $stmt = $this->DB->prepare("
            SELECT * FROM account
            LEFT JOIN website_accounts ON account.id=website_accounts.account_id
            LEFT JOIN website_account_groups ON website_accounts.g_id=website_account_groups.g_id
            WHERE id=?");
        $stmt->execute([(int)$acct_id]);
        return RemoveXSS($stmt->fetch(PDO::FETCH_ASSOC));
    }
    function getgroup($g_id=false){
        $stmt = $this->DB->prepare("SELECT * FROM website_account_groups WHERE g_id=?");
        $stmt->execute([(int)$g_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    function parsesettings($str){
        $set_pre = explode("\n",$str);
        foreach($set_pre as $set_str){$set_str_arr = explode('=',$set_str); $set[$set_str_arr[0]] = $set_str_arr[1]; }
        return $set;
    }
    function getlogin($acct_id=false){
        $stmt = $this->DB->prepare("SELECT username FROM account WHERE id=?");
        $stmt->execute([(int)$acct_id]);
        $res = $stmt->fetchColumn();
        if($res == null) return false;
        return $res;
    }
    function getid($acct_name=false){
        $stmt = $this->DB->prepare("SELECT id FROM account WHERE username=?");
        $stmt->execute([$acct_name]);
        $res = $stmt->fetchColumn();
        if($res == null) return false;
        return $res;
    }
    function gethash($str=false){
        if($str)return sha1(base64_encode(md5(utf8_encode($str)))); // Returns 40 char hash.
        else return false;
    }

    // ONLINE FUNCTIONS //
    function onlinelist_update()  // Updates list & delete old
    {
        $GLOBALS['guests_online']=0;
        $stmt = $this->DB->query("SELECT * FROM `website_online`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $result_row)
        {
            if(time()-$result_row['logged'] <= 60*10)
            {
                if($result_row['user_id']>0){
                  $GLOBALS['users_online'][] = $result_row['user_name'];
                }else{
                  $GLOBALS['guests_online']++;
                }
            }
            else
            {
                $stmt2 = $this->DB->prepare("DELETE FROM `website_online` WHERE `id`=? LIMIT 1");
                $stmt2->execute([$result_row['id']]);
            }
        }
        //db_query("UPDATE `acm_config` SET `val`='".time()."' WHERE `key`='last_onlinelist_update' LIMIT 1");
        // update_settings('last_onlinelist_update',time());
    }

    function onlinelist_add() // Add or update list with new user
    {
        global $user;

        $cur_time = time();
        $stmt = $this->DB->prepare("SELECT count(*) FROM `website_online` WHERE `user_id`=?");
        $stmt->execute([(int)$this->user['id']]);
        if($stmt->fetchColumn() > 0)
        {
            $stmt = $this->DB->prepare("UPDATE `website_online` SET `user_ip`=?,`logged`=?,`currenturl`=? WHERE `user_id`=? LIMIT 1");
            $stmt->execute([$this->user['ip'], $cur_time, $_SERVER['REQUEST_URI'], (int)$this->user['id']]);
        }
        else
        {
            $stmt = $this->DB->prepare("INSERT INTO `website_online` (`user_id`,`user_name`,`user_ip`,`logged`,`currenturl`) VALUES (?,?,?,?,?)");
            $stmt->execute([(int)$this->user['id'], $this->user['username'], $this->user['ip'], $cur_time, $_SERVER['REQUEST_URI']]);
        }
    }

    function onlinelist_del() // Delete user from list
    {
        global $user;
        $stmt = $this->DB->prepare("DELETE FROM `website_online` WHERE `user_id`=? LIMIT 1");
        $stmt->execute([(int)$this->user['id']]);
    }

    function onlinelist_addguest() // Add or update list with new guest
    {
        global $user;

        $cur_time = time();
        $stmt = $this->DB->prepare("SELECT count(*) FROM `website_online` WHERE `user_id`='0' AND `user_ip`=?");
        $stmt->execute([$this->user['ip']]);
        if($stmt->fetchColumn() > 0)
        {
            $stmt = $this->DB->prepare("UPDATE `website_online` SET `user_ip`=?,`logged`=?,`currenturl`=? WHERE `user_id`='0' AND `user_ip`=? LIMIT 1");
            $stmt->execute([$this->user['ip'], $cur_time, $_SERVER['REQUEST_URI'], $this->user['ip']]);
        }
        else
        {
            $stmt = $this->DB->prepare("INSERT INTO `website_online` (`user_ip`,`logged`,`currenturl`) VALUES (?,?,?)");
            $stmt->execute([$this->user['ip'], $cur_time, $_SERVER['REQUEST_URI']]);
        }
    }

    function onlinelist_delguest() // Delete guest from list
    {
        global $user;
        $stmt = $this->DB->prepare("DELETE FROM `website_online` WHERE `user_id`='0' AND `user_ip`=? LIMIT 1");
        $stmt->execute([$this->user['ip']]);
    }
}
?>



