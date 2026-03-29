<?php
// ================================================================
// bot_event_config.php
// ================================================================
// Configuration for the Phase 5 bot event pipeline.
// Adjust forum IDs and milestone thresholds to match your server.
// ================================================================

$botEventConfig = [

    // ----------------------------------------------------------------
    // Level milestones to announce, per expansion.
    // Only these exact levels will generate a level_up event.
    // ----------------------------------------------------------------
    'level_milestones' => [
        'classic' => [10, 20, 30, 40, 50, 60],
        'tbc'     => [10, 20, 30, 40, 50, 60, 70],
        'wotlk'   => [10, 20, 30, 40, 50, 60, 70, 80],
    ],

    // ----------------------------------------------------------------
    // Profession skill IDs (MaNGOS) and display names.
    // ----------------------------------------------------------------
    'professions' => [
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
        755 => 'Jewelcrafting',  // TBC+
        773 => 'Inscription',    // WotLK+
    ],

    // Skill value milestones to announce.
    'profession_milestones' => [75, 150, 225, 300],

    // ----------------------------------------------------------------
    // Realm expansion map (realm_id => expansion slug).
    // Must match realmDbMap keys in config-protected.php.
    // ----------------------------------------------------------------
    'realm_expansion' => [
        1 => 'classic',
        2 => 'tbc',
        3 => 'wotlk',
    ],

    // ----------------------------------------------------------------
    // Forum targeting: which forum_id to post each event type to,
    // per realm. Set to null to skip posting for that combination.
    // ----------------------------------------------------------------
    'forum_targets' => [
        1 => [
            'level_up'             => 2, // Classic forum
            'guild_created'        => 5, // Guild Recruitment
            'profession_milestone' => 2, // Classic forum
        ],
        2 => [
            'level_up'             => 3, // The Burning Crusade forum
            'guild_created'        => 5, // Guild Recruitment
            'profession_milestone' => 3,
        ],
        3 => [
            'level_up'             => 4, // Wrath of the Lich King forum
            'guild_created'        => 5, // Guild Recruitment
            'profession_milestone' => 4,
        ],
    ],
];
