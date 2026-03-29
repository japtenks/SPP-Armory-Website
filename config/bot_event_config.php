<?php
// ================================================================
// bot_event_config.php
// ================================================================
// Configuration for the Phase 5 bot event pipeline.
// Adjust forum IDs and milestone thresholds to match your server.
// ================================================================

$botEventConfig = [

    // ----------------------------------------------------------------
    // Which realms should the bot event pipeline actively scan/process.
    // Keep this limited to installed and accessible realms.
    // ----------------------------------------------------------------
    'enabled_realms' => [1],

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
    // Achievement badges — reads from character_achievement table.
    // All earned achievements are picked up except those in the exclude list.
    // ----------------------------------------------------------------

    // ----------------------------------------------------------------
    // Bot reactions — random reply posts after a new forum topic is created.
    // Applied to: level_up, profession_milestone, achievement_badge topics.
    // NOT applied to guild threads (keeps guild history clean).
    // ----------------------------------------------------------------

    // Min and max number of reaction replies to post per new topic.
    'reaction_count' => [1, 3],

    // Minimum and maximum delay in seconds after the original post.
    // Keep this short so replies feel like quick forum banter.
    'reaction_min_delay_sec' => 120,  // 2 minutes
    'reaction_max_delay_sec' => 900,  // 15 minutes

    // Guild-thread chatter uses guild members only, excluding the lowest
    // initiate rank when the guild rank table is available.
    'guild_reaction_count' => [1, 2],
    'guild_reaction_min_delay_sec' => 180,  // 3 minutes
    'guild_reaction_max_delay_sec' => 1200, // 20 minutes

    // ----------------------------------------------------------------
    // How many days back to scan on each run.
    // INSERT IGNORE + dedupe keys prevent double-posting, so wider = more history on first run.
    'achievement_lookback_days' => 30,

    // Achievement IDs to skip entirely.
    // Level achievements are covered by the level_up scanner.
    // To find remaining level IDs: SELECT ID, Title_Lang_enUS FROM achievement_dbc WHERE Title_Lang_enUS LIKE 'Level %';
    'achievement_badge_exclude' => [
        6,    // Level 10  — covered by level_up
        7,    // Level 20  — covered by level_up
        8,    // Level 30  — covered by level_up
        9,    // Level 40  — covered by level_up
        10,   // Level 50  — covered by level_up
        11,   // Level 60  — covered by level_up
        12,   // Level 70  — covered by level_up
        13,   // Level 80  — covered by level_up

        238,  // An Honorable Kill — too common (~1000 chars), not notable enough
    ],

    // ----------------------------------------------------------------
    // Guild roster update thresholds.
    //   min_joins    — minimum net new members since last post to trigger
    //   cooldown_sec — minimum seconds between roster posts per guild
    // A post also fires when any members have left, subject to cooldown.
    // ----------------------------------------------------------------
    'guild_roster_thresholds' => [
        'min_joins'    => 8,
        'cooldown_sec' => 43200, // 12 hours
    ],

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
            'level_up'             => 2,  // Classic forum
            'guild_created'        => 5,  // Guild Recruitment
            'profession_milestone' => 2,  // Classic forum
            'guild_roster_update'  => 5,  // Guild Recruitment
            'achievement_badge'    => 2,  // Classic forum (item milestones, quest completions)
        ],
        2 => [
            'level_up'             => 3,  // The Burning Crusade forum
            'guild_created'        => 5,  // Guild Recruitment
            'profession_milestone' => 3,
            'guild_roster_update'  => 5,  // Guild Recruitment
            'achievement_badge'    => 3,  // TBC forum
        ],
        3 => [
            'level_up'             => 4,  // Wrath of the Lich King forum
            'guild_created'        => 5,  // Guild Recruitment
            'profession_milestone' => 4,
            'guild_roster_update'  => 5,  // Guild Recruitment
            'achievement_badge'    => 4,  // WotLK forum
        ],
    ],
];
