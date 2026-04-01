<?php
require_once(dirname(__FILE__, 4) . '/core/xfer/com_db.php');
require_once(dirname(__FILE__, 4) . '/core/xfer/com_search.php');

$botCommands = loadCommands($pdo, $world_db, 'bot');
$gmCommands = loadCommands($pdo, $world_db, 'gm');
$userGmLevel = (int)($user['gmlevel'] ?? 0);

if (($user['id'] ?? 0) > 0) {
    $gmCommands = array_values(array_filter($gmCommands, function ($cmd) use ($userGmLevel) {
        return (int)($cmd['security'] ?? 0) <= $userGmLevel;
    }));
}

function spp_botcommand_extract_tags(array $command): array
{
    $name = strtolower((string)($command['name'] ?? ''));
    $category = strtolower((string)($command['category'] ?? ''));
    $help = strtolower((string)($command['help'] ?? ''));
    $blob = $name . ' ' . $category . ' ' . $help;

    $states = array();
    if (strpos($blob, 'combat behavior') !== false || strpos($blob, '[c:co ') !== false || strpos($blob, 'combat state') !== false) {
        $states[] = 'co';
    }
    if (strpos($blob, 'non combat behavior') !== false || strpos($blob, '[c:nc ') !== false || strpos($blob, 'non-combat') !== false) {
        $states[] = 'nc';
    }
    if (strpos($blob, 'reaction behavior') !== false || strpos($blob, '[c:react ') !== false || strpos($blob, 'reaction state') !== false) {
        $states[] = 'react';
    }
    if (strpos($blob, 'dead state behavior') !== false || strpos($blob, '[c:dead ') !== false || strpos($blob, 'dead state') !== false) {
        $states[] = 'dead';
    }
    if (empty($states)) {
        $states[] = 'general';
    }

    $roles = array();
    if (preg_match('/\btank\b|\bthreat\b|\btaunt\b/', $blob)) {
        $roles[] = 'tank';
    }
    if (preg_match('/\bheal\b|\bhealer\b|\bholy\b|\brestoration\b|\bpreheal\b/', $blob)) {
        $roles[] = 'healer';
    }
    if (preg_match('/\bdps\b|\bdamage\b|\bboost\b|\bmelee\b|\branged\b|\battack\b/', $blob)) {
        $roles[] = 'dps';
    }
    if (empty($roles)) {
        $roles[] = 'general';
    }

    $classes = array();
    $classMap = array(
        'warrior' => array('warrior', 'arms', 'fury', 'protection'),
        'paladin' => array('paladin', 'retribution'),
        'hunter' => array('hunter', 'beast mastery', 'marksmanship', 'survival'),
        'rogue' => array('rogue', 'assassination', 'subtlety', 'combat rogue'),
        'priest' => array('priest', 'discipline', 'shadow priest'),
        'shaman' => array('shaman', 'elemental', 'enhancement'),
        'mage' => array('mage', 'arcane', 'frost mage', 'fire mage'),
        'warlock' => array('warlock', 'affliction', 'demonology', 'destruction'),
        'druid' => array('druid', 'balance', 'feral', 'restoration druid'),
        'deathknight' => array('death knight', 'deathknight', 'blood', 'unholy'),
    );
    foreach ($classMap as $className => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($blob, $keyword) !== false) {
                $classes[] = $className;
                break;
            }
        }
    }
    if (empty($classes)) {
        $classes[] = 'all';
    }

    return array(
        'states' => array_values(array_unique($states)),
        'roles' => array_values(array_unique($roles)),
        'classes' => array_values(array_unique($classes)),
    );
}

function spp_macro_options_from_commands(array $commands, array $keywords, array $fallback = array()): array
{
    $options = array();
    foreach ($commands as $command) {
        $name = trim((string)($command['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $needle = strtolower($name);
        foreach ($keywords as $keyword) {
            if (strpos($needle, strtolower($keyword)) !== false) {
                $options[$name] = array(
                    'label' => $name,
                    'value' => $name,
                );
                break;
            }
        }
    }

    if (empty($options)) {
        foreach ($fallback as $name) {
            $options[$name] = array(
                'label' => $name,
                'value' => $name,
            );
        }
    }

    ksort($options, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values($options);
}

function spp_macro_options_from_categories(array $commands, array $categories, array $fallback = array()): array
{
    $options = array();
    $wanted = array_map('strtolower', $categories);

    foreach ($commands as $command) {
        $category = strtolower((string)($command['category'] ?? ''));
        $name = trim((string)($command['name'] ?? ''));
        if ($name === '' || !in_array($category, $wanted, true)) {
            continue;
        }

        $options[$name] = array(
            'label' => $name,
            'value' => $name,
        );
    }

    if (empty($options)) {
        foreach ($fallback as $name) {
            $options[$name] = array(
                'label' => $name,
                'value' => $name,
            );
        }
    }

    ksort($options, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values($options);
}

function spp_macro_filter_named_options(array $options, array $excludePatterns = array(), array $includePatterns = array()): array
{
    $filtered = array();

    foreach ($options as $option) {
        $value = strtolower(trim((string)($option['value'] ?? '')));
        if ($value === '') {
            continue;
        }

        $included = empty($includePatterns);
        foreach ($includePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $included = true;
                break;
            }
        }
        if (!$included) {
            continue;
        }

        $excluded = false;
        foreach ($excludePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $excluded = true;
                break;
            }
        }
        if ($excluded) {
            continue;
        }

        $filtered[] = $option;
    }

    return $filtered;
}

function spp_macro_remove_option_values(array $options, array $takenOptions): array
{
    $taken = array();
    foreach ($takenOptions as $option) {
        $value = strtolower(trim((string)($option['value'] ?? '')));
        if ($value !== '') {
            $taken[$value] = true;
        }
    }

    return array_values(array_filter($options, function ($option) use ($taken) {
        $value = strtolower(trim((string)($option['value'] ?? '')));
        return $value === '' || !isset($taken[$value]);
    }));
}

function spp_macro_expand_general_states(array $states): array
{
    $expanded = array();
    foreach ($states as $state) {
        if ($state === 'general') {
            $expanded = array_merge($expanded, array('co', 'nc', 'react', 'dead'));
            continue;
        }
        $expanded[] = $state;
    }

    if (empty($expanded)) {
        $expanded = array('co', 'nc');
    }

    return array_values(array_unique($expanded));
}

function spp_macro_merge_strategy_sets(array $base, array $extra): array
{
    foreach ($extra as $state => $options) {
        if (!isset($base[$state])) {
            $base[$state] = array();
        }
        $base[$state] = array_values(array_unique(array_merge($base[$state], $options)));
        natcasesort($base[$state]);
        $base[$state] = array_values($base[$state]);
    }

    return $base;
}

function spp_macro_class_strategy_sets(array $commands, string $classKey, array $fallbackSets = array()): array
{
    $classNeedles = array(
        'warrior' => array('warrior'),
        'paladin' => array('paladin'),
        'hunter' => array('hunter'),
        'rogue' => array('rogue'),
        'priest' => array('priest'),
        'shaman' => array('shaman'),
        'mage' => array('mage'),
        'warlock' => array('warlock'),
        'druid' => array('druid'),
        'deathknight' => array('deathknight', 'death knight'),
    );

    $dynamicSets = array(
        'co' => array(),
        'nc' => array(),
        'react' => array(),
        'dead' => array(),
    );

    foreach ($commands as $command) {
        if (strtolower((string)($command['category'] ?? '')) !== 'strategy') {
            continue;
        }

        $name = trim((string)($command['name'] ?? ''));
        if ($name === '') {
            continue;
        }

        $haystack = strtolower($name . ' ' . (string)($command['help'] ?? ''));
        $matchesClass = false;
        foreach (($classNeedles[$classKey] ?? array($classKey)) as $needle) {
            if (strpos($haystack, strtolower($needle)) !== false) {
                $matchesClass = true;
                break;
            }
        }
        if (!$matchesClass) {
            continue;
        }

        $states = spp_macro_expand_general_states($command['state_tags'] ?? array());
        foreach ($states as $state) {
            if (!isset($dynamicSets[$state])) {
                $dynamicSets[$state] = array();
            }
            $dynamicSets[$state][] = '+' . $name;
        }
    }

    return spp_macro_merge_strategy_sets($fallbackSets, $dynamicSets);
}

$botCommands = array_values(array_map(function ($command) {
    $tags = spp_botcommand_extract_tags($command);
    $command['state_tags'] = $tags['states'];
    $command['role_tags'] = $tags['roles'];
    $command['class_tags'] = $tags['classes'];
    return $command;
}, $botCommands));

$questMacroOptions = spp_macro_options_from_commands(
    $botCommands,
    array('quest'),
    array(
        'quest',
        'accept quest',
        'accept all quests',
        'accept quest share',
        'auto share quest',
        'clean quest log',
        'confirm quest',
        'drop quest',
        'query quest',
        'quest details',
        'quest objective completed',
        'quest reward',
    )
);

$rtscMacroOptions = spp_macro_options_from_commands(
    $botCommands,
    array('rtsc', 'formation', 'position', 'follow target'),
    array(
        'rtsc',
        'set formation',
        'position',
        'follow target',
    )
);

$movementMacroOptions = spp_macro_options_from_categories(
    $botCommands,
    array('action'),
    array(
        'follow',
        'stay',
        'guard',
        'free',
        'flee',
        'return',
        'do follow',
        'mount',
        'pull',
        'pull back',
    )
);
$movementMacroOptions = spp_macro_filter_named_options(
    $movementMacroOptions,
    array(
        '/\bquest\b/',
        '/\bshaman\b|\bwarrior\b|\bpaladin\b|\bhunter\b|\brogue\b|\bpriest\b|\bmage\b|\bwarlock\b|\bdruid\b|\bdeath ?knight\b/',
        '/\bspell\b|\btotem\b|\bcurse\b|\bpoison\b|\bblessing\b|\baura\b|\bheal\b|\bshadow\b|\bfrost\b|\bfire\b|\bpet\b/',
    ),
    array(
        '/\bfollow\b|\bstay\b|\bguard\b|\bfree\b|\bflee\b|\breturn\b|\bmount\b|\bpull\b|\bformation\b|\bposition\b|\brtsc\b|\bmove\b|\battack\b/'
    )
);
$movementMacroOptions = spp_macro_remove_option_values($movementMacroOptions, $rtscMacroOptions);
$movementMacroOptions = spp_macro_remove_option_values($movementMacroOptions, $questMacroOptions);

$macroStatePresetOptions = array(
    array('label' => 'co (combat)', 'value' => 'co'),
    array('label' => 'nc (non-combat)', 'value' => 'nc'),
    array('label' => 'react (reaction)', 'value' => 'react'),
    array('label' => 'dead', 'value' => 'dead'),
);

$macroClassPresetConfigs = array(
    'warrior' => array(
        'label' => 'warrior',
        'strategies' => array(
            'co' => array('+tank', '+tank assist', '+threat', '+dps', '+dps assist', '+charge', '-threat'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg'),
            'react' => array('+pvp', '+intervene', '+charge'),
            'dead' => array('+ghost'),
        ),
    ),
    'paladin' => array(
        'label' => 'paladin',
        'strategies' => array(
            'co' => array('+tank', '+tank assist', '+threat', '+offheal', '+dps assist', '+cast time', '+blessing', '-threat'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg', '+conserve mana'),
            'react' => array('+pvp', '+cleanse', '+blessing'),
            'dead' => array('+ghost'),
        ),
    ),
    'hunter' => array(
        'label' => 'hunter',
        'strategies' => array(
            'co' => array('+dps', '+dps assist', '+ranged', '+close', '+pet', '+traps', '-threat'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg'),
            'react' => array('+pvp', '+kite', '+flee'),
            'dead' => array('+ghost'),
        ),
    ),
    'rogue' => array(
        'label' => 'rogue',
        'strategies' => array(
            'co' => array('+dps', '+dps assist', '+stealth', '+close', '+behind', '-threat'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg'),
            'react' => array('+pvp', '+flee', '+stealth'),
            'dead' => array('+ghost'),
        ),
    ),
    'priest' => array(
        'label' => 'priest',
        'strategies' => array(
            'co' => array('+offheal', '+heal', '+dps assist', '+cast time', '+shadow', '+discipline'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg', '+conserve mana'),
            'react' => array('+pvp', '+dispel', '+preheal'),
            'dead' => array('+ghost'),
        ),
    ),
    'shaman' => array(
        'label' => 'shaman',
        'strategies' => array(
            'co' => array('+dps', '+offheal', '+totems', '+dps assist', '+cast time', '+enhancement'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg', '+conserve mana'),
            'react' => array('+pvp', '+purge', '+totems'),
            'dead' => array('+ghost'),
        ),
    ),
    'mage' => array(
        'label' => 'mage',
        'strategies' => array(
            'co' => array('+dps', '+dps assist', '+ranged', '+cast time', '+frost', '+aoe', '-threat'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg', '+conserve mana'),
            'react' => array('+pvp', '+flee', '+counterspell'),
            'dead' => array('+ghost'),
        ),
    ),
    'warlock' => array(
        'label' => 'warlock',
        'strategies' => array(
            'co' => array('+dps', '+dps assist', '+ranged', '+pet', '+curses', '+cast time', '-threat'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg', '+conserve mana'),
            'react' => array('+pvp', '+fear', '+pet'),
            'dead' => array('+ghost'),
        ),
    ),
    'druid' => array(
        'label' => 'druid',
        'strategies' => array(
            'co' => array('+offheal', '+dps', '+tank', '+dps assist', '+cast time', '+balance', '+feral'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg', '+conserve mana'),
            'react' => array('+pvp', '+flee', '+remove curse'),
            'dead' => array('+ghost'),
        ),
    ),
    'deathknight' => array(
        'label' => 'death knight',
        'strategies' => array(
            'co' => array('+tank', '+dps', '+tank assist', '+threat', '+dps assist', '+close'),
            'nc' => array('+follow', '+loot', '+food', '+quest', '+grind', '+rpg'),
            'react' => array('+pvp', '+flee', '+close'),
            'dead' => array('+ghost'),
        ),
    ),
);
foreach ($macroClassPresetConfigs as $classKey => $classConfig) {
    $macroClassPresetConfigs[$classKey]['strategies'] = spp_macro_class_strategy_sets(
        $botCommands,
        $classKey,
        $classConfig['strategies']
    );
}

$chatFilterFamilies = array(
    array(
        'title' => 'Strategy Filters',
        'description' => 'Select bots by strategies enabled in a specific bot state.',
        'tokens' => array('@co=', '@noco=', '@nc=', '@nonc=', '@react=', '@noreact=', '@dead=', '@nodead='),
        'examples' => array(
            '@nc=rpg' => 'Bots with the non-combat rpg strategy enabled.',
            '@nonc=travel' => 'Bots without the travel strategy in non-combat.',
            '@co=melee' => 'Bots with the melee strategy in combat.',
            '@react=pvp' => 'Bots with the pvp strategy in reaction state.',
            '@dead=<>' => 'Bots with the <> strategy in dead state.',
        ),
    ),
    array(
        'title' => 'Role and Combat Filters',
        'description' => 'Select bots by role or by whether they fight at melee or range.',
        'tokens' => array('@tank', '@dps', '@heal', '@notank', '@nodps', '@noheal', '@melee', '@ranged'),
        'examples' => array(
            '@tank' => 'Bots with a tank role/spec.',
            '@dps' => 'Bots that are neither tank nor healer.',
            '@heal' => 'Bots with a healing role/spec.',
            '@melee' => 'Bots that fight in melee.',
            '@ranged' => 'Bots that fight at range.',
        ),
    ),
    array(
        'title' => 'Class Filters',
        'description' => 'Select bots by class. Death Knight only applies where supported by the expansion.',
        'tokens' => array('@warrior', '@paladin', '@hunter', '@rogue', '@priest', '@shaman', '@mage', '@warlock', '@druid', '@deathknight'),
        'examples' => array(
            '@warrior' => 'Warrior bots only.',
            '@mage' => 'Mage bots only.',
            '@rogue' => 'Rogue bots only.',
            '@warlock' => 'Warlock bots only.',
        ),
    ),
    array(
        'title' => 'Raid Icon Filters',
        'description' => 'Select bots that are marked with, or targeting, a raid target icon.',
        'tokens' => array('@star', '@circle', '@diamond', '@triangle', '@moon', '@square', '@cross', '@skull'),
        'examples' => array(
            '@star' => 'Bots marked with or targeting star.',
            '@circle' => 'Bots marked with or targeting circle.',
            '@skull' => 'Bots marked with or targeting skull.',
        ),
    ),
    array(
        'title' => 'Level Filters',
        'description' => 'Select bots by exact level or level range.',
        'tokens' => array('@60', '@10-20'),
        'examples' => array(
            '@60' => 'Bots that are level 60.',
            '@10-20' => 'Bots between levels 10 and 20.',
        ),
    ),
    array(
        'title' => 'Group Filters',
        'description' => 'Select bots by group status, raid status, subgroup, or group leadership.',
        'tokens' => array('@group', '@group2', '@group4-6', '@nogroup', '@leader', '@raid', '@noraid', '@rleader'),
        'examples' => array(
            '@group' => 'Bots that are in a group.',
            '@group2' => 'Bots in subgroup 2.',
            '@group4-6' => 'Bots in subgroups 4 through 6.',
            '@leader' => 'Bots leading their current group.',
            '@raid' => 'Bots in a raid group.',
        ),
    ),
    array(
        'title' => 'Guild Filters',
        'description' => 'Select bots by guild membership, guild name, guild rank, or guild leadership.',
        'tokens' => array('@guild', '@guild=', '@rank=', '@noguild', '@gleader'),
        'examples' => array(
            '@guild' => 'Bots in any guild.',
            '@guild=raiders' => 'Bots in the guild named raiders.',
            '@rank=Initiate' => 'Bots with the rank Initiate.',
            '@noguild' => 'Bots with no guild.',
            '@gleader' => 'Bots that lead their guild.',
        ),
    ),
    array(
        'title' => 'State Filters',
        'description' => 'Select bots by repair status, bag space, or whether they are inside an instance.',
        'tokens' => array('@needrepair', '@bagfull', '@bagalmostfull', '@outside', '@inside'),
        'examples' => array(
            '@needrepair' => 'Bots below 20% durability.',
            '@bagfull' => 'Bots with no bag space left.',
            '@bagalmostfull' => 'Bots with low bag space.',
            '@outside' => 'Bots outside an instance.',
            '@inside' => 'Bots inside an instance.',
        ),
    ),
    array(
        'title' => 'Item Usage Filters',
        'description' => 'Select bots by how they value an item link or qualifier.',
        'tokens' => array('@use=', '@need=', '@greed=', '@sell='),
        'examples' => array(
            '@use=[itemlink]' => 'Bots with any meaningful use for the item.',
            '@need=[itemlink]' => 'Bots that would need-roll the item.',
            '@greed=[itemlink]' => 'Bots that would greed-roll the item.',
            '@sell=[itemlink]' => 'Bots that would vendor or AH the item.',
        ),
    ),
    array(
        'title' => 'Talent Spec Filters',
        'description' => 'Select bots by their primary talent specialization name.',
        'tokens' => array('@holy', '@frost', '@shadow', '@restoration', '@protection', '@balance'),
        'examples' => array(
            '@holy' => 'Holy-spec bots.',
            '@frost' => 'Frost-spec bots.',
            '@shadow' => 'Shadow-spec bots.',
        ),
    ),
    array(
        'title' => 'Location Filters',
        'description' => 'Select bots by current map or zone name.',
        'tokens' => array('@azeroth', '@eastern kingdoms', '@dun morogh'),
        'examples' => array(
            '@azeroth' => 'Bots in Azeroth overworld.',
            '@eastern kingdoms' => 'Bots in Eastern Kingdoms overworld.',
            '@dun morogh' => 'Bots in the Dun Morogh zone.',
        ),
    ),
    array(
        'title' => 'Random Filters',
        'description' => 'Randomly select a subset of bots, optionally using a fixed distribution.',
        'tokens' => array('@random', '@random=', '@fixedrandom', '@fixedrandom='),
        'examples' => array(
            '@random' => 'About a 50% chance that a bot responds.',
            '@random=25' => 'About a 25% chance that a bot responds.',
            '@fixedrandom' => 'A fixed 50% bot subset.',
            '@fixedrandom=25' => 'A fixed 25% bot subset.',
        ),
    ),
    array(
        'title' => 'Gear Filters',
        'description' => 'Select bots by broad gear tier bands derived from gearscore.',
        'tokens' => array('@tier1', '@tier2-3'),
        'examples' => array(
            '@tier1' => 'Bots around tier 1 gear.',
            '@tier2-3' => 'Bots around tier 2 or 3 gear.',
        ),
    ),
    array(
        'title' => 'Quest Filters',
        'description' => 'Select bots that currently have a specific quest.',
        'tokens' => array('@quest='),
        'examples' => array(
            '@quest=523' => 'Bots that currently have quest 523.',
            '@quest=[quest link]' => 'Bots that currently have the linked quest.',
        ),
    ),
);

$macroFilterOptions = array(
    array('group' => 'Strategy', 'label' => '@co=', 'token' => '@co=', 'needsValue' => true, 'placeholder' => 'dps'),
    array('group' => 'Strategy', 'label' => '@noco=', 'token' => '@noco=', 'needsValue' => true, 'placeholder' => 'threat'),
    array('group' => 'Strategy', 'label' => '@nc=', 'token' => '@nc=', 'needsValue' => true, 'placeholder' => 'rpg'),
    array('group' => 'Strategy', 'label' => '@nonc=', 'token' => '@nonc=', 'needsValue' => true, 'placeholder' => 'travel'),
    array('group' => 'Strategy', 'label' => '@react=', 'token' => '@react=', 'needsValue' => true, 'placeholder' => 'pvp'),
    array('group' => 'Strategy', 'label' => '@noreact=', 'token' => '@noreact=', 'needsValue' => true, 'placeholder' => 'pvp'),
    array('group' => 'Strategy', 'label' => '@dead=', 'token' => '@dead=', 'needsValue' => true, 'placeholder' => '<>'),
    array('group' => 'Strategy', 'label' => '@nodead=', 'token' => '@nodead=', 'needsValue' => true, 'placeholder' => '<>'),
    array('group' => 'Role and Combat', 'label' => '@tank', 'token' => '@tank', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@dps', 'token' => '@dps', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@heal', 'token' => '@heal', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@notank', 'token' => '@notank', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@nodps', 'token' => '@nodps', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@noheal', 'token' => '@noheal', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@melee', 'token' => '@melee', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Role and Combat', 'label' => '@ranged', 'token' => '@ranged', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@warrior', 'token' => '@warrior', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@paladin', 'token' => '@paladin', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@hunter', 'token' => '@hunter', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@rogue', 'token' => '@rogue', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@priest', 'token' => '@priest', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@shaman', 'token' => '@shaman', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@mage', 'token' => '@mage', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@warlock', 'token' => '@warlock', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@druid', 'token' => '@druid', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Class', 'label' => '@deathknight', 'token' => '@deathknight', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@star', 'token' => '@star', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@circle', 'token' => '@circle', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@diamond', 'token' => '@diamond', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@triangle', 'token' => '@triangle', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@moon', 'token' => '@moon', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@square', 'token' => '@square', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@cross', 'token' => '@cross', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Raid Icons', 'label' => '@skull', 'token' => '@skull', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Level', 'label' => '@60', 'token' => '@60', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Level', 'label' => '@10-20', 'token' => '@10-20', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@group', 'token' => '@group', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@group2', 'token' => '@group2', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@group4-6', 'token' => '@group4-6', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@nogroup', 'token' => '@nogroup', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@leader', 'token' => '@leader', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@raid', 'token' => '@raid', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@noraid', 'token' => '@noraid', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Group', 'label' => '@rleader', 'token' => '@rleader', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Guild', 'label' => '@guild', 'token' => '@guild', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Guild', 'label' => '@guild=', 'token' => '@guild=', 'needsValue' => true, 'placeholder' => 'raiders'),
    array('group' => 'Guild', 'label' => '@rank=', 'token' => '@rank=', 'needsValue' => true, 'placeholder' => 'Initiate'),
    array('group' => 'Guild', 'label' => '@noguild', 'token' => '@noguild', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Guild', 'label' => '@gleader', 'token' => '@gleader', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'State', 'label' => '@needrepair', 'token' => '@needrepair', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'State', 'label' => '@bagfull', 'token' => '@bagfull', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'State', 'label' => '@bagalmostfull', 'token' => '@bagalmostfull', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'State', 'label' => '@outside', 'token' => '@outside', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'State', 'label' => '@inside', 'token' => '@inside', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Item Usage', 'label' => '@use=', 'token' => '@use=', 'needsValue' => true, 'placeholder' => '[itemlink]'),
    array('group' => 'Item Usage', 'label' => '@need=', 'token' => '@need=', 'needsValue' => true, 'placeholder' => '[itemlink]'),
    array('group' => 'Item Usage', 'label' => '@greed=', 'token' => '@greed=', 'needsValue' => true, 'placeholder' => '[itemlink]'),
    array('group' => 'Item Usage', 'label' => '@sell=', 'token' => '@sell=', 'needsValue' => true, 'placeholder' => '[itemlink]'),
    array('group' => 'Talent Spec', 'label' => '@holy', 'token' => '@holy', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Talent Spec', 'label' => '@frost', 'token' => '@frost', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Talent Spec', 'label' => '@shadow', 'token' => '@shadow', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Talent Spec', 'label' => '@restoration', 'token' => '@restoration', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Location', 'label' => '@azeroth', 'token' => '@azeroth', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Location', 'label' => '@eastern kingdoms', 'token' => '@eastern kingdoms', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Location', 'label' => '@dun morogh', 'token' => '@dun morogh', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Location', 'label' => '@custom zone/map', 'token' => '@', 'needsValue' => true, 'placeholder' => 'zone or map name'),
    array('group' => 'Random', 'label' => '@random', 'token' => '@random', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Random', 'label' => '@random=', 'token' => '@random=', 'needsValue' => true, 'placeholder' => '25'),
    array('group' => 'Random', 'label' => '@fixedrandom', 'token' => '@fixedrandom', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Random', 'label' => '@fixedrandom=', 'token' => '@fixedrandom=', 'needsValue' => true, 'placeholder' => '25'),
    array('group' => 'Gear', 'label' => '@tier1', 'token' => '@tier1', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Gear', 'label' => '@tier2-3', 'token' => '@tier2-3', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Quest', 'label' => '@quest=', 'token' => '@quest=', 'needsValue' => true, 'placeholder' => '523 or [quest link]'),
);

$macroPresets = array(
    array('group' => 'Movement and Control', 'key' => 'movement_family', 'label' => 'movement / control', 'command' => '', 'mode' => 'options', 'needsValue' => false, 'placeholder' => '', 'optionLabel' => 'Movement action', 'optionPlaceholder' => 'Choose a movement or control command', 'options' => $movementMacroOptions, 'customPlaceholder' => 'type any movement or control command'),
    array('group' => 'RTSC and Positioning', 'key' => 'rtsc_family', 'label' => 'rtsc / formation', 'command' => '', 'mode' => 'options', 'needsValue' => false, 'placeholder' => '', 'optionLabel' => 'RTSC action', 'optionPlaceholder' => 'Choose an RTSC or formation command', 'options' => $rtscMacroOptions, 'customPlaceholder' => 'type any rtsc or formation command'),
    array('group' => 'Utility', 'key' => 'grind', 'label' => 'grind', 'command' => 'grind', 'mode' => 'direct', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Utility', 'key' => 'loot', 'label' => 'loot', 'command' => 'loot', 'mode' => 'direct', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Utility', 'key' => 'quest', 'label' => 'quest command', 'command' => '', 'mode' => 'options', 'needsValue' => false, 'placeholder' => '', 'optionLabel' => 'Quest action', 'optionPlaceholder' => 'Choose a quest command', 'options' => $questMacroOptions, 'customPlaceholder' => 'type any quest command'),
    array('group' => 'Utility', 'key' => 'save_ai', 'label' => 'save ai', 'command' => 'save ai', 'mode' => 'direct', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Utility', 'key' => 'reset_ai', 'label' => 'reset ai', 'command' => 'reset ai', 'mode' => 'direct', 'needsValue' => false, 'placeholder' => ''),
    array('group' => 'Strategy Setters', 'key' => 'co', 'label' => 'co <strategies>', 'command' => 'co', 'mode' => 'value', 'needsValue' => true, 'placeholder' => '+dps,+dps assist,-threat'),
    array('group' => 'Strategy Setters', 'key' => 'nc', 'label' => 'nc <strategies>', 'command' => 'nc', 'mode' => 'value', 'needsValue' => true, 'placeholder' => '+rpg,+quest,+grind'),
    array('group' => 'Strategy Setters', 'key' => 'react', 'label' => 'react <strategies>', 'command' => 'react', 'mode' => 'value', 'needsValue' => true, 'placeholder' => '+pvp'),
    array('group' => 'Strategy Setters', 'key' => 'dead', 'label' => 'dead <strategies>', 'command' => 'dead', 'mode' => 'value', 'needsValue' => true, 'placeholder' => '+ghost'),
);
foreach ($macroClassPresetConfigs as $classKey => $classConfig) {
    $macroPresets[] = array(
        'group' => 'Class Strategy Builders',
        'key' => 'class_' . $classKey,
        'label' => $classConfig['label'],
        'command' => '',
        'mode' => 'class_strategies',
        'needsValue' => false,
        'placeholder' => '',
        'optionLabel' => 'State',
        'optionPlaceholder' => 'Choose a state',
        'options' => $macroStatePresetOptions,
        'strategyOptions' => $classConfig['strategies'],
    );
}
$macroPresets[] = array('group' => 'Custom', 'key' => 'custom', 'label' => 'Custom command', 'command' => '', 'mode' => 'custom', 'needsValue' => true, 'placeholder' => 'type any bot whisper command');
$macroLayerFilterOptions = array_values(array_filter($macroFilterOptions, function ($option) {
    return empty($option['needsValue']);
}));
$commandTabs = array('strategies', 'vanilla', 'macros', 'filters', 'bot', 'commands', 'builder');
$gmSecurityValues = array();
$gmPrefixValues = array();
foreach ($gmCommands as $gmCommand) {
    $security = trim((string)($gmCommand['security'] ?? ''));
    if ($security !== '') {
        $gmSecurityValues[$security] = true;
    }
    $commandName = trim((string)($gmCommand['name'] ?? ''));
    if ($commandName !== '') {
        $parts = preg_split('/\s+/', $commandName);
        $prefix = strtolower((string)($parts[0] ?? ''));
        if ($prefix !== '') {
            $gmPrefixValues[$prefix] = true;
        }
    }
}
$gmSecurityValues = array_keys($gmSecurityValues);
sort($gmSecurityValues, SORT_NATURAL);
$gmPrefixValues = array_keys($gmPrefixValues);
sort($gmPrefixValues, SORT_NATURAL);
$activeCommandTab = strtolower(trim((string)($_GET['tab'] ?? (($sub ?? '') === 'commands' ? 'commands' : 'strategies'))));
if (!in_array($activeCommandTab, $commandTabs, true)) {
    $activeCommandTab = 'strategies';
}
?>

<?php builddiv_start(1, 'Bot Guide'); ?>

<style>
.sref-tabs { display:flex; gap:4px; margin-bottom:12px; flex-wrap:wrap; }
.sref-tab-btn {
    padding:6px 16px; cursor:pointer; border:none; border-radius:4px 4px 0 0;
    background:#2a2a2a; color:#aaa; font-size:13px; font-weight:600;
}
.sref-tab-btn.active { background:#444; color:#f0c070; border-bottom:2px solid #f0c070; }
.sref-panel { display:none; }
.sref-panel.active { display:block; }
.sref-panel table { width:100%; border-collapse:collapse; margin-bottom:16px; font-size:13px; }
.sref-panel th { background:#2a2a2a; color:#f0c070; padding:6px 8px; text-align:left; }
.sref-panel td { padding:5px 8px; border-bottom:1px solid #333; vertical-align:top; }
.sref-panel td code, .sref-panel th code { background:#1a1a1a; padding:1px 4px; border-radius:3px; font-size:12px; color:#7ec8e3; }
.sref-panel h3 { color:#f0c070; margin:20px 0 6px; font-size:14px; border-bottom:1px solid #444; padding-bottom:4px; }
.sref-panel h4 { color:#ccc; margin:12px 0 4px; font-size:13px; }
.sref-panel pre { background:#1a1a1a; padding:10px; border-radius:4px; font-size:12px; color:#aed6a0; overflow-x:auto; margin:6px 0 12px; }
.sref-panel p { color:#bbb; font-size:13px; margin:4px 0 10px; }
.sref-flavor-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
@media(max-width:700px){ .sref-flavor-grid { grid-template-columns:1fr; } }
.sref-flavor-card { background:#1e1e1e; border:1px solid #383838; border-radius:6px; padding:10px 12px; }
.sref-flavor-card h4 { color:#f0c070; margin:0 0 6px; font-size:13px; text-transform:uppercase; letter-spacing:.5px; }
.sref-flavor-card p { color:#999; font-size:12px; margin:0 0 8px; font-style:italic; }
.sref-flavor-card table { font-size:12px; }
.sref-flavor-card td { padding:3px 6px; border-bottom:1px solid #2a2a2a; }
.sref-flavor-card td:first-child { color:#7ec8e3; width:40px; font-weight:600; }

.csb-section { margin-bottom:18px; }
.csb-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:6px; }
.csb-label { color:#aaa; font-size:12px; min-width:54px; }
.csb-input { background:#1a1a1a; border:1px solid #444; color:#ddd; padding:4px 8px; border-radius:4px; font-size:13px; }
.csb-input:focus { outline:none; border-color:#f0c070; }
.csb-select { background:#1a1a1a; border:1px solid #444; color:#ddd; padding:4px 6px; border-radius:4px; font-size:12px; }
.csb-select:focus { outline:none; border-color:#f0c070; }
.csb-btn { padding:4px 10px; border:none; border-radius:4px; cursor:pointer; font-size:12px; font-weight:600; }
.csb-btn-add { background:#2a4a2a; color:#7ec87e; }
.csb-btn-add:hover { background:#3a5a3a; }
.csb-btn-del { background:#4a2a2a; color:#e07e7e; }
.csb-btn-del:hover { background:#5a3a3a; }
.csb-btn-copy { background:#2a3a4a; color:#7ec8e3; padding:5px 14px; }
.csb-btn-copy:hover { background:#3a4a5a; }
.csb-line-card { background:#1e1e1e; border:1px solid #383838; border-radius:6px; padding:10px 12px; margin-bottom:10px; }
.csb-line-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.csb-line-num { color:#888; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.csb-action-row { display:flex; align-items:center; gap:6px; margin-bottom:5px; flex-wrap:wrap; }
.csb-priority { width:52px; }
.csb-qual { min-width:140px; }
.csb-output { background:#111; border:1px solid #333; border-radius:6px; padding:12px 14px; font-family:monospace; font-size:12px; color:#aed6a0; white-space:pre-wrap; word-break:break-all; margin-bottom:8px; min-height:40px; }
.csb-output-label { color:#888; font-size:11px; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
.csb-copy-row { display:flex; align-items:center; gap:8px; margin-bottom:12px; }
.csb-scope-row { display:flex; gap:16px; align-items:center; }
.csb-radio { accent-color:#f0c070; }
.csb-sep { border:none; border-top:1px solid #333; margin:14px 0; }

.cff-search { width:100%; max-width:420px; margin-bottom:12px; }
.cff-note { color:#999; font-size:12px; margin-bottom:10px; }
.cff-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:12px; }
.cff-card { background:#1e1e1e; border:1px solid #383838; border-radius:6px; padding:12px; }
.cff-card h4 { color:#f0c070; margin:0 0 6px; font-size:13px; }
.cff-card p { color:#aaa; font-size:12px; margin:0 0 10px; }
.cff-card summary { cursor:pointer; list-style:none; }
.cff-card summary::-webkit-details-marker { display:none; }
.cff-card summary h4 { display:flex; align-items:center; justify-content:space-between; }
.cff-token-row { margin-bottom:8px; font-size:12px; color:#bbb; }
.cff-token-row code { margin-right:4px; }
.cff-example-list { display:flex; flex-direction:column; gap:6px; }
.cff-example-item { background:#151515; border:1px solid #2d2d2d; border-radius:4px; padding:8px; }
.cff-example-item code { display:block; margin-bottom:4px; }
.ref-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:12px; }
.ref-card { background:#1e1e1e; border:1px solid #383838; border-radius:6px; padding:12px; }
.ref-card ul { margin:8px 0 0; padding-left:18px; color:#bbb; }
.ref-card li { margin-bottom:6px; }
.vanilla-stack { display:flex; flex-direction:column; gap:12px; margin:16px 0; }
.vanilla-card { background:#1e1e1e; border:1px solid #383838; border-radius:6px; }
.vanilla-card summary { cursor:pointer; list-style:none; padding:12px 14px; }
.vanilla-card summary::-webkit-details-marker { display:none; }
.vanilla-card summary h4 { color:#f0c070; margin:0; font-size:13px; display:flex; align-items:center; justify-content:space-between; gap:12px; text-transform:uppercase; letter-spacing:.4px; }
.vanilla-toggle { color:#f0c070; font-size:18px; line-height:1; }
.vanilla-body { padding:0 14px 14px; }
.vanilla-body p:first-child { margin-top:0; }
.vanilla-body ul { margin:8px 0 12px; padding-left:18px; color:#bbb; }
.vanilla-body li { margin-bottom:8px; }
.vanilla-body pre { margin-top:8px; }
.cmd-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:12px; }
.cmd-card { background:#1e1e1e; border:1px solid #383838; border-radius:6px; padding:12px; }
.cmd-card h4 { color:#f0c070; margin:0 0 8px; font-size:14px; }
.cmd-header { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:8px; }
.cmd-header h4 { margin:0; }
.cmd-toggle { min-width:72px; }
.cmd-meta { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:10px; }
.cmd-chip { background:#151515; border:1px solid #2d2d2d; border-radius:999px; padding:3px 8px; color:#bbb; font-size:11px; }
.cmd-chip.is-clickable { cursor:pointer; }
.cmd-chip.is-clickable:hover { border-color:#f0c070; color:#f0c070; }
.cmd-help { color:#bbb; font-size:12px; line-height:1.45; white-space:pre-line; }
.cmd-help.is-collapsed { display:none; }
.cmd-filter-bar { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 12px; }
.cmd-filter-label { color:#888; font-size:12px; margin-right:4px; }
.cmd-filter-reset { margin-left:4px; }

.mb-card { background:#1e1e1e; border:1px solid #383838; border-radius:6px; padding:12px; margin-bottom:14px; }
.mb-filter-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:8px; }
.mb-filter-list { margin-top:8px; }
.mb-help-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px; margin:12px 0; }
.mb-help-card { background:#181818; border:1px solid #2d2d2d; border-radius:6px; padding:10px 12px; }
.mb-help-card h4 { margin:0 0 6px; color:#f0c070; font-size:13px; }
.mb-help-card p { margin:0; color:#aaa; font-size:12px; }
.mb-status { color:#d8bf7a; font-size:12px; margin-bottom:8px; }
.mb-quick-groups { display:flex; flex-direction:column; gap:10px; margin:10px 0 12px; }
.mb-quick-group { display:flex; gap:8px; align-items:flex-start; flex-wrap:wrap; }
.mb-quick-label { color:#888; font-size:12px; min-width:44px; padding-top:6px; }
.mb-quick-buttons { display:flex; flex-wrap:wrap; gap:8px; }
.mb-quick-btn { background:#151515; border:1px solid #3a3a3a; color:#bbb; border-radius:999px; padding:4px 10px; cursor:pointer; font-size:12px; }
.mb-quick-btn:hover { border-color:#f0c070; color:#f0c070; }
.mb-row { display:flex; align-items:flex-start; gap:12px; margin-bottom:10px; }
.mb-row .csb-label { min-width:68px; padding-top:7px; }
.mb-row-fields { display:flex; flex:0 1 auto; gap:10px; flex-wrap:wrap; align-items:center; }
.mb-row-fields .csb-input,
.mb-row-fields .csb-select { flex:0 1 auto; min-width:0; }
#mb-target { width:20ch; }
#mb-delivery { width:14ch; }
#mb-preset { width:18ch; }
#mb-preset-option { width:18ch; }
#mb-preset-value { width:34ch; max-width:100%; }
.mb-sub-builder { margin:6px 0 10px 80px; max-width:52ch; padding:10px 12px; background:#181818; border:1px solid #2d2d2d; border-radius:6px; }
.mb-sub-builder.is-hidden { display:none; }
.mb-sub-title { color:#888; font-size:11px; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px; }
.mb-strategy-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:8px; }
.mb-strategy-row .csb-select { width:28ch; max-width:100%; min-width:0; }
.mb-layer { background:#181818; border:1px solid #2d2d2d; border-radius:6px; padding:10px 12px; margin-bottom:10px; }
.mb-layer-head { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; }
.mb-layer-title { color:#f0c070; font-size:12px; font-weight:600; }
.mb-layer-grid { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.mb-layer-grid .csb-select,
.mb-layer-grid .csb-input { min-width:0; }
.mb-layer-target { width:14ch; }
.mb-layer-preset { width:18ch; }
.mb-layer-option { width:18ch; }
.mb-layer-value { width:26ch; max-width:100%; }
@media(max-width:700px){
  .mb-row { flex-direction:column; gap:6px; }
  .mb-row .csb-label { min-width:0; padding-top:0; }
  .mb-row-fields { width:100%; }
  #mb-target,
  #mb-delivery,
  #mb-preset,
  #mb-preset-option,
  #mb-preset-value { width:100%; }
  .mb-sub-builder { margin-left:0; }
  .mb-strategy-row .csb-select { width:100%; }
  .mb-layer-grid .csb-select,
  .mb-layer-grid .csb-input { width:100%; }
}
</style>

<div class="modern-content">
  <div class="sref-tabs">
    <button class="sref-tab-btn<?php echo $activeCommandTab === 'strategies' ? ' active' : ''; ?>" onclick="srefTab(this,'tab-strategies')">Strategy Reference</button>
    <button class="sref-tab-btn<?php echo $activeCommandTab === 'vanilla' ? ' active' : ''; ?>" onclick="srefTab(this,'tab-vanilla')">Vanilla Raiding</button>
    <button class="sref-tab-btn<?php echo $activeCommandTab === 'macros' ? ' active' : ''; ?>" onclick="srefTab(this,'tab-macros')">Macro Builder</button>
    <button class="sref-tab-btn<?php echo $activeCommandTab === 'filters' ? ' active' : ''; ?>" onclick="srefTab(this,'tab-filters')">Chat Filters</button>
    <button class="sref-tab-btn<?php echo $activeCommandTab === 'bot' ? ' active' : ''; ?>" onclick="srefTab(this,'tab-bot')">Bot Commands</button>
    <button class="sref-tab-btn<?php echo $activeCommandTab === 'commands' ? ' active' : ''; ?>" onclick="srefTab(this,'tab-commands')">Commands</button>
    <button class="sref-tab-btn<?php echo $activeCommandTab === 'builder' ? ' active' : ''; ?>" onclick="srefTab(this,'tab-builder')">Custom Builder</button>
  </div>

  <div id="tab-bot" class="sref-panel<?php echo $activeCommandTab === 'bot' ? ' active' : ''; ?>">
    <input type="text" id="botCommandSearch" class="csb-input cff-search" oninput="filterBotCommandCards()" placeholder="Search bot commands...">
    <div class="cmd-filter-bar">
      <span class="cmd-filter-label">Type</span>
      <select id="botCommandTypeFilter" class="csb-select" onchange="filterBotCommandCards()">
        <option value="all">All types</option>
        <option value="action">Action</option>
        <option value="strategy">Strategy</option>
        <option value="trigger">Trigger</option>
        <option value="value">Value</option>
        <option value="list">List</option>
        <option value="chatfilter">Chatfilter</option>
        <option value="object">Object</option>
        <option value="template">Template</option>
        <option value="help">Help</option>
      </select>
      <span class="cmd-filter-label">State</span>
      <select id="botCommandStateFilter" class="csb-select" onchange="filterBotCommandCards()">
        <option value="all">All states</option>
        <option value="co">Combat</option>
        <option value="nc">Non-combat</option>
        <option value="react">Reaction</option>
        <option value="dead">Dead</option>
        <option value="general">General</option>
      </select>
      <span class="cmd-filter-label">Role</span>
      <select id="botCommandRoleFilter" class="csb-select" onchange="filterBotCommandCards()">
        <option value="all">All roles</option>
        <option value="tank">Tank</option>
        <option value="dps">DPS</option>
        <option value="healer">Healer</option>
        <option value="general">General</option>
      </select>
      <span class="cmd-filter-label">Class</span>
      <select id="botCommandClassFilter" class="csb-select" onchange="filterBotCommandCards()">
        <option value="all">All classes</option>
        <option value="warrior">Warrior</option>
        <option value="paladin">Paladin</option>
        <option value="hunter">Hunter</option>
        <option value="rogue">Rogue</option>
        <option value="priest">Priest</option>
        <option value="shaman">Shaman</option>
        <option value="mage">Mage</option>
        <option value="warlock">Warlock</option>
        <option value="druid">Druid</option>
        <option value="deathknight">Death Knight</option>
      </select>
      <button type="button" class="csb-btn csb-btn-copy cmd-filter-reset" onclick="resetBotCommandFilters()">Reset Filters</button>
    </div>
    <div class="cff-note">Browse by state first, then role, then class. Search still matches command name, metadata, and help text.</div>
    <div class="cmd-grid" id="botCommandCards">
      <?php foreach ($botCommands as $topic): ?>
      <?php
        $searchBlob = strtolower(
            ($topic['name'] ?? '') . ' ' .
            ($topic['category'] ?? '') . ' ' .
            ($topic['subcategory'] ?? '') . ' ' .
            ($topic['security'] ?? '') . ' ' .
            ($topic['help'] ?? '') . ' ' .
            implode(' ', $topic['state_tags'] ?? array()) . ' ' .
            implode(' ', $topic['role_tags'] ?? array()) . ' ' .
            implode(' ', $topic['class_tags'] ?? array())
        );
      ?>
      <div class="cmd-card"
           data-filter-search="<?php echo htmlspecialchars($searchBlob); ?>"
           data-type="<?php echo htmlspecialchars(strtolower((string)($topic['category'] ?? ''))); ?>"
           data-state-tags="<?php echo htmlspecialchars(implode(',', $topic['state_tags'] ?? array())); ?>"
           data-role-tags="<?php echo htmlspecialchars(implode(',', $topic['role_tags'] ?? array())); ?>"
           data-class-tags="<?php echo htmlspecialchars(implode(',', $topic['class_tags'] ?? array())); ?>">
        <div class="cmd-header">
          <h4><?php echo htmlspecialchars($topic['name']); ?></h4>
          <button type="button" class="csb-btn csb-btn-copy cmd-toggle" onclick="toggleCommandCard(this)" aria-label="Expand command details">+</button>
        </div>
        <div class="cmd-meta">
          <?php if (($topic['category'] ?? '') !== '' && ($topic['category'] ?? '') !== '-'): ?>
          <button type="button" class="cmd-chip is-clickable" onclick="applyBotChipFilter('type','<?php echo htmlspecialchars(strtolower((string)$topic['category']), ENT_QUOTES); ?>')" title="Filter bot commands by type <?php echo htmlspecialchars((string)$topic['category']); ?>"><?php echo htmlspecialchars($topic['category']); ?></button>
          <?php endif; ?>
          <?php foreach (($topic['state_tags'] ?? array()) as $tag): ?>
          <button type="button" class="cmd-chip is-clickable" onclick="applyBotChipFilter('state','<?php echo htmlspecialchars($tag, ENT_QUOTES); ?>')" title="Filter bot commands by state <?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars($tag === 'react' ? 'State reaction' : 'State ' . $tag); ?></button>
          <?php endforeach; ?>
          <?php foreach (($topic['role_tags'] ?? array()) as $tag): ?>
          <button type="button" class="cmd-chip is-clickable" onclick="applyBotChipFilter('role','<?php echo htmlspecialchars($tag, ENT_QUOTES); ?>')" title="Filter bot commands by role <?php echo htmlspecialchars($tag); ?>">Role <?php echo htmlspecialchars($tag); ?></button>
          <?php endforeach; ?>
          <?php foreach (($topic['class_tags'] ?? array()) as $tag): ?>
          <?php if ($tag !== 'all'): ?>
          <button type="button" class="cmd-chip is-clickable" onclick="applyBotChipFilter('class','<?php echo htmlspecialchars($tag, ENT_QUOTES); ?>')" title="Filter bot commands by class <?php echo htmlspecialchars($tag); ?>"><?php echo htmlspecialchars(ucfirst($tag)); ?></button>
          <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <div class="cmd-help is-collapsed"><?php echo nl2br(htmlspecialchars($topic['help'])); ?></div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($botCommands)): ?>
      <div class="cmd-card"><div class="cmd-help">No bot commands found.</div></div>
      <?php endif; ?>
    </div>
  </div>

  <div id="tab-commands" class="sref-panel<?php echo $activeCommandTab === 'commands' ? ' active' : ''; ?>">
    <input type="text" id="gmCommandSearch" class="csb-input cff-search" oninput="filterGmCommandCards()" placeholder="Search GM commands...">
    <div class="cmd-filter-bar">
      <span class="cmd-filter-label">Security</span>
      <select id="gmCommandSecurityFilter" class="csb-select" onchange="filterGmCommandCards()">
        <option value="all">All levels</option>
        <?php foreach ($gmSecurityValues as $securityValue): ?>
        <option value="<?php echo htmlspecialchars($securityValue); ?>"><?php echo htmlspecialchars($securityValue); ?></option>
        <?php endforeach; ?>
      </select>
      <span class="cmd-filter-label">Prefix</span>
      <select id="gmCommandPrefixFilter" class="csb-select" onchange="filterGmCommandCards()">
        <option value="all">All prefixes</option>
        <?php foreach ($gmPrefixValues as $prefixValue): ?>
        <option value="<?php echo htmlspecialchars($prefixValue); ?>"><?php echo htmlspecialchars($prefixValue); ?></option>
        <?php endforeach; ?>
      </select>
      <button type="button" class="csb-btn csb-btn-copy cmd-filter-reset" onclick="resetGmCommandFilters()">Reset Filters</button>
    </div>
    <div class="cff-note">Search by command name, security level, or help text. Prefix comes from the first word of the command name.</div>
    <div class="cmd-grid" id="gmCommandCards">
      <?php foreach ($gmCommands as $cmd): ?>
      <?php
        $parts = preg_split('/\s+/', trim((string)($cmd['name'] ?? '')));
        $prefix = strtolower((string)($parts[0] ?? ''));
        $searchBlob = strtolower(
            ($cmd['name'] ?? '') . ' ' .
            ($cmd['security'] ?? '') . ' ' .
            ($cmd['help'] ?? '')
        );
      ?>
      <div class="cmd-card"
           data-filter-search="<?php echo htmlspecialchars($searchBlob); ?>"
           data-security="<?php echo htmlspecialchars((string)($cmd['security'] ?? '')); ?>"
           data-prefix="<?php echo htmlspecialchars($prefix); ?>">
        <div class="cmd-header">
          <h4><?php echo htmlspecialchars($cmd['name']); ?></h4>
          <button type="button" class="csb-btn csb-btn-copy cmd-toggle" onclick="toggleCommandCard(this)" aria-label="Expand command details">+</button>
        </div>
        <div class="cmd-meta">
          <button type="button" class="cmd-chip is-clickable" onclick="applyGmChipFilter('security','<?php echo htmlspecialchars((string)($cmd['security'] ?? ''), ENT_QUOTES); ?>')" title="Filter GM commands by security <?php echo htmlspecialchars((string)($cmd['security'] ?? '')); ?>">Security <?php echo htmlspecialchars($cmd['security']); ?></button>
          <?php if ($prefix !== ''): ?>
          <button type="button" class="cmd-chip is-clickable" onclick="applyGmChipFilter('prefix','<?php echo htmlspecialchars($prefix, ENT_QUOTES); ?>')" title="Filter GM commands by prefix <?php echo htmlspecialchars($prefix); ?>"><?php echo htmlspecialchars($prefix); ?></button>
          <?php endif; ?>
        </div>
        <div class="cmd-help is-collapsed"><?php echo nl2br(htmlspecialchars($cmd['help'])); ?></div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($gmCommands)): ?>
      <div class="cmd-card"><div class="cmd-help">No GM commands found for this account level.</div></div>
      <?php endif; ?>
    </div>
  </div>

  <div id="tab-filters" class="sref-panel<?php echo $activeCommandTab === 'filters' ? ' active' : ''; ?>">
    <h3>Chat Filters</h3>
    <p>Chat filters are prefix tokens that narrow which bots respond before the command runs. You can chain multiple filters together, then place the command after them.</p>
    <div class="mb-help-grid">
      <div class="mb-help-card">
        <h4>Order</h4>
        <p><code>/w BotName @tank @60 follow</code></p>
      </div>
      <div class="mb-help-card">
        <h4>Chaining</h4>
        <p>Each filter narrows the pool further. The command starts after the last filter token.</p>
      </div>
      <div class="mb-help-card">
        <h4>Source</h4>
        <p>This tab reflects the current filter surface implemented in <code>playerbots/playerbot/ChatFilter.cpp</code>.</p>
      </div>
    </div>
    <input type="text" id="chatFilterSearch" class="csb-input cff-search" oninput="filterChatFilterCards()" placeholder="Search filter names, descriptions, or examples...">
    <div class="cff-note">Value-based filters like <code>@guild=</code>, <code>@rank=</code>, <code>@co=</code>, <code>@quest=</code>, and <code>@use=</code> expect text after the token.</div>
    <div class="cff-grid" id="chatFilterCards">
      <?php foreach ($chatFilterFamilies as $family): ?>
      <?php
        $searchBlob = strtolower(
            $family['title'] . ' ' .
            $family['description'] . ' ' .
            implode(' ', $family['tokens']) . ' ' .
            implode(' ', array_keys($family['examples'])) . ' ' .
            implode(' ', array_values($family['examples']))
        );
      ?>
      <details class="cff-card" data-filter-search="<?php echo htmlspecialchars($searchBlob); ?>">
          <summary>
            <h4><?php echo htmlspecialchars($family['title']); ?><span class="cff-toggle-indicator">+</span></h4>
          </summary>
        <p><?php echo htmlspecialchars($family['description']); ?></p>
        <div class="cff-token-row">
          <?php foreach ($family['tokens'] as $token): ?>
          <code><?php echo htmlspecialchars($token); ?></code>
          <?php endforeach; ?>
        </div>
        <div class="cff-example-list">
          <?php foreach ($family['examples'] as $syntax => $meaning): ?>
          <div class="cff-example-item">
            <code><?php echo htmlspecialchars($syntax); ?></code>
            <span><?php echo htmlspecialchars($meaning); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>

  <div id="tab-strategies" class="sref-panel<?php echo $activeCommandTab === 'strategies' ? ' active' : ''; ?>">
    <h3>Strategy Reference</h3>
    <p>This page is most useful when it helps you answer three questions quickly: which state am I changing, what role package should the bot run, and what movement or follow behavior do I want around that package.</p>
    <table>
      <thead><tr><th>Key</th><th>Bot State</th><th>When Active</th></tr></thead>
      <tbody>
        <tr><td><code>co</code></td><td>Combat</td><td>While the bot is in a fight.</td></tr>
        <tr><td><code>nc</code></td><td>Non-combat</td><td>Wandering, questing, traveling, idle.</td></tr>
        <tr><td><code>react</code></td><td>Reaction</td><td>Parallel to combat for immediate responses.</td></tr>
        <tr><td><code>dead</code></td><td>Dead</td><td>While the bot is a ghost.</td></tr>
      </tbody>
    </table>
    <table>
      <thead><tr><th>Priority Band</th><th>Examples</th></tr></thead>
      <tbody>
        <tr><td>90 - Emergency</td><td>Critical health alert, emergency heal.</td></tr>
        <tr><td>80 - Critical heal</td><td>Major healing response.</td></tr>
        <tr><td>60-70 - Heal</td><td>Light or medium heals.</td></tr>
        <tr><td>50 - Dispel</td><td>Remove curse, abolish poison.</td></tr>
        <tr><td>40 - Interrupt</td><td>Kick, counterspell.</td></tr>
        <tr><td>30 - Move</td><td>Charge, disengage.</td></tr>
        <tr><td>20 - High</td><td>Major cooldowns.</td></tr>
        <tr><td>10 - Normal</td><td>Standard rotation.</td></tr>
        <tr><td>1 - Idle</td><td>Fallback behavior like melee or wand.</td></tr>
      </tbody>
    </table>
    <p>Strategy changes use <code>+add</code> and <code>-remove</code> syntax, comma-separated: <code>+dps,+dps assist,-threat</code></p>

    <div class="ref-grid">
      <div class="ref-card">
        <h4>Role Starters</h4>
        <ul>
          <li><code>DPS</code>: <code>co +dps,+dps assist,-threat</code></li>
          <li><code>Tank</code>: <code>co +dps,+tank assist,+threat,+boost</code></li>
          <li><code>Healer</code>: <code>co +offheal,+dps assist,+cast time</code></li>
          <li><code>Leveling</code>: pair a combat package with <code>nc +rpg,+quest,+grind,+loot,+wander</code></li>
        </ul>
      </div>
      <div class="ref-card">
        <h4>Movement and Follow</h4>
        <ul>
          <li><code>follow</code>: stay on the leader</li>
          <li><code>stay</code>: hold the current position</li>
          <li><code>guard</code>: defend the master's spot</li>
          <li><code>free</code>: move independently</li>
          <li><code>wander</code>: roam near players, then snap back when too far</li>
        </ul>
      </div>
      <div class="ref-card">
        <h4>Positioning and Safety</h4>
        <ul>
          <li><code>behind</code>, <code>close</code>, <code>ranged</code>, <code>kite</code>, <code>pull back</code></li>
          <li><code>avoid aoe</code> and <code>avoid mobs</code> prevent bad pulls and bad floor effects</li>
          <li><code>flee</code>, <code>preheal</code>, and <code>cast time</code> are the main survival helpers</li>
        </ul>
      </div>
      <div class="ref-card">
        <h4>Persistence Workflow</h4>
        <ul>
          <li>Change one or more states with <code>co</code>, <code>nc</code>, <code>react</code>, or <code>dead</code></li>
          <li>Save the setup with <code>save ai</code></li>
          <li>Reuse later with <code>load ai &lt;preset&gt;</code></li>
          <li>Reset to defaults with <code>reset ai</code></li>
        </ul>
      </div>
    </div>

    <h3>Useful Starter Loads</h3>
    <pre>Solo leveling bot
co: +dps,-threat,+custom::say
nc: +rpg,+quest,+grind,+loot,+wander,+custom::say

Group DPS bot
co: +dps,+dps assist,-threat,+boost
nc: +follow,+loot,+delayed roll,+food

Group tank bot
co: +dps,+tank assist,+threat,+boost
nc: +follow,+loot,+delayed roll,+food

Group healer bot
co: +offheal,+dps assist,+cast time
nc: +follow,+loot,+delayed roll,+food,+conserve mana

BG farmer
co: +dps,+dps assist,+threat,+boost,+pvp,+duel
nc: +bg,+wander,+rpg
react: +pvp</pre>

    <h3>Commands You Actually Reuse</h3>
    <table>
      <thead><tr><th>Command</th><th>Effect</th></tr></thead>
      <tbody>
        <tr><td><code>.bot co &lt;strategies&gt;</code></td><td>Change combat strategies.</td></tr>
        <tr><td><code>.bot nc &lt;strategies&gt;</code></td><td>Change non-combat strategies.</td></tr>
        <tr><td><code>.bot react &lt;strategies&gt;</code></td><td>Change reaction strategies.</td></tr>
        <tr><td><code>.bot dead &lt;strategies&gt;</code></td><td>Change dead strategies.</td></tr>
        <tr><td><code>.bot save ai</code></td><td>Persist current strategies.</td></tr>
        <tr><td><code>.bot save ai &lt;preset&gt;</code></td><td>Save to a named preset.</td></tr>
        <tr><td><code>.bot load ai &lt;preset&gt;</code></td><td>Load a named preset.</td></tr>
        <tr><td><code>.bot list ai</code></td><td>List saved presets.</td></tr>
        <tr><td><code>.bot reset ai</code></td><td>Reset to default class/spec strategies.</td></tr>
      </tbody>
    </table>
    <p>Strategy syntax: <code>+strategy</code> add, <code>-strategy</code> remove, <code>~strategy</code> toggle, and comma-separate multiple entries.</p>
  </div>

  <div id="tab-vanilla" class="sref-panel<?php echo $activeCommandTab === 'vanilla' ? ' active' : ''; ?>">
    <h3>Vanilla Raiding</h3>
    <p><strong>Credit:</strong> This tab is adapted from Ile's <em>SPP Raiding - Vanilla</em> guide, SPP AI Playerbot Player, Dev and Raid Progression Leader.</p>
    <div class="vanilla-stack">
      <details class="vanilla-card">
        <summary><h4><span>Before You Raid</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>Raiding with bots is much more micro-heavy than raiding with people. Even easier raids can feel hard if your control habits are still rough.</p>
          <ul>
            <li>Expect to spend real attention on positioning, recoveries, and command timing instead of only your own rotation.</li>
            <li>Do not judge your setup by one wipe. Many raid issues are workflow issues, not raw bot power issues.</li>
            <li>If you are still learning, practice in forgiving dungeons before using 40-man raids as your classroom.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>Build A Real Roster</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>Do not PUG with bots. A mishmash roster with random specs, weak gear, and unclear roles makes raid debugging miserable.</p>
          <ul>
            <li>Build a stable core and gear it with intent instead of swapping random bodies in and out.</li>
            <li>Keep role identity clear so you know who your MT, off-tanks, focus healers, and priority DPS are.</li>
            <li>Use guild ranks, notes, MOTD, and guild info as your lightweight roster tracker.</li>
            <li><code>/g @rank=Veteran join</code> is a practical example of using guild metadata to assemble a core group fast.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>RTSC Is The Raid Game</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>SPP raiding is often closer to an RTS than a normal MMO raid. RTSC is the tool that lets you actually play that layer.</p>
          <p><strong>Credit:</strong> RTSC was added by Mostlikely, and it is one of the most important bot-control tools on the page.</p>
          <ul>
            <li><code>rtsc save &lt;name&gt;</code> stores a useful spot for later.</li>
            <li><code>rtsc go &lt;name&gt;</code> sends bots to the saved spot.</li>
            <li><code>rtsc unsave &lt;name&gt;</code> deletes a location you no longer need.</li>
            <li>Saved RTSC locations persist per bot, which is why encounter-specific macros are worth the setup.</li>
            <li><a href="https://www.youtube.com/watch?v=_hdX6ssVDi8" target="_blank" rel="noopener noreferrer">RTSC Control Demo on YouTube</a> is the walkthrough referenced in the guide.</li>
          </ul>
          <p>RTSC becomes especially important on bosses like Baron Geddon, Firemaw, Chromaggus, Twin Emperors, C'Thun, Heigan, Four Horsemen, Sapphiron, and Kel'Thuzad.</p>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>Focus Healing</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>Focus healing is for stabilizing a tank or another priority target, not for turning the entire healing roster into tunnel vision bots.</p>
          <ul>
            <li><code>focus heal +Name</code> assigns a healer-capable bot to focus that player or bot.</li>
            <li><code>focus heal none</code> removes the assignment.</li>
            <li>A good working range is roughly 0 focus healers in 5-mans and around 1-4 in raids, depending on fight size and pressure.</li>
            <li>Too many focus healers can lower raid stability because they stop naturally covering the rest of the group.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>Threat Management</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>Threat is one of the first raid problems that gets amplified with bots, especially early on when tanks are still gearing and the raid is not yet cleanly paced.</p>
          <ul>
            <li><code>co +wait for attack</code> and <code>wait for attack X</code> buy tank time before DPS starts.</li>
            <li><code>co +threat</code> helps DPS avoid climbing over the tank, though it will not save a fully inactive tank.</li>
            <li>Be careful forcing <code>+threat</code> onto healers, because delayed healing after a threat reset can be worse than healer aggro.</li>
          </ul>
          <pre>Main pull delay
/ra @dps wait for attack 10

Tank setup
/ra @tank co +tank,+tank assist,+threat

Threat-aware melee
/ra @melee co +dps,+dps assist,+threat</pre>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>Formation And Range</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>Formation controls whether raid-wide CC, beams, spins, fears, and splash effects become recoverable mistakes or instant wipes.</p>
          <ul>
            <li><code>/ra formation arrow</code> is a fast way to spread bots, but it is clunky for travel and can cause ninja pulls if left on carelessly.</li>
            <li>Try the built-in shapes: <code>near</code>, <code>chaos</code>, <code>circle</code>, <code>arrow</code>, <code>melee</code>, <code>queue</code>, and <code>line</code>.</li>
            <li><code>formation near</code> with <code>range followraid 1</code> is a strong RTSC starting point.</li>
            <li>The three range settings worth learning are <code>range follow</code>, <code>range followraid</code>, and <code>range attack</code>.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>World Buffs And Consumables</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>Class-specific world buffs and consumables are a practical part of raid preparation.</p>
          <ul>
            <li>Configure your packages through <code>AiPlayerbot.WorldBuff</code>.</li>
            <li><code>/ra nc +wbuff</code> becomes the fast raid-wide application once those configs exist.</li>
            <li>Newer playerbot builds also include <code>wbuff travel</code>, so world-buff prep can be handled as a travel workflow instead of only as an instant local apply.</li>
            <li>ZG, MC, and BWL are much more approachable without heavy crutches; AQ40 and Naxx benefit much more from serious preparation.</li>
          </ul>
          <p><a href="/index.php?n=server&sub=wbuffbuilder">Open the World Buff Builder</a> to load class starters and generate copy-ready <code>AiPlayerbot.WorldBuff</code> lines.</p>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>Spell Mechanics And Relative Difficulty</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>This section explains what the guide means by the difficulty tags shown on raid mechanics. The ratings are about how much bot micro and encounter-specific control a mechanic demands, not just how dangerous the spell looks to a human player.</p>
          <p>The spell IDs are useful if you want to experiment with <code>AiPlayerbot.ImmuneSpellIds</code> in <code>aiplayerbot.conf</code>, but the guide's recommendation is to be careful with that. Adding too many immunities can flatten the challenge and remove the payoff from finally solving a hard fight with your own setup.</p>
          <p><strong>Author note:</strong> “I can assure you it will feel amazing after you’ve killed Four Horsemen for the first time with your own handcrafted strategies!”</p>
          <p>Boss difficulty is judged in the gear context of the raid's intended phase. A fight rated <code>[2]</code> in progression gear may feel trivial in full BIS, but the guide rates it for when players would normally be learning it.</p>
          <ul>
            <li><code>[1]</code> = very easy to counter. Little to no microing required.</li>
            <li><code>[2]</code> = moderate microing. Undergeared raids may feel the mechanic more sharply.</li>
            <li><code>[3]</code> = frequent microing. You will spend meaningful attention controlling bots instead of only playing your character.</li>
            <li><code>[4]</code> = very micro intense. Usually needs a proper premade setup and solid knowledge of bot strategies.</li>
            <li><code>[5]</code> = optimal play, heavy preparation, and major micromanagement during the fight.</li>
            <li><code>[NA]</code> = not realistically counterable in practice because of bugs, missing bot logic, or other technical limitations.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>Zul'Gurub</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>Zul'Gurub is a strong early raid classroom for synchronized kills, spreading, and a first taste of bot-specific mechanic handling before the harder 40-man tiers.</p>
          <p><strong>High Priest Thekal [2]</strong></p>
          <ul>
            <li>Phase 1 is the main check: Thekal and both adds need to die within about five seconds of each other or they reset the attempt by resurrecting.</li>
            <li>This is one of the cleaner early fights for learning RTI assignment and synchronized burst windows with bots.</li>
          </ul>
          <p><strong>Hakkar [3]</strong></p>
          <ul>
            <li><code>24322 - Blood Siphon</code>: the goal is to have <code>Poisonous Blood</code> available on the raid to blunt the siphon cycle.</li>
            <li><code>24321 - Poisonous Blood</code>: control Sons of Hakkar around the room so you can use them when needed instead of letting the fight get messy.</li>
            <li><code>24328 - Corrupted Blood</code>: this is a spacing check first and a healing check second.</li>
            <li><code>5246 - Intimidating Shout</code>: the guide's practical answer is brute-force warrior control, for example <code>/ra @warrior cast Intimidating Shout</code>.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>AQ20</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>AQ20 sits in a useful middle spot: still approachable, but already teaching tank swaps, movement routing, and targeted strategy swaps that matter later in AQ40 and Naxx.</p>
          <p><strong>Kurinnaxx [2]</strong></p>
          <ul>
            <li><code>25646 - Mortal Wound</code>: this is a straightforward early tank-swap lesson.</li>
          </ul>
          <p><strong>General Rajaxx [2]</strong></p>
          <ul>
            <li>The pre-pull wave set is usually manageable if the tank is geared and the raid is stable.</li>
            <li><code>25599 - Thundercrash</code>: be ready for the aggro reset with quick taunts and strong heal recovery.</li>
          </ul>
          <p><strong>Moam [2]</strong></p>
          <ul>
            <li><code>26639 - Drain Mana</code>: hunters and warlocks can help by draining mana back.</li>
            <li><code>28450 - Arcane Explosion</code>: if Moam caps mana, the whole raid pays for it fast.</li>
          </ul>
          <p><strong>Buru the Gorger [2]</strong></p>
          <ul>
            <li>The encounter is mainly about movement routing: kite Buru into eggs and use <code>do follow</code> style control to keep bots moving with you cleanly.</li>
          </ul>
          <p><strong>Ayamiss [1]</strong></p>
          <ul>
            <li>Keep melee on the ground mobs with RTI support while ranged burn the boss. It is one of the simpler fights in the guide.</li>
          </ul>
          <p><strong>Ossirian [3]</strong></p>
          <ul>
            <li><code>25176 - Strength of Ossirian</code>: the crystal game is the whole fight. Move, click the right crystal, then adapt caster strategies to the current weakness.</li>
            <li>The guide specifically calls out mage school swapping here as a good place to use class-specific command control.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>Onyxia's Lair</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>Onyxia is one of the earliest real raid checks in the guide. The fight is rated for progression gear, not full BIS, so it is treated as an early positioning and breath-control lesson instead of a farm boss.</p>
          <p><strong>Onyxia [2]</strong></p>
          <ul>
            <li><code>23364 - Tail Lash</code>: dragons punish sloppy rear positioning immediately, so keep the raid disciplined around her body.</li>
            <li><code>17086 - Breath</code>: this is the real danger. A bad angle can sweep the raid and end the pull instantly, so adaptive positioning matters more than raw throughput.</li>
            <li>The guide treats Onyxia as one of the first fights where safe-spot awareness and responsive movement start mattering for bot raids, even though the encounter is not especially hard for geared human groups.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>Molten Core</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>Molten Core is still the easiest 40-man raid in the guide, but it is where most players learn whether their roster and movement habits are actually raid-ready.</p>
          <p><strong>Magmadar [3]</strong></p>
          <ul>
            <li><code>19428 - Lava Bomb</code>: spread ranged and make sure melee are not sitting in fire.</li>
            <li><code>19408 - Panic</code>: Fear Ward or Tremor Totem smooths the fight out immediately.</li>
            <li><code>19451 - Frenzy</code>: Tranquilizing Shot is the clean answer.</li>
          </ul>
          <p><strong>Shazzrah [1]</strong></p>
          <ul>
            <li><code>19712 - Arcane Explosion</code>: keep everyone except the main tank out of the radius.</li>
            <li><code>28391 - Blink</code>: basic aggro reset, so expect quick tank recovery.</li>
          </ul>
          <p><strong>Baron Geddon [3]</strong></p>
          <ul>
            <li><code>20475 - Living Bomb</code>: the classic first RTSC check in MC. One bad bomb in the pack can wipe the raid instantly.</li>
            <li><code>19695 - Inferno</code>: either use RTSC or a disciplined <code>/ra do follow</code> reset.</li>
          </ul>
          <p><strong>Golemagg [2]</strong></p>
          <ul>
            <li>Use three RTIs and three tanks: one for Golemagg and one for each Core Rager.</li>
            <li><code>13880 - Magma Splash</code>: stacking fire damage and armor reduction. Tank swaps are optional but useful if your MT is under pressure.</li>
          </ul>
          <p><strong>Majordomo Executus [3]</strong></p>
          <ul>
            <li>This is more of a raid-assignment check than a single-boss mechanics check.</li>
            <li>Give warriors different RTIs, CC the healers, kill the healers first, and burn the elites last.</li>
          </ul>
          <p><strong>Ragnaros [3]</strong></p>
          <ul>
            <li><code>20566 - Wrath of Ragnaros</code>: huge knockback in melee. Position for it and be ready for tank recovery.</li>
            <li><code>21158 - Lava Burst</code>: raid-wide spacing matters before the pull, not after the first burst lands.</li>
            <li><strong>Submerge [2]</strong>: Sons of Flame are an off-tank pickup problem. Pull them away from casters because of their mana burn.</li>
            <li>This is one of the earliest fights where a pre-pull RTSC layout really starts paying for itself.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>Blackwing Lair</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p><strong>Vaelastrasz [2]</strong></p>
          <ul>
            <li><code>18173 / 23620 - Burning Adrenaline</code>: treat it like a bigger Baron Geddon bomb and move people decisively.</li>
          </ul>
          <p><strong>Broodlord Lashlayer [2]</strong></p>
          <ul>
            <li><code>24573 - Mortal Strike</code>: this is mostly a tank-and-healer check.</li>
            <li><code>18670 - Knock Away</code>: easier if your tank is already positioned correctly.</li>
          </ul>
          <p><strong>Firemaw [3]</strong>, <strong>Ebonroc [2]</strong>, <strong>Flamegor [2]</strong></p>
          <ul>
            <li><code>23339 - Wing Buffet</code>: all three drakes punish weak positioning and threat recovery.</li>
            <li><strong>Firemaw:</strong> <code>9574, 16536, 9658, 10452, 16168, 22433, 22713, 25651, 25668, 23341 - Flame Buffet</code> is a stacking DoT that falls off with proper LOS positioning. This is one of the clearest RTSC line-of-sight checks in the guide.</li>
            <li><strong>Ebonroc:</strong> <code>Shadow of Ebonroc</code> can be played around by stopping attacks while it is up.</li>
            <li><strong>Flamegor:</strong> <code>Frenzy</code> means Tranquilizing Shot still matters here too.</li>
          </ul>
          <p><strong>Chromaggus [3]</strong></p>
          <ul>
            <li><code>23310 - Time Lapse</code> is the hardest breath. DPS and healers need to be out of LOS when breaths go out.</li>
            <li><code>23316 - Ignite Flesh</code>, <code>23187 - Frost Burn</code>, and <code>23313 - Corrosive Acid</code> are easier individually but still punish sloppy breath handling.</li>
            <li><code>23170 - Brood Affliction: Bronze</code> means Hourglass Sand support matters.</li>
            <li>The guide's layout is basically three RTSC zones: a ranged spot, a LOS-and-cleanse spot, and a melee-and-main-tank spot.</li>
          </ul>
          <p><strong>Nefarian [4]</strong></p>
          <ul>
            <li><code>22539 - Shadow Flame</code>: Onyxia Scale Cloaks and facing discipline remain mandatory.</li>
            <li><code>22686 - Bellowing Roar</code>: Berserker Rage, Fear Ward, and Tremor Totem smooth the fight out.</li>
            <li><strong>Class calls:</strong> some are mild, some are brutal. Warrior call is the most dangerous because it hurts both tank durability and threat.</li>
            <li><strong>Bone Constructs [2]:</strong> when Nefarian drops under 20%, the room turns into an AOE cleanup check.</li>
            <li><strong>Suppression Room:</strong> rogue disarm support may still be unreliable, so treat it as a known caveat.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>AQ40 Trash</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>The guide calls out AQ40 trash as a real progression wall, especially later in the instance. A lot of these packs are more dangerous than the bosses around them if the pull plan and bot control are sloppy.</p>
          <p><strong>Note from author:</strong> Welcome to the endgame.</p>
          <p><strong>Anubisath Sentinel [3]</strong></p>
          <ul>
            <li>Use a mage with <code>cast Detect Magic</code> to reveal the random ability package before you commit the pull.</li>
            <li><code>13022 - Fire and Arcane Reflect</code>: swap mages away from fire and arcane damage.</li>
            <li><code>19595 - Shadow and Frost Reflect</code>: swap mages toward fire and keep warlocks and shadow priests from killing themselves into the reflect.</li>
            <li><code>24573 - Mortal Strike</code>: manageable with focus healing.</li>
            <li><code>26046 - Mana Burn</code>: drag the mob away from casters.</li>
            <li><code>26546 - Shadow Storm</code>: stack the raid near the target instead of leaving ranged spread out.</li>
          </ul>
          <p><strong>Obsidian Eradicator [3]</strong>, <strong>Obsidian Brainwasher [2]</strong></p>
          <ul>
            <li><strong>Eradicator:</strong> <code>26639 - Drain Mana</code> into <code>26458 - Shock Blast</code> is a mana-control problem first and a DPS problem second.</li>
            <li><strong>Brainwasher:</strong> <code>26079 - Cause Insanity</code> means you need fast CC on MC targets, and its mana-burn pressure rewards quick focus fire.</li>
          </ul>
          <p><strong>Vekniss Guardian [4]</strong>, <strong>Vekniss Warrior [1]</strong></p>
          <ul>
            <li><strong>Guardian:</strong> <code>26025 - Impale</code> is a huge positioning and kill-priority problem.</li>
            <li><strong>Warrior:</strong> the death borers are mostly an AOE cleanup tax.</li>
          </ul>
          <p><strong>Bug Tunnel [2]</strong></p>
          <ul>
            <li>The guide recommends strong AOE and even forcing mages into <code>co -ranged</code> so they stay planted and pump damage.</li>
            <li>Have a <code>do follow</code> reset ready in case bots fall behind while you move the pack route forward.</li>
          </ul>
          <p><strong>Vekniss Hive Crawler [1]</strong>, <strong>Vekniss Wasp [1]</strong>, <strong>Vekniss Stinger [2]</strong></p>
          <ul>
            <li><strong>Hive Crawler:</strong> poison bolts and sunder armor are manageable if cleanse and healing are awake.</li>
            <li><strong>Wasp/Stinger:</strong> the catalyst plus charge combo is the real danger, so burn stingers fast.</li>
          </ul>
          <p><strong>Qiraji Lasher [2]</strong></p>
          <ul>
            <li><code>26038 - Whirlwind</code> and <code>26027 - Knockback</code> are both trash-pull killers if the raid is positioned near other packs.</li>
          </ul>
          <p><strong>Anubisath Defender [3]</strong></p>
          <ul>
            <li><strong>Tip:</strong> Whisper a mage <code>cast Detect Magic</code> while targeting Anubisath Defender to reveal its randomly chosen abilities.</li>
            <li>This is another detect-magic pack where reflect handling matters just as much as raw tanking.</li>
            <li><code>26558 - Meteor</code>: stack the raid so the damage splits properly.</li>
            <li><code>26556 - Plague</code>: if a bot gets it, this can turn into heavy RTSC micro fast.</li>
            <li><code>25698 - Explode</code>: either finish the mob instantly or force the raid out before the cast ends.</li>
          </ul>
          <p><strong>Qiraji Champion Packs [NA]</strong></p>
          <ul>
            <li><strong>Qiraji Champion:</strong> fear and knock-away make walling and spacing important.</li>
            <li><strong>Qiraji Slayer [4]:</strong> whirlwind plus long silence can wipe a raid in seconds if it is not burned immediately.</li>
            <li><strong>Qiraji Mindslayer [NA]:</strong> the guide specifically calls out its bugged mana-burn behavior as one of the nastier technical limitations in the instance.</li>
          </ul>
          <p><strong>Warder And Nullifier Packs [3]</strong></p>
          <ul>
            <li><strong>Anubisath Warder:</strong> keep it away from the raid, especially if <code>Fire Nova</code> overlaps with other pressure.</li>
            <li><strong>Obsidian Nullifier:</strong> <code>26639 - Drain Mana</code> into <code>26552 - Nullify</code> can create instant wipe conditions if the raid is clumped and unprepared.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>AQ40</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p><strong>The Prophet Skeram [3]</strong></p>
          <ul>
            <li><code>28401 - Blink</code> at 75%, 50%, and 25% creates a tank and melee RTSC problem, not just a threat problem.</li>
            <li><code>785 - True Fulfillment</code> means you need an answer for MC targets.</li>
          </ul>
          <p><strong>The Bug Trio [3]</strong></p>
          <ul>
            <li><strong>Yauj:</strong> <code>26580 - Fear</code> is an AOE fear plus aggro reset. Counter it with warrior taunts and proper RTI management.</li>
            <li><strong>Yauj:</strong> <code>25807 - Great Heal</code> rewards creating space between the bugs with RTSC.</li>
            <li><strong>Yauj:</strong> <code>25808 - Dispel</code> is another reason to keep the bosses spaced instead of stacked in LOS.</li>
            <li><strong>Vem:</strong> <code>26561 - Berserker Charge</code> punishes bad RTSC LOS and can flatten anyone caught in the path.</li>
            <li><strong>Vem:</strong> <code>18670 - Knock Away</code> is much easier if the tank is set against a wall.</li>
            <li><strong>Lord Kri:</strong> <code>25812 - Toxic Volley</code> is the nature-resistance pressure point and a big reason to kill Kri first.</li>
            <li><strong>Lord Kri:</strong> <code>26590 - Summon Poison Cloud</code> is easy only if Kri dies in a safe spot.</li>
          </ul>
          <p><strong>Battleguard Sartura [4]</strong></p>
          <ul>
            <li><code>26038 - Whirlwind</code> turns the whole fight into RTSC choreography.</li>
            <li>The guide's shape is explicit: melee in the center, ranged on the inner rim, healers on the outer rim.</li>
          </ul>
          <p><strong>Fankriss [2]</strong>, <strong>Viscidus [4]</strong>, <strong>Princess Huhuran [4]</strong></p>
          <ul>
            <li><strong>Fankriss:</strong> <code>25646 - Mortal Wound</code> is a normal tank-swap check.</li>
            <li><strong>Fankriss:</strong> <code>518 - Summon Worm</code> means Spawn of Fankriss needs immediate RTI focus.</li>
            <li><strong>Viscidus:</strong> <code>25991 - Poison Bolt Volley</code> is constant raid poison pressure and pushes cleanse classes hard.</li>
            <li><strong>Viscidus:</strong> <code>25989 - Toxin</code> is manageable if people are not parked in bad ground.</li>
            <li><strong>Huhuran:</strong> <code>26052 - Poison Bolt</code> on the nearest 15 targets is the big nature-resistance and healer-depth gate.</li>
            <li><strong>Huhuran:</strong> <code>26051 - Frenzy</code> still needs Tranquilizing Shot.</li>
            <li><strong>Huhuran:</strong> <code>26050 - Acid Spit</code> means tank swaps matter.</li>
            <li><strong>Huhuran:</strong> <code>26180 - Wyvern Sting</code> is usually manageable, but it can still cause deaths if cleanses and healing get awkward.</li>
            <li><strong>Huhuran:</strong> <code>26053 - Noxious Poison</code> punishes bad spread and bad silence positioning.</li>
            <li><strong>Huhuran:</strong> <code>26068 - Berserk</code> at 30% is the cue for cooldowns and a fast finish.</li>
          </ul>
          <p><strong>Twin Emperors [5]</strong></p>
          <ul>
            <li><code>7393 - Heal Brother</code>: if the twins get too close, the attempt is over almost instantly.</li>
            <li><code>800 - Twin Teleport</code>: the guide treats this as a full pre-planned macro encounter with warrior tanks, warlock tanks, and bug-control assignments.</li>
            <li><strong>Vek'lor:</strong> <code>26006 - Shadow Bolt</code> is why the guide wants two shadow-resistant warlock tanks.</li>
            <li><strong>Vek'lor:</strong> <code>568 - Arcane Burst</code> can be used to help warrior tanks advance if they survive the opening pressure.</li>
            <li><strong>Vek'lor:</strong> <code>26607 - Blizzard</code> is manageable if positioning stays disciplined.</li>
            <li><strong>Vek'lor:</strong> <code>804 - Explode Bug</code> is a huge AOE punishment if the bug control falls apart.</li>
            <li><strong>Vek'nilash:</strong> <code>26613 - Unbalancing Strike</code> is a tank-defense problem supported by focus heals, Demo Shout, and Disarm.</li>
            <li><strong>Vek'nilash:</strong> <code>26007 - Uppercut</code> is mostly about not getting launched into Blizzard or bug explosions.</li>
            <li><strong>Vek'nilash:</strong> <code>802 - Mutate Bug</code> is why the guide recommends a dedicated bug-hunter group with specific RTIs.</li>
          </ul>
          <p><strong>Ouro [3]</strong></p>
          <ul>
            <li><code>26102 - Sand Blast</code> and underground <code>Quake</code> turn the fight into dynamic RTSC movement.</li>
            <li>At 20%, berserk overlaps with the normal control problem, so the fight accelerates hard.</li>
          </ul>
          <p><strong>C'Thun [5]</strong></p>
          <ul>
            <li><code>26134 - Eye Beam</code>: pre-planned spread is mandatory or the room chain-detonates.</li>
            <li><code>26029 - Dark Glare</code>: surprisingly manageable with a few prepared RTSC macros, but only if you are watching for it.</li>
            <li><strong>Phase 2 stomach:</strong> this is one of the most micro-heavy jobs in the whole guide. Kill flesh tentacles fast or the room gets overrun.</li>
            <li><strong>Giant Eye Tentacle [4]:</strong> treat it like phase 1 all over again and burn it immediately.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>Naxxramas</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p><strong>Naxxramas - Trash</strong></p>
          <ul>
            <li>TBD</li>
          </ul>
          <p><strong>Spider Wing</strong></p>
          <p><strong>Anub'rekhan [3]</strong></p>
          <ul>
            <li><code>28783 - Impale</code>: random target launch plus fall damage. Stable positioning and decent healing usually cover it.</li>
            <li><code>28785 - Locust Swarm</code>: the real fight. Kite around the room edge with RTSC, speed tools, and clean pathing.</li>
            <li>Each Locust cycle also spawns a Crypt Guard, so the movement plan has to account for pickup and space.</li>
          </ul>
          <p><strong>Faerlina [1]</strong></p>
          <ul>
            <li><code>28794 - Rain of Fire</code>: basic AOE placement check.</li>
            <li><code>19953 - Enrage</code>: after one minute she ramps up, but this is still one of the easier encounters in the wing.</li>
          </ul>
          <p><strong>Maexxna [2]</strong></p>
          <ul>
            <li><code>28776 - Necrotic Poison</code>: easy if cleanse classes are awake.</li>
            <li><code>29484 - Web Spray</code>: the whole raid is stunned for 8 seconds, so tank durability matters more than clever micro here.</li>
            <li><code>28747 - Enrage</code> at 30% is the burn window. If it overlaps badly with Web Spray, the tank can disappear fast.</li>
          </ul>
          <p><strong>Plague Wing</strong></p>
          <p><strong>Noth the Plaguebringer [1]</strong></p>
          <ul>
            <li><code>29211 - Blink</code>: straightforward threat reset as long as a tank is ready.</li>
            <li><code>29213 - Curse of the Plaguebringer</code>: decurse quickly and avoid letting it spread.</li>
            <li>Balcony teleports are more about keeping the add phases organized than about a single dangerous cast.</li>
          </ul>
          <p><strong>Heigan the Unclean [4]</strong></p>
          <ul>
            <li>The gauntlet before the boss is easier than the BWL suppression run, but it still sets the tone.</li>
            <li><code>14033 - Mana Burn</code>: manageable with normal positioning.</li>
            <li><code>29371 - Eruption</code>: this is the real fight. The guide treats it as a pure RTSC timing and execution check.</li>
          </ul>
          <p><strong>Loatheb [3]</strong></p>
          <ul>
            <li><code>29185, 29201, 29196, 29198 - Corrupted Mind</code>: healer rhythm is completely different here, so pre-pull setup matters more than normal instincts.</li>
            <li><code>29865 - Poison Aura</code>: Greater Nature Protection Potions help smooth it out.</li>
            <li><code>29204 - Inevitable Doom</code>: once the Doom cadence speeds up, the fight becomes a hard race.</li>
          </ul>
          <p><strong>Abomination Wing</strong></p>
          <p><strong>Patchwerk [2]</strong></p>
          <ul>
            <li><code>28308 - Hateful Strike</code>: mostly about proper tanks and focus healing. The guide notes CMaNGOS behavior is not perfectly blizzlike here because Hateful can still hit the MT.</li>
            <li><code>19953 - Enrage</code> at 5% is the signal to finish with cooldowns.</li>
          </ul>
          <p><strong>Grobbulus [3]</strong></p>
          <ul>
            <li><code>28240 - Poison Cloud</code>: kite the boss around the room and respect the route.</li>
            <li><code>28169 - Mutating Injection</code>: dynamic RTSC problem. Where the target runs determines whether the room stays playable.</li>
            <li><code>28157 - Slime Spray</code>: keep Grobbulus facing only the main tank.</li>
            <li><code>28137 - Slime Stream</code>: do not let the kite get too wide or too fast.</li>
          </ul>
          <p><strong>Gluth [NA]</strong></p>
          <ul>
            <li><strong>Note from author:</strong> Gluth is currently broken. <code>Decimate</code> does not decrease Zombie Chow HP, which makes the intended add cycle practically impossible.</li>
            <li><code>29685 - Terrifying Roar</code>: fear handling is standard if your wards and totems are ready.</li>
            <li><code>25646 - Mortal Wound</code>: another tank-swap check.</li>
            <li><code>28404 - Zombie Chow Search</code>: if the chow reaches Gluth, the boss heals.</li>
            <li><code>28375 - Decimate</code>: this is the bugged mechanic the guide flags as the reason the fight is effectively NA.</li>
          </ul>
          <p><strong>Thaddius [4]</strong></p>
          <ul>
            <li><strong>Note from author:</strong> Stalagg and Feugen can despawn permanently after a wipe, which breaks phase 1 and may require GM activation for Thaddius.</li>
            <li><strong>Stalagg and Feugen:</strong> kill timing and side assignments matter. The guide recommends four tanks total, with melee on Feugen's side and ranged on Stalagg's.</li>
            <li><code>28089 - Polarity Shift</code>: one of the heaviest micro checks in the raid. Mixed charges standing together can erase the raid instantly.</li>
          </ul>
          <p><strong>Death Knight Wing</strong></p>
          <p><strong>Instructor Razuvious [2]</strong></p>
          <ul>
            <li><code>26613 - Unbalancing Strike</code>: either mind-control understudies or treat spare warriors as meat shields.</li>
            <li><code>29107 - Disrupting Shout</code>: keep mana users safe with spacing and LOS discipline.</li>
          </ul>
          <p><strong>Gothik the Harvester [4]</strong></p>
          <ul>
            <li>The whole fight is about balancing your split between live and undead sides for 4 minutes and 30 seconds.</li>
            <li><code>15245 - Shadow Bolt Volley</code> from Unrelenting Riders is the scariest add cast and should be interrupted or the mob killed immediately.</li>
          </ul>
          <p><strong>Four Horsemen [5]</strong></p>
          <ul>
            <li><strong>Note from author:</strong> the guide is blunt here. For many players this is the wall, and it may not be worth brute-forcing without a highly refined setup.</li>
            <li><code>28834, 28832, 28833, 28835 - Marks</code>: the core mechanic. The room has to be split into corners and rotated cleanly.</li>
            <li><code>28884 - Meteor</code>: simple if Thane's group is stacked correctly.</li>
            <li><code>28882 - Righteous Fire</code>: Mograine tanks need to be sturdy.</li>
            <li><code>10320 - Holy Wrath</code>: Zeliek punishes sloppy spread the same way C'Thun punishes bad beam spacing.</li>
            <li><code>28863 - Void Zone</code>: the guide calls this one of the least forgiving interactions in the whole encounter because the movement correction can break the rest of the setup.</li>
          </ul>
          <p><strong>Frostwyrm Lair</strong></p>
          <p><strong>Sapphiron [4]</strong></p>
          <ul>
            <li><code>28531 - Frost Aura</code>: constant raid damage that makes frost resistance, healer depth, and potion use matter.</li>
            <li><code>28542 - Life Drain</code>: decurse quickly; Shadow Protection can help.</li>
            <li><code>28561 - Summon Blizzard</code>: dynamic positioning issue, though the guide notes brute resistance prep can matter more than elegant movement here.</li>
            <li><code>31800 - Icebolt</code>: spread correctly in the air phase so splash damage does not ruin the safe blocks.</li>
            <li><code>28524 - Frost Breath</code>: the defining wipe mechanic. Everyone has to collapse behind an ice block in time.</li>
          </ul>
          <p><strong>Kel'Thuzad [4]</strong></p>
          <ul>
            <li><strong>Note from author:</strong> phase 1 add volume can cause serious lag with a full bot raid.</li>
            <li><strong>Phase 1:</strong> melee should clear Unstoppable Abominations while ranged handle Soldiers of the Frozen Wastes and Soul Weavers.</li>
            <li><strong>Phase 2:</strong> <code>28478 - Frostbolt</code> should be interrupted, <code>28479 - Frostbolt Volley</code> is potion and resistance pressure, <code>28410 - Chains of Kel'Thuzad</code> needs CC, <code>27810 - Shadow Fissure</code> needs fast RTSC movement, <code>27808 - Frost Blast</code> demands fast healing, and <code>27819 - Detonate Mana</code> rewards proper ranged spread.</li>
            <li><strong>Phase 3:</strong> the phase 2 problems stay active while five Guardians of Icecrown spawn and need off-tank pickup, with up to three shackled by priests.</li>
          </ul>
        </div>
      </details>

      <details class="vanilla-card">
        <summary><h4><span>Known Vanilla Caveats</span><span class="vanilla-toggle">+</span></h4></summary>
        <div class="vanilla-body">
          <p>These notes matter because they tell you when a wipe source may be a core limitation rather than a player mistake.</p>
          <ul>
            <li><strong>Suppression Devices in BWL:</strong> rogue disarm support may not be reliable.</li>
            <li><strong>Gluth:</strong> <code>Decimate</code> may fail to reduce Zombie Chow health, breaking the intended add cycle.</li>
            <li><strong>Thaddius:</strong> Stalagg and Feugen can despawn after a wipe, and activation may need GM help depending on core state.</li>
            <li><strong>Kel'Thuzad phase 1:</strong> a full bot raid can cause major performance pressure from sheer add count.</li>
          </ul>
        </div>
      </details>

    </div>
  </div>

  <div id="tab-builder" class="sref-panel<?php echo $activeCommandTab === 'builder' ? ' active' : ''; ?>">
    <h3>Custom Strategies</h3>
    <p><code>custom::&lt;name&gt;</code> is a database-driven trigger to action pipeline you define yourself. Each line maps one trigger to one or more actions, and the first matching line fires.</p>
    <p><strong>Syntax:</strong> <code>trigger&gt;action1!priority,action2!priority</code>. Use <code>say::text_name</code> to speak a DB text name and <code>emote::emote_name</code> to perform an emote.</p>
    <p><strong>In-game editing:</strong> whisper the bot <code>cs &lt;name&gt; &lt;idx&gt; &lt;action_line&gt;</code> to set a line, <code>cs &lt;name&gt; &lt;idx&gt;</code> to delete, and <code>cs &lt;name&gt; ?</code> to list.</p>
    <hr class="csb-sep">

    <div class="csb-section">
      <div class="csb-row">
        <span class="csb-label">Name</span>
        <input id="csb-name" class="csb-input" type="text" placeholder="e.g. pvpcall" value="mysay" oninput="csbUpdateOutput()">
        <span style="color:#666;font-size:12px;">Activated as <code id="csb-activation-preview">+custom::mysay</code></span>
      </div>
      <div class="csb-row">
        <span class="csb-label">Scope</span>
        <div class="csb-scope-row">
          <label style="font-size:13px;color:#bbb;cursor:pointer;">
            <input class="csb-radio" type="radio" name="csb-owner" value="0" checked onchange="csbToggleGuid()"> Global (all bots)
          </label>
          <label style="font-size:13px;color:#bbb;cursor:pointer;">
            <input class="csb-radio" type="radio" name="csb-owner" value="guid" onchange="csbToggleGuid()"> Specific bot (GUID)
          </label>
          <input id="csb-guid" class="csb-input csb-priority" type="text" placeholder="GUID" style="display:none;" oninput="csbUpdateOutput()">
        </div>
      </div>
    </div>

    <div id="csb-lines"></div>
    <button class="csb-btn csb-btn-add" onclick="csbAddLine()" style="margin-bottom:16px;">+ Add Line</button>

    <hr class="csb-sep">
    <h3>Output</h3>
    <div class="csb-output-label">Activation string</div>
    <div class="csb-copy-row">
      <div class="csb-output" id="csb-out-activation" style="flex:1;min-height:unset;padding:6px 10px;"></div>
      <button class="csb-btn csb-btn-copy" onclick="csbCopy('csb-out-activation')">Copy</button>
    </div>
    <div class="csb-output-label">SQL INSERT (paste into MariaDB)</div>
    <div class="csb-copy-row">
      <div class="csb-output" id="csb-out-sql" style="flex:1;"></div>
      <button class="csb-btn csb-btn-copy" onclick="csbCopy('csb-out-sql')">Copy</button>
    </div>
    <div class="csb-output-label">In-game cs commands (whisper bot)</div>
    <div class="csb-copy-row">
      <div class="csb-output" id="csb-out-cs" style="flex:1;"></div>
      <button class="csb-btn csb-btn-copy" onclick="csbCopy('csb-out-cs')">Copy</button>
    </div>
  </div>

  <div id="tab-macros" class="sref-panel<?php echo $activeCommandTab === 'macros' ? ' active' : ''; ?>">
      <h3>Macro Builder</h3>
      <p>Build ready-to-use bot control macros. Add any filters you want, choose how the command should be sent, pick a preset, and copy the finished macro straight into the game.</p>

      <div class="mb-help-grid">
        <div class="mb-help-card">
          <h4>Macro Shape</h4>
          <p><code>/w BotName @tank @60 follow</code> or <code>/ra @warrior co +dps,+threat</code></p>
        </div>
        <div class="mb-help-card">
          <h4>Multiple Filters</h4>
          <p>Stack filters in order to target exactly the bots you want before the command executes.</p>
        </div>
        <div class="mb-help-card">
        <h4>Preset Focus</h4>
        <p>Use presets for common movement, utility, and strategy commands, or switch to the custom preset for raw command text.</p>
      </div>
    </div>

      <div class="mb-card">
        <h3 style="margin-top:0;">Send</h3>
        <div class="mb-row">
          <span class="csb-label">Send</span>
          <div class="mb-row-fields">
            <select id="mb-delivery" class="csb-select" onchange="mbUpdateDeliveryMode()">
              <option value="whisper">/w whisper</option>
              <option value="party">/p party</option>
              <option value="raid">/ra raid</option>
              <option value="guild">/g guild</option>
              <option value="say">/s say</option>
            </select>
          </div>
        </div>
        <div class="mb-row" id="mb-target-row">
          <span class="csb-label">Bot</span>
          <div class="mb-row-fields">
            <input id="mb-target" class="csb-input" type="text" placeholder="BotName" oninput="mbUpdateOutput()">
          </div>
        </div>
      </div>

      <div class="mb-card">
          <h3 style="margin-top:0;">Filters</h3>
          <p>Add filters in the order you want them to appear before the command text.</p>
          <div class="mb-quick-groups">
            <div class="mb-quick-group">
              <span class="mb-quick-label">Roles</span>
            <div class="mb-quick-buttons">
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@tank')">tank</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@dps')">dps</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@heal')">heal</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@melee')">melee</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@ranged')">ranged</button>
            </div>
          </div>
          <div class="mb-quick-group">
            <span class="mb-quick-label">Class</span>
            <div class="mb-quick-buttons">
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@warrior')">warrior</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@paladin')">paladin</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@hunter')">hunter</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@rogue')">rogue</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@priest')">priest</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@shaman')">shaman</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@mage')">mage</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@warlock')">warlock</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@druid')">druid</button>
              <button type="button" class="mb-quick-btn" onclick="mbAddQuickFilter('@deathknight')">death knight</button>
            </div>
          </div>
        </div>
          <div id="mb-filters" class="mb-filter-list"></div>
          <button class="csb-btn csb-btn-add" onclick="mbAddFilter()">+ Add Filter</button>
      </div>

      <div class="mb-card">
        <h3 style="margin-top:0;">Builder</h3>
        <div id="mb-layers"></div>
        <button type="button" class="csb-btn csb-btn-add" onclick="mbAddLayer()">+ Add Layer</button>
        <div class="mb-status" id="mb-status">Choose how to send the macro, add any filters you want, and pick a command preset.</div>
      </div>

      <div class="mb-card">
        <h3 style="margin-top:0;">Output</h3>
        <div class="csb-output-label">Final macro</div>
      <div class="csb-copy-row">
        <div class="csb-output" id="mb-out-macro" style="flex:1;"></div>
        <button class="csb-btn csb-btn-copy" onclick="csbCopy('mb-out-macro')">Copy</button>
      </div>
      <div class="csb-output-label">Command preview</div>
      <div class="csb-copy-row">
        <div class="csb-output" id="mb-out-command" style="flex:1;"></div>
        <button class="csb-btn csb-btn-copy" onclick="csbCopy('mb-out-command')">Copy</button>
      </div>
    </div>
  </div>
</div>

<datalist id="csb-say-list">
  <option value="critical health"><option value="low health"><option value="low mana">
  <option value="aoe"><option value="taunt"><option value="attacking"><option value="fleeing">
  <option value="fleeing_far"><option value="following"><option value="staying"><option value="guarding">
  <option value="grinding"><option value="loot"><option value="hello"><option value="goodbye">
  <option value="join_group"><option value="join_raid"><option value="no ammo"><option value="low ammo">
  <option value="reply"><option value="suggest_trade"><option value="suggest_something">
  <option value="broadcast_levelup_generic"><option value="broadcast_killed_player">
  <option value="broadcast_killed_elite"><option value="broadcast_killed_worldboss">
  <option value="broadcast_quest_turned_in"><option value="broadcast_looting_item_epic">
  <option value="broadcast_looting_item_legendary"><option value="broadcast_looting_item_rare">
  <option value="quest_accept"><option value="quest_remove"><option value="quest_status_completed">
  <option value="quest_error_bag_full"><option value="use_command"><option value="equip_command">
  <option value="error_far"><option value="wait_travel_close"><option value="wait_travel_far">
</datalist>

<datalist id="csb-emote-list">
  <option value="helpme"><option value="healme"><option value="flee"><option value="charge">
  <option value="danger"><option value="oom"><option value="openfire"><option value="wait">
  <option value="follow"><option value="train"><option value="joke"><option value="silly">
  <option value="hug"><option value="kneel"><option value="kiss"><option value="point">
  <option value="roar"><option value="rude"><option value="chicken"><option value="flirt">
  <option value="introduce"><option value="anecdote"><option value="dance"><option value="bow">
  <option value="cheer"><option value="cry"><option value="laugh"><option value="wave">
  <option value="salute"><option value="flex"><option value="no"><option value="yes">
  <option value="beg"><option value="applaud"><option value="sleep"><option value="shy"><option value="talk">
</datalist>

  <script src="/templates/offlike/js/commands.js"></script>
  <script>
  const CHAT_FILTER_OPTIONS = <?php echo json_encode($macroFilterOptions); ?>;
  const LAYER_FILTER_OPTIONS = <?php echo json_encode($macroLayerFilterOptions); ?>;
  const MACRO_PRESETS = <?php echo json_encode($macroPresets); ?>;

function srefTab(btn, panelId) {
    document.querySelectorAll('.sref-tab-btn').forEach(function (b) { b.classList.remove('active'); });
    document.querySelectorAll('.sref-panel').forEach(function (p) { p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById(panelId).classList.add('active');
    if (panelId === 'tab-builder') csbRender();
    if (panelId === 'tab-macros') mbRender();
}

function filterChatFilterCards() {
    const query = (((document.getElementById('chatFilterSearch') || {}).value) || '').toLowerCase().trim();
    document.querySelectorAll('#chatFilterCards .cff-card').forEach(function (card) {
        const haystack = card.getAttribute('data-filter-search') || '';
        card.style.display = !query || haystack.indexOf(query) !== -1 ? '' : 'none';
    });
}

function syncChatFilterCardIndicators() {
    document.querySelectorAll('#chatFilterCards .cff-card').forEach(function (card) {
        const indicator = card.querySelector('.cff-toggle-indicator');
        if (!indicator) return;
        indicator.textContent = card.hasAttribute('open') ? '-' : '+';
    });
}

function syncVanillaCardIndicators() {
    document.querySelectorAll('#tab-vanilla .vanilla-card').forEach(function (card) {
        const indicator = card.querySelector('.vanilla-toggle');
        if (!indicator) return;
        indicator.textContent = card.hasAttribute('open') ? '-' : '+';
    });
}

function filterCardGrid(inputId, selector) {
    const query = (((document.getElementById(inputId) || {}).value) || '').toLowerCase().trim();
    document.querySelectorAll(selector).forEach(function (card) {
        const haystack = card.getAttribute('data-filter-search') || '';
        card.style.display = !query || haystack.indexOf(query) !== -1 ? '' : 'none';
    });
}

function toggleCommandCard(button) {
    const card = button.closest('.cmd-card');
    if (!card) return;
    const help = card.querySelector('.cmd-help');
    if (!help) return;
    const isCollapsed = help.classList.contains('is-collapsed');
    help.classList.toggle('is-collapsed', !isCollapsed);
    button.textContent = isCollapsed ? '-' : '+';
    button.setAttribute('aria-label', isCollapsed ? 'Collapse command details' : 'Expand command details');
}

function toggleSelectValue(selectId, value) {
    const select = document.getElementById(selectId);
    if (!select) return;
    select.value = select.value === value ? 'all' : value;
}

function applyBotChipFilter(kind, value) {
    if (kind === 'type') toggleSelectValue('botCommandTypeFilter', value);
    if (kind === 'state') toggleSelectValue('botCommandStateFilter', value);
    if (kind === 'role') toggleSelectValue('botCommandRoleFilter', value);
    if (kind === 'class') toggleSelectValue('botCommandClassFilter', value);
    filterBotCommandCards();
}

function applyGmChipFilter(kind, value) {
    if (kind === 'security') toggleSelectValue('gmCommandSecurityFilter', value);
    if (kind === 'prefix') toggleSelectValue('gmCommandPrefixFilter', value);
    filterGmCommandCards();
}

function filterBotCommandCards() {
    const query = (((document.getElementById('botCommandSearch') || {}).value) || '').toLowerCase().trim();
    const type = (((document.getElementById('botCommandTypeFilter') || {}).value) || 'all').toLowerCase();
    const state = (((document.getElementById('botCommandStateFilter') || {}).value) || 'all').toLowerCase();
    const role = (((document.getElementById('botCommandRoleFilter') || {}).value) || 'all').toLowerCase();
    const playerClass = (((document.getElementById('botCommandClassFilter') || {}).value) || 'all').toLowerCase();

    document.querySelectorAll('#botCommandCards .cmd-card').forEach(function (card) {
        const haystack = card.getAttribute('data-filter-search') || '';
        const typeValue = (card.getAttribute('data-type') || '').toLowerCase();
        const stateTags = (card.getAttribute('data-state-tags') || '').split(',').filter(Boolean);
        const roleTags = (card.getAttribute('data-role-tags') || '').split(',').filter(Boolean);
        const classTags = (card.getAttribute('data-class-tags') || '').split(',').filter(Boolean);

        const searchMatch = !query || haystack.indexOf(query) !== -1;
        const typeMatch = type === 'all' || typeValue === type;
        const stateMatch = state === 'all' || stateTags.indexOf(state) !== -1;
        const roleMatch = role === 'all' || roleTags.indexOf(role) !== -1;
        const classMatch = playerClass === 'all' || classTags.indexOf(playerClass) !== -1;

        card.style.display = (searchMatch && typeMatch && stateMatch && roleMatch && classMatch) ? '' : 'none';
    });
}

function filterGmCommandCards() {
    const query = (((document.getElementById('gmCommandSearch') || {}).value) || '').toLowerCase().trim();
    const security = (((document.getElementById('gmCommandSecurityFilter') || {}).value) || 'all').toLowerCase();
    const prefix = (((document.getElementById('gmCommandPrefixFilter') || {}).value) || 'all').toLowerCase();

    document.querySelectorAll('#gmCommandCards .cmd-card').forEach(function (card) {
        const haystack = card.getAttribute('data-filter-search') || '';
        const securityValue = (card.getAttribute('data-security') || '').toLowerCase();
        const prefixValue = (card.getAttribute('data-prefix') || '').toLowerCase();

        const searchMatch = !query || haystack.indexOf(query) !== -1;
        const securityMatch = security === 'all' || securityValue === security;
        const prefixMatch = prefix === 'all' || prefixValue === prefix;

        card.style.display = (searchMatch && securityMatch && prefixMatch) ? '' : 'none';
    });
}

function resetBotCommandFilters() {
    const search = document.getElementById('botCommandSearch');
    const type = document.getElementById('botCommandTypeFilter');
    const state = document.getElementById('botCommandStateFilter');
    const role = document.getElementById('botCommandRoleFilter');
    const playerClass = document.getElementById('botCommandClassFilter');
    if (search) search.value = '';
    if (type) type.value = 'all';
    if (state) state.value = 'all';
    if (role) role.value = 'all';
    if (playerClass) playerClass.value = 'all';
    filterBotCommandCards();
}

function resetGmCommandFilters() {
    const search = document.getElementById('gmCommandSearch');
    const security = document.getElementById('gmCommandSecurityFilter');
    const prefix = document.getElementById('gmCommandPrefixFilter');
    if (search) search.value = '';
    if (security) security.value = 'all';
    if (prefix) prefix.value = 'all';
    filterGmCommandCards();
}

const CSB_TRIGGERS = {
  'Health and Resources': [
    'critical health','low health','medium health','almost full health',
    'no mana','low mana','medium mana','high mana','almost full mana',
    'no energy available','light energy available','medium energy available','high energy available',
    'light rage available','medium rage available','high rage available'
  ],
  'Combat': [
    'combat start','combat end','death',
    'no target','target in sight','target changed','invalid target','not facing target',
    'has aggro','lose aggro','high threat','medium threat','some threat','no threat',
    'multiple attackers','has attackers','no attackers','possible adds',
    'enemy is close','enemy player near','enemy player ten yards',
    'enemy out of melee','enemy out of spell',
    'behind target','not behind target','panic','outnumbered'
  ],
  'Party': [
    'party member critical health','party member low health','party member medium health',
    'party member almost full health','party member dead','protect party member','no pet'
  ],
  'Movement and Position': [
    'far from master','not near master',
    'wander far','wander medium','wander near',
    'swimming','move stuck','move long stuck','falling','falling far',
    'can loot','loot available','far from loot target'
  ],
  'Battleground and PvP': [
    'in battleground','in pvp','in pve',
    'bg active','bg ended','bg waiting','bg invite active',
    'player has flag','player has no flag','team has flag','enemy team has flag',
    'enemy flagcarrier near','in battleground without flag'
  ],
  'Status Effects': [
    'dead','corpse near','mounted','rooted','party member rooted',
    'feared','stunned','charmed'
  ],
  'RPG': [
    'no rpg target','has rpg target','far from rpg target','near rpg target',
    'rpg wander','rpg start quest','rpg end quest','rpg buy',
    'rpg sell','rpg repair','rpg train'
  ],
  'Timing': [
    'random','timer','seldom','often','very often',
    'random bot update','no non bot players around','new player nearby'
  ],
  'Buffs and Items': [
    'potion cooldown','use trinket','need world buff',
    'give food','give water',
    'has blessing of salvation','has greater blessing of salvation'
  ]
};

const CSB_ACTIONS = {
  'Qualified': ['say::','emote::'],
  'Communication': ['talk','suggest what to do','greet'],
  'Combat': [
    'attack','melee','dps assist','tank assist','dps aoe',
    'flee','flee with pet','shoot',
    'interrupt current spell','attack enemy player','attack least hp target'
  ],
  'Survival and Healing': [
    'healing potion','healthstone','mana potion','food','drink',
    'use bandage','try emergency','whipper root tuber',
    'fire protection potion','free action potion'
  ],
  'Movement': [
    'follow','stay','return','runaway','flee to master',
    'mount','hearthstone','move random','guard'
  ],
  'Loot': [
    'loot','move to loot','add loot','release loot','auto loot roll','reveal gathering item'
  ],
  'Battleground': [
    'free bg join','bg tactics','bg move to objective',
    'bg move to start','attack enemy flag carrier'
  ],
  'Racials': [
    'war stomp','berserking','blood fury','shadowmeld','stoneform',
    'arcane torrent','will of the forsaken','cannibalize','mana tap',
    'escape artist','perception','every_man_for_himself','gift of the naaru'
  ],
  'Misc': [
    'delay','reset','random bot update',
    'xp gain','honor gain','invite nearby','check mail','update gear'
  ]
};

let csbLines = [];
let csbInitialized = false;
  let mbFilters = [];
  let mbInitialized = false;
  let mbLayers = [];

function csbInit() {
  if (csbInitialized) return;
  csbInitialized = true;
  csbLines = [{
    trigger: 'critical health',
    actions: [
      { type: 'emote::', qualifier: 'helpme', priority: 99 },
      { type: 'say::', qualifier: 'critical health', priority: 98 }
    ]
  }];
  csbRender();
}

function csbToggleGuid() {
  const isGuid = document.querySelector('input[name="csb-owner"]:checked').value === 'guid';
  document.getElementById('csb-guid').style.display = isGuid ? '' : 'none';
  csbUpdateOutput();
}

function csbAddLine() {
  csbLines.push({ trigger: 'low health', actions: [{ type: 'say::', qualifier: 'low health', priority: 98 }] });
  csbRender();
}

function csbRemoveLine(idx) {
  csbLines.splice(idx, 1);
  csbRender();
}

function csbAddAction(lineIdx) {
  csbLines[lineIdx].actions.push({ type: 'emote::', qualifier: 'helpme', priority: 99 });
  csbRender();
}

function csbRemoveAction(lineIdx, actionIdx) {
  csbLines[lineIdx].actions.splice(actionIdx, 1);
  csbRender();
}

function csbSet(lineIdx, field, value) {
  csbLines[lineIdx][field] = value;
  csbUpdateOutput();
}

function csbSetAction(lineIdx, actionIdx, field, value) {
  if (field === 'type') {
    csbLines[lineIdx].actions[actionIdx].type = value;
    if (value === 'say::') csbLines[lineIdx].actions[actionIdx].qualifier = 'critical health';
    else if (value === 'emote::') csbLines[lineIdx].actions[actionIdx].qualifier = 'helpme';
    else csbLines[lineIdx].actions[actionIdx].qualifier = '';
    csbRender();
  } else {
    csbLines[lineIdx].actions[actionIdx][field] = value;
    csbUpdateOutput();
  }
}

function csbBuildTriggerSelect(lineIdx, selected) {
  let html = '<select class="csb-select" style="min-width:180px;" onchange="csbSet(' + lineIdx + ',\'trigger\',this.value)">';
  Object.keys(CSB_TRIGGERS).forEach(function(group) {
    html += '<optgroup label="' + group + '">';
    CSB_TRIGGERS[group].forEach(function(trigger) {
      html += '<option value="' + trigger + '"' + (trigger === selected ? ' selected' : '') + '>' + trigger + '</option>';
    });
    html += '</optgroup>';
  });
  html += '</select>';
  return html;
}

function csbBuildActionSelect(lineIdx, actionIdx, selected) {
  let html = '<select class="csb-select" style="min-width:130px;" onchange="csbSetAction(' + lineIdx + ',' + actionIdx + ',\'type\',this.value)">';
  Object.keys(CSB_ACTIONS).forEach(function(group) {
    html += '<optgroup label="' + group + '">';
    CSB_ACTIONS[group].forEach(function(action) {
      html += '<option value="' + action + '"' + (action === selected ? ' selected' : '') + '>' + action + '</option>';
    });
    html += '</optgroup>';
  });
  html += '</select>';
  return html;
}

function csbRender() {
  const container = document.getElementById('csb-lines');
  if (!container) return;
  let html = '';
  csbLines.forEach(function(line, li) {
    html += '<div class="csb-line-card">';
    html += '<div class="csb-line-header"><span class="csb-line-num">Line ' + (li + 1) + '</span>';
    html += '<button class="csb-btn csb-btn-del" onclick="csbRemoveLine(' + li + ')">Remove</button></div>';
    html += '<div class="csb-row"><span class="csb-label">Trigger</span>' + csbBuildTriggerSelect(li, line.trigger) + '</div>';
    html += '<div style="margin-left:62px;">';
    line.actions.forEach(function(action, ai) {
      const needsQualifier = action.type === 'say::' || action.type === 'emote::';
      const listAttr = action.type === 'say::' ? 'list="csb-say-list"' : (action.type === 'emote::' ? 'list="csb-emote-list"' : '');
      html += '<div class="csb-action-row">';
      html += csbBuildActionSelect(li, ai, action.type);
      if (needsQualifier) {
        html += '<input class="csb-input csb-qual" type="text" ' + listAttr + ' placeholder="qualifier" value="' + (action.qualifier || '').replace(/"/g, '&quot;') + '" oninput="csbSetAction(' + li + ',' + ai + ',\'qualifier\',this.value)">';
      }
      html += '!<input class="csb-input csb-priority" type="number" min="1" max="100" value="' + action.priority + '" oninput="csbSetAction(' + li + ',' + ai + ',\'priority\',this.value)">';
      html += '<button class="csb-btn csb-btn-del" onclick="csbRemoveAction(' + li + ',' + ai + ')">Remove</button>';
      html += '</div>';
    });
    html += '<button class="csb-btn csb-btn-add" style="margin-top:4px;" onclick="csbAddAction(' + li + ')">+ Action</button>';
    html += '</div></div>';
  });
  container.innerHTML = html;
  csbUpdateOutput();
}

function csbBuildActionLine(line) {
  const actionString = line.actions.map(function(action) {
    const name = (action.type === 'say::' || action.type === 'emote::') ? action.type + action.qualifier : action.type;
    return name + '!' + action.priority;
  }).join(',');
  return line.trigger + '>' + actionString;
}

function csbUpdateOutput() {
  const name = ((document.getElementById('csb-name') || {}).value || 'mysay').trim() || 'mysay';
  const ownerRadio = document.querySelector('input[name="csb-owner"]:checked');
  const ownerValue = ownerRadio && ownerRadio.value === 'guid' ? (((document.getElementById('csb-guid') || {}).value || '0').trim() || '0') : '0';

  const preview = document.getElementById('csb-activation-preview');
  if (preview) preview.textContent = '+custom::' + name;

  const actionLines = csbLines.map(csbBuildActionLine);
  const activationOutput = document.getElementById('csb-out-activation');
  if (activationOutput) activationOutput.textContent = '+custom::' + name;

  const sqlRows = actionLines.map(function(line, index) {
    return "  ('" + name + "', " + (index + 1) + ", " + ownerValue + ", '" + line + "')";
  }).join(",\n");
  const sql = 'INSERT INTO ai_playerbot_custom_strategy (name, idx, owner, action_line) VALUES\n' + sqlRows + ';';
  const sqlOutput = document.getElementById('csb-out-sql');
  if (sqlOutput) sqlOutput.textContent = actionLines.length ? sql : '(add at least one line)';

  const csLines = actionLines.map(function(line, index) {
    return 'cs ' + name + ' ' + (index + 1) + ' ' + line;
  }).join('\n');
  const csOutput = document.getElementById('csb-out-cs');
  if (csOutput) csOutput.textContent = actionLines.length ? csLines : '(add at least one line)';
}

function csbCopy(elementId) {
  const element = document.getElementById(elementId);
  const text = element ? element.textContent : '';
  if (!text) return;
  navigator.clipboard.writeText(text).then(function() {
    const btn = Array.from(document.querySelectorAll('.csb-btn-copy')).find(function(candidate) {
      return (candidate.getAttribute('onclick') || '').indexOf("csbCopy('" + elementId + "')") !== -1;
    });
    if (!btn) return;
    const original = btn.textContent;
    btn.textContent = 'Copied!';
    setTimeout(function() { btn.textContent = original; }, 1500);
  });
}

function mbInit() {
  if (mbInitialized) return;
  mbInitialized = true;
  mbFilters = [];
  mbLayers = [mbCreateLayer()];
  mbAddFilter('@tank');
  mbRenderLayers();
}

function mbRender() {
  mbInit();
  mbRenderFilters();
  mbUpdateOutput();
}

function mbGetPresetByKey(key) {
  return MACRO_PRESETS.find(function(preset) { return preset.key === key; }) || MACRO_PRESETS[0];
}

function mbCreateLayer() {
  return {
    targetFilter: '',
    presetKey: (MACRO_PRESETS[0] || {}).key || '',
    optionValue: '',
    value: '',
    strategies: []
  };
}

function mbGetDeliveryMode() {
  return (((document.getElementById('mb-delivery') || {}).value) || 'whisper').trim();
}

function mbGetDeliveryPrefix() {
  const mode = mbGetDeliveryMode();
  if (mode === 'party') return '/p';
  if (mode === 'raid') return '/ra';
  if (mode === 'guild') return '/g';
  if (mode === 'say') return '/s';
  return '/w';
}

function mbUpdateDeliveryMode() {
  const targetRow = document.getElementById('mb-target-row');
  const target = document.getElementById('mb-target');
  const isWhisper = mbGetDeliveryMode() === 'whisper';
  if (targetRow) targetRow.style.display = isWhisper ? '' : 'none';
  if (target) {
    target.style.display = isWhisper ? '' : 'none';
    target.placeholder = isWhisper ? 'BotName' : '';
  }
  mbUpdateOutput();
}

function mbBuildPresetSelect(index, selectedKey) {
  let html = '<select class="csb-select mb-layer-preset" onchange="mbSetLayerPreset(' + index + ',this.value)">';
  let currentGroup = '';
  MACRO_PRESETS.forEach(function(preset) {
    if (preset.group !== currentGroup) {
      if (currentGroup) html += '</optgroup>';
      currentGroup = preset.group;
      html += '<optgroup label="' + preset.group + '">';
    }
    html += '<option value="' + preset.key + '"' + (preset.key === selectedKey ? ' selected' : '') + '>' + preset.label + '</option>';
  });
  if (currentGroup) html += '</optgroup>';
  html += '</select>';
  return html;
}

function mbBuildLayerTargetSelect(index, selectedValue) {
  let html = '<select class="csb-select mb-layer-target" onchange="mbSetLayerTargetFilter(' + index + ',this.value)">';
  html += '<option value="">No split target</option>';
  let currentGroup = '';
  LAYER_FILTER_OPTIONS.forEach(function(option) {
    if (option.group !== currentGroup) {
      if (currentGroup) html += '</optgroup>';
      currentGroup = option.group;
      html += '<optgroup label="' + option.group + '">';
    }
    html += '<option value="' + option.token + '"' + (option.token === selectedValue ? ' selected' : '') + '>' + option.label + '</option>';
  });
  if (currentGroup) html += '</optgroup>';
  html += '</select>';
  return html;
}

function mbGetLayerPreset(index) {
  return mbGetPresetByKey((mbLayers[index] || {}).presetKey || '');
}

function mbGetClassStrategyOptions(index) {
  const layer = mbLayers[index];
  const preset = mbGetLayerPreset(index);
  const selectedState = (layer && layer.optionValue) || '';
  if (!preset || (preset.mode || '') !== 'class_strategies' || !selectedState) return [];
  return (preset.strategyOptions && preset.strategyOptions[selectedState]) ? preset.strategyOptions[selectedState] : [];
}

function mbRenderLayerClassBuilder(index) {
  const layer = mbLayers[index];
  const options = mbGetClassStrategyOptions(index);
  if (!layer || !options.length) return '';

  layer.strategies = layer.strategies.filter(function(selection) {
    return options.indexOf(selection) !== -1;
  });
  if (!layer.strategies.length) {
    layer.strategies = [options[0]];
  }

  let html = '<div class="mb-sub-builder"><div class="mb-sub-title">Add strategies for this state</div>';
  layer.strategies.forEach(function(selection, strategyIndex) {
    html += '<div class="mb-strategy-row">';
    html += '<select class="csb-select" onchange="mbSetLayerStrategy(' + index + ',' + strategyIndex + ',this.value)">';
    options.forEach(function(option) {
      html += '<option value="' + option.replace(/"/g, '&quot;') + '"' + (option === selection ? ' selected' : '') + '>' + option + '</option>';
    });
    html += '</select>';
    if (layer.strategies.length > 1) {
      html += '<button type="button" class="csb-btn csb-btn-del" onclick="mbRemoveLayerStrategy(' + index + ',' + strategyIndex + ')">Remove</button>';
    }
    html += '</div>';
  });
  html += '<button type="button" class="csb-btn csb-btn-add" onclick="mbAddLayerStrategy(' + index + ')">+ Add Row</button></div>';
  return html;
}

function mbRenderLayers() {
  const container = document.getElementById('mb-layers');
  if (!container) return;
  let html = '';
  mbLayers.forEach(function(layer, index) {
    const preset = mbGetLayerPreset(index);
    const mode = preset.mode || (preset.needsValue ? 'value' : 'direct');
    html += '<div class="mb-layer">';
    html += '<div class="mb-layer-head"><span class="mb-layer-title">Layer ' + (index + 1) + '</span>';
    if (mbLayers.length > 1) {
      html += '<button type="button" class="csb-btn csb-btn-del" onclick="mbRemoveLayer(' + index + ')">Remove</button>';
    }
    html += '</div>';
    html += '<div class="mb-layer-grid">';
    html += mbBuildLayerTargetSelect(index, layer.targetFilter || '');
    html += mbBuildPresetSelect(index, layer.presetKey || '');
    if (mode === 'options' || mode === 'class_strategies') {
      html += '<select class="csb-select mb-layer-option" onchange="mbSetLayerOption(' + index + ',this.value)">';
      html += '<option value="">' + (preset.optionPlaceholder || 'Choose an option') + '</option>';
      (preset.options || []).forEach(function(option) {
        html += '<option value="' + option.value.replace(/"/g, '&quot;') + '"' + (option.value === (layer.optionValue || '') ? ' selected' : '') + '>' + option.label + '</option>';
      });
      if (mode === 'options') {
        html += '<option value="__custom__"' + ((layer.optionValue || '') === '__custom__' ? ' selected' : '') + '>Custom...</option>';
      }
      html += '</select>';
    }
    const showValue = mode === 'value' || mode === 'custom' || (mode === 'options' && (layer.optionValue || '') === '__custom__');
    if (showValue) {
      html += '<input class="csb-input mb-layer-value" type="text" placeholder="' + (((layer.optionValue || '') === '__custom__' ? (preset.customPlaceholder || preset.placeholder || '') : (preset.placeholder || '')).replace(/"/g, '&quot;')) + '" value="' + (layer.value || '').replace(/"/g, '&quot;') + '" oninput="mbSetLayerValue(' + index + ',this.value)">';
    }
    html += '</div>';
    if (mode === 'class_strategies') {
      html += mbRenderLayerClassBuilder(index);
    }
    html += '</div>';
  });
  container.innerHTML = html;
}

function mbSetLayerTargetFilter(index, value) {
  mbLayers[index].targetFilter = value;
  mbUpdateOutput();
}

function mbSetLayerPreset(index, value) {
  mbLayers[index].presetKey = value;
  mbLayers[index].optionValue = '';
  mbLayers[index].value = '';
  mbLayers[index].strategies = [];
  mbRenderLayers();
  mbUpdateOutput();
}

function mbSetLayerOption(index, value) {
  mbLayers[index].optionValue = value;
  if (value !== '__custom__') {
    mbLayers[index].value = '';
  }
  mbLayers[index].strategies = [];
  mbRenderLayers();
  mbUpdateOutput();
}

function mbSetLayerValue(index, value) {
  mbLayers[index].value = value;
  mbUpdateOutput();
}

function mbAddLayer() {
  mbLayers.push(mbCreateLayer());
  mbRenderLayers();
  mbUpdateOutput();
}

function mbRemoveLayer(index) {
  mbLayers.splice(index, 1);
  if (!mbLayers.length) mbLayers = [mbCreateLayer()];
  mbRenderLayers();
  mbUpdateOutput();
}

function mbAddLayerStrategy(index) {
  const options = mbGetClassStrategyOptions(index);
  if (!options.length) return;
  mbLayers[index].strategies.push(options[0]);
  mbRenderLayers();
  mbUpdateOutput();
}

function mbAddFilter(initialToken) {
  const fallback = CHAT_FILTER_OPTIONS[0] || { token: '@tank', needsValue: false, placeholder: '' };
  const match = CHAT_FILTER_OPTIONS.find(function(option) { return option.token === initialToken || option.label === initialToken; }) || fallback;
  mbFilters.push({
    token: match.token,
    needsValue: !!match.needsValue,
    placeholder: match.placeholder || '',
    value: ''
  });
  mbRenderFilters();
}

function mbAddQuickFilter(token) {
  const existing = mbFilters.some(function(filter) {
    return (filter.token || '').trim().toLowerCase() === String(token || '').trim().toLowerCase();
  });
  if (!existing) {
    mbAddFilter(token);
    return;
  }
  mbUpdateOutput();
}

function mbSetLayerStrategy(layerIndex, strategyIndex, value) {
  mbLayers[layerIndex].strategies[strategyIndex] = value;
  mbUpdateOutput();
}

function mbRemoveLayerStrategy(layerIndex, strategyIndex) {
  mbLayers[layerIndex].strategies.splice(strategyIndex, 1);
  if (!mbLayers[layerIndex].strategies.length) {
    const options = mbGetClassStrategyOptions(layerIndex);
    if (options.length) mbLayers[layerIndex].strategies = [options[0]];
  }
  mbRenderLayers();
  mbUpdateOutput();
}

function mbRemoveFilter(index) {
  mbFilters.splice(index, 1);
  mbRenderFilters();
}

function mbSetFilterToken(index, value) {
  const match = CHAT_FILTER_OPTIONS.find(function(option) { return option.token === value; });
  if (!match) return;
  mbFilters[index].token = match.token;
  mbFilters[index].needsValue = !!match.needsValue;
  mbFilters[index].placeholder = match.placeholder || '';
  if (!match.needsValue) mbFilters[index].value = '';
  mbRenderFilters();
}

function mbSetFilterValue(index, value) {
  mbFilters[index].value = value;
  mbUpdateOutput();
}

function mbBuildFilterSelect(index, selectedToken) {
  let html = '<select class="csb-select" onchange="mbSetFilterToken(' + index + ',this.value)">';
  let currentGroup = '';
  CHAT_FILTER_OPTIONS.forEach(function(option) {
    if (option.group !== currentGroup) {
      if (currentGroup) html += '</optgroup>';
      currentGroup = option.group;
      html += '<optgroup label="' + option.group + '">';
    }
    html += '<option value="' + option.token + '"' + (option.token === selectedToken ? ' selected' : '') + '>' + option.label + '</option>';
  });
  if (currentGroup) html += '</optgroup>';
  html += '</select>';
  return html;
}

function mbRenderFilters() {
  const container = document.getElementById('mb-filters');
  if (!container) return;
  let html = '';
  mbFilters.forEach(function(filter, index) {
    html += '<div class="mb-filter-row">';
    html += mbBuildFilterSelect(index, filter.token);
    if (filter.needsValue) {
      html += '<input class="csb-input" type="text" placeholder="' + (filter.placeholder || 'value') + '" value="' + (filter.value || '').replace(/"/g, '&quot;') + '" oninput="mbSetFilterValue(' + index + ',this.value)">';
    } else {
      html += '<span style="color:#777;font-size:12px;">No value needed</span>';
    }
    html += '<button class="csb-btn csb-btn-del" onclick="mbRemoveFilter(' + index + ')">Remove</button>';
    html += '</div>';
  });
  if (!mbFilters.length) {
    html = '<div class="cff-note">No filters added yet. You can still build a direct whisper command, or add filters to narrow the response pool.</div>';
  }
  container.innerHTML = html;
  mbUpdateOutput();
}

function mbBuildFilterText(filter) {
  const token = (filter.token || '').trim();
  const value = (filter.value || '').trim();
  if (!token) return '';
  if (!filter.needsValue) return token;
  if (token === '@') return value ? '@' + value : '';
  return value ? token + value : '';
}

function mbBuildLayerText(layer) {
  const preset = mbGetPresetByKey(layer.presetKey || '');
  const selectedOption = (layer.optionValue || '').trim();
  const rawValue = (layer.value || '').trim();
  if (!preset) return '';
  const mode = preset.mode || (preset.needsValue ? 'value' : 'direct');
  let commandText = '';
  if (mode === 'direct') commandText = preset.command;
  if (mode === 'value') commandText = rawValue ? (preset.command + ' ' + rawValue) : '';
  if (mode === 'custom') commandText = rawValue;
  if (mode === 'class_strategies') {
    const picks = (layer.strategies || []).filter(function(selection) { return !!selection; });
    commandText = (selectedOption && picks.length) ? (selectedOption + ' ' + picks.join(',')) : '';
  }
  if (mode === 'options') {
    if (!selectedOption) commandText = '';
    else if (selectedOption === '__custom__') commandText = rawValue;
    else commandText = selectedOption;
  }
  if (!commandText) return '';
  return ((layer.targetFilter || '').trim() ? ((layer.targetFilter || '').trim() + ' ') : '') + commandText;
}

function mbUpdateOutput() {
  const target = (((document.getElementById('mb-target') || {}).value) || '').trim();
  const delivery = mbGetDeliveryMode();
  const deliveryPrefix = mbGetDeliveryPrefix();
  const layerTexts = mbLayers.map(mbBuildLayerText);
  const commandText = layerTexts.filter(function(text) { return !!text; }).join(' ');
  const builtFilters = mbFilters.map(mbBuildFilterText);
  const validFilters = builtFilters.filter(function(text) { return !!text; });
  const hasMissingFilterValue = mbFilters.some(function(filter, index) {
    return filter.needsValue && !builtFilters[index];
  });

  const status = document.getElementById('mb-status');
  const macroOutput = document.getElementById('mb-out-macro');
  const commandOutput = document.getElementById('mb-out-command');

  let statusText = 'Ready to build.';
  let preview = '';
  let macro = '';
  const requiresTarget = delivery === 'whisper';

  if ((!target && requiresTarget) && !commandText) {
      statusText = 'Enter a bot target for whisper mode and build at least one command layer.';
    } else if (!target && requiresTarget) {
      statusText = 'Enter a bot target to produce a valid whisper macro.';
    } else if (!commandText) {
      statusText = 'Choose presets and fill any required layer values.';
  } else if (hasMissingFilterValue) {
    statusText = 'Fill in all value-based filters before copying the macro.';
  } else {
    preview = validFilters.concat([commandText]).join(' ');
    macro = requiresTarget ? (deliveryPrefix + ' ' + target + ' ' + preview) : (deliveryPrefix + ' ' + preview);
    statusText = requiresTarget ? 'Whisper macro is valid and ready to copy.' : 'Macro is valid and ready to copy.';
  }

  if (status) status.textContent = statusText;
  if (macroOutput) macroOutput.textContent = macro || '(complete the required fields to generate a macro)';
  if (commandOutput) commandOutput.textContent = preview || '(filters and command preview will appear here)';
}

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('#chatFilterCards .cff-card').forEach(function(card) {
    card.addEventListener('toggle', syncChatFilterCardIndicators);
  });
  document.querySelectorAll('#tab-vanilla .vanilla-card').forEach(function(card) {
    card.addEventListener('toggle', syncVanillaCardIndicators);
  });
  syncChatFilterCardIndicators();
  syncVanillaCardIndicators();
  mbUpdateDeliveryMode();
  csbInit();
  mbInit();
});
</script>

<?php builddiv_end(); ?>
