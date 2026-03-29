<?php
declare(strict_types=1);

// ============================================================
// tools/guild_json.php
// ============================================================
// Shared helpers for reading and writing guild summary JSON
// files stored under jsons/guilds/realm-{id}/.
//
// Consumed by: scan_bot_events.php, process_bot_events.php
// ============================================================

if (!defined('GUILD_JSON_BASE')) {
    define('GUILD_JSON_BASE', dirname(__DIR__) . '/jsons/guilds');
}

function guild_json_path(int $realmId, int $guildId): string {
    return GUILD_JSON_BASE . '/realm-' . $realmId . '/guild-' . $guildId . '.summary.json';
}

function guild_json_read(int $realmId, int $guildId): ?array {
    $path = guild_json_path($realmId, $guildId);
    if (!file_exists($path)) return null;
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function guild_json_write(int $realmId, int $guildId, array $data): bool {
    $dir = GUILD_JSON_BASE . '/realm-' . $realmId;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return (bool)file_put_contents(
        guild_json_path($realmId, $guildId),
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

/**
 * Build an initial summary skeleton for a guild seen for the first time.
 * The roster snapshot is set to the current member list so the next scan
 * can compute a meaningful delta.
 */
function guild_json_skeleton(
    int    $realmId,
    int    $guildId,
    string $guildName,
    int    $leaderGuid,
    string $leaderName,
    array  $memberGuids,
    array  $memberDetails = []
): array {
    return [
        'realm_id'           => $realmId,
        'guild_id'           => $guildId,
        'guild_name'         => $guildName,
        'thread_topic_id'    => null,
        'roster_post_id'     => null,
        'recruitment_status' => 'unknown',
        'posting_identity'   => [
            'mode'          => 'guild_leader_or_marked_officer',
            'leader_guid'   => $leaderGuid,
            'leader_name'   => $leaderName,
            'officer_guids' => [],
            'officer_names' => [],
        ],
        'roster' => [
            'member_count'   => count($memberGuids),
            'member_guids'   => $memberGuids,
            'member_details' => $memberDetails,
            'captured_at'    => date('Y-m-d H:i:s'),
        ],
        'last_forum_roster_post' => null,
        'pending_delta' => [
            'joined_guids' => [],
            'left_guids'   => [],
        ],
    ];
}
