<?php
if(INCLUDED!==true)exit;

$pathway_info[] = array('title'=>$lang['site_config'],'link'=>'');
require_once(__DIR__ . '/admin.config.read.php');

$configView = spp_admin_config_build_view($MW->getConfig);
$configfilepath = $configView['configfilepath'];
$config = $configView['config'];
$configCount = $configView['configCount'];

?>
