<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

// Load core logic from the server statistic component
require_once(__DIR__ . '/../server/server.statistic.php');

// Prepare theme paths
$templateDir = 'templates/' . (string)$MW->getConfig->generic->template;
$tpl_file = $templateDir . '/server/server.statistic.php';
$func_file = $templateDir . '/body_functions.php';
$header_file = $templateDir . '/body_header.php';
$footer_file = $templateDir . '/body_footer.php';

// Include helper functions
if (file_exists($func_file)) include_once($func_file);

// Output the full themed page
if (file_exists($header_file)) include($header_file);

if (file_exists($tpl_file)) {
    include($tpl_file);
} else {
    echo '<div style="color:red;text-align:center;margin:50px;">Template missing: ' . htmlspecialchars($tpl_file) . '</div>';
}

if (file_exists($footer_file)) include($footer_file);
