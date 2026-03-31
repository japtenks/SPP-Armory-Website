<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once(__DIR__ . '/bot_maintenance_common.php');

$options = bot_maintenance_parse_cli_args($argv);
$config = bot_maintenance_config();
$response = bot_maintenance_fresh_reset($options, $config);
bot_maintenance_record_action('fresh_reset', $response);
bot_maintenance_console_output($response);
exit(((string)($response['status'] ?? '') === 'ok') ? 0 : 1);
