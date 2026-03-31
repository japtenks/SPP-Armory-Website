<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_botevents_action_url(array $params)
{
    return spp_action_url('index.php', $params, 'admin_botevents');
}

function spp_admin_botevents_resolve_php_cli_binary(): string
{
    $candidates = array_filter(array_unique([
        (string)(PHP_BINARY ?? ''),
        (defined('PHP_BINDIR') ? rtrim((string)PHP_BINDIR, '/\\') . DIRECTORY_SEPARATOR . 'php' : ''),
        '/usr/bin/php',
        '/usr/local/bin/php',
        '/opt/cpanel/ea-php82/root/usr/bin/php',
        '/opt/cpanel/ea-php81/root/usr/bin/php',
        '/opt/cpanel/ea-php80/root/usr/bin/php',
        '/opt/cpanel/ea-php74/root/usr/bin/php',
        'php',
    ]));

    foreach ($candidates as $candidate) {
        if ($candidate === 'php') {
            return $candidate;
        }
        if (@is_file($candidate) && @is_executable($candidate)) {
            return $candidate;
        }
    }

    return 'php';
}

function spp_admin_botevents_run_script(string $phpBin, string $scriptPath, array $extraArgs = []): array
{
    $args = array_map('escapeshellarg', $extraArgs);
    $cmd = escapeshellcmd($phpBin) . ' ' . escapeshellarg($scriptPath)
         . (empty($args) ? '' : ' ' . implode(' ', $args));

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = proc_open($cmd, $descriptors, $pipes, $scriptPath ? dirname($scriptPath) : null);
    if (!is_resource($proc)) {
        return ['stdout' => '', 'stderr' => 'proc_open failed - check PHP disable_functions.', 'code' => -1];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);

    return ['stdout' => $stdout, 'stderr' => $stderr, 'code' => $code];
}

function spp_admin_botevents_build_command(string $scriptPath, array $extraArgs = []): string
{
    $parts = ['php'];

    if (is_string($scriptPath) && $scriptPath !== '') {
        $relativeScript = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $scriptPath);
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if (is_string($docRoot) && $docRoot !== '' && stripos($relativeScript, $docRoot) === 0) {
            $relativeScript = ltrim(substr($relativeScript, strlen($docRoot)), '/\\');
        }
        $parts[] = $relativeScript;
    }

    foreach ($extraArgs as $arg) {
        $parts[] = $arg;
    }

    return implode(' ', $parts);
}

function spp_admin_botevents_append_event_type_args(array $args, array $selectedEventTypes): array
{
    if (!empty($selectedEventTypes)) {
        $args[] = '--event=' . implode(',', $selectedEventTypes);
    }
    return $args;
}

function spp_admin_botevents_config_path(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bot_event_config.php';
}

function spp_admin_botevents_default_config(): array
{
    return array(
        'enabled_realms' => array(1),
        'level_milestones' => array(
            'classic' => array(10, 20, 30, 40, 50, 60),
            'tbc' => array(10, 20, 30, 40, 50, 60, 70),
            'wotlk' => array(10, 20, 30, 40, 50, 60, 70, 80),
        ),
        'professions' => array(
            164 => 'Blacksmithing',
            165 => 'Leatherworking',
            171 => 'Alchemy',
            182 => 'Herbalism',
            185 => 'Cooking',
            186 => 'Mining',
            197 => 'Tailoring',
            202 => 'Engineering',
            333 => 'Enchanting',
            356 => 'Fishing',
            393 => 'Skinning',
            755 => 'Jewelcrafting',
            773 => 'Inscription',
        ),
        'profession_milestones' => array(75, 150, 225, 300),
        'reaction_count' => array(1, 3),
        'reaction_min_delay_sec' => 120,
        'reaction_max_delay_sec' => 900,
        'guild_reaction_count' => array(1, 2),
        'guild_reaction_min_delay_sec' => 180,
        'guild_reaction_max_delay_sec' => 1200,
        'achievement_lookback_days' => 1,
        'achievement_badge_min_points' => 10,
        'achievement_badge_min_level' => 20,
        'achievement_badge_exclude_categories' => array(92, 96, 97, 122),
        'achievement_badge_featured_ids' => array(),
        'achievement_badge_exclude' => array(6, 7, 8, 9, 10, 11, 12, 13, 238),
        'guild_roster_thresholds' => array(
            'min_joins' => 5,
            'cooldown_sec' => 43200,
        ),
        'realm_expansion' => array(
            1 => 'classic',
            2 => 'tbc',
            3 => 'wotlk',
        ),
        'forum_targets' => array(
            1 => array(
                'level_up' => 2,
                'guild_created' => 5,
                'profession_milestone' => 2,
                'guild_roster_update' => 5,
                'achievement_badge' => 2,
            ),
            2 => array(
                'level_up' => 3,
                'guild_created' => 5,
                'profession_milestone' => 3,
                'guild_roster_update' => 5,
                'achievement_badge' => 3,
            ),
            3 => array(
                'level_up' => 4,
                'guild_created' => 5,
                'profession_milestone' => 4,
                'guild_roster_update' => 5,
                'achievement_badge' => 4,
            ),
        ),
    );
}

function spp_admin_botevents_load_config(): array
{
    $path = spp_admin_botevents_config_path();
    $defaults = spp_admin_botevents_default_config();

    if (!is_file($path)) {
        return array(
            'config' => $defaults,
            'error' => 'Config file not found: ' . $path,
        );
    }

    $botEventConfig = array();
    require $path;
    if (!isset($botEventConfig) || !is_array($botEventConfig)) {
        return array(
            'config' => $defaults,
            'error' => 'Config file did not produce a valid $botEventConfig array.',
        );
    }

    return array(
        'config' => array_replace_recursive($defaults, $botEventConfig),
        'error' => '',
    );
}

function spp_admin_botevents_realm_options(array $realmDbMap, array $config): array
{
    $realmExpansion = isset($config['realm_expansion']) && is_array($config['realm_expansion'])
        ? $config['realm_expansion']
        : array();

    $realmIds = array_values(array_unique(array_merge(
        array_map('intval', array_keys($realmDbMap)),
        array_map('intval', array_keys($realmExpansion))
    )));
    sort($realmIds);

    $options = array();
    foreach ($realmIds as $realmId) {
        $resolvedName = function_exists('spp_get_armory_realm_name')
            ? (string)(spp_get_armory_realm_name((int)$realmId) ?? '')
            : '';
        $fallbackName = 'Realm ' . (int)$realmId;
        if (isset($realmDbMap[$realmId]) && is_array($realmDbMap[$realmId]) && !empty($realmDbMap[$realmId]['name'])) {
            $fallbackName = (string)$realmDbMap[$realmId]['name'];
        }

        $options[] = array(
            'id' => (int)$realmId,
            'name' => $resolvedName !== '' ? $resolvedName : $fallbackName,
            'expansion' => (string)($realmExpansion[$realmId] ?? ''),
        );
    }

    return $options;
}

function spp_admin_botevents_parse_int_list($value): array
{
    if (is_array($value)) {
        $value = implode(',', array_map('strval', $value));
    }

    $tokens = preg_split('/[\s,]+/', (string)$value, -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($tokens)) {
        return array();
    }

    $items = array();
    foreach ($tokens as $token) {
        if (preg_match('/^-?\d+$/', $token)) {
            $items[] = (int)$token;
        }
    }

    $items = array_values(array_unique($items));
    sort($items);
    return $items;
}

function spp_admin_botevents_parse_positive_int($value, int $default = 0, int $minimum = 0): int
{
    $value = trim((string)$value);
    if ($value === '' || !preg_match('/^-?\d+$/', $value)) {
        return max($minimum, $default);
    }

    return max($minimum, (int)$value);
}

function spp_admin_botevents_parse_optional_int($value): ?int
{
    $value = trim((string)$value);
    if ($value === '' || !preg_match('/^-?\d+$/', $value)) {
        return null;
    }

    return (int)$value;
}

function spp_admin_botevents_parse_range($minValue, $maxValue, array $fallback, int $minimum = 0): array
{
    $min = spp_admin_botevents_parse_positive_int($minValue, (int)($fallback[0] ?? $minimum), $minimum);
    $max = spp_admin_botevents_parse_positive_int($maxValue, (int)($fallback[1] ?? $min), $minimum);
    if ($max < $min) {
        $max = $min;
    }

    return array($min, $max);
}

function spp_admin_botevents_normalize_config_for_form(array $config, array $realmOptions): array
{
    $expansions = array('classic', 'tbc', 'wotlk');
    $eventTypes = array('level_up', 'guild_created', 'profession_milestone', 'guild_roster_update', 'achievement_badge');

    $config['enabled_realms'] = array_values(array_unique(array_map('intval', $config['enabled_realms'] ?? array())));
    sort($config['enabled_realms']);

    foreach ($expansions as $expansion) {
        $config['level_milestones'][$expansion] = spp_admin_botevents_parse_int_list($config['level_milestones'][$expansion] ?? array());
    }

    $config['profession_milestones'] = spp_admin_botevents_parse_int_list($config['profession_milestones'] ?? array());
    $config['reaction_count'] = spp_admin_botevents_parse_range($config['reaction_count'][0] ?? 1, $config['reaction_count'][1] ?? 1, array(1, 1), 0);
    $config['guild_reaction_count'] = spp_admin_botevents_parse_range($config['guild_reaction_count'][0] ?? 1, $config['guild_reaction_count'][1] ?? 1, array(1, 1), 0);
    $config['reaction_min_delay_sec'] = spp_admin_botevents_parse_positive_int($config['reaction_min_delay_sec'] ?? 0, 0, 0);
    $config['reaction_max_delay_sec'] = spp_admin_botevents_parse_positive_int($config['reaction_max_delay_sec'] ?? 0, 0, 0);
    if ($config['reaction_max_delay_sec'] < $config['reaction_min_delay_sec']) {
        $config['reaction_max_delay_sec'] = $config['reaction_min_delay_sec'];
    }
    $config['guild_reaction_min_delay_sec'] = spp_admin_botevents_parse_positive_int($config['guild_reaction_min_delay_sec'] ?? 0, 0, 0);
    $config['guild_reaction_max_delay_sec'] = spp_admin_botevents_parse_positive_int($config['guild_reaction_max_delay_sec'] ?? 0, 0, 0);
    if ($config['guild_reaction_max_delay_sec'] < $config['guild_reaction_min_delay_sec']) {
        $config['guild_reaction_max_delay_sec'] = $config['guild_reaction_min_delay_sec'];
    }

    $config['achievement_lookback_days'] = spp_admin_botevents_parse_positive_int($config['achievement_lookback_days'] ?? 1, 1, 1);
    $config['achievement_badge_min_points'] = spp_admin_botevents_parse_positive_int($config['achievement_badge_min_points'] ?? 0, 0, 0);
    $config['achievement_badge_min_level'] = spp_admin_botevents_parse_positive_int($config['achievement_badge_min_level'] ?? 0, 0, 0);
    $config['achievement_badge_exclude_categories'] = spp_admin_botevents_parse_int_list($config['achievement_badge_exclude_categories'] ?? array());
    $config['achievement_badge_featured_ids'] = spp_admin_botevents_parse_int_list($config['achievement_badge_featured_ids'] ?? array());
    $config['achievement_badge_exclude'] = spp_admin_botevents_parse_int_list($config['achievement_badge_exclude'] ?? array());

    $config['guild_roster_thresholds']['min_joins'] = spp_admin_botevents_parse_positive_int($config['guild_roster_thresholds']['min_joins'] ?? 1, 1, 1);
    $config['guild_roster_thresholds']['cooldown_sec'] = spp_admin_botevents_parse_positive_int($config['guild_roster_thresholds']['cooldown_sec'] ?? 0, 0, 0);

    foreach ($realmOptions as $realm) {
        $realmId = (int)$realm['id'];
        $config['realm_expansion'][$realmId] = (string)($config['realm_expansion'][$realmId] ?? $realm['expansion'] ?? 'classic');
        if (!in_array($config['realm_expansion'][$realmId], $expansions, true)) {
            $config['realm_expansion'][$realmId] = 'classic';
        }

        if (!isset($config['forum_targets'][$realmId]) || !is_array($config['forum_targets'][$realmId])) {
            $config['forum_targets'][$realmId] = array();
        }

        foreach ($eventTypes as $eventType) {
            $config['forum_targets'][$realmId][$eventType] = spp_admin_botevents_parse_optional_int($config['forum_targets'][$realmId][$eventType] ?? null);
        }
    }

    return $config;
}

function spp_admin_botevents_extract_config_from_request(array $request, array $existingConfig, array $realmOptions): array
{
    $config = $existingConfig;

    $config['enabled_realms'] = array_values(array_unique(array_map('intval', $request['enabled_realms'] ?? array())));
    sort($config['enabled_realms']);

    foreach (array('classic', 'tbc', 'wotlk') as $expansion) {
        $config['level_milestones'][$expansion] = spp_admin_botevents_parse_int_list($request['level_milestones'][$expansion] ?? '');
    }

    $config['profession_milestones'] = spp_admin_botevents_parse_int_list($request['profession_milestones'] ?? '');
    $config['reaction_count'] = spp_admin_botevents_parse_range($request['reaction_count_min'] ?? '', $request['reaction_count_max'] ?? '', $existingConfig['reaction_count'] ?? array(1, 1), 0);
    $config['guild_reaction_count'] = spp_admin_botevents_parse_range($request['guild_reaction_count_min'] ?? '', $request['guild_reaction_count_max'] ?? '', $existingConfig['guild_reaction_count'] ?? array(1, 1), 0);
    $config['reaction_min_delay_sec'] = spp_admin_botevents_parse_positive_int($request['reaction_min_delay_sec'] ?? '', (int)($existingConfig['reaction_min_delay_sec'] ?? 0), 0);
    $config['reaction_max_delay_sec'] = spp_admin_botevents_parse_positive_int($request['reaction_max_delay_sec'] ?? '', (int)($existingConfig['reaction_max_delay_sec'] ?? 0), 0);
    if ($config['reaction_max_delay_sec'] < $config['reaction_min_delay_sec']) {
        $config['reaction_max_delay_sec'] = $config['reaction_min_delay_sec'];
    }
    $config['guild_reaction_min_delay_sec'] = spp_admin_botevents_parse_positive_int($request['guild_reaction_min_delay_sec'] ?? '', (int)($existingConfig['guild_reaction_min_delay_sec'] ?? 0), 0);
    $config['guild_reaction_max_delay_sec'] = spp_admin_botevents_parse_positive_int($request['guild_reaction_max_delay_sec'] ?? '', (int)($existingConfig['guild_reaction_max_delay_sec'] ?? 0), 0);
    if ($config['guild_reaction_max_delay_sec'] < $config['guild_reaction_min_delay_sec']) {
        $config['guild_reaction_max_delay_sec'] = $config['guild_reaction_min_delay_sec'];
    }

    $config['guild_roster_thresholds']['min_joins'] = spp_admin_botevents_parse_positive_int($request['guild_roster_min_joins'] ?? '', (int)($existingConfig['guild_roster_thresholds']['min_joins'] ?? 1), 1);
    $config['guild_roster_thresholds']['cooldown_sec'] = spp_admin_botevents_parse_positive_int($request['guild_roster_cooldown_sec'] ?? '', (int)($existingConfig['guild_roster_thresholds']['cooldown_sec'] ?? 0), 0);

    $postedRealmExpansion = isset($request['realm_expansion']) && is_array($request['realm_expansion'])
        ? $request['realm_expansion']
        : array();
    $postedForumTargets = isset($request['forum_targets']) && is_array($request['forum_targets'])
        ? $request['forum_targets']
        : array();

    foreach ($realmOptions as $realm) {
        $realmId = (int)$realm['id'];
        $expansion = strtolower(trim((string)($postedRealmExpansion[$realmId] ?? ($existingConfig['realm_expansion'][$realmId] ?? 'classic'))));
        if (!in_array($expansion, array('classic', 'tbc', 'wotlk'), true)) {
            $expansion = 'classic';
        }
        $config['realm_expansion'][$realmId] = $expansion;

        if (!isset($config['forum_targets'][$realmId]) || !is_array($config['forum_targets'][$realmId])) {
            $config['forum_targets'][$realmId] = array();
        }

        foreach (array('level_up', 'guild_created', 'profession_milestone', 'guild_roster_update', 'achievement_badge') as $eventType) {
            $config['forum_targets'][$realmId][$eventType] = spp_admin_botevents_parse_optional_int($postedForumTargets[$realmId][$eventType] ?? '');
        }
    }

    return spp_admin_botevents_normalize_config_for_form($config, $realmOptions);
}

function spp_admin_botevents_extract_achievement_config_from_request(array $request, array $existingConfig, array $realmOptions): array
{
    $config = $existingConfig;
    $config['achievement_lookback_days'] = spp_admin_botevents_parse_positive_int($request['achievement_lookback_days'] ?? '', (int)($existingConfig['achievement_lookback_days'] ?? 1), 1);
    $config['achievement_badge_min_points'] = spp_admin_botevents_parse_positive_int($request['achievement_badge_min_points'] ?? '', (int)($existingConfig['achievement_badge_min_points'] ?? 0), 0);
    $config['achievement_badge_min_level'] = spp_admin_botevents_parse_positive_int($request['achievement_badge_min_level'] ?? '', (int)($existingConfig['achievement_badge_min_level'] ?? 0), 0);
    $config['achievement_badge_exclude_categories'] = spp_admin_botevents_parse_int_list($request['achievement_badge_exclude_categories'] ?? array());
    $config['achievement_badge_featured_ids'] = spp_admin_botevents_parse_int_list($request['achievement_badge_featured_ids'] ?? array());
    $config['achievement_badge_exclude'] = spp_admin_botevents_parse_int_list($request['achievement_badge_exclude'] ?? array());

    return spp_admin_botevents_normalize_config_for_form($config, $realmOptions);
}

function spp_admin_botevents_table_exists(PDO $pdo, string $table): bool
{
    static $cache = array();
    $key = spl_object_hash($pdo) . ':' . $table;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        $cache[$key] = $stmt && $stmt->fetchColumn() !== false;
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

function spp_admin_botevents_build_achievement_catalog(array $realmDbMap, array $config): array
{
    $realmOptions = spp_admin_botevents_realm_options($realmDbMap, $config);
    $enabledRealms = array_values(array_unique(array_map('intval', $config['enabled_realms'] ?? array())));
    $preferredRealmId = (int)($enabledRealms[0] ?? ($realmOptions[0]['id'] ?? 0));

    $result = array(
        'realmId' => $preferredRealmId,
        'realmName' => '',
        'source' => '',
        'categories' => array(),
        'achievements' => array(),
        'error' => '',
    );

    if ($preferredRealmId <= 0 || !isset($realmDbMap[$preferredRealmId])) {
        $result['error'] = 'No realm is available for achievement metadata.';
        return $result;
    }

    $result['realmName'] = (string)(spp_get_armory_realm_name($preferredRealmId) ?? ('Realm ' . $preferredRealmId));

    try {
        $worldPdo = spp_get_pdo('world', $preferredRealmId);
        $armoryPdo = spp_get_pdo('armory', $preferredRealmId);
        $categories = array();
        $achievements = array();

        if ($worldPdo instanceof PDO && spp_admin_botevents_table_exists($worldPdo, 'achievement_dbc')) {
            $result['source'] = 'world';
            if (spp_admin_botevents_table_exists($worldPdo, 'achievement_category_dbc')) {
                $stmt = $worldPdo->query('SELECT `ID`, `Name_Lang_enUS`, `Parent` FROM `achievement_category_dbc` ORDER BY `Name_Lang_enUS` ASC');
                foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array() as $row) {
                    $categories[(int)$row['ID']] = array(
                        'id' => (int)$row['ID'],
                        'name' => trim((string)($row['Name_Lang_enUS'] ?? '')),
                        'parent' => (int)($row['Parent'] ?? -1),
                    );
                }
            }

            $stmt = $worldPdo->query('SELECT `ID`, `Title_Lang_enUS`, `Description_Lang_enUS`, `Points`, `Category` FROM `achievement_dbc` ORDER BY `Points` DESC, `Title_Lang_enUS` ASC');
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array() as $row) {
                $categoryId = (int)($row['Category'] ?? 0);
                $achievements[] = array(
                    'id' => (int)$row['ID'],
                    'name' => trim((string)($row['Title_Lang_enUS'] ?? '')),
                    'description' => trim((string)($row['Description_Lang_enUS'] ?? '')),
                    'points' => (int)($row['Points'] ?? 0),
                    'category_id' => $categoryId,
                    'category_name' => (string)($categories[$categoryId]['name'] ?? ''),
                );
            }
        } elseif ($armoryPdo instanceof PDO && spp_admin_botevents_table_exists($armoryPdo, 'dbc_achievement')) {
            $result['source'] = 'armory';
            if (spp_admin_botevents_table_exists($armoryPdo, 'dbc_achievement_category')) {
                $stmt = $armoryPdo->query('SELECT `id`, `name`, `ref_achievement_category` FROM `dbc_achievement_category` ORDER BY `name` ASC');
                foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array() as $row) {
                    $categories[(int)$row['id']] = array(
                        'id' => (int)$row['id'],
                        'name' => trim((string)($row['name'] ?? '')),
                        'parent' => (int)($row['ref_achievement_category'] ?? -1),
                    );
                }
            }

            $stmt = $armoryPdo->query('SELECT `id`, `name`, `description`, `points`, `ref_achievement_category` FROM `dbc_achievement` ORDER BY `points` DESC, `name` ASC');
            foreach ($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array() as $row) {
                $categoryId = (int)($row['ref_achievement_category'] ?? 0);
                $achievements[] = array(
                    'id' => (int)$row['id'],
                    'name' => trim((string)($row['name'] ?? '')),
                    'description' => trim((string)($row['description'] ?? '')),
                    'points' => (int)($row['points'] ?? 0),
                    'category_id' => $categoryId,
                    'category_name' => (string)($categories[$categoryId]['name'] ?? ''),
                );
            }
        } else {
            $result['error'] = 'Achievement metadata tables were not found for the selected realm.';
            return $result;
        }

        foreach ($categories as &$category) {
            $parentId = (int)($category['parent'] ?? -1);
            $category['parent_name'] = (string)($categories[$parentId]['name'] ?? '');
        }
        unset($category);

        $result['categories'] = array_values($categories);
        $result['achievements'] = $achievements;
    } catch (Throwable $e) {
        $result['error'] = 'Achievement metadata lookup failed: ' . $e->getMessage();
    }

    return $result;
}

function spp_admin_botevents_export_value($value, int $depth = 0): string
{
    if (is_array($value)) {
        if (empty($value)) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth);
        $childIndent = str_repeat('    ', $depth + 1);
        $isList = array_keys($value) === range(0, count($value) - 1);
        $lines = array('[');
        foreach ($value as $key => $item) {
            $prefix = $isList ? '' : spp_admin_botevents_export_value($key, 0) . ' => ';
            $lines[] = $childIndent . $prefix . spp_admin_botevents_export_value($item, $depth + 1) . ',';
        }
        $lines[] = $indent . ']';
        return implode(PHP_EOL, $lines);
    }

    if (is_string($value)) {
        return "'" . str_replace(array('\\', "'"), array('\\\\', "\\'"), $value) . "'";
    }

    if ($value === null) {
        return 'null';
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    return (string)$value;
}

function spp_admin_botevents_render_config(array $config): string
{
    return "<?php\n"
        . "// Auto-generated by the Bot Events admin panel.\n"
        . '$botEventConfig = ' . spp_admin_botevents_export_value($config, 0) . ";\n";
}

function spp_admin_botevents_write_config(array $config): array
{
    $path = spp_admin_botevents_config_path();
    $dir = dirname($path);
    if (!is_dir($dir) || !is_writable($dir)) {
        return array(
            'ok' => false,
            'message' => 'Config directory is not writable: ' . $dir,
        );
    }

    $bytes = @file_put_contents($path, spp_admin_botevents_render_config($config), LOCK_EX);
    if ($bytes === false) {
        return array(
            'ok' => false,
            'message' => 'Failed to write bot event config: ' . $path,
        );
    }

    return array(
        'ok' => true,
        'message' => '',
    );
}
