<?php
if(INCLUDED!==true)exit;
// ==================== //
$pathway_info[] = array('title'=>'Character Tools', 'link'=>'index.php?n=admin&sub=chartools');
// ==================== //
?>
<?php
include "chartools/charconfig.php";
include "chartools/add.php";
include "chartools/functionstransfer.php";
include "chartools/functionsrename.php";
include "chartools/functionsrace.php";
include "chartools/tabs.php";

$adminChartoolsCsrfToken = spp_csrf_token('admin_chartools');
?>
