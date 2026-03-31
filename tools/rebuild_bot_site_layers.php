<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once(__DIR__ . '/bot_maintenance_common.php');

$options = bot_maintenance_parse_cli_args($argv);
$response = bot_maintenance_rebuild_site_layers($options);
bot_maintenance_record_action('rebuild_site_layers', $response);
bot_maintenance_console_output($response);
exit(((string)($response['status'] ?? '') === 'ok') ? 0 : 1);
