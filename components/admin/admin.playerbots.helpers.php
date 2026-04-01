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

function spp_admin_playerbots_forum_tone_groups(): array
{
    return array(
        'public' => array(
            'label' => 'Public Topic Reactions',
            'description' => 'Used when other bots reply to normal public event topics.',
            'keys' => array(
                'forum:reaction:level_up' => array(
                    'label' => 'Level Up',
                    'placeholder' => "grats!\n<level> already, damn",
                ),
                'forum:reaction:profession_milestone' => array(
                    'label' => 'Profession Milestone',
                    'placeholder' => "nice <skill> gains\nbags full of mats finally paid off",
                ),
                'forum:reaction:achievement_badge' => array(
                    'label' => 'Achievement Badge',
                    'placeholder' => "worth the grind\nactual legend",
                ),
                'forum:reaction:generic' => array(
                    'label' => 'Generic Fallback',
                    'placeholder' => "big day for <name>\nserver's heating up tonight",
                ),
            ),
        ),
        'guild' => array(
            'label' => 'Guild Thread Reactions',
            'description' => 'Used when guildmates react inside recruitment and roster threads.',
            'keys' => array(
                'forum:guild_reaction:guild_created' => array(
                    'label' => 'Guild Created',
                    'placeholder' => "guild's up, let's build it right\n<guild> starts now",
                ),
                'forum:guild_reaction:guild_roster_update' => array(
                    'label' => 'Guild Roster Update',
                    'placeholder' => "welcome aboard\nroster looking healthy",
                ),
                'forum:guild_reaction:level_up' => array(
                    'label' => 'Guild Level Up',
                    'placeholder' => "good stuff <name>\nwe'll get you geared",
                ),
                'forum:guild_reaction:profession_milestone' => array(
                    'label' => 'Guild Profession Milestone',
                    'placeholder' => "guild bank thanks you\nyou are on consumable duty now",
                ),
                'forum:guild_reaction:achievement_badge' => array(
                    'label' => 'Guild Achievement Badge',
                    'placeholder' => "nice one <name>\nthat's going in the guild stories",
                ),
                'forum:guild_reaction:generic' => array(
                    'label' => 'Guild Generic Fallback',
                    'placeholder' => "that's one of ours\nanother win for the tabard",
                ),
            ),
        ),
        'guild_seed_leveling' => array(
            'label' => 'Guild Seed: Leveling / Questing',
            'description' => 'Initial recruitment post lines for leveling and questing guilds.',
            'keys' => array(
                'forum:guild_seed:leveling_questing:intro' => array(
                    'label' => 'Intro',
                    'placeholder' => "[b]<guild>[/b] is gathering companions for the long road ahead.\n[b]<guild>[/b] has raised its banner for questers and steady hands.",
                ),
                'forum:guild_seed:leveling_questing:focus' => array(
                    'label' => 'Focus',
                    'placeholder' => "We are building around leveling, questing, and helping one another through the rough stretches.\nMost of our strength is on the road, working through zones and dungeon detours.",
                ),
                'forum:guild_seed:leveling_questing:needs' => array(
                    'label' => 'Needs',
                    'placeholder' => "We are especially glad to see <needs>.\nAnyone is welcome, though <needs> would round us out nicely.",
                ),
                'forum:guild_seed:leveling_questing:closing' => array(
                    'label' => 'Closing',
                    'placeholder' => "If you want company more than spectacle, speak with [b]<leader_name>[/b] in-game.\nIf that sounds like your pace, whisper [b]<leader_name>[/b] and travel with us.",
                ),
            ),
        ),
        'guild_seed_dungeon' => array(
            'label' => 'Guild Seed: Dungeon / Social',
            'description' => 'Initial recruitment post lines for dungeon and social guilds.',
            'keys' => array(
                'forum:guild_seed:dungeon_social:intro' => array(
                    'label' => 'Intro',
                    'placeholder' => "[b]<guild>[/b] is recruiting for a lively dungeon-going roster.\n[b]<guild>[/b] is opening its doors to players who like steady groups and familiar company.",
                ),
                'forum:guild_seed:dungeon_social:focus' => array(
                    'label' => 'Focus',
                    'placeholder' => "We are building around five-man runs, social grouping, and players who enjoy returning to the same crew.\nOur guild leans toward dungeons, evening grouping, and keeping a dependable bench of familiar names.",
                ),
                'forum:guild_seed:dungeon_social:needs' => array(
                    'label' => 'Needs',
                    'placeholder' => "Right now <needs> would help keep our runs moving smoothly.\nWe welcome all comers, with a special eye for <needs>.",
                ),
                'forum:guild_seed:dungeon_social:closing' => array(
                    'label' => 'Closing',
                    'placeholder' => "If that sounds like your sort of company, whisper [b]<leader_name>[/b] in-game.\nSeek out [b]<leader_name>[/b] if you want a guild that actually groups together.",
                ),
            ),
        ),
        'guild_seed_raiding' => array(
            'label' => 'Guild Seed: Raiding / Progression',
            'description' => 'Initial recruitment post lines for progression-minded guilds.',
            'keys' => array(
                'forum:guild_seed:raiding_progression:intro' => array(
                    'label' => 'Intro',
                    'placeholder' => "[b]<guild>[/b] is recruiting to build a sharper progression roster.\n[b]<guild>[/b] is laying the groundwork for a disciplined endgame push.",
                ),
                'forum:guild_seed:raiding_progression:focus' => array(
                    'label' => 'Focus',
                    'placeholder' => "We are shaping a roster for reliable progression, better preparation, and a stronger weekly core.\nOur eye is on organized growth, cleaner rosters, and turning potential into real progression.",
                ),
                'forum:guild_seed:raiding_progression:needs' => array(
                    'label' => 'Needs',
                    'placeholder' => "Prepared <needs> are a particular priority for us right now.\nWe are open broadly, though <needs> would strengthen the roster immediately.",
                ),
                'forum:guild_seed:raiding_progression:closing' => array(
                    'label' => 'Closing',
                    'placeholder' => "Players who want structure and progress should speak with [b]<leader_name>[/b].\nIf you want to help build something serious, whisper [b]<leader_name>[/b] in-game.",
                ),
            ),
        ),
        'guild_seed_pvp' => array(
            'label' => 'Guild Seed: PvP / Mercenary',
            'description' => 'Initial recruitment post lines for PvP-heavy guilds.',
            'keys' => array(
                'forum:guild_seed:pvp_mercenary:intro' => array(
                    'label' => 'Intro',
                    'placeholder' => "[b]<guild>[/b] is recruiting for contested roads and hard fights.\n[b]<guild>[/b] is looking for the sort of adventurers who prefer banners and skirmishes to quiet inns.",
                ),
                'forum:guild_seed:pvp_mercenary:focus' => array(
                    'label' => 'Focus',
                    'placeholder' => "We lean toward battlegrounds, contested zones, and keeping a roster ready for sudden violence.\nThis is a guild for players who enjoy pressure, world PvP, and a little swagger in their step.",
                ),
                'forum:guild_seed:pvp_mercenary:needs' => array(
                    'label' => 'Needs',
                    'placeholder' => "<needs> would fit our current warband especially well.\nAll fighters are welcome, though <needs> are particularly useful to us now.",
                ),
                'forum:guild_seed:pvp_mercenary:closing' => array(
                    'label' => 'Closing',
                    'placeholder' => "If you like your victories noisy, whisper [b]<leader_name>[/b] in-game.\nThose looking for battle can seek out [b]<leader_name>[/b] and join the fight.",
                ),
            ),
        ),
        'guild_seed_generic' => array(
            'label' => 'Guild Seed: Generic Fallbacks',
            'description' => 'Used when a guild type has no specific seed lines yet.',
            'keys' => array(
                'forum:guild_seed:generic:intro' => array(
                    'label' => 'Generic Intro',
                    'placeholder' => "[b]<guild>[/b] is recruiting new hands.\n[b]<guild>[/b] is opening its banner to fresh company.",
                ),
                'forum:guild_seed:generic:focus' => array(
                    'label' => 'Generic Focus',
                    'placeholder' => "We are building a steadier roster and welcoming the right sort of company.\nOur guild is looking for dependable adventurers and a stronger bench.",
                ),
                'forum:guild_seed:generic:needs' => array(
                    'label' => 'Generic Needs',
                    'placeholder' => "<needs> would be especially welcome.\nAll adventurers are welcome, with a special eye for <needs>.",
                ),
                'forum:guild_seed:generic:closing' => array(
                    'label' => 'Generic Closing',
                    'placeholder' => "Whisper [b]<leader_name>[/b] in-game if you would like to join us.\nThose interested can speak with [b]<leader_name>[/b] in-game.",
                ),
            ),
        ),
    );
}

function spp_admin_playerbots_forum_tone_key_map(): array
{
    $map = array();
    foreach (spp_admin_playerbots_forum_tone_groups() as $group) {
        foreach (($group['keys'] ?? array()) as $key => $meta) {
            $map[(string)$key] = is_array($meta) ? $meta : array();
        }
    }
    return $map;
}

function spp_admin_playerbots_forum_tone_keys(): array
{
    return array_keys(spp_admin_playerbots_forum_tone_key_map());
}

function spp_admin_playerbots_normalize_forum_tone_lines(string $value): array
{
    $lines = preg_split('/\r\n|\r|\n/', $value) ?: array();
    $normalized = array();
    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '') {
            continue;
        }
        $normalized[] = $line;
    }
    return $normalized;
}

function spp_admin_playerbots_fetch_forum_tone_state(PDO $worldPdo): array
{
    $state = array_fill_keys(spp_admin_playerbots_forum_tone_keys(), '');
    $keys = spp_admin_playerbots_forum_tone_keys();
    if (empty($keys)) {
        return $state;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $worldPdo->prepare("
            SELECT `name`, `text`, `id`
            FROM `ai_playerbot_texts`
            WHERE `name` IN ($placeholders)
            ORDER BY `name` ASC, `id` ASC
        ");
        $stmt->execute($keys);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: array() as $row) {
            $name = strtolower(trim((string)($row['name'] ?? '')));
            if (!array_key_exists($name, $state)) {
                continue;
            }
            $line = trim((string)($row['text'] ?? ''));
            if ($line === '') {
                continue;
            }
            $state[$name] = trim($state[$name] === '' ? $line : ($state[$name] . "\n" . $line));
        }
    } catch (Throwable $e) {
        error_log('[admin.playerbots] Failed loading forum tone rows: ' . $e->getMessage());
    }

    return $state;
}

function spp_admin_playerbots_strategy_builder_options(): array
{
    return array(
        'co' => array('dps', 'dps assist', 'dps aoe', 'tank', 'tank assist', 'threat', 'boost', 'offheal', 'cast time', 'custom::say', 'attack tagged', 'duel', 'pvp', 'avoid mobs'),
        'nc' => array('follow', 'loot', 'delayed roll', 'food', 'conserve mana', 'quest', 'grind', 'wander', 'gather', 'consumables', 'rpg', 'rpg guild', 'rpg vendor', 'rpg maintenance', 'rpg craft', 'rpg explore', 'rpg bg', 'tfish', 'duel', 'free', 'roll', 'custom::say'),
        'dead' => array('auto release', 'flee', 'corpse', 'return', 'delay'),
        'react' => array('preheal', 'flee', 'avoid aoe', 'pvp'),
    );
}

function spp_admin_playerbots_random_bot_baseline_profile(): array
{
    return array(
        'label' => 'Unguilded Random Bot Baseline',
        'description' => 'Noisy noob baseline: overeager DPS, sloppy threat, gathers and loots everything, duels randomly, and drifts into guild life on its own.',
        'co' => '+dps,+dps aoe,+threat,+boost,+custom::say',
        'nc' => '+rpg guild,+quest,+grind,+rpg vendor,+rpg maintenance,+loot,+wander,+custom::say,+duel,+gather,+roll',
        'dead' => '',
        'react' => '+flee',
    );
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
        'default' => array(
            'label' => 'Clear Guild Flavor',
            'description' => 'Removes the guild layer so members fall back to the unguilded baseline plus any personal overrides.',
            'co' => '',
            'nc' => '',
            'dead' => '',
            'react' => '',
        ),
        'leveling' => array(
            'label' => 'Leveling Guild',
            'description' => 'Still levels broadly, but acts more coached than the noob baseline: calmer threat, cleaner group habits, and less loot-goblin chaos.',
            'co' => '+dps,+dps assist,-threat,+boost',
            'nc' => '+rpg,-rpg explore,+grind,+wander,+gather,+consumables,+food,+loot,+delayed roll,+custom::say',
            'dead' => '',
            'react' => '+flee,+avoid aoe',
        ),
        'quest' => array(
            'label' => 'Questing / Social Guild',
            'description' => 'More social and quest-hub directed than the baseline, with less raw chaos and better tag discipline while still feeling alive.',
            'co' => '+dps,+dps assist,-threat,+custom::say,+attack tagged',
            'nc' => '+rpg,-rpg bg,+wander,+tfish,+gather,+consumables,+food,+loot,+delayed roll,+custom::say',
            'dead' => '',
            'react' => '+flee,+avoid aoe',
        ),
        'profession' => array(
            'label' => 'Profession / Farming Guild',
            'description' => 'Less quest-driven and more gather-craft-maintain focused. This is the organized economy guild where farming loops matter more than social chaos.',
            'co' => '+dps,+dps aoe,-threat,+boost,+custom::say,-avoid mobs',
            'nc' => '+grind,+gather,+rpg vendor,+rpg maintenance,+rpg craft,+loot,+wander',
            'dead' => '',
            'react' => '+flee',
        ),
        'pvp' => array(
            'label' => 'PvP Guild',
            'description' => 'Purposefully aggressive and PvP-aware, with battleground life, stronger pressure, and less interest in peaceful wandering.',
            'co' => '+dps,+dps assist,+threat,+boost,+pvp,+duel',
            'nc' => '+grind,+rpg vendor,+rpg maintenance,+loot,+consumables,+food,+free,+custom::say,+duel,+rpg bg,+pvp,+start duel',
            'dead' => '',
            'react' => '+avoid aoe,+pvp',
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

function spp_admin_playerbots_strategy_value_to_map(string $value): array
{
    $map = array();
    foreach (spp_admin_playerbots_parse_strategy_tokens($value) as $token) {
        $key = spp_admin_playerbots_strategy_token_key($token);
        if ($key === '') {
            continue;
        }
        $map[$key] = $token;
    }

    return $map;
}

function spp_admin_playerbots_merge_strategy_value_sets(array $baseValues, array $overrideValues): array
{
    $merged = array_fill_keys(spp_admin_playerbots_strategy_keys(), '');
    foreach (spp_admin_playerbots_strategy_keys() as $strategyKey) {
        $merged[$strategyKey] = spp_admin_playerbots_merge_strategy_delta(
            (string)($baseValues[$strategyKey] ?? ''),
            (string)($overrideValues[$strategyKey] ?? '')
        );
    }

    return $merged;
}

function spp_admin_playerbots_build_strategy_override_value(string $baseValue, string $effectiveValue): string
{
    $baseMap = spp_admin_playerbots_strategy_value_to_map($baseValue);
    $effectiveMap = spp_admin_playerbots_strategy_value_to_map($effectiveValue);
    $overrideTokens = array();

    foreach ($effectiveMap as $tokenKey => $tokenValue) {
        if (isset($baseMap[$tokenKey]) && $baseMap[$tokenKey] === $tokenValue) {
            continue;
        }
        $overrideTokens[] = $tokenValue;
    }

    return implode(',', $overrideTokens);
}

function spp_admin_playerbots_build_strategy_override_set(array $baseValues, array $effectiveValues): array
{
    $overrideValues = array_fill_keys(spp_admin_playerbots_strategy_keys(), '');
    foreach (spp_admin_playerbots_strategy_keys() as $strategyKey) {
        $overrideValues[$strategyKey] = spp_admin_playerbots_build_strategy_override_value(
            (string)($baseValues[$strategyKey] ?? ''),
            (string)($effectiveValues[$strategyKey] ?? '')
        );
    }

    return $overrideValues;
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

function spp_admin_playerbots_fetch_effective_strategy_state_for_guids(PDO $charsPdo, array $guids, array $profiles): array
{
    $emptyValues = array_fill_keys(spp_admin_playerbots_strategy_keys(), '');
    $guids = array_values(array_unique(array_filter(array_map('intval', $guids))));
    if (empty($guids)) {
        return array(
            'values' => $emptyValues,
            'base_values' => $emptyValues,
            'override_values' => $emptyValues,
            'consistent' => true,
            'member_count' => 0,
            'profile_key' => spp_admin_playerbots_detect_strategy_profile_key($emptyValues, $profiles),
            'mixed_count' => 0,
        );
    }

    $basePerGuid = spp_admin_playerbots_fetch_strategy_rows_for_guids($charsPdo, $guids, 'default');
    $overridePerGuid = spp_admin_playerbots_fetch_strategy_rows_for_guids($charsPdo, $guids, '');
    $effectivePerGuid = array();
    foreach ($guids as $guid) {
        $effectivePerGuid[$guid] = spp_admin_playerbots_merge_strategy_value_sets(
            $basePerGuid[$guid] ?? $emptyValues,
            $overridePerGuid[$guid] ?? $emptyValues
        );
    }

    $baselineGuid = $guids[0];
    $baseline = $effectivePerGuid[$baselineGuid] ?? $emptyValues;
    $baselineBase = $basePerGuid[$baselineGuid] ?? $emptyValues;
    $baselineOverride = $overridePerGuid[$baselineGuid] ?? $emptyValues;

    $consistent = true;
    $mixedCount = 0;
    foreach ($effectivePerGuid as $values) {
        if ($values !== $baseline) {
            $consistent = false;
            $mixedCount++;
        }
    }

    return array(
        'values' => $baseline,
        'base_values' => $baselineBase,
        'override_values' => $baselineOverride,
        'consistent' => $consistent,
        'member_count' => count($guids),
        'profile_key' => spp_admin_playerbots_detect_strategy_profile_key($baselineOverride, $profiles),
        'mixed_count' => $mixedCount,
    );
}

function spp_admin_playerbots_fetch_guild_strategy_state(PDO $charsPdo, int $guildId): array
{
    if ($guildId <= 0) {
        return spp_admin_playerbots_fetch_strategy_state_for_guids($charsPdo, array(), 'default', spp_admin_playerbots_guild_strategy_profiles());
    }

    $stmt = $charsPdo->prepare("SELECT guid FROM guild_member WHERE guildid = ? ORDER BY guid ASC");
    $stmt->execute(array($guildId));
    $memberGuids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: array());

    return spp_admin_playerbots_fetch_strategy_state_for_guids($charsPdo, $memberGuids, 'default', spp_admin_playerbots_guild_strategy_profiles());
}

function spp_admin_playerbots_fetch_character_strategy_state(PDO $charsPdo, int $characterGuid): array
{
    return spp_admin_playerbots_fetch_effective_strategy_state_for_guids($charsPdo, $characterGuid > 0 ? array($characterGuid) : array(), spp_admin_playerbots_bot_strategy_profiles());
}
