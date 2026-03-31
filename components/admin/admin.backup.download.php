<?php
session_start();

define('INCLUDED', true);

require_once(__DIR__ . '/../../config/config-protected.php');
require_once(__DIR__ . '/../../core/class.mangosweb.php');

$MW = new mangosweb();

require_once(__DIR__ . '/../../core/common.php');
require_once(__DIR__ . '/../../core/request.php');
require_once(__DIR__ . '/../../core/mangos.class.php');
require_once(__DIR__ . '/../../core/class.auth.php');
require_once(__DIR__ . '/admin.backup.helpers.php');

$auth = new AUTH(null, $MW->getConfig);
$user = $auth->user ?? array();
$isAdmin = !empty($user['g_is_admin']) || !empty($user['g_is_supadmin']);

if (!$isAdmin) {
    http_response_code(403);
    exit('Forbidden');
}

$file = spp_admin_backup_basename((string)($_GET['file'] ?? ''));
if ($file === '' || !preg_match('/\.sql$/i', $file)) {
    http_response_code(404);
    exit('File not found');
}

$path = spp_admin_backup_output_dir() . DIRECTORY_SEPARATOR . $file;
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('File not found');
}

header('Content-Type: application/sql; charset=utf-8');
header('Content-Length: ' . (string)filesize($path));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $file) . '"');
readfile($path);
exit;
