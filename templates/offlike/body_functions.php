<?php
$templategenderimage = array(
    0 => $currtmp.'/images/pixel.gif',
    1 => $currtmp.'/images/icons/male.gif',
    2 => $currtmp.'/images/icons/female.gif'
);

function population_view($n) {
    global $lang;
    $maxlow = 500;
    $maxmedium = 700;
    $maxhigh = 2000;
    if($n <= $maxlow){
        return '<font color="green">' . $lang['low'] . '</font>';
    }elseif($n > $maxlow && $n <= $maxmedium){
        return '<font color="orange">' . $lang['medium'] . '</font>';
    }elseif($n > $maxmedium && $n <= $maxhigh){
        return '<font color="red">' . $lang['high'] . '</font>';
    }
    else
        return '<font color="red">' . $lang['full'] . '</font>';
}

function build_menu_items($links_arr){
    global $user;
    global $lang;
    $r = "\n";
    foreach($links_arr as $menu_item){
        $ignore_item = 0;
        if($menu_item[2]) {


            $do_menu_excl = explode('!',$menu_item[2]);
            if(count($do_menu_excl) == 2) {
                if($user[$do_menu_excl[1]]) {
                    $ignore_item = 1;
                }
            }
            else {
                if(!$user[$do_menu_excl[0]]) {
                    $ignore_item = 1;
                }
            }
        }
        if(!$ignore_item && isset($menu_item[0]) && isset($lang[$menu_item[0]]))
            $r .='                                                <div><a class="menufiller" href="'.$menu_item[1].'">'.$lang[$menu_item[0]].'</a></div>'."\n";
    }
    return $r;
}

// ------------------ MAIN MENU ------------------
function build_main_menu($asList = false) {
    global $mainnav_links, $lang;
    if (empty($mainnav_links)) return;

    foreach ($mainnav_links as $menuname => $menuitems) {
                // Skip unwanted menus
        if (
            stripos($menuname, 'account') !== false || 
            stripos($menuname, 'news')    !== false
        ) continue;

        if (count($menuitems) > 0) {
            $menukey   = preg_replace('/^\d+-/', '', $menuname);
            $menulabel = $lang[$menukey] ?? $menukey;

            if (!$asList) {
                echo '<div class="menu-block">';
                foreach ($menuitems as $item) {
                    if (isset($item[0], $lang[$item[0]])) {
                        if ($menukey === "community" && $item[0] === "server_rules") {
                            echo '<div class="menu-item"><a href="'.$item[1].'">'.$lang[$item[0]].'</a></div>';
                        } elseif ($menukey !== "community" || $item[0] !== "server_rules") {
                            echo '<div class="menu-item"><a href="'.$item[1].'">'.$lang[$item[0]].'</a></div>';
                        }
                    }
                }
                echo '</div>';
            } else {
                echo '<li class="has-sub"><a href="#">'.$menulabel.'</a><ul>';
                foreach ($menuitems as $item) {
                    if (isset($item[0], $lang[$item[0]])) {
                        if ($menukey === "community" && $item[0] === "server_rules") {
                            echo '<li><a href="'.$item[1].'">'.$lang[$item[0]].'</a></li>';
                        } elseif ($menukey !== "community" || $item[0] !== "server_rules") {
                            echo '<li><a href="'.$item[1].'">'.$lang[$item[0]].'</a></li>';
                        }
                    }
                }
                echo '</ul></li>';
            }
        }
    }

// Place Languages after the last main menu entry (Support)

//build_language_menu($asList);

}

function build_serverinfo_menu($asList = true) {
    global $servers, $lang, $MW;

    // Bail out early if no servers
    if (empty($servers)) return;

    $cfg = $MW->getConfig->components->server_information;

    if ($asList) {
        echo '<li class="has-sub"><a href="#">🖥️ '.$lang['serverinfo'].'</a><ul>';

        if (!empty($servers)) {
            foreach ($servers as $server) {
                echo '<li><div class="serverinfo-tooltip">';

                echo '<div class="info-row"><span class="label">'.$lang['si_name'].':</span> 
                      <span class="value"><b>'.$server['name'].'</b></span></div>';

                if (!empty($cfg->realm_status)) {
                    $status = $server['realm_status']
                        ? '<span class="status-online">▲ '.$lang['online'].'</span>'
                        : '<span class="status-offline">▼ '.$lang['offline'].'</span>';
                    echo '<div class="info-row"><span class="label">'.$lang['si_status'].':</span> 
                          <span class="value">'.$status.'</span></div>';
                }

                if (!empty($cfg->server_ip)) {
                    echo '<div class="info-row"><span class="label">'.$lang['si_ip'].':</span> 
                          <span class="value">'.$server['server_ip'].'</span></div>';
                }

                if (!empty($cfg->online)) {
                    echo '<div class="info-row"><span class="label">'.$lang['si_pop'].':</span>';
                    if (!empty($server['realm_status'])) {
                        echo '<span class="value"><a href="index.php?n=server&sub=playermap" class="maplink">Player Map</a></span>';
                    } else {
                        echo '<span class="value"><span class="maplink-offline">Player Map</span></span>';
                    }
                    echo '</div>';
                }

                if (!empty($cfg->population)) {
                    echo '<div class="info-row"><span class="label">'.$lang['si_bon'].':</span> 
                          <span class="value status-online">'.$server['population'].'</span></div>';
                }

                if (!empty($cfg->online)) {
                    echo '<div class="info-row"><span class="label">'.$lang['si_on'].':</span> 
                          <span class="value">'.$server['realplayersonline'].'</span></div>';
                }

                if (!empty($cfg->characters)) {
                    echo '<div class="info-row"><span class="label">'.$lang['si_chars'].':</span> 
                          <span class="value">'.$server['characters'].'</span></div>';
                }

                if (!empty($cfg->accounts)) {
                    $acctInfo = $server['accounts'];
                    if (!empty($cfg->active_accounts)) {
                        $acctInfo .= ' <span class="small">('.$server['active_accounts'].' active)</span>';
                    }
                    echo '<div class="info-row"><span class="label">'.$lang['si_acc'].':</span> 
                          <span class="value">'.$acctInfo.'</span></div>';
                }

                echo '</div></li>';
            }
        } else {
            echo '<li><div class="serverinfo-tooltip"><div class="info-row"><span class="label">'.$lang['serverinfo'].':</span> 
                  <span class="value"><i>No data</i></span></div></div></li>';
        }

        echo '</ul></li>';
    }
}

// ------------------ ACCOUNT MENU ------------------
function build_account_menu($asList = true) {
    global $user, $auth, $languages;

    // Pick display name: current char if available, else "Account"
    $charName = "Account";
    if (!empty($GLOBALS['characters']) && !empty($user["character_id"])) {
        foreach ($GLOBALS['characters'] as $character) {
            if ($character["guid"] == $user["character_id"]) {
                $charName = htmlspecialchars($character["name"]);
                break;
            }
        }
    }

if ($asList) {
    echo '<li class="has-sub account-dropdown">';
    echo '<a href="#">';
    echo '<span class="account-icon">👤</span>';
    echo '<span class="account-name">'.$charName.' ▼</span>';
    echo '</a>';
    echo '<ul>';
}


    if ($user['id'] <= 0) {
        echo '<li><a href="index.php?n=account&sub=login">Login</a></li>';
        echo '<li><a href="index.php?n=account&sub=register">Register</a></li>';
    } else {

        // Messages + Userlist
        if ($user["g_use_pm"]) {
            $userpm_num = $auth->check_pm();
            $label = ($userpm_num > 0) ? "Messages ($userpm_num)" : "Messages";
            echo '<li><a href="' . mw_url('account','pms') . '">' . $label . '</a></li>';
            echo '<li><a href="index.php?n=account&sub=userlist">Userlist</a></li>';
        }

        // Admin Panel (GM only)
        if (!empty($user['gmlevel']) && $user['gmlevel'] > 0) {
            echo '<li><a href="index.php?n=admin">Admin Panel</a></li>';
        }

        // Characters list
        if (!empty($GLOBALS['characters'])) {
            foreach ($GLOBALS['characters'] as $character) {
                $isActive = ($user["character_id"] == $character["guid"]);
                $activeMark = $isActive ? "&gt; " : "";
                $class = $isActive ? "char-item active-char" : "char-item";

                echo '<li class="'.$class.'"><a href="index.php" 
                      onclick="document.cookie=\'cur_selected_character='.$character['guid'].'; path=/\';">'
                      .$activeMark.htmlspecialchars($character['name']).'</a></li>';
            }
            echo '<li class="menu-spacer"></li>';
        }

        // Logout
        echo '<li><a href="?n=account&sub=login&action=logout">Logout</a></li>';
    }



    if ($asList) {
        echo '</ul></li>';
    }
}

// ------------------ RIGHT MENU ------------------
function build_right_menu($wrap = true) {
    global $user, $languages, $DB, $MW;
    if ($wrap) echo "<ul class='right-menu accordion-menu'>";

    if ($user['id'] > 0 && !empty($GLOBALS['characters'])) {
        echo '<li class="has-sub"><a href="#">'.lang('character_menu',false).'</a><ul>';
        foreach ($GLOBALS['characters'] as $character) {
            $active = ($user["character_id"] == $character["guid"]) ? "&gt; " : "";
            echo '<li><a href="javascript:setcookie(\'cur_selected_character\', \''.$character['guid'].'\'); window.location.reload();">'
                 .$active.htmlspecialchars($character['name']).'</a></li>';
        }
        echo '</ul></li>';
    }

    if (!empty($GLOBALS['context_menu'])) {
        echo '<li class="has-sub"><a href="#">'.lang('context_menu',false).'</a><ul>';
        foreach ($GLOBALS['context_menu'] as $cmenuitem) {
            echo '<li><a href="'.$cmenuitem['link'].'">'.htmlspecialchars($cmenuitem['title']).'</a></li>';
        }
        echo '</ul></li>';
    }

    if (!empty($languages)) {
        echo '<li class="has-sub"><a href="#">'.lang('choose_lang',false).'</a><ul>';
        foreach ($languages as $lang_s => $lang_name) {
            $active = ($GLOBALS['user_cur_lang'] == $lang_s) ? "&gt; " : "";
            echo '<li><a href="javascript:setcookie(\'Language\', \''.$lang_s.'\'); window.location.reload();">'
                 .$active.htmlspecialchars($lang_name).'</a></li>';
        }
        echo '</ul></li>';
    }

    if ((int)$MW->getConfig->generic_values->realm_info->multirealm) {
        $realms = $DB->select("SELECT `id`,`name` FROM `realmlist` ORDER by id DESC");
        if (!empty($realms)) {
            echo '<li class="has-sub"><a href="#">'.lang('realm_menu',false).'</a><ul>';
            foreach ($realms as $realm) {
                $active = ($user['cur_selected_realmd'] == $realm['id']) ? "&gt; " : "";
                echo '<li><a href="javascript:setcookie(\'cur_selected_realmd\', \''.$realm['id'].'\'); window.location.reload();">'
                     .$active.htmlspecialchars($realm['name']).'</a></li>';
            }
            echo '</ul></li>';
        }
    }

    if ($wrap) echo "</ul>";
}

function build_language_menu($asList = true) {
    global $languages;

    if (empty($languages)) return;

    if ($asList) {
        echo '<li class="has-sub"><a href="#">🌐 Languages</a><ul>';
        foreach ($languages as $lang_s => $lang_name) {
            $active = ($GLOBALS['user_cur_lang'] == $lang_s) ? "&gt; " : "";
            echo '<li><a href="javascript:setcookie(\'Language\', \''.$lang_s.'\'); window.location.reload();">'
                 .$active.htmlspecialchars($lang_name).'</a></li>';
        }
        echo '</ul></li>';
    } else {
        echo '<div class="menu-block has-sub">';
        echo '<a href="#">🌐 Languages</a>';
        echo '<div class="menu-sub">';
        foreach ($languages as $lang_s => $lang_name) {
            $active = ($GLOBALS['user_cur_lang'] == $lang_s) ? "&gt; " : "";
            echo '<div class="menu-item"><a href="javascript:setcookie(\'Language\', \''.$lang_s.'\'); window.location.reload();">'
                 .$active.htmlspecialchars($lang_name).'</a></div>';
        }
        echo '</div></div>';
    }
}


function write_subheader($subheader){
    global $MW;
	global $currtmp;
    echo '<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody><tr>
    <td width="24"><img src="'.$currtmp.'/images/subheader-left-sword.gif" height="20" width="24" alt=""/></td>
    <td bgcolor="#05374a" width="100%"><b style="color:white;">'.$subheader.':</b></td>
    <td width="10"><img src="'.$currtmp.'/images/subheader-right.gif" height="20" width="10" alt=""/></td>
</tr>
</tbody></table>';
}
function write_metalborder_header(){
    global $MW;
	global $currtmp;
    echo '<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody>
<tr>
    <td width="12"><img src="'.$currtmp.'/images/metalborder-top-left.gif" height="12" width="12" alt=""/></td>
    <td style="background:url(\''.$currtmp.'/images/metalborder-top.gif\');"></td>
    <td width="12"><img src="'.$currtmp.'/images/metalborder-top-right.gif" height="12" width="12" alt=""/></td>
</tr>
<tr>
    <td style="background:url(\''.$currtmp.'/images/metalborder-left.gif\');"></td>
    <td>
';
}

function write_metalborder_footer(){
    global $MW;
	global $currtmp;
    echo '        </td>
        <td style="background:url(\''.$currtmp.'/images/metalborder-right.gif\');"></td>
    </tr>
    <tr>
        <td><img src="'.$currtmp.'/images/metalborder-bot-left.gif" height="11" width="12" alt=""/></td>
        <td style="background:url(\''.$currtmp.'/images/metalborder-bot.gif\');"></td>
        <td><img src="'.$currtmp.'/images/metalborder-bot-right.gif" height="11" width="12" alt=""/></td>
    </tr>
    </tbody>
</table>
';
}

function write_form_tool(){
    global $MW;
	global $currtmp;
    $template_href = $currtmp . "/";
?>
        <div id="form_tool">
            <ul id="bbcode_tool">
                <li id="bbcode_b"><a href="#"><img src="<?php echo $template_href;?>images/button-bold.gif" alt="<?php lang('editor_bold'); ?>" title="<?php lang('editor_bold'); ?>"></a></li>
                <li id="bbcode_i"><a href="#"><img src="<?php echo $template_href;?>images/button-italic.gif" alt="<?php lang('editor_italic'); ?>" title="<?php lang('editor_italic'); ?>"></a></li>
                <li id="bbcode_u"><a href="#"><img src="<?php echo $template_href;?>images/button-underline.gif" alt="<?php lang('editor_underline'); ?>" title="<?php lang('editor_underline'); ?>"></a></li>
                <li id="bbcode_url"><a href="#"><img src="<?php echo $template_href;?>images/button-url.gif" alt="<?php lang('editor_link'); ?>" title="<?php lang('editor_link'); ?>"></a></li>
                <li id="bbcode_img"><a href="#"><img src="<?php echo $template_href;?>images/button-img.gif" alt="<?php lang('editor_image'); ?>" title="<?php lang('editor_image'); ?>"></a></li>
                <li id="bbcode_blockquote"><a href="#"><img src="<?php echo $template_href;?>images/button-quote.gif" alt="<?php lang('editor_quote'); ?>" title="<?php lang('editor_quote'); ?>"></a></li>
            </ul>
            <ul id="text_tool">
                <li id="text_size"><a href="#"><img src="<?php echo $template_href;?>images/button-size.gif" alt="<?php lang('editor_size'); ?>" title="<?php lang('editor_size'); ?>"></a>
                    <ul>
                        <li id="text_size-hugesize"><a href="#">Huge</a></li>
                        <li id="text_size-largesize"><a href="#">Large</a></li>
                        <li id="text_size-mediumsize"><a href="#">Medium</a></li>
                    </ul>
                </li>
                <li id="text_color"><a href="#"><img src="<?php echo $template_href;?>images/button-color.gif" alt="<?php lang('editor_color'); ?>" title="<?php lang('editor_color'); ?>"></a>
                    <ul>
                        <li id="text_color-red"><a href="#"><?php lang('editor_color_red'); ?></a></li>
                        <li id="text_color-green"><a href="#"><?php lang('editor_color_green'); ?></a></li>
                        <li id="text_color-blue"><a href="#"><?php lang('editor_color_blue'); ?></a></li>
                        <li id="text_color-custom"><a href="#"><?php lang('editor_color_custom'); ?></a></li>
                    </ul>
                </li>
                <li id="text_align"><a href="#"><img src="<?php echo $template_href;?>images/button-list.gif" alt="<?php lang('editor_align'); ?>" title="<?php lang('editor_align'); ?>"></a>
                    <ul>
                        <li id="text_align-left"><a href="#"><?php lang('editor_align_left'); ?></a></li>
                        <li id="text_align-right"><a href="#"><?php lang('editor_align_right'); ?></a></li>
                        <li id="text_align-center"><a href="#"><?php lang('editor_align_center'); ?></a></li>
                        <li id="text_align-justify"><a href="#"><?php lang('editor_align_justify'); ?></a></li>
                    </ul>
                </li>
                <li id="text_smile"><a href="#"><img src="<?php echo $template_href;?>images/button-emote.gif" alt="<?php lang('editor_smile'); ?>" title="<?php lang('editor_smile'); ?>"></a>
                    <ul>
<?php
$smiles = load_smiles();
$smilepath = (string)$MW->getConfig->generic->smiles_path;
foreach($smiles as $smile):
    $smilename = ucfirst(str_replace('.gif','',str_replace('.png','',$smile)));
?>
                        <li id="text_smile-<?php echo $smilepath.$smile;?>"><a href="#" title="<?php echo $smilename;?>"><img src="<?php echo $smilepath.$smile;?>" alt="<?php echo $smilename;?>"></a></li>
<?php
endforeach;
?>
                    </ul>
                </li>
            </ul>
        </div>
<?php
}

function random_screenshot(){
  $fa = array();
  if ($handle = opendir('images/screenshots/thumbs/')) {
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && $file != "Thumbs.db" && $file != "index.html") {
            $fa[] = $file;
        }
    }
    closedir($handle);
  }
  $fnum = count($fa);
  $fpos = rand(0, $fnum-1);
  return $fa[$fpos];
}
function build_pathway(){
    global $lang;
    global $pathway_info;
    global $title_str,$pathway_str;
    $path_info2 = array($pathway_info);
    $path_c = count($path_info2);
    $pathway_info[$path_c-1]['link'] = '';
    $pathway_str = '';
    if(empty($_REQUEST['n']) || !is_array($pathway_info))$pathway_str .= ' <b><u>'.$lang['mainpage'].'</u></b>';
    else $pathway_str .= '<a href="./">'.$lang['mainpage'].'</a>';
    if(is_array($pathway_info)){
        foreach($pathway_info as $newpath){
            if(isset($newpath['title'])){
                if(empty($newpath['link'])) $pathway_str .= ' &raquo; '.$newpath['title'].'';
                else $pathway_str .= ' &raquo; <a href="'.$newpath['link'].'">'.$newpath['title'].'</a>';
                $title_str .= ' &raquo; '.$newpath['title'];
            }
        }
    }
    $pathway_str .= '';
}
// !!!!!!!!!!!!!!!! //
build_pathway();

function load_banners($type){
    global $DB;
    $result = $DB->select("SELECT * FROM banners WHERE type=?d ORDER BY num_click DESC",$type);
    return $result;
}

function paginate($num_pages, $cur_page, $link_to){
  $pages = array();
  $link_to_all = false;
  if ($cur_page == -1)
  {
    $cur_page = 1;
    $link_to_all = true;
  }
  if ($num_pages <= 1)
    $pages = array('1');
  else
  {
    $tens = floor($num_pages/10);
    for ($i=1;$i<=$tens;$i++)
    {
      $tp = $i*10;
      $pages[$tp] = "<a href='$link_to&p=$tp'>$tp</a>";
    }
    if ($cur_page > 3)
    {
      $pages[1] = "<a href='$link_to&p=1'>1</a>";
    }
    for ($current = $cur_page - 2, $stop = $cur_page + 3; $current < $stop; ++$current)
    {
      if ($current < 1 || $current > $num_pages){
        continue;
      }elseif ($current != $cur_page || $link_to_all){
        $pages[$current] = "<a href='$link_to&p=$current'>$current</a>";
      }else{
        $pages[$current] = '[ '.$current.' ]';
      }
    }
    if ($cur_page <= ($num_pages-3))
    {
      $pages[$num_pages] = "<a href='$link_to&p=$num_pages'>$num_pages</a>";
    }
  }
  $pages = array_unique($pages);
  ksort($pages);
  $pp = implode(' ', $pages);
  return str_replace('//','/',$pp);
}

function builddiv_start($type = 0, $title = "No title set") {
  echo '<div class="modern-wrapper">';
  if ($title !== "No title set") {
    echo '<div class="modern-header">'.$title.'</div>';
  }
  echo '<div class="modern-content">';
}

function builddiv_end() {
  echo '</div></div>';
}

?>

<?php

function compact_paginate($current, $total, $base) {
      $html = '';
      $range = 2;
      $show_first = $current > $range + 2;
      $show_last  = $current < $total - ($range + 1);

      if ($current > 1)
        $html .= '<a href="'.$base.'&p='.($current-1).'">« Prev</a> ';

      if ($show_first)
        $html .= '<a href="'.$base.'&p=1">1</a> … ';

      for ($i = max(1, $current-$range); $i <= min($total, $current+$range); $i++) {
        $active = $i == $current ? 'class="active"' : '';
        $html .= '<a '.$active.' href="'.$base.'&p='.$i.'">'.$i.'</a> ';
      }

      if ($show_last)
        $html .= '… <a href="'.$base.'&p='.$total.'">'.$total.'</a> ';

      if ($current < $total)
        $html .= '<a href="'.$base.'&p='.($current+1).'">Next »</a>';

      return $html;
    }
	
function render_page_size_form($items_per_page, $extra_params = [], $show_bots = true, $show_per_page = true) {
    // detect current page params
    $n   = isset($_GET['n'])   ? htmlspecialchars($_GET['n'])   : '';
    $sub = isset($_GET['sub']) ? htmlspecialchars($_GET['sub']) : '';

    // persistent GET vars
    $persist = ['char', 'lvl', 'minlvl', 'maxlvl', 'class', 'race', 'p'];
    $persist = array_unique(array_merge($persist, $extra_params));

    echo '<form method="get" class="page-size-form">';
    if ($n)   echo '<input type="hidden" name="n" value="' . $n . '">';
    if ($sub) echo '<input type="hidden" name="sub" value="' . $sub . '">';

    // preserve other query parameters
    foreach ($persist as $param) {
        if (isset($_GET[$param]) && $param !== 'per_page') {
            echo '<input type="hidden" name="' . htmlspecialchars($param) . 
                 '" value="' . htmlspecialchars($_GET[$param]) . '">';
        }
    }

    // per-page selector
    if ($show_per_page) {
        echo '<label for="per_page">Show:</label>';
        echo '<select id="per_page" name="per_page" onchange="this.form.submit()">';
        foreach ([10, 25, 50, 100] as $opt) {
            $sel = ($items_per_page == $opt) ? 'selected' : '';
            echo "<option value=\"$opt\" $sel>$opt</option>";
        }
        echo '</select> <span>per page</span>';
    }

    // show bots checkbox
    if ($show_bots) {
        $checked = !empty($_GET['show_bots']) ? 'checked' : '';
        echo '<label style="margin-left:10px;">';
        echo '<input type="hidden" name="show_bots" value="0">';
        echo '<input type="checkbox" name="show_bots" value="1" onchange="this.form.submit()" ' . $checked . '>';
        echo ' Include bots</label>';
    }

    echo '</form>';
}

?>
