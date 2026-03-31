<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

fwrite(STDERR, "bot_maintenance_helper.php is deprecated. Use one of the dedicated scripts instead:\n");
fwrite(STDERR, "  php tools/bot_maintenance_status.php\n");
fwrite(STDERR, "  php tools/reset_forum_realm.php --realm=1\n");
fwrite(STDERR, "  php tools/fresh_bot_reset.php --realm=1\n");
fwrite(STDERR, "  php tools/rebuild_bot_site_layers.php --realm=1\n");
exit(1);
