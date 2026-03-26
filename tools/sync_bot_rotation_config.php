<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$root = dirname(__DIR__, 3);
$expansion = $argv[1] ?? 'vanilla';

$realmAliases = [
    'vanilla' => 1,
    'tbc' => 2,
    'wotlk' => 3,
];

if (!isset($realmAliases[$expansion])) {
    fwrite(STDERR, "Unsupported expansion: {$expansion}\n");
    exit(1);
}

$confPath = $root . DIRECTORY_SEPARATOR . 'Settings' . DIRECTORY_SEPARATOR . $expansion . DIRECTORY_SEPARATOR . 'aiplayerbot.conf';
$cnfPath = $root . DIRECTORY_SEPARATOR . 'Server' . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'connection.cnf';
$websiteConfigPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config-protected.php';

if (!is_file($confPath)) {
    fwrite(STDERR, "Config not found: {$confPath}\n");
    exit(1);
}

if (is_file($websiteConfigPath)) {
    $websiteConfig = file_get_contents($websiteConfigPath);
    if ($websiteConfig !== false) {
        preg_match("/'host'\\s*=>\\s*'([^']+)'/", $websiteConfig, $hostMatch);
        preg_match("/'port'\\s*=>\\s*(\\d+)/", $websiteConfig, $portMatch);
        preg_match("/'user'\\s*=>\\s*'([^']+)'/", $websiteConfig, $userMatch);
        preg_match("/'pass'\\s*=>\\s*'([^']*)'/", $websiteConfig, $passMatch);

        $dbHost = $hostMatch[1] ?? null;
        $dbPort = isset($portMatch[1]) ? (int)$portMatch[1] : null;
        $dbUser = $userMatch[1] ?? null;
        $dbPass = $passMatch[1] ?? '';
    }
}

if (empty($dbUser)) {
    if (!is_file($cnfPath)) {
        fwrite(STDERR, "DB config not found: {$cnfPath}\n");
        exit(1);
    }

    $dbIni = parse_ini_file($cnfPath, true);
    $client = $dbIni['client'] ?? [];
    $dbHost = $client['host'] ?? '127.0.0.1';
    $dbPort = (int)($client['port'] ?? 3306);
    $dbUser = $client['user'] ?? null;
    $dbPass = $client['password'] ?? '';
}

if (empty($dbUser) || empty($dbHost) || empty($dbPort)) {
    fwrite(STDERR, "Could not resolve database credentials.\n");
    exit(1);
}

$websiteConfig = file_get_contents($websiteConfigPath);
if ($websiteConfig === false) {
    fwrite(STDERR, "Could not read website config: {$websiteConfigPath}\n");
    exit(1);
}

$realmDbMap = [];
$match = [];
if (!preg_match('/\$realmDbMap\s*=\s*(\[[\s\S]*?\]);/', $websiteConfig, $match)) {
    fwrite(STDERR, "Could not locate realmDbMap in website config.\n");
    exit(1);
}

try {
    eval('$realmDbMap = ' . $match[1] . ';');
} catch (ParseError $e) {
    fwrite(STDERR, "Failed parsing website config: {$e->getMessage()}\n");
    exit(1);
}

if (!isset($realmDbMap) || !is_array($realmDbMap)) {
    fwrite(STDERR, "Website config did not define realmDbMap.\n");
    exit(1);
}

$realmId = $realmAliases[$expansion];
if (!isset($realmDbMap[$realmId]['realmd'], $realmDbMap[$realmId]['chars'])) {
    fwrite(STDERR, "Missing realm DB mapping for {$expansion} (realm {$realmId}).\n");
    exit(1);
}

$raw = file($confPath, FILE_IGNORE_NEW_LINES);
if ($raw === false) {
    fwrite(STDERR, "Could not read {$confPath}\n");
    exit(1);
}

$settings = [];
foreach ($raw as $line) {
    $trimmed = trim($line);
    if ($trimmed === '' || $trimmed[0] === '#' || strpos($trimmed, '=') === false) {
        continue;
    }

    [$key, $value] = array_map('trim', explode('=', $trimmed, 2));
    if ($key === '') {
        continue;
    }

    $settings[$key] = trim($value, " \t\n\r\0\x0B\"'");
}

$readInt = static function (array $settings, string $key): ?int {
    if (!isset($settings[$key])) {
        return null;
    }
    $value = trim((string)$settings[$key]);
    if ($value === '' || !preg_match('/^-?\d+$/', $value)) {
        return null;
    }
    return (int)$value;
};

$minIn = $readInt($settings, 'AiPlayerbot.MinRandomBotInWorldTime');
$maxIn = $readInt($settings, 'AiPlayerbot.MaxRandomBotInWorldTime');
$minOff = $readInt($settings, 'AiPlayerbot.MinRandomBotRandomizeTime');
$maxOff = $readInt($settings, 'AiPlayerbot.MaxRandomRandomizeTime');
$minBots = $readInt($settings, 'AiPlayerbot.MinRandomBots');
$maxBots = $readInt($settings, 'AiPlayerbot.MaxRandomBots');
$accounts = $readInt($settings, 'AiPlayerbot.RandomBotAccountCount');
$rebalanceMin = $readInt($settings, 'AiPlayerbot.RandomBotCountChangeMinInterval');
$rebalanceMax = $readInt($settings, 'AiPlayerbot.RandomBotCountChangeMaxInterval');
$maxLogins = $readInt($settings, 'AiPlayerbot.RandomBotsMaxLoginsPerInterval');
$prefix = $settings['AiPlayerbot.RandomBotAccountPrefix'] ?? 'RNDBOT';

$avgIn = ($minIn !== null && $maxIn !== null) ? round(($minIn + $maxIn) / 2, 1) : null;
$avgOff = ($minOff !== null && $maxOff !== null) ? round(($minOff + $maxOff) / 2, 1) : null;
$expected = null;
if ($avgIn !== null && $avgOff !== null && ($avgIn + $avgOff) > 0) {
    $expected = round(($avgIn / ($avgIn + $avgOff)) * 100, 1);
}

$realm = [
    'realm' => $realmId,
    'realmd' => $realmDbMap[$realmId]['realmd'],
    'chars' => $realmDbMap[$realmId]['chars'],
];

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$realm['realmd']};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $select = $pdo->prepare("SELECT * FROM bot_rotation_config WHERE realm = :realm LIMIT 1");
    $select->execute([':realm' => $realm['realm']]);
    $existing = $select->fetch() ?: [];

    $historyStmt = $pdo->prepare("
        SELECT
            cfg_min_in_world_sec,
            cfg_max_in_world_sec,
            cfg_avg_in_world_sec,
            cfg_min_offline_sec,
            cfg_max_offline_sec,
            cfg_avg_offline_sec,
            cfg_expected_online_pct,
            cfg_min_bots,
            cfg_max_bots,
            cfg_account_count,
            cfg_rebalance_min_sec,
            cfg_rebalance_max_sec,
            cfg_max_logins_per_interval
        FROM bot_rotation_log
        WHERE realm = :realm
        ORDER BY snapshot_time DESC
        LIMIT 1
    ");
    $historyStmt->execute([':realm' => $realm['realm']]);
    $history = $historyStmt->fetch() ?: [];

    $existingInt = static function (array $existing, string $key): ?int {
        if (!isset($existing[$key]) || $existing[$key] === null || $existing[$key] === '') {
            return null;
        }
        return (int)$existing[$key];
    };

    $existingFloat = static function (array $existing, string $key): ?float {
        if (!isset($existing[$key]) || $existing[$key] === null || $existing[$key] === '') {
            return null;
        }
        return (float)$existing[$key];
    };

    $fromHistoryInt = static function (array $history, string $key): ?int {
        if (!isset($history[$key]) || $history[$key] === null || $history[$key] === '') {
            return null;
        }
        return (int)$history[$key];
    };

    $fromHistoryFloat = static function (array $history, string $key): ?float {
        if (!isset($history[$key]) || $history[$key] === null || $history[$key] === '') {
            return null;
        }
        return (float)$history[$key];
    };

    $minIn = $minIn ?? $fromHistoryInt($history, 'cfg_min_in_world_sec') ?? $existingInt($existing, 'min_in_world_sec');
    $maxIn = $maxIn ?? $fromHistoryInt($history, 'cfg_max_in_world_sec') ?? $existingInt($existing, 'max_in_world_sec');
    $minOff = $minOff ?? $fromHistoryInt($history, 'cfg_min_offline_sec') ?? $existingInt($existing, 'min_offline_sec');
    $maxOff = $maxOff ?? $fromHistoryInt($history, 'cfg_max_offline_sec') ?? $existingInt($existing, 'max_offline_sec');
    $minBots = $minBots ?? $fromHistoryInt($history, 'cfg_min_bots') ?? $existingInt($existing, 'min_random_bots');
    $maxBots = $maxBots ?? $fromHistoryInt($history, 'cfg_max_bots') ?? $existingInt($existing, 'max_random_bots');
    $accounts = $accounts ?? $fromHistoryInt($history, 'cfg_account_count') ?? $existingInt($existing, 'account_count');
    $rebalanceMin = $rebalanceMin ?? $fromHistoryInt($history, 'cfg_rebalance_min_sec') ?? $existingInt($existing, 'rebalance_min_sec');
    $rebalanceMax = $rebalanceMax ?? $fromHistoryInt($history, 'cfg_rebalance_max_sec') ?? $existingInt($existing, 'rebalance_max_sec');
    $maxLogins = $maxLogins ?? $fromHistoryInt($history, 'cfg_max_logins_per_interval') ?? $existingInt($existing, 'max_logins_per_interval');
    $prefix = isset($settings['AiPlayerbot.RandomBotAccountPrefix'])
        ? $prefix
        : ($existing['random_bot_account_prefix'] ?? 'RNDBOT');

    $avgIn = ($minIn !== null && $maxIn !== null)
        ? round(($minIn + $maxIn) / 2, 1)
        : ($fromHistoryFloat($history, 'cfg_avg_in_world_sec') ?? $existingFloat($existing, 'avg_in_world_sec'));
    $avgOff = ($minOff !== null && $maxOff !== null)
        ? round(($minOff + $maxOff) / 2, 1)
        : ($fromHistoryFloat($history, 'cfg_avg_offline_sec') ?? $existingFloat($existing, 'avg_offline_sec'));
    $expected = ($avgIn !== null && $avgOff !== null && ($avgIn + $avgOff) > 0)
        ? round(($avgIn / ($avgIn + $avgOff)) * 100, 1)
        : ($fromHistoryFloat($history, 'cfg_expected_online_pct') ?? $existingFloat($existing, 'expected_online_pct'));

    $sql = "
        INSERT INTO bot_rotation_config (
            realm, expansion, char_db, random_bot_account_prefix,
            min_in_world_sec, max_in_world_sec, min_offline_sec, max_offline_sec,
            avg_in_world_sec, avg_offline_sec, expected_online_pct,
            min_random_bots, max_random_bots, account_count,
            rebalance_min_sec, rebalance_max_sec, max_logins_per_interval, last_synced
        ) VALUES (
            :realm, :expansion, :char_db, :prefix,
            :min_in, :max_in, :min_off, :max_off,
            :avg_in, :avg_off, :expected,
            :min_bots, :max_bots, :accounts,
            :rebalance_min, :rebalance_max, :max_logins, NOW()
        )
        ON DUPLICATE KEY UPDATE
            expansion = VALUES(expansion),
            char_db = VALUES(char_db),
            random_bot_account_prefix = VALUES(random_bot_account_prefix),
            min_in_world_sec = VALUES(min_in_world_sec),
            max_in_world_sec = VALUES(max_in_world_sec),
            min_offline_sec = VALUES(min_offline_sec),
            max_offline_sec = VALUES(max_offline_sec),
            avg_in_world_sec = VALUES(avg_in_world_sec),
            avg_offline_sec = VALUES(avg_offline_sec),
            expected_online_pct = VALUES(expected_online_pct),
            min_random_bots = VALUES(min_random_bots),
            max_random_bots = VALUES(max_random_bots),
            account_count = VALUES(account_count),
            rebalance_min_sec = VALUES(rebalance_min_sec),
            rebalance_max_sec = VALUES(rebalance_max_sec),
            max_logins_per_interval = VALUES(max_logins_per_interval),
            last_synced = NOW()
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':realm' => $realm['realm'],
        ':expansion' => $expansion,
        ':char_db' => $realm['chars'],
        ':prefix' => $prefix,
        ':min_in' => $minIn,
        ':max_in' => $maxIn,
        ':min_off' => $minOff,
        ':max_off' => $maxOff,
        ':avg_in' => $avgIn,
        ':avg_off' => $avgOff,
        ':expected' => $expected,
        ':min_bots' => $minBots,
        ':max_bots' => $maxBots,
        ':accounts' => $accounts,
        ':rebalance_min' => $rebalanceMin,
        ':rebalance_max' => $rebalanceMax,
        ':max_logins' => $maxLogins,
    ]);

    fwrite(STDOUT, "Synced bot rotation config for {$expansion}: ");
    fwrite(
        STDOUT,
        sprintf(
            "bots=%s-%s accounts=%s in=%s-%s off=%s-%s expected=%s%%\n",
            $minBots ?? 'null',
            $maxBots ?? 'null',
            $accounts ?? 'null',
            $minIn ?? 'null',
            $maxIn ?? 'null',
            $minOff ?? 'null',
            $maxOff ?? 'null',
            $expected ?? 'null'
        )
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Sync failed: {$e->getMessage()}\n");
    exit(1);
}
