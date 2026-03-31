<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_playerbots_redirect_url(int $realmId, int $guildId = 0, int $characterGuid = 0, array $extra = array()): string
{
    $params = array_merge(array(
        'n' => 'admin',
        'sub' => 'playerbots',
        'realm' => $realmId,
    ), $extra);

    if ($guildId > 0) {
        $params['guildid'] = $guildId;
    }
    if ($characterGuid > 0) {
        $params['character_guid'] = $characterGuid;
    }

    return 'index.php?' . http_build_query($params, '', '&');
}

function spp_admin_playerbots_strategy_keys(): array
{
    return array('co', 'nc', 'dead', 'react');
}

function spp_admin_playerbots_bot_strategy_profiles(): array
{
    return array(
        'tank' => array(
            'label' => 'Tank',
            'description' => 'Party tank that keeps threat and leads pulls.',
            'co' => '+dps,+tank assist,+threat,+boost',
            'nc' => '+follow,+loot,+delayed roll,+food',
            'dead' => '',
            'react' => '',
        ),
        'dps' => array(
            'label' => 'DPS',
            'description' => 'Group damage dealer that follows the lead target.',
            'co' => '+dps,+dps assist,-threat,+boost',
            'nc' => '+follow,+loot,+delayed roll,+food',
            'dead' => '',
            'react' => '',
        ),
        'healer' => array(
            'label' => 'Healer',
            'description' => 'Support healer with mana-aware follow behavior.',
            'co' => '+offheal,+dps assist,+cast time',
            'nc' => '+follow,+loot,+delayed roll,+food,+conserve mana',
            'dead' => '',
            'react' => '+preheal',
        ),
        'custom' => array(
            'label' => 'Custom',
            'description' => 'Start from the current values and edit freely.',
            'co' => '',
            'nc' => '',
            'dead' => '',
            'react' => '',
        ),
    );
}

function spp_admin_playerbots_guild_strategy_profiles(): array
{
    return array(
        'leveling' => array(
            'label' => 'Leveling',
            'description' => 'Questing and wandering profile for autonomous bots.',
            'co' => '+dps,+dps assist,-threat,+custom::say',
            'nc' => '+rpg,+quest,+grind,+loot,+wander,+custom::say',
            'dead' => '',
            'react' => '',
        ),
        'quest' => array(
            'label' => 'Quest',
            'description' => 'Quest-hub focused RPG profile.',
            'co' => '+dps,+dps assist,-threat,+custom::say',
            'nc' => '+rpg,+rpg quest,+loot,+tfish,+wander,+custom::say',
            'dead' => '',
            'react' => '',
        ),
        'pvp' => array(
            'label' => 'PvP',
            'description' => 'Battleground and hostile-player focused profile.',
            'co' => '+dps,+dps assist,+threat,+boost,+pvp,+duel,+custom::say',
            'nc' => '+rpg,+wander,+bg,+custom::say',
            'dead' => '',
            'react' => '+pvp',
        ),
        'farming' => array(
            'label' => 'Farming',
            'description' => 'Resource-gathering profile for quiet bots.',
            'co' => '+dps,-threat',
            'nc' => '+gather,+grind,+loot,+tfish,+wander,+rpg maintenance',
            'dead' => '',
            'react' => '',
        ),
        'custom' => array(
            'label' => 'Custom',
            'description' => 'Start from the current values and edit freely.',
            'co' => '',
            'nc' => '',
            'dead' => '',
            'react' => '',
        ),
        'default' => array(
            'label' => 'Base Defaults',
            'description' => 'Keeps each bot on its current base snapshot unless you add custom guild deltas.',
            'co' => '',
            'nc' => '',
            'dead' => '',
            'react' => '',
        ),
    );
}

function spp_admin_playerbots_detect_realm_expansion(array $realmInfo): string
{
    $parts = array();
    foreach (array('world', 'chars', 'armory', 'bots') as $field) {
        if (!empty($realmInfo[$field])) {
            $parts[] = strtolower((string)$realmInfo[$field]);
        }
    }
    $haystack = implode(' ', $parts);

    if (strpos($haystack, 'wotlk') !== false) {
        return 'wotlk';
    }
    if (strpos($haystack, 'tbc') !== false) {
        return 'tbc';
    }

    return 'classic';
}

function spp_admin_playerbots_expansion_label(string $expansionKey): string
{
    $labels = array(
        'classic' => 'Classic',
        'tbc' => 'TBC',
        'wotlk' => 'WotLK',
    );

    return $labels[$expansionKey] ?? ucfirst($expansionKey);
}

function spp_admin_playerbots_meeting_location_options(array $realmInfo, string $currentLocation = ''): array
{
    static $cache = array();

    $expansionKey = spp_admin_playerbots_detect_realm_expansion($realmInfo);
    if (!isset($cache[$expansionKey])) {
        $cache[$expansionKey] = array();
        $path = 'C:\\Git\\playerbots\\sql\\world\\' . $expansionKey . '\\ai_playerbot_travel_nodes.sql';

        if (is_readable($path)) {
            $contents = @file_get_contents($path);
            if (is_string($contents) && preg_match_all("/\\(\\d+,\\s*'((?:[^'\\\\]|\\\\.)+)'\\s*,/", $contents, $matches)) {
                foreach ($matches[1] as $rawName) {
                    $name = str_replace("\\'", "'", (string)$rawName);
                    $name = trim($name);
                    if ($name === '') {
                        continue;
                    }
                    $cache[$expansionKey][$name] = $name;
                }
            }
        }

        natcasesort($cache[$expansionKey]);
        $cache[$expansionKey] = array_values($cache[$expansionKey]);
    }

    $locations = $cache[$expansionKey];
    if ($currentLocation !== '' && !in_array($currentLocation, $locations, true)) {
        array_unshift($locations, $currentLocation);
    }

    return $locations;
}

function spp_admin_playerbots_fetch_realm_name(int $realmId, array $realmInfo): string
{
    $db = $GLOBALS['db'] ?? null;
    $realmdDb = (string)($realmInfo['realmd'] ?? '');
    if (is_array($db) && $realmdDb !== '') {
        try {
            $pdo = new PDO(
                "mysql:host={$db['host']};port={$db['port']};dbname={$realmdDb};charset=utf8mb4",
                $db['user'],
                $db['pass'],
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                )
            );
            $stmt = $pdo->prepare("SELECT `name` FROM `realmlist` WHERE `id` = ? LIMIT 1");
            $stmt->execute(array($realmId));
            $name = $stmt->fetchColumn();
            if ($name !== false && trim((string)$name) !== '') {
                return trim((string)$name);
            }
        } catch (Throwable $e) {
            error_log('[admin.playerbots] Failed fetching realm name: ' . $e->getMessage());
        }
    }

    return 'SPP-' . spp_admin_playerbots_expansion_label(spp_admin_playerbots_detect_realm_expansion($realmInfo));
}

function spp_admin_playerbots_build_realm_options(array $realmDbMap): array
{
    $options = array();
    $labelCounts = array();

    foreach ($realmDbMap as $realmId => $realmInfo) {
        $realmId = (int)$realmId;
        $label = spp_admin_playerbots_fetch_realm_name($realmId, is_array($realmInfo) ? $realmInfo : array());
        $labelCounts[$label] = ($labelCounts[$label] ?? 0) + 1;
        $options[] = array(
            'realm_id' => $realmId,
            'label' => $label,
        );
    }

    foreach ($options as &$option) {
        if (($labelCounts[$option['label']] ?? 0) > 1) {
            $option['label'] .= ' (Realm ' . (int)$option['realm_id'] . ')';
        }
    }
    unset($option);

    usort($options, function (array $left, array $right): int {
        return ($left['realm_id'] ?? 0) <=> ($right['realm_id'] ?? 0);
    });

    return $options;
}

function spp_admin_playerbots_class_names(): array
{
    return array(
        'warrior',
        'paladin',
        'hunter',
        'rogue',
        'priest',
        'shaman',
        'mage',
        'warlock',
        'druid',
        'death knight',
        'deathknight',
    );
}

function spp_admin_playerbots_role_names(): array
{
    return array('all', 'melee', 'ranged', 'tank', 'dps', 'heal');
}

function spp_admin_playerbots_parse_time_token(string $token): ?array
{
    $token = trim($token);
    if ($token === '' || !preg_match('/^(\d{1,2}):(\d{2})([AaPp][Mm])?$/', $token, $matches)) {
        return null;
    }

    $hour = (int)$matches[1];
    $minute = (int)$matches[2];
    $suffix = isset($matches[3]) ? strtoupper((string)$matches[3]) : '';

    if ($minute < 0 || $minute > 59 || $hour < 0 || $hour > 23) {
        return null;
    }

    if ($suffix === 'AM') {
        if ($hour === 12) {
            $hour = 0;
        }
    } elseif ($suffix === 'PM') {
        if ($hour !== 12) {
            $hour = ($hour % 12) + 12;
        }
    }

    if ($hour < 0 || $hour > 23) {
        return null;
    }

    return array(
        'hour' => $hour,
        'minute' => $minute,
        'normalized' => sprintf('%02d:%02d', $hour, $minute),
    );
}

function spp_admin_playerbots_parse_meeting_directive(string $motd): array
{
    $result = array(
        'found' => false,
        'valid' => false,
        'location' => '',
        'start' => '',
        'end' => '',
        'normalized_start' => '',
        'normalized_end' => '',
        'display' => '',
        'error' => '',
        'raw' => '',
    );

    if (!preg_match('/Meeting:\s*(.+?)\s+(\d{1,2}:\d{2}(?:[AaPp][Mm])?)\s+(\d{1,2}:\d{2}(?:[AaPp][Mm])?)/s', $motd, $matches)) {
        return $result;
    }

    $result['found'] = true;
    $result['raw'] = trim((string)$matches[0]);
    $result['location'] = trim((string)$matches[1]);
    $result['start'] = trim((string)$matches[2]);
    $result['end'] = trim((string)$matches[3]);

    if ($result['location'] === '') {
        $result['error'] = 'Meeting directive is missing a location.';
        return $result;
    }

    $startTime = spp_admin_playerbots_parse_time_token($result['start']);
    $endTime = spp_admin_playerbots_parse_time_token($result['end']);
    if ($startTime === null || $endTime === null) {
        $result['error'] = 'Meeting directive uses an unsupported time format.';
        return $result;
    }

    $result['valid'] = true;
    $result['normalized_start'] = $startTime['normalized'];
    $result['normalized_end'] = $endTime['normalized'];
    $result['display'] = $result['location'] . ' (' . $startTime['normalized'] . ' - ' . $endTime['normalized'] . ')';

    return $result;
}

function spp_admin_playerbots_upsert_meeting_directive(string $motd, string $location, string $startTime, string $endTime): string
{
    $directive = 'Meeting: ' . trim($location) . ' ' . trim($startTime) . ' ' . trim($endTime);
    $motd = trim($motd);

    if (strpos($motd, 'Meeting:') === false) {
        return $motd === '' ? $directive : ($motd . "\n" . $directive);
    }

    $updated = preg_replace('/Meeting:\s*(.+?)\s+(\d{1,2}:\d{2}(?:[AaPp][Mm])?)\s+(\d{1,2}:\d{2}(?:[AaPp][Mm])?)/s', $directive, $motd, 1);
    return trim(is_string($updated) ? $updated : $directive);
}

function spp_admin_playerbots_replace_share_block(string $guildInfo, string $shareBlock): string
{
    $guildInfo = trim($guildInfo);
    $shareBlock = trim($shareBlock);
    $shareSection = $shareBlock === '' ? '' : ("Share:\n" . $shareBlock);
    $sharePos = strpos($guildInfo, 'Share:');

    if ($sharePos === false) {
        return trim($guildInfo . ($guildInfo !== '' && $shareSection !== '' ? "\n\n" : '') . $shareSection);
    }

    $prefix = trim(substr($guildInfo, 0, $sharePos));
    if ($prefix === '') {
        return $shareSection;
    }

    return $shareSection === '' ? $prefix : trim($prefix . "\n\n" . $shareSection);
}

function spp_admin_playerbots_extract_share_block(string $guildInfo): string
{
    $sharePos = strpos($guildInfo, 'Share:');
    if ($sharePos === false) {
        return '';
    }

    return trim(substr($guildInfo, $sharePos + 6));
}

function spp_admin_playerbots_validate_share_block(string $shareBlock): array
{
    $errors = array();
    $entries = array();
    $shareBlock = trim(str_replace("\r\n", "\n", $shareBlock));
    if ($shareBlock === '') {
        return array('errors' => $errors, 'entries' => $entries);
    }

    $validFilters = array_merge(spp_admin_playerbots_role_names(), spp_admin_playerbots_class_names());
    $lines = explode("\n", $shareBlock);
    foreach ($lines as $index => $line) {
        $lineNumber = $index + 1;
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        if (strpos($line, ':') === false) {
            $errors[] = 'Share line ' . $lineNumber . ' must use "<filter>: <item> <amount>".';
            continue;
        }

        list($filter, $itemsSection) = array_map('trim', explode(':', $line, 2));
        $filterLower = strtolower($filter);
        if (!in_array($filterLower, $validFilters, true)) {
            $errors[] = 'Share line ' . $lineNumber . ' uses an unknown filter "' . $filter . '".';
            continue;
        }

        if ($itemsSection === '') {
            $errors[] = 'Share line ' . $lineNumber . ' is missing item targets.';
            continue;
        }

        $parsedItems = array();
        foreach (array_map('trim', explode(',', $itemsSection)) as $itemEntry) {
            if ($itemEntry === '' || !preg_match('/^(.+?)\s+(\d+)$/', $itemEntry, $matches)) {
                $errors[] = 'Share line ' . $lineNumber . ' has an invalid item target "' . $itemEntry . '".';
                continue 2;
            }

            $itemName = trim((string)$matches[1]);
            $amount = (int)$matches[2];
            if ($itemName === '' || $amount <= 0) {
                $errors[] = 'Share line ' . $lineNumber . ' has an invalid item target "' . $itemEntry . '".';
                continue 2;
            }

            $parsedItems[] = array('item_name' => $itemName, 'amount' => $amount);
        }

        $entries[] = array('filter' => $filter, 'items' => $parsedItems);
    }

    return array('errors' => $errors, 'entries' => $entries);
}

function spp_admin_playerbots_validate_order_note(string $note): array
{
    $trimmed = trim($note);
    if ($trimmed === '') {
        return array('valid' => true, 'type' => 'none', 'target' => '', 'amount' => null, 'normalized' => '');
    }

    if (strcasecmp($trimmed, 'skip order') === 0) {
        return array('valid' => true, 'type' => 'skip order', 'target' => '', 'amount' => null, 'normalized' => 'skip order');
    }

    if (!preg_match('/^(Craft|Farm|Kill|Explore):\s*(.+)$/i', $trimmed, $matches)) {
        return array('valid' => false, 'error' => 'Officer notes must be empty, "skip order", or use Craft:/Farm:/Kill:/Explore:.');
    }

    $type = ucfirst(strtolower((string)$matches[1]));
    $body = trim((string)$matches[2]);
    if ($body === '') {
        return array('valid' => false, 'error' => $type . ' notes must include a target.');
    }

    $amount = null;
    if (($type === 'Craft' || $type === 'Farm') && preg_match('/^(.+?)\s+(\d+)$/', $body, $bodyMatches)) {
        $candidateTarget = trim((string)$bodyMatches[1]);
        $candidateAmount = (int)$bodyMatches[2];
        if ($candidateTarget !== '' && $candidateAmount > 0) {
            $body = $candidateTarget;
            $amount = $candidateAmount;
        }
    }

    return array(
        'valid' => true,
        'type' => strtolower($type),
        'target' => $body,
        'amount' => $amount,
        'normalized' => $amount !== null ? ($type . ': ' . $body . ' ' . $amount) : ($type . ': ' . $body),
    );
}

function spp_admin_playerbots_decode_personality_value(?string $storedValue): string
{
    $storedValue = (string)$storedValue;
    $prefix = 'manual saved string::llmdefaultprompt>';
    if ($storedValue === '') {
        return '';
    }
    if (strpos($storedValue, $prefix) !== 0) {
        return $storedValue;
    }
    return substr($storedValue, strlen($prefix));
}

function spp_admin_playerbots_normalize_strategy_value(string $value): string
{
    $value = str_replace(array("\r\n", "\r"), "\n", trim($value));
    $value = preg_replace('/\s*\n\s*/', '', $value);
    return trim((string)$value);
}

function spp_admin_playerbots_parse_strategy_tokens(string $value): array
{
    $value = spp_admin_playerbots_normalize_strategy_value($value);
    if ($value === '') {
        return array();
    }

    $tokens = array();
    foreach (explode(',', $value) as $token) {
        $token = trim($token);
        if ($token === '') {
            continue;
        }
        $tokens[] = $token;
    }

    return $tokens;
}

function spp_admin_playerbots_strategy_token_key(string $token): string
{
    $token = trim($token);
    if ($token === '') {
        return '';
    }

    $prefix = substr($token, 0, 1);
    if ($prefix === '+' || $prefix === '-' || $prefix === '~') {
        $token = substr($token, 1);
    }

    return strtolower(trim($token));
}

function spp_admin_playerbots_merge_strategy_delta(string $currentValue, string $deltaValue): string
{
    $merged = array();
    $order = array();

    foreach (spp_admin_playerbots_parse_strategy_tokens($currentValue) as $token) {
        $key = spp_admin_playerbots_strategy_token_key($token);
        if ($key === '') {
            continue;
        }
        if (!array_key_exists($key, $merged)) {
            $order[] = $key;
        }
        $merged[$key] = $token;
    }

    foreach (spp_admin_playerbots_parse_strategy_tokens($deltaValue) as $token) {
        $key = spp_admin_playerbots_strategy_token_key($token);
        if ($key === '') {
            continue;
        }
        if (!array_key_exists($key, $merged)) {
            $order[] = $key;
        }
        $merged[$key] = $token;
    }

    $result = array();
    foreach ($order as $key) {
        if (!isset($merged[$key]) || trim((string)$merged[$key]) === '') {
            continue;
        }
        $result[] = $merged[$key];
    }

    return implode(',', $result);
}

function spp_admin_playerbots_fetch_strategy_rows_for_guids(PDO $charsPdo, array $guids, string $preset): array
{
    $guids = array_values(array_unique(array_filter(array_map('intval', $guids))));
    $result = array();
    foreach ($guids as $guid) {
        $result[$guid] = array_fill_keys(spp_admin_playerbots_strategy_keys(), '');
    }

    if (empty($guids)) {
        return $result;
    }

    $placeholders = implode(',', array_fill(0, count($guids), '?'));
    $strategyKeys = spp_admin_playerbots_strategy_keys();
    $keyPlaceholders = implode(',', array_fill(0, count($strategyKeys), '?'));
    $stmt = $charsPdo->prepare("
        SELECT guid, `key`, value
        FROM ai_playerbot_db_store
        WHERE guid IN ($placeholders)
          AND preset = ?
          AND `key` IN ($keyPlaceholders)
    ");
    $stmt->execute(array_merge($guids, array($preset), $strategyKeys));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: array() as $row) {
        $guid = (int)($row['guid'] ?? 0);
        $key = (string)($row['key'] ?? '');
        if (!isset($result[$guid][$key])) {
            continue;
        }
        $result[$guid][$key] = spp_admin_playerbots_normalize_strategy_value((string)($row['value'] ?? ''));
    }

    return $result;
}

function spp_admin_playerbots_strategy_values_are_empty(array $values): bool
{
    foreach (spp_admin_playerbots_strategy_keys() as $strategyKey) {
        if (trim((string)($values[$strategyKey] ?? '')) !== '') {
            return false;
        }
    }

    return true;
}

function spp_admin_playerbots_parse_live_botstrat_output(string $output): ?array
{
    $values = array_fill_keys(spp_admin_playerbots_strategy_keys(), '');
    $matches = array();
    if (!preg_match_all('/^\s*(co|nc|react|dead)\s*:\s*(.*?)\s*$/mi', $output, $matches, PREG_SET_ORDER)) {
        return null;
    }

    foreach ($matches as $match) {
        $key = strtolower((string)($match[1] ?? ''));
        if (!array_key_exists($key, $values)) {
            continue;
        }
        $values[$key] = spp_admin_playerbots_normalize_strategy_value((string)($match[2] ?? ''));
    }

    return $values;
}

function spp_admin_playerbots_fetch_live_strategy_snapshot(int $realmId, string $characterName, string &$errorMessage = ''): ?array
{
    $characterName = trim($characterName);
    if ($realmId <= 0 || $characterName === '') {
        $errorMessage = 'Missing realm or character name for live strategy lookup.';
        return null;
    }

    $soapOutput = spp_mangos_soap_execute_command($realmId, '.ec botstrat ' . $characterName, $errorMessage);
    if ($soapOutput === false) {
        return null;
    }

    $snapshot = spp_admin_playerbots_parse_live_botstrat_output((string)$soapOutput);
    if ($snapshot === null) {
        $errorMessage = 'The live bot strategy command did not return a readable strategy snapshot.';
        return null;
    }

    return $snapshot;
}

function spp_admin_playerbots_detect_strategy_profile_key(array $values, array $profiles): string
{
    foreach ($profiles as $key => $profile) {
        $candidate = array(
            'co' => spp_admin_playerbots_normalize_strategy_value((string)($profile['co'] ?? '')),
            'nc' => spp_admin_playerbots_normalize_strategy_value((string)($profile['nc'] ?? '')),
            'dead' => spp_admin_playerbots_normalize_strategy_value((string)($profile['dead'] ?? '')),
            'react' => spp_admin_playerbots_normalize_strategy_value((string)($profile['react'] ?? '')),
        );
        if ($candidate === $values) {
            return $key;
        }
    }

    return 'custom';
}

function spp_admin_playerbots_fetch_strategy_state_for_guids(PDO $charsPdo, array $guids, string $preset, array $profiles): array
{
    $emptyValues = array_fill_keys(spp_admin_playerbots_strategy_keys(), '');
    $guids = array_values(array_unique(array_filter(array_map('intval', $guids))));
    if (empty($guids)) {
        return array(
            'values' => $emptyValues,
            'consistent' => true,
            'member_count' => 0,
            'profile_key' => spp_admin_playerbots_detect_strategy_profile_key($emptyValues, $profiles),
            'mixed_count' => 0,
        );
    }

    $perGuid = spp_admin_playerbots_fetch_strategy_rows_for_guids($charsPdo, $guids, $preset);

    $baseline = reset($perGuid);
    if (!is_array($baseline)) {
        $baseline = $emptyValues;
    }

    $consistent = true;
    $mixedCount = 0;
    foreach ($perGuid as $values) {
        if ($values !== $baseline) {
            $consistent = false;
            $mixedCount++;
        }
    }

    return array(
        'values' => $baseline,
        'consistent' => $consistent,
        'member_count' => count($guids),
        'profile_key' => spp_admin_playerbots_detect_strategy_profile_key($baseline, $profiles),
        'mixed_count' => $mixedCount,
    );
}

function spp_admin_playerbots_fetch_guild_strategy_state(PDO $charsPdo, int $guildId): array
{
    if ($guildId <= 0) {
        return spp_admin_playerbots_fetch_strategy_state_for_guids($charsPdo, array(), '', spp_admin_playerbots_guild_strategy_profiles());
    }

    $stmt = $charsPdo->prepare("SELECT guid FROM guild_member WHERE guildid = ? ORDER BY guid ASC");
    $stmt->execute(array($guildId));
    $memberGuids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: array());

    return spp_admin_playerbots_fetch_strategy_state_for_guids($charsPdo, $memberGuids, '', spp_admin_playerbots_guild_strategy_profiles());
}

function spp_admin_playerbots_fetch_character_strategy_state(PDO $charsPdo, int $characterGuid): array
{
    return spp_admin_playerbots_fetch_strategy_state_for_guids($charsPdo, $characterGuid > 0 ? array($characterGuid) : array(), '', spp_admin_playerbots_bot_strategy_profiles());
}
