<?php
$siteRoot = dirname(__DIR__, 3);
require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/components/forum/forum.func.php');

if (!function_exists('spp_class_icon_url')) {
    function spp_class_icon_url($classId)
    {
        $classId = (int)$classId;
        $extensions = [
            1 => 'jpg',
            2 => 'jpg',
            3 => 'jpg',
            4 => 'jpg',
            5 => 'jpg',
            6 => 'gif',
            7 => 'jpg',
            8 => 'jpg',
            9 => 'jpg',
            11 => 'jpg',
        ];

        if (!isset($extensions[$classId])) {
            return '/templates/offlike/images/armory/icons/64x64/404.png';
        }

        return '/templates/offlike/images/armory/icons/64x64/class-' . $classId . '.' . $extensions[$classId];
    }
}

if (!function_exists('spp_race_icon_url')) {
    function spp_race_icon_url($raceId, $gender)
    {
        $raceId = (int)$raceId;
        $gender = ((int)$gender === 1) ? 'female' : 'male';
        $icons = [
            1 => 'achievement_character_human_' . $gender,
            2 => 'achievement_character_orc_' . $gender,
            3 => 'achievement_character_dwarf_' . $gender,
            4 => 'achievement_character_nightelf_' . $gender,
            5 => 'achievement_character_undead_' . $gender,
            6 => 'achievement_character_tauren_' . $gender,
            7 => 'achievement_character_gnome_' . $gender,
            8 => 'achievement_character_troll_' . $gender,
            10 => 'achievement_character_bloodelf_' . $gender,
            11 => 'achievement_character_draenei_' . $gender,
        ];

        if (!isset($icons[$raceId])) {
            return '/templates/offlike/images/armory/icons/64x64/404.png';
        }

        return '/templates/offlike/images/armory/icons/64x64/' . $icons[$raceId] . '.png';
    }
}

if (!function_exists('spp_guild_roster_sort_compare')) {
    function spp_guild_roster_sort_compare(array $left, array $right, $sortBy, $sortDir, array $classNames, array $raceNames, array $memberAverageItemLevels) {
        $direction = strtoupper($sortDir) === 'ASC' ? 1 : -1;
        $leftGuid = (int)($left['guid'] ?? 0);
        $rightGuid = (int)($right['guid'] ?? 0);
        $leftAvgItemLevel = (float)($memberAverageItemLevels[$leftGuid] ?? 0);
        $rightAvgItemLevel = (float)($memberAverageItemLevels[$rightGuid] ?? 0);

        switch ($sortBy) {
            case 'name':
                $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                break;
            case 'race':
                $comparison = strcasecmp((string)($raceNames[(int)($left['race'] ?? 0)] ?? 'Unknown'), (string)($raceNames[(int)($right['race'] ?? 0)] ?? 'Unknown'));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'class':
                $comparison = strcasecmp((string)($classNames[(int)($left['class'] ?? 0)] ?? 'Unknown'), (string)($classNames[(int)($right['class'] ?? 0)] ?? 'Unknown'));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'level':
                $comparison = ((int)($left['level'] ?? 0) <=> (int)($right['level'] ?? 0));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'ilvl':
                $comparison = ($leftAvgItemLevel <=> $rightAvgItemLevel);
                if ($comparison === 0) {
                    $comparison = ((int)($left['level'] ?? 0) <=> (int)($right['level'] ?? 0));
                }
                break;
            case 'rank':
                $comparison = ((int)($left['rank'] ?? 0) <=> (int)($right['rank'] ?? 0));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['rank_name'] ?? ''), (string)($right['rank_name'] ?? ''));
                }
                break;
            default:
                $comparison = ((int)($left['rank'] ?? 0) <=> (int)($right['rank'] ?? 0));
                if ($comparison === 0) {
                    $comparison = ((int)($right['level'] ?? 0) <=> (int)($left['level'] ?? 0));
                }
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
        }

        return $comparison * $direction;
    }
}

if (!function_exists('spp_guild_roster_sort_url')) {
    function spp_guild_roster_sort_url($baseUrl, $sortBy, $currentSortBy, $currentSortDir) {
        $nextSortDir = ($currentSortBy === $sortBy && strtoupper($currentSortDir) === 'ASC') ? 'DESC' : 'ASC';
        return $baseUrl . '&sort=' . rawurlencode($sortBy) . '&dir=' . rawurlencode($nextSortDir) . '&p=1';
    }
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
if (!is_array($realmMap) || empty($realmMap)) {
    die("Realm DB map not loaded");
}

$realmId = spp_resolve_realm_id($realmMap);
if (!isset($realmMap[$realmId])) {
    die("Invalid realm ID");
}

$realmDB = $realmMap[$realmId]['chars'];
$realmWorldDB = $realmMap[$realmId]['world'];
$armoryRealm = spp_get_armory_realm_name($realmId) ?? '';
$currtmp = '/armory';
$charsPdo  = spp_get_pdo('chars', $realmId);
$realmdPdo = spp_get_pdo('realmd', $realmId);

$guildId = isset($_GET['guildid']) ? (int)$_GET['guildid'] : 0;
if ($guildId < 1) {
    echo "<div style='padding:24px;color:#f5c46b;'>No guild selected.</div>";
    return;
}

$classNames = [
  1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
  6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'
];
$raceNames = [
  1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead',
  6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei'
];
$allianceRaces = [1, 3, 4, 7, 11, 22, 25, 29];

$stmt = $charsPdo->prepare("SELECT guildid, name, leaderguid, motd FROM {$realmDB}.guild WHERE guildid=?");
$stmt->execute([(int)$guildId]);
$guild = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$guild) {
    echo "<div style='padding:24px;color:#f5c46b;'>Guild not found.</div>";
    return;
}

$selectedWebsiteCharacterId = 0;
if (isset($user['id']) && (int)$user['id'] > 0) {
    $stmt = $realmdPdo->prepare("SELECT character_id FROM website_accounts WHERE account_id=?");
    $stmt->execute([(int)$user['id']]);
    $selectedWebsiteCharacterId = (int)$stmt->fetchColumn();
}
$selectedGuildMember = null;
if ($selectedWebsiteCharacterId > 0) {
    $stmt = $charsPdo->prepare("SELECT gm.guid, gm.rank, COALESCE(gr.rights, 0) AS rank_rights
         FROM {$realmDB}.guild_member gm
         LEFT JOIN {$realmDB}.guild_rank gr ON gr.guildid = gm.guildid AND gr.rid = gm.rank
         WHERE gm.guildid=? AND gm.guid=?");
    $stmt->execute([(int)$guildId, (int)$selectedWebsiteCharacterId]);
    $selectedGuildMember = $stmt->fetch(PDO::FETCH_ASSOC);
}
$isSelectedGuildLeader = $selectedWebsiteCharacterId > 0 && $selectedWebsiteCharacterId === (int)$guild['leaderguid'];
$isGm = (int)($user['gmlevel'] ?? 0) >= 1;
$selectedGuildRankRights = isset($selectedGuildMember['rank_rights']) ? (int)$selectedGuildMember['rank_rights'] : 0;
$guildSetMotdRight = 4096;
$guildViewOfficerNoteRight = 16384;
$canEditGuildNotes = $isSelectedGuildLeader || $isGm;
$canViewOfficerNotes = $isSelectedGuildLeader || $isGm || (($selectedGuildRankRights & $guildViewOfficerNoteRight) === $guildViewOfficerNoteRight);
$canEditGuildMotd = $isSelectedGuildLeader || $isGm || (($selectedGuildRankRights & $guildSetMotdRight) === $guildSetMotdRight);
$canManageGuildRoster = $isSelectedGuildLeader || $isGm;
$guildNoteFeedback = '';
$guildNoteError = '';
$guildMotdFeedback = '';
$guildMotdError = '';
$guildReturnUrl = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : ('index.php?n=server&sub=guild&realm=' . $realmId . '&guildid=' . $guildId);
$guildCsrfToken = spp_csrf_token('guild_page');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guild_roster_action']) && $_POST['guild_roster_action'] === 'manage_member') {
    spp_require_csrf('guild_page');
    if (!$canManageGuildRoster) {
        $guildNoteError = 'Only the selected guild leader can manage guild members from the website.';
    } else {
        $actionType = isset($_POST['guild_roster_action_type']) ? trim((string)$_POST['guild_roster_action_type']) : '';
        $targetGuid = isset($_POST['target_guid']) ? (int)$_POST['target_guid'] : 0;
        $stmt = $charsPdo->prepare("SELECT c.guid, c.name, gm.rank
             FROM {$realmDB}.guild_member gm
             INNER JOIN {$realmDB}.characters c ON c.guid = gm.guid
             WHERE gm.guildid=? AND gm.guid=?");
        $stmt->execute([(int)$guildId, (int)$targetGuid]);
        $targetMember = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetMember) {
            $guildNoteError = 'That guild member could not be found.';
        } elseif ((int)$targetMember['guid'] === (int)$guild['leaderguid']) {
            $guildNoteError = 'The guild leader cannot be managed from the website.';
        } else {
            $targetName = (string)$targetMember['name'];
            $targetRank = (int)$targetMember['rank'];
            $stmt = $charsPdo->prepare("SELECT COALESCE(MAX(rid), 0) FROM {$realmDB}.guild_rank WHERE guildid=?");
            $stmt->execute([(int)$guildId]);
            $maxGuildRankId = (int)$stmt->fetchColumn();
            $soapCommand = '';
            $successLabel = '';

            if ($actionType === 'rank_up') {
                if ($targetRank <= 1) {
                    $guildNoteError = 'That member is already at the highest rank that can be adjusted from the roster.';
                } else {
                    $soapCommand = '.guild rank ' . $targetName . ' ' . ($targetRank - 1);
                    $successLabel = 'Promoted ' . $targetName . '.';
                }
            } elseif ($actionType === 'rank_down') {
                if ($targetRank >= $maxGuildRankId) {
                    $guildNoteError = 'That member is already at the lowest guild rank.';
                } else {
                    $soapCommand = '.guild rank ' . $targetName . ' ' . ($targetRank + 1);
                    $successLabel = 'Demoted ' . $targetName . '.';
                }
            } elseif ($actionType === 'kick') {
                $soapCommand = '.guild uninvite ' . $targetName;
                $successLabel = 'Removed ' . $targetName . ' from the guild.';
            } else {
                $guildNoteError = 'Unknown guild roster action.';
            }

            if ($guildNoteError === '' && $soapCommand !== '') {
                $soapError = '';
                $soapResult = spp_mangos_soap_execute_command($realmId, $soapCommand, $soapError);
                if ($soapResult === false) {
                    $guildNoteError = $soapError !== '' ? $soapError : 'The guild action failed.';
                } else {
                    $guildNoteFeedback = $successLabel;
                    if ($soapResult !== '') {
                        $guildNoteFeedback .= ' ' . $soapResult;
                    }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guild_form_action']) && $_POST['guild_form_action'] === 'save_guild_data') {
    spp_require_csrf('guild_page');
    $submitMode = isset($_POST['guild_submit_mode']) ? (string)$_POST['guild_submit_mode'] : '';
    $shouldSaveMotd = in_array($submitMode, ['motd_only', 'all_notes'], true);
    $shouldSaveNotes = $submitMode === 'all_notes';

    if ($shouldSaveMotd) {
        if (!$canEditGuildMotd) {
            $guildMotdError = 'Your selected guild character does not have permission to update the guild MOTD.';
        } else {
            $newMotd = substr(trim((string)($_POST['guild_motd'] ?? '')), 0, 128);
            $stmt = $charsPdo->prepare("UPDATE {$realmDB}.guild SET motd=? WHERE guildid=?");
            $stmt->execute([$newMotd, (int)$guildId]);
            $guildMotdFeedback = 'Guild MOTD updated.';
        }
    }

    if ($shouldSaveNotes) {
        if (!$canEditGuildNotes) {
            $guildNoteError = 'Only the selected guild leader can update guild notes from the website.';
        } else {
            $publicNotes = isset($_POST['pnote']) && is_array($_POST['pnote']) ? $_POST['pnote'] : [];
            $officerNotes = isset($_POST['offnote']) && is_array($_POST['offnote']) ? $_POST['offnote'] : [];
            $noteGuids = array_unique(array_merge(array_keys($publicNotes), array_keys($officerNotes)));

            if (count($noteGuids) === 0) {
                $guildNoteError = 'No guild notes were submitted.';
            } else {
                $noteGuidInts = array_map('intval', $noteGuids);
                $notePlaceholders = implode(',', array_fill(0, count($noteGuidInts), '?'));
                $stmt = $charsPdo->prepare("SELECT guid FROM {$realmDB}.guild_member WHERE guildid=? AND guid IN ($notePlaceholders)");
                $stmt->execute(array_merge([(int)$guildId], $noteGuidInts));
                $validMemberGuids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

                if (count($validMemberGuids) === 0) {
                    $guildNoteError = 'No valid guild members were found for the submitted notes.';
                } else {
                    foreach ($validMemberGuids as $validGuid) {
                        $validGuid = (int)$validGuid;
                        $publicNote = substr(trim((string)($publicNotes[$validGuid] ?? '')), 0, 31);
                        $officerNote = substr(trim((string)($officerNotes[$validGuid] ?? '')), 0, 31);
                        $stmt = $charsPdo->prepare("UPDATE {$realmDB}.guild_member SET pnote=?, offnote=? WHERE guildid=? AND guid=?");
                        $stmt->execute([$publicNote, $officerNote, (int)$guildId, $validGuid]);
                    }

                    $guildNoteFeedback = 'Guild notes updated.';
                }
            }
        }
    }

    if ($guildNoteError === '' && $guildMotdError === '' && ($guildNoteFeedback !== '' || $guildMotdFeedback !== '')) {
        $redirectUrl = preg_replace('/([?&])guild_(note|motd)_saved=1(&|$)/', '$1', $guildReturnUrl);
        $redirectUrl = rtrim((string)$redirectUrl, '?&');
        if ($guildNoteFeedback !== '') {
            $redirectUrl .= (strpos($redirectUrl, '?') === false ? '?' : '&') . 'guild_note_saved=1';
        }
        if ($guildMotdFeedback !== '') {
            $redirectUrl .= (strpos($redirectUrl, '?') === false ? '?' : '&') . 'guild_motd_saved=1';
        }
        header('Location: ' . $redirectUrl);
        exit;
    }
}

// ── Guild flavor profiles (must match sync_guild_strategies.sh + ExtraCommandsModule.cpp) ──
$guildFlavorProfiles = [
    'leveling' => [
        'label' => 'Leveling',
        'desc'  => 'Quests, grinds, repairs, trains. Behaves like a real leveling player.',
        'co'    => '+dps,+dps assist,-threat,+custom::say',
        'nc'    => '+rpg,+quest,+grind,+loot,+wander,+custom::say',
        'react' => '',
    ],
    'quest' => [
        'label' => 'Quest',
        'desc'  => 'NPC-focused. Moves purposefully between quest hubs, fishes while traveling.',
        'co'    => '+dps,+dps assist,-threat,+custom::say',
        'nc'    => '+rpg,+rpg quest,+loot,+tfish,+wander,+custom::say',
        'react' => '',
    ],
    'pvp' => [
        'label' => 'PvP',
        'desc'  => 'Aggressive. Queues battlegrounds, roams for enemy players, duels.',
        'co'    => '+dps,+dps assist,+threat,+boost,+pvp,+duel,+custom::say',
        'nc'    => '+rpg,+wander,+bg,+custom::say',
        'react' => '+pvp',
    ],
    'farming' => [
        'label' => 'Farming',
        'desc'  => 'Silent resource gatherers. Mining, herbing, fishing. No questing.',
        'co'    => '+dps,-threat',
        'nc'    => '+gather,+grind,+loot,+tfish,+wander,+rpg maintenance',
        'react' => '',
    ],
    'default' => [
        'label' => 'Default',
        'desc'  => 'Clears all overrides. Bots fall back to server-wide config.',
        'co'    => '',
        'nc'    => '',
        'react' => '',
    ],
];

$flavorFeedback = '';
$flavorError    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guild_form_action']) && $_POST['guild_form_action'] === 'save_guild_flavor') {
    spp_require_csrf('guild_page');
    if (!$isSelectedGuildLeader && !$isGm) {
        $flavorError = 'Only the guild leader can change the bot strategy flavor.';
    } else {
        $newFlavor = isset($_POST['guild_flavor']) ? trim((string)$_POST['guild_flavor']) : '';
        if (!array_key_exists($newFlavor, $guildFlavorProfiles)) {
            $flavorError = 'Invalid flavor selected.';
        } else {
            // Get all guild member GUIDs
            $stmt = $charsPdo->prepare("SELECT guid FROM {$realmDB}.guild_member WHERE guildid=?");
            $stmt->execute([(int)$guildId]);
            $memberGuids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($memberGuids as $memberGuid) {
                $memberGuid = (int)$memberGuid;

                // Clear existing overrides
                $charsPdo->prepare("DELETE FROM {$realmDB}.ai_playerbot_db_store WHERE guid=? AND preset='default'")
                         ->execute([$memberGuid]);

                if ($newFlavor !== 'default') {
                    $fp = $guildFlavorProfiles[$newFlavor];
                    $charsPdo->prepare("INSERT INTO {$realmDB}.ai_playerbot_db_store (guid, preset, `key`, value) VALUES (?,  'default', 'co', ?)")
                             ->execute([$memberGuid, $fp['co']]);
                    $charsPdo->prepare("INSERT INTO {$realmDB}.ai_playerbot_db_store (guid, preset, `key`, value) VALUES (?, 'default', 'nc', ?)")
                             ->execute([$memberGuid, $fp['nc']]);
                    if ($fp['react'] !== '') {
                        $charsPdo->prepare("INSERT INTO {$realmDB}.ai_playerbot_db_store (guid, preset, `key`, value) VALUES (?, 'default', 'react', ?)")
                                 ->execute([$memberGuid, $fp['react']]);
                    }
                }
            }

            $flavorFeedback = 'Guild flavor set to <strong>' . htmlspecialchars($guildFlavorProfiles[$newFlavor]['label']) . '</strong> for ' . count($memberGuids) . ' members. Bots will apply the new strategies on next relog.';
        }
    }
}

// Detect current flavor by checking a sample member's DB store
$currentFlavor = 'default';
$stmt = $charsPdo->prepare(
    "SELECT `key`, value FROM {$realmDB}.ai_playerbot_db_store
     WHERE preset='default'
     AND guid = (SELECT guid FROM {$realmDB}.guild_member WHERE guildid=? LIMIT 1)");
$stmt->execute([(int)$guildId]);
$sampleOverrides = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $sampleOverrides[$row['key']] = $row['value'];
}
if (!empty($sampleOverrides)) {
    foreach ($guildFlavorProfiles as $flavorKey => $fp) {
        if ($flavorKey === 'default') continue;
        if (($sampleOverrides['co'] ?? '') === $fp['co'] && ($sampleOverrides['nc'] ?? '') === $fp['nc']) {
            $currentFlavor = $flavorKey;
            break;
        }
    }
    if ($currentFlavor === 'default') $currentFlavor = 'custom';
}

if (isset($_GET['guild_note_saved']) && (int)$_GET['guild_note_saved'] === 1) {
    $guildNoteFeedback = 'Guild notes updated.';
}
if (isset($_GET['guild_motd_saved']) && (int)$_GET['guild_motd_saved'] === 1) {
    $guildMotdFeedback = 'Guild MOTD updated.';
}

$guildEstablishedLabel = 'Unknown';
try {
    $createdColumn = null;
    foreach (array('createdate', 'create_date', 'created_at') as $candidateColumn) {
        $stmt = $charsPdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='guild' AND COLUMN_NAME=?");
        $stmt->execute([$realmDB, $candidateColumn]);
        $columnExists = $stmt->fetchColumn();
        if ((int)$columnExists > 0) {
            $createdColumn = $candidateColumn;
            break;
        }
    }

    if ($createdColumn !== null) {
        $stmt = $charsPdo->prepare("SELECT `{$createdColumn}` FROM {$realmDB}.guild WHERE guildid=?");
        $stmt->execute([(int)$guildId]);
        $createdValue = $stmt->fetchColumn();
        if (!empty($createdValue) && $createdValue !== '0000-00-00 00:00:00') {
            $createdTs = is_numeric($createdValue) ? (int)$createdValue : strtotime((string)$createdValue);
            if ($createdTs > 0) {
                $guildEstablishedLabel = date('M j, Y', $createdTs);
            }
        }
    }

    if ($guildEstablishedLabel === 'Unknown') {
        $eventTimeColumn = null;
        $stmt = $charsPdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME='guild_eventlog'");
        $stmt->execute([$realmDB]);
        $eventTableExists = $stmt->fetchColumn();
        if ((int)$eventTableExists > 0) {
            foreach (array('TimeStamp', 'timestamp', 'time', 'event_time') as $candidateColumn) {
                $stmt = $charsPdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='guild_eventlog' AND COLUMN_NAME=?");
                $stmt->execute([$realmDB, $candidateColumn]);
                $columnExists = $stmt->fetchColumn();
                if ((int)$columnExists > 0) {
                    $eventTimeColumn = $candidateColumn;
                    break;
                }
            }

            if ($eventTimeColumn !== null) {
                $stmt = $charsPdo->prepare("SELECT MIN(`{$eventTimeColumn}`) FROM {$realmDB}.guild_eventlog WHERE guildid=?");
                $stmt->execute([(int)$guildId]);
                $firstEventValue = $stmt->fetchColumn();
                if (!empty($firstEventValue)) {
                    $firstEventTs = is_numeric($firstEventValue) ? (int)$firstEventValue : strtotime((string)$firstEventValue);
                    if ($firstEventTs > 0) {
                        $guildEstablishedLabel = date('M j, Y', $firstEventTs);
                    }
                }
            }
        }
    }
} catch (Throwable $e) {
    error_log('[guild] Failed resolving established date: ' . $e->getMessage());
}

$stmt = $charsPdo->prepare("SELECT guid, name, race, class, level, gender FROM {$realmDB}.characters WHERE guid=?");
$stmt->execute([(int)$guild['leaderguid']]);
$leader = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $charsPdo->prepare("
    SELECT
      c.guid,
      c.name,
      c.race,
      c.class,
      c.level,
      c.gender,
      gm.rank,
      gm.pnote,
      gm.offnote,
      gr.rname AS rank_name
    FROM {$realmDB}.guild_member gm
    LEFT JOIN {$realmDB}.characters c ON gm.guid = c.guid
    LEFT JOIN {$realmDB}.guild_rank gr ON gr.guildid = gm.guildid AND gr.rid = gm.rank
    WHERE gm.guildid=?
    ORDER BY gm.rank ASC, c.level DESC, c.name ASC
");
$stmt->execute([(int)$guildId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!is_array($members)) {
    $members = [];
}

$memberAverageItemLevels = [];
if (!empty($members)) {
    $memberIds = array_values(array_unique(array_map(static function ($member) {
        return (int)($member['guid'] ?? 0);
    }, $members)));
    $memberIds = array_values(array_filter($memberIds));

    if (!empty($memberIds)) {
        $memberIdSql = implode(',', $memberIds);
        try {
            $stmt = $charsPdo->prepare("
                SELECT
                  ci.guid,
                  ROUND(AVG(it.ItemLevel), 1) AS avg_item_level
                FROM {$realmDB}.character_inventory ci
                INNER JOIN {$realmWorldDB}.item_template it ON it.entry = ci.item_template
                WHERE ci.guid IN ({$memberIdSql})
                  AND ci.bag = 0
                  AND ci.slot BETWEEN 0 AND 18
                  AND ci.slot NOT IN (3, 18)
                  AND ci.item_template > 0
                GROUP BY ci.guid
            ");
            $stmt->execute([]);
            $itemLevelRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (is_array($itemLevelRows)) {
                foreach ($itemLevelRows as $itemLevelRow) {
                    $memberAverageItemLevels[(int)$itemLevelRow['guid']] = round((float)$itemLevelRow['avg_item_level'], 1);
                }
            }
        } catch (Throwable $e) {
            error_log('[guild] Failed loading average item levels: ' . $e->getMessage());
        }
    }
}

$guildMembers = count($members);
$avgLevel = 0;
$maxLevel = 0;
$guildAverageItemLevelTotal = 0.0;
$guildAverageItemLevelCount = 0;
$classBreakdown = [];
$classDisplayOrder = [1, 2, 3, 4, 5, 7, 8, 9, 11, 6];
$rankOptions = [];

foreach ($members as $member) {
    $level = (int)($member['level'] ?? 0);
    $avgLevel += $level;
    if ($level > $maxLevel) $maxLevel = $level;

    $memberItemLevel = (float)($memberAverageItemLevels[(int)($member['guid'] ?? 0)] ?? 0);
    if ($memberItemLevel > 0) {
        $guildAverageItemLevelTotal += $memberItemLevel;
        $guildAverageItemLevelCount++;
    }

    $classId = (int)($member['class'] ?? 0);
    if (!isset($classBreakdown[$classId])) $classBreakdown[$classId] = 0;
    $classBreakdown[$classId]++;

    $rankId = (int)($member['rank'] ?? 0);
    if (!isset($rankOptions[$rankId])) {
        $rankOptions[$rankId] = !empty($member['rank_name']) ? $member['rank_name'] : ('Rank ' . $rankId);
    }
}

$avgLevel = $guildMembers > 0 ? round($avgLevel / $guildMembers, 1) : 0;
$guildAverageItemLevel = $guildAverageItemLevelCount > 0 ? round($guildAverageItemLevelTotal / $guildAverageItemLevelCount, 1) : 0;
$maxGuildRankId = !empty($rankOptions) ? max(array_keys($rankOptions)) : 0;
$orderedClassBreakdown = [];
foreach ($classDisplayOrder as $classId) {
    if (!empty($classBreakdown[$classId])) {
        $orderedClassBreakdown[$classId] = $classBreakdown[$classId];
    }
}
foreach ($classBreakdown as $classId => $classCount) {
    if (!isset($orderedClassBreakdown[$classId])) {
        $orderedClassBreakdown[$classId] = $classCount;
    }
}
$factionName = ($leader && in_array((int)$leader['race'], $allianceRaces, true)) ? 'Alliance' : 'Horde';
$factionSlug = strtolower($factionName);
$crest = 'templates/offlike/images/modern/logo-' . $factionSlug . '.png';
$heroBg = 'templates/offlike/images/modern/' . $factionSlug . '_guild.jpg';
$pageBg = $heroBg;
$motd = trim((string)$guild['motd']) !== '' ? $guild['motd'] : 'No message set.';

$selectedName = trim($_GET['name'] ?? '');
$selectedClass = isset($_GET['class']) ? (int)$_GET['class'] : -1;
$selectedRank = isset($_GET['rank']) ? (int)$_GET['rank'] : -1;
$selectedMax = isset($_GET['maxonly']) ? 1 : 0;
$p = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$itemsPerPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
$sortBy = strtolower(trim($_GET['sort'] ?? 'rank'));
$sortDir = strtoupper(trim($_GET['dir'] ?? 'ASC'));
$allowedSorts = array('name', 'race', 'class', 'level', 'ilvl', 'rank');
if (!in_array($sortBy, $allowedSorts, true)) {
    $sortBy = 'rank';
}
if ($sortDir !== 'ASC' && $sortDir !== 'DESC') {
    $sortDir = 'ASC';
}

$filteredMembers = [];
foreach ($members as $member) {
    if ($selectedName !== '' && stripos((string)$member['name'], $selectedName) === false) continue;
    if ($selectedClass > 0 && (int)$member['class'] !== $selectedClass) continue;
    if ($selectedRank >= 0 && (int)$member['rank'] !== $selectedRank) continue;
    if ($selectedMax && (int)$member['level'] < $maxLevel) continue;
    $filteredMembers[] = $member;
}

if (!empty($filteredMembers)) {
    usort($filteredMembers, function ($left, $right) use ($sortBy, $sortDir, $classNames, $raceNames, $memberAverageItemLevels) {
        return spp_guild_roster_sort_compare($left, $right, $sortBy, $sortDir, $classNames, $raceNames, $memberAverageItemLevels);
    });
}

$totalMembers = count($filteredMembers);
$pageCount = max(1, (int)ceil($totalMembers / $itemsPerPage));
if ($p > $pageCount) $p = $pageCount;
$offset = ($p - 1) * $itemsPerPage;
$membersPage = array_slice($filteredMembers, $offset, $itemsPerPage);
$resultStart = $totalMembers > 0 ? $offset + 1 : 0;
$resultEnd = min($offset + $itemsPerPage, $totalMembers);

$baseUrl = 'index.php?n=server&sub=guild&realm=' . $realmId . '&guildid=' . $guildId . '&per_page=' . $itemsPerPage;
if ($selectedName !== '') $baseUrl .= '&name=' . urlencode($selectedName);
if ($selectedClass > 0) $baseUrl .= '&class=' . $selectedClass;
if ($selectedRank >= 0) $baseUrl .= '&rank=' . $selectedRank;
if ($selectedMax) $baseUrl .= '&maxonly=1';
?>
<style>



.guild-hero {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.95fr);
  gap: 24px;
  padding: 28px 30px 22px;
    background: linear-gradient(180deg, rgba(2, 4, 12, 0.92), rgba(1, 2, 8, 0.98));
  border: 1px solid rgba(255, 196, 0, 0.22);
  border: 1px solid rgba(255, 196, 0, 0.22);
  border-radius: 18px 18px 0 0;
}
.guild-hero-main {
  display: flex;
  align-items: center;
  gap: 22px;
}
.guild-crest {
  width: 92px;
  height: 92px;
  object-fit: contain;
  filter: drop-shadow(0 0 12px rgba(255, 193, 7, 0.22));
}
.guild-title {
  margin: 0 0 8px;
  font-size: 3rem;
  line-height: 0.98;
  color: #fff4c9;
}
.guild-subtitle {
  margin: 0 0 8px;
  font-size: 1.3rem;
  color: #d9c99a;
}
.guild-masterline {
    margin: 0;
    font-size: 1rem;
    color: #f2ddb0;
  }
.guild-masterline strong {
    color: #ffd65e;
  }
  .guild-establishedline {
    margin: 4px 0 0;
    font-size: 0.98rem;
    color: #d7c28e;
  }
  .guild-establishedline strong {
    color: #f4d06a;
    font-weight: 700;
  }
  .guild-meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px 24px;
    align-content: start;
}
.guild-meta-card {
  min-width: 0;
}
.guild-meta-label {
  display: block;
  margin-bottom: 6px;
  font-size: 0.82rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #c4b27c;
}
.guild-meta-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #ffd65e;
}
.guild-shell {
  display: grid;
  grid-template-columns: minmax(0, 1.75fr) minmax(320px, 0.95fr);
  gap: 18px;
  align-items: stretch;
  padding: 26px 24px 30px;
  overflow: hidden;
  border-top: 0;
  border-radius: 0 0 18px 18px;
}
.guild-side-stack {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  min-width: 0;
  height: 100%;
  align-self: stretch;
}
.guild-section {
  padding: 22px 24px 18px;
  background: rgba(4, 8, 18, 0.34);
  border: 1px solid rgba(255, 204, 72, 0.18);
  border-radius: 16px;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
  min-width: 0;
}
.guild-roster-panel {
  min-width: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  align-self: stretch;
}
.guild-motd-panel {
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  padding-bottom: 20px;
}
.guild-insights-panel {
  flex: 1 1 auto;
  border-top: 0;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}
.guild-panel-title,
.guild-side-title {
  margin: 0 0 16px;
  font-size: 1.9rem;
  color: #fff7d1;
}
.guild-side-title {
  font-size: 1.55rem;
}
.guild-filter-grid {
  display: grid;
  grid-template-columns: minmax(240px, 1.7fr) 180px 180px auto;
  gap: 14px;
  margin-bottom: 16px;
}
.guild-input,
.guild-select {
  width: 90%;
  height: 46px;
  padding: 0 14px;
  color: #f8f1d4;
  background: rgba(4, 6, 16, 0.94);
  border: 1px solid rgba(255, 196, 0, 0.75);
}
.guild-check {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: #f4d05c;
  font-weight: 700;
}
.guild-summary {
  margin: 4px 0 14px;
  color: #ffcc66;
  font-weight: 700;
}
.guild-roster-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin: 4px 0 14px;
  flex-wrap: wrap;
}
.guild-wip-note {
  padding: 10px 14px;
  border-radius: 999px;
  border: 1px solid rgba(255, 204, 72, 0.2);
  background: rgba(255, 204, 72, 0.08);
  color: #d9c99a;
  font-size: 0.92rem;
}
.guild-note-banner {
  margin: 0 0 14px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid rgba(255, 204, 72, 0.22);
  background: rgba(255, 204, 72, 0.08);
  color: #ffe39a;
}
.guild-note-banner.is-error {
  border-color: rgba(255, 122, 122, 0.3);
  background: rgba(95, 16, 16, 0.4);
  color: #ffd5d5;
}
.guild-roster {
  width: 100%;
  min-width: 1100px;
  border-collapse: collapse;
  background: rgba(10, 10, 18, 0.72);
}
.guild-roster-table-wrap {
  flex: 1 1 auto;
  max-width: 100%;
  min-height: 0;
  height: 620px;
  max-height: 620px;
  overflow: auto;
  overscroll-behavior: contain;
  padding-bottom: 10px;
  scrollbar-gutter: stable both-edges;
}
.guild-roster-table-wrap::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}
.guild-roster-table-wrap::-webkit-scrollbar-thumb {
  background: rgba(255, 204, 72, 0.28);
  border-radius: 999px;
}
.guild-roster thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: rgba(10, 10, 18, 0.96);
  padding: 14px 16px;
  text-align: left;
  font-size: 0.95rem;
  color: #ffc21c;
  border-bottom: 1px solid rgba(255, 204, 72, 0.28);
}
.guild-roster thead th a {
  color: inherit;
  text-decoration: none;
  font-weight: 700;
}
.guild-roster thead th a:hover,
.guild-roster thead th a.is-active {
  color: #fff1b0;
}
.guild-roster tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid rgba(255, 204, 72, 0.14);
  vertical-align: middle;
}
.guild-roster tbody td.guild-notes-cell {
  min-width: 210px;
}
.guild-roster thead th.guild-note-col,
.guild-roster tbody td.guild-note-col {
  width: 180px;
}
.guild-roster tbody tr:nth-child(odd) {
  background: rgba(255, 255, 255, 0.03);
}
.guild-note-stack {
  display: grid;
  gap: 8px;
}
.guild-note-line {
  display: grid;
  gap: 4px;
}
.guild-note-card {
  display: grid;
  gap: 6px;
}
.guild-note-label {
  color: #c4b27c;
  font-size: 0.76rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
.guild-note-value {
  color: #f3e6bf;
  line-height: 1.4;
}
.guild-note-value.is-empty {
  color: #8f7d57;
  font-style: italic;
}
.guild-note-form {
  display: grid;
  gap: 10px;
}
.guild-note-form.is-compact {
  gap: 8px;
}
.guild-note-input {
  width: 10rem;
  min-height: 40px;
  padding: 0 22px;
  color: #f8f1d4;
  background: rgba(4, 6, 16, 0.94);
  border: 1px solid rgba(255, 196, 0, 0.4);
  border-radius: 10px;
}
.guild-note-help {
  color: #9f8b60;
  font-size: 0.84rem;
}
.guild-note-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}
.guild-note-actions.is-compact {
  flex-direction: column;
  align-items: stretch;
  gap: 8px;
}
.guild-note-save {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 38px;
  padding: 0 14px;
  border-radius: 999px;
  border: 1px solid rgba(255, 204, 72, 0.3);
  background: linear-gradient(180deg, #ffd87a, #d9a63d);
  color: #120d03;
  font-weight: 800;
  cursor: pointer;
}
.guild-note-save.is-compact {
  width: 100%;
}
.guild-action-cell {
  min-width: 116px;
}
.guild-action-form {
  margin: 0;
}
.guild-action-buttons {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: nowrap;
}
.guild-action-btn {
  min-height: 34px;
  min-width: 0;
  padding: 0 8px;
  border-radius: 999px;
  border: 1px solid rgba(255, 204, 72, 0.26);
  background: rgba(255, 204, 72, 0.1);
  color: #ffe39a;
  font-weight: 700;
  cursor: pointer;
  font-size: 0.82rem;
  line-height: 1;
  white-space: nowrap;
}
.guild-action-btn.is-symbol {
  min-width: 40px;
  padding: 0;
  font-size: 1rem;
  line-height: 1;
}
.guild-action-btn:hover {
  background: rgba(255, 204, 72, 0.18);
}
.guild-action-btn.is-danger {
  border-color: rgba(255, 122, 122, 0.35);
  background: rgba(120, 26, 26, 0.35);
  color: #ffd5d5;
}
.guild-action-placeholder {
  color: #8f7d57;
  font-style: italic;
}
.guild-member {
  display: flex;
  align-items: center;
  gap: 12px;
}
.guild-portrait,
.guild-class-icon,
.guild-race-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid rgba(255, 198, 0, 0.45);
  object-fit: cover;
  background: #050505;
}
.guild-rank {
  color: #d6bc7e;
}
.guild-side-copy {
  margin: 0;
  line-height: 1.55;
  color: #d9c99a;
}
.guild-motd-form {
  display: grid;
  gap: 12px;
}
.guild-motd-input {
  width: 90%;
  min-height: 92px;
  padding: 12px 14px;
  color: #f8f1d4;
  background: rgba(4, 6, 16, 0.94);
  border: 1px solid rgba(255, 196, 0, 0.4);
  border-radius: 14px;
  resize: vertical;
  line-height: 1.5;
}
.guild-motd-input:focus {
  outline: none;
  border-color: rgba(255, 220, 112, 0.8);
  box-shadow: 0 0 0 2px rgba(255, 196, 0, 0.12);
}
.guild-motd-help {
  display: grid;
  gap: 8px;
  color: #d7c28e;
  font-size: 0.92rem;
  line-height: 1.55;
}
.guild-motd-help strong {
  color: #ffe39a;
}
.guild-motd-example {
  margin: 0;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid rgba(255, 196, 0, 0.18);
  background: rgba(255, 204, 72, 0.08);
  color: #fff0bf;
  font-family: Consolas, "Courier New", monospace;
  font-size: 0.9rem;
}
.guild-motd-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.guild-motd-status {
  color: #cdbb8b;
  font-size: 0.88rem;
}
.guild-motd-current {
  margin: 0 0 12px;
}
.guild-divider {
  margin-top: 18px;
  padding-top: 18px;
  border-top: 1px solid rgba(255, 204, 72, 0.16);
}
.guild-breakdown {
  display: grid;
  gap: 10px;
}
.guild-breakdown-row {
  display: grid;
  grid-template-columns: 110px 1fr 34px;
  gap: 12px;
  align-items: center;
}
.guild-breakdown-label {
  color: var(--class-color, #e5d4a0);
  font-weight: 700;
}
.guild-breakdown-bar {
  height: 14px;
  border-radius: 999px;
  background: rgba(255,255,255,0.08);
  overflow: hidden;
}
.guild-breakdown-fill {
  height: 100%;
  border-radius: 999px;
  box-shadow: 0 0 14px rgba(var(--class-color-rgb, 255, 204, 72), 0.22);
}
.guild-breakdown-row > div:last-child {
  color: var(--class-color, #f3e6bf);
  font-weight: 700;
}
.guild-level-breakdown {
  margin-top: 18px;
}
.guild-level-columns {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(34px, 44px));
  justify-content: center;
  gap: 8px;
  align-items: end;
  min-height: 190px;
  margin-top: 12px;
}
.guild-level-column {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.guild-level-value {
  color: #f3d58d;
  font-weight: 700;
  font-size: 0.84rem;
}
.guild-level-track {
  display: flex;
  align-items: end;
  justify-content: center;
  width: 100%;
  height: 140px;
  padding: 6px;
  border-radius: 12px 12px 8px 8px;
  background:
    linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01)),
    repeating-linear-gradient(
      to top,
      rgba(255,255,255,0.05) 0,
      rgba(255,255,255,0.05) 1px,
      transparent 1px,
      transparent 20%
    );
  box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
}
.guild-level-fill {
  width: 100%;
  min-height: 8px;
  border-radius: 8px 8px 6px 6px;
  background: var(--class-color, #ffcc66);
}
.guild-level-label {
  color: var(--class-color, #f3e6bf);
  font-weight: 700;
  font-size: 0.74rem;
  text-align: center;
  line-height: 1.15;
}
.class-warrior { --class-color:#C79C6E; --class-color-rgb:199,156,110; }
.class-mage { --class-color:#69CCF0; --class-color-rgb:105,204,240; }
.class-priest { --class-color:#FFFFFF; --class-color-rgb:255,255,255; }
.class-hunter { --class-color:#ABD473; --class-color-rgb:171,212,115; }
.class-rogue { --class-color:#FFF569; --class-color-rgb:255,245,105; }
.class-warlock { --class-color:#9482C9; --class-color-rgb:148,130,201; }
.class-paladin { --class-color:#F58CBA; --class-color-rgb:245,140,186; }
.class-druid { --class-color:#FF7D0A; --class-color-rgb:255,125,10; }
.class-shaman { --class-color:#0070DE; --class-color-rgb:0,112,222; }
.class-deathknight { --class-color:#C41F3B; --class-color-rgb:196,31,59; }
.class-priest .guild-breakdown-fill {
  box-shadow: inset 0 0 0 1px rgba(12, 12, 18, 0.55), 0 0 14px rgba(255,255,255,0.28);
}
[class*="class-"] a {
  color: var(--class-color);
  text-decoration: none;
  font-weight: 700;
}
@media (max-width: 1366px) {
  .guild-hero,
  .guild-shell {
    grid-template-columns: 1fr;
  }
  .guild-roster {
    min-width: 1000px;
  }
  .guild-roster-table-wrap {
    height: 520px;
    max-height: 520px;
  }
  .guild-side-stack {
    min-height: auto;
  }
  .guild-motd-panel {
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
    padding-bottom: 18px;
  }
  .guild-insights-panel {
    border-top: 1px solid rgba(255, 204, 72, 0.18);
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
    margin-top: 18px;
  }
  .guild-roster thead th,
  .guild-roster tbody td {
    padding: 12px 10px;
  }
  .guild-roster tbody td.guild-notes-cell {
    min-width: 170px;
  }
  .guild-roster thead th.guild-note-col,
  .guild-roster tbody td.guild-note-col {
    width: 150px;
  }
  .guild-action-cell {
    min-width: 128px;
  }
}
@media (max-width: 760px) {
  .guild-filter-grid {
    grid-template-columns: 1fr;
  }
  .guild-title {
    font-size: 2.2rem;
  }
  .guild-meta {
    grid-template-columns: 1fr 1fr;
  }
}
</style>

<?php $maxBreakdown = $classBreakdown ? max($classBreakdown) : 1; ?>
<?php
$guildClassLevelBuckets = [];
foreach ($members as $member) {
  $classId = (int)($member['class'] ?? 0);
  $level = (int)($member['level'] ?? 0);
  if ($classId <= 0 || $level <= 0) {
    continue;
  }
  if (!isset($guildClassLevelBuckets[$classId])) {
    $guildClassLevelBuckets[$classId] = [];
  }
  $guildClassLevelBuckets[$classId][] = $level;
}

$guildClassLevelCards = [];
$guildMedianLevelMax = 0;
foreach ($orderedClassBreakdown as $classId => $classCount) {
  $levels = $guildClassLevelBuckets[$classId] ?? [];
  sort($levels, SORT_NUMERIC);
  $levelCount = count($levels);
  $medianLevel = 0;
  if ($levelCount > 0) {
    $middle = (int)floor(($levelCount - 1) / 2);
    if ($levelCount % 2 === 0) {
      $medianLevel = (int)round(($levels[$middle] + $levels[$middle + 1]) / 2);
    } else {
      $medianLevel = (int)$levels[$middle];
    }
  }
  $guildMedianLevelMax = max($guildMedianLevelMax, $medianLevel);
  $guildClassLevelCards[$classId] = $medianLevel;
}
?>
<div class="guild-page">
<div class="guild-detail">
  <div class="guild-hero">
      <div class="guild-hero-main">
        <img class="guild-crest" src="<?php echo $crest; ?>" alt="<?php echo htmlspecialchars($factionName); ?>">
        <div>
          <h1 class="guild-title"><?php echo htmlspecialchars($guild['name']); ?></h1>
          <p class="guild-subtitle"><?php echo htmlspecialchars($armoryRealm); ?></p>
          <p class="guild-masterline">Guild Master <strong><?php echo $leader ? htmlspecialchars($leader['name']) : 'Unknown'; ?></strong></p>
          <p class="guild-establishedline">Established <strong><?php echo htmlspecialchars($guildEstablishedLabel); ?></strong></p>
        </div>
      </div>
      <div class="guild-meta">
        <div class="guild-meta-card">
          <span class="guild-meta-label">Faction</span>
        <span class="guild-meta-value"><?php echo htmlspecialchars($factionName); ?></span>
      </div>
      <div class="guild-meta-card">
        <span class="guild-meta-label">Members</span>
        <span class="guild-meta-value"><?php echo $guildMembers; ?></span>
      </div>
      <div class="guild-meta-card">
        <span class="guild-meta-label">Average Level</span>
        <span class="guild-meta-value"><?php echo $avgLevel; ?></span>
      </div>
        <div class="guild-meta-card">
          <span class="guild-meta-label">Average iLvl</span>
          <span class="guild-meta-value"><?php echo $guildAverageItemLevel > 0 ? number_format($guildAverageItemLevel, 1) : '-'; ?></span>
        </div>
      </div>
    </div>

  <div class="guild-shell">
    <section class="guild-section guild-roster-panel">
      <h2 class="guild-panel-title">Guild Roster</h2>
      <form method="get" class="guild-filter-grid">
        <input type="hidden" name="n" value="server">
        <input type="hidden" name="sub" value="guild">
        <input type="hidden" name="realm" value="<?php echo $realmId; ?>">
        <input type="hidden" name="guildid" value="<?php echo $guildId; ?>">
        <input type="hidden" name="p" value="1">
        <input type="hidden" name="per_page" value="<?php echo $itemsPerPage; ?>">

        <input class="guild-input" type="text" name="name" value="<?php echo htmlspecialchars($selectedName); ?>" placeholder="Search member name...">
        <select class="guild-select" name="class">
          <option value="-1">All Classes</option>
          <?php foreach($classNames as $classId => $className): ?>
            <option value="<?php echo $classId; ?>"<?php echo $selectedClass === $classId ? ' selected' : ''; ?>><?php echo htmlspecialchars($className); ?></option>
          <?php endforeach; ?>
        </select>
        <select class="guild-select" name="rank">
          <option value="-1">All Ranks</option>
          <?php foreach($rankOptions as $rankId => $rankName): ?>
            <option value="<?php echo (int)$rankId; ?>"<?php echo $selectedRank === (int)$rankId ? ' selected' : ''; ?>><?php echo htmlspecialchars($rankName); ?></option>
          <?php endforeach; ?>
        </select>
        <label class="guild-check"><input type="checkbox" name="maxonly" value="1"<?php echo $selectedMax ? ' checked' : ''; ?> onchange="this.form.submit()"> Max Level Only</label>
        </form>

      <div class="guild-roster-toolbar">
        <div class="guild-summary">Showing <?php echo $resultStart; ?>-<?php echo $resultEnd; ?> of <?php echo $totalMembers; ?> members</div>
      </div>
      <?php if ($guildNoteFeedback !== ''): ?><div class="guild-note-banner"><?php echo htmlspecialchars($guildNoteFeedback); ?></div><?php endif; ?>
      <?php if ($guildNoteError !== ''): ?><div class="guild-note-banner is-error"><?php echo htmlspecialchars($guildNoteError); ?></div><?php endif; ?>

      <?php if ($canEditGuildNotes): ?>
        <form method="post" id="guild-note-bulk-form">
          <input type="hidden" name="guild_form_action" value="save_guild_data">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($guildCsrfToken, ENT_QUOTES); ?>">
        </form>
      <?php endif; ?>
      <div class="guild-roster-table-wrap">
      <table class="guild-roster">
        <thead>
          <tr>
            <th><a class="<?php echo $sortBy === 'name' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'name', $sortBy, $sortDir)); ?>">Name<?php echo $sortBy === 'name' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'race' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'race', $sortBy, $sortDir)); ?>">Race<?php echo $sortBy === 'race' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'class' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'class', $sortBy, $sortDir)); ?>">Class<?php echo $sortBy === 'class' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'level' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'level', $sortBy, $sortDir)); ?>">Level<?php echo $sortBy === 'level' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'ilvl' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'ilvl', $sortBy, $sortDir)); ?>">Avg iLvl<?php echo $sortBy === 'ilvl' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></th>
            <th><a class="<?php echo $sortBy === 'rank' ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(spp_guild_roster_sort_url($baseUrl, 'rank', $sortBy, $sortDir)); ?>">Guild Rank<?php echo $sortBy === 'rank' ? ($sortDir === 'ASC' ? ' ↑' : ' ↓') : ''; ?></a></th>
            <th class="guild-note-col">Public Note</th>
            <?php if ($canViewOfficerNotes): ?>
              <th class="guild-note-col">Officer Note</th>
            <?php endif; ?>
            <?php if ($canManageGuildRoster): ?>
              <th class="guild-action-cell">Actions</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (count($membersPage)): ?>
            <?php foreach($membersPage as $member): ?>
              <?php
                $memberClassName = $classNames[(int)$member['class']] ?? 'Unknown';
                $memberClassSlug = strtolower(str_replace(' ', '', $memberClassName));
                $memberRaceName = $raceNames[(int)$member['race']] ?? 'Unknown';
                $portrait = get_character_portrait_path($member['guid'], $member['gender'], $member['race'], $member['class']);
                $publicNoteValue = trim((string)($member['pnote'] ?? ''));
                $officerNoteValue = trim((string)($member['offnote'] ?? ''));
              ?>
              <tr>
                <td>
                  <div class="guild-member class-<?php echo $memberClassSlug; ?>">
                    <img class="guild-portrait" src="<?php echo $portrait; ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                    <a href="index.php?n=server&sub=character&realm=<?php echo (int)$realmId; ?>&character=<?php echo urlencode($member['name']); ?>"><?php echo htmlspecialchars($member['name']); ?></a>
                  </div>
                </td>
                <td><img class="guild-race-icon" src="<?php echo htmlspecialchars(spp_race_icon_url($member['race'], $member['gender'])); ?>" alt="<?php echo htmlspecialchars($memberRaceName); ?>" title="<?php echo htmlspecialchars($memberRaceName); ?>"></td>
                <td><img class="guild-class-icon" src="<?php echo htmlspecialchars(spp_class_icon_url($member['class'])); ?>" alt="<?php echo htmlspecialchars($memberClassName); ?>" title="<?php echo htmlspecialchars($memberClassName); ?>"></td>
                <td><?php echo (int)$member['level']; ?></td>
                <td><?php echo !empty($memberAverageItemLevels[(int)$member['guid']]) ? number_format((float)$memberAverageItemLevels[(int)$member['guid']], 1) : '-'; ?></td>
                <td class="guild-rank"><?php echo htmlspecialchars(!empty($member['rank_name']) ? $member['rank_name'] : ('Rank ' . (int)$member['rank'])); ?></td>
                <td class="guild-notes-cell guild-note-col">
                  <?php if ($canEditGuildNotes): ?>
                    <input class="guild-note-input" id="pnote-<?php echo (int)$member['guid']; ?>" type="text" name="pnote[<?php echo (int)$member['guid']; ?>]" maxlength="31" value="<?php echo htmlspecialchars($publicNoteValue); ?>" form="guild-note-bulk-form">
                  <?php else: ?>
                    <div class="guild-note-card">
                      <div class="guild-note-value<?php echo $publicNoteValue === '' ? ' is-empty' : ''; ?>"><?php echo $publicNoteValue !== '' ? htmlspecialchars($publicNoteValue) : 'No public note'; ?></div>
                    </div>
                  <?php endif; ?>
                </td>
                <?php if ($canViewOfficerNotes): ?>
                  <td class="guild-notes-cell guild-note-col">
                    <?php if ($canEditGuildNotes): ?>
                      <input class="guild-note-input" id="offnote-<?php echo (int)$member['guid']; ?>" type="text" name="offnote[<?php echo (int)$member['guid']; ?>]" maxlength="31" value="<?php echo htmlspecialchars($officerNoteValue); ?>" form="guild-note-bulk-form">
                    <?php else: ?>
                      <div class="guild-note-card">
                        <div class="guild-note-value<?php echo $officerNoteValue === '' ? ' is-empty' : ''; ?>"><?php echo $officerNoteValue !== '' ? htmlspecialchars($officerNoteValue) : 'No officer note'; ?></div>
                      </div>
                    <?php endif; ?>
                  </td>
                <?php endif; ?>
                <?php if ($canManageGuildRoster): ?>
                  <td class="guild-action-cell">
                    <?php if ((int)$member['guid'] === (int)$guild['leaderguid']): ?>
                      <span class="guild-action-placeholder">Guild leader</span>
                    <?php else: ?>
                      <form method="post" class="guild-action-form">
                        <input type="hidden" name="guild_roster_action" value="manage_member">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($guildCsrfToken, ENT_QUOTES); ?>">
                        <input type="hidden" name="target_guid" value="<?php echo (int)$member['guid']; ?>">
                        <div class="guild-action-buttons">
                          <button class="guild-action-btn is-symbol" type="submit" name="guild_roster_action_type" value="rank_up" title="Rank Up" aria-label="Rank Up"<?php echo (int)$member['rank'] <= 1 ? ' disabled' : ''; ?>>↑</button>
                          <button class="guild-action-btn is-symbol" type="submit" name="guild_roster_action_type" value="rank_down" title="Rank Down" aria-label="Rank Down"<?php echo (int)$member['rank'] >= $maxGuildRankId ? ' disabled' : ''; ?>>↓</button>
                          <button class="guild-action-btn is-danger" type="submit" name="guild_roster_action_type" value="kick" onclick="return confirm('Kick <?php echo htmlspecialchars(addslashes((string)$member['name'])); ?> from the guild?');">Kick</button>
                        </div>
                      </form>
                    <?php endif; ?>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="<?php echo 7 + ($canViewOfficerNotes ? 1 : 0) + ($canManageGuildRoster ? 1 : 0); ?>">No roster members matched the current filter.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      </div>

      <?php if ($pageCount > 1): ?>
        <div class="pagination-controls"><div class="page-links"><?php echo compact_paginate($p, $pageCount, $baseUrl); ?></div></div>
      <?php endif; ?>

      <?php if ($canEditGuildNotes): ?>
        <div style="margin-top:10px;">
          <button class="guild-note-save" type="submit" name="guild_submit_mode" value="all_notes" form="guild-note-bulk-form">Save All Notes &amp; MOTD</button>
        </div>
      <?php endif; ?>
    </section>

    <div class="guild-side-stack">
      <section class="guild-section guild-motd-panel">
        <h3 class="guild-side-title">Message Of The Day</h3>
        <?php if ($guildMotdFeedback !== ''): ?>
          <div class="guild-note-banner"><?php echo htmlspecialchars($guildMotdFeedback); ?></div>
        <?php endif; ?>
        <?php if ($guildMotdError !== ''): ?>
          <div class="guild-note-banner is-error"><?php echo htmlspecialchars($guildMotdError); ?></div>
        <?php endif; ?>

        <?php if ($canEditGuildMotd): ?>
          <?php if ($canEditGuildNotes): ?>
            <div class="guild-motd-form">
              <textarea class="guild-motd-input" name="guild_motd" maxlength="128" form="guild-note-bulk-form"><?php echo htmlspecialchars((string)($guild['motd'] ?? '')); ?></textarea>
              <div class="guild-motd-actions">
                <span class="guild-motd-status">Saved together with the roster from the Save All Notes button.</span>
              </div>
              <div class="guild-motd-help">
                <div><strong>Guild meetings format:</strong> <code>Meeting:[location] [start time] [end time]</code></div>
                <p class="guild-motd-example">Meeting:Stormwind City 15:00 18:00</p>
                <p class="guild-motd-example">Meeting:Stormwind City 3:00pm 6:00pm</p>
                <div>Bots begin traveling 30 minutes before the start time and stay until the end time.</div>
              </div>
            </div>
          <?php else: ?>
            <form class="guild-motd-form" method="post">
              <input type="hidden" name="guild_form_action" value="save_guild_data">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($guildCsrfToken, ENT_QUOTES); ?>">
              <textarea class="guild-motd-input" name="guild_motd" maxlength="128"><?php echo htmlspecialchars((string)($guild['motd'] ?? '')); ?></textarea>
              <div class="guild-motd-actions">
                <button class="guild-note-save" type="submit" name="guild_submit_mode" value="motd_only">Save MOTD</button>
                <span class="guild-motd-status">Writes to the live in-game guild MOTD.</span>
              </div>
              <div class="guild-motd-help">
                <div><strong>Guild meetings format:</strong> <code>Meeting:[location] [start time] [end time]</code></div>
                <p class="guild-motd-example">Meeting:Stormwind City 15:00 18:00</p>
                <p class="guild-motd-example">Meeting:Stormwind City 3:00pm 6:00pm</p>
                <div>Bots begin traveling 30 minutes before the start time and stay until the end time.</div>
              </div>
            </form>
          <?php endif; ?>
        <?php else: ?>
          <p class="guild-side-copy guild-motd-current"><?php echo htmlspecialchars($motd); ?></p>
          <div class="guild-motd-help">
            <div><strong>Guild meetings format:</strong> <code>Meeting:[location] [start time] [end time]</code></div>
            <p class="guild-motd-example">Meeting:Stormwind City 15:00 18:00</p>
            <div>Set by the guild leader in-game or from the website to direct bot meetings.</div>
          </div>
        <?php endif; ?>
      </section>

      <?php if ($isSelectedGuildLeader || $isGm): ?>
      <section class="guild-section">
        <h3 class="guild-side-title">Bot Strategy Flavor</h3>
        <p class="guild-side-copy">
          Sets the AI strategy profile for all bots in this guild.
          Changes take effect on each bot's next relog.
          Currently: <strong><?php echo htmlspecialchars(ucfirst($currentFlavor)); ?></strong>
        </p>

        <?php if ($flavorFeedback !== ''): ?>
          <div style="background:#1a3a1a;border:1px solid #3a6a3a;border-radius:4px;padding:8px 10px;margin-bottom:10px;font-size:13px;color:#7ec87e;">
            <?php echo $flavorFeedback; ?>
          </div>
        <?php endif; ?>
        <?php if ($flavorError !== ''): ?>
          <div style="background:#3a1a1a;border:1px solid #6a3a3a;border-radius:4px;padding:8px 10px;margin-bottom:10px;font-size:13px;color:#e07e7e;">
            <?php echo htmlspecialchars($flavorError); ?>
          </div>
        <?php endif; ?>

        <form method="post" style="margin-top:8px;">
          <input type="hidden" name="guild_form_action" value="save_guild_flavor">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($guildCsrfToken, ENT_QUOTES); ?>">
          <select name="guild_flavor" style="width:100%;background:#1a1a1a;border:1px solid #444;color:#ddd;padding:6px 8px;border-radius:4px;font-size:13px;margin-bottom:8px;">
            <?php foreach ($guildFlavorProfiles as $fKey => $fData): ?>
              <option value="<?php echo htmlspecialchars($fKey); ?>"
                <?php echo ($currentFlavor === $fKey ? 'selected' : ''); ?>>
                <?php echo htmlspecialchars($fData['label']); ?> — <?php echo htmlspecialchars($fData['desc']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" style="background:#2a3a4a;color:#7ec8e3;border:none;border-radius:4px;padding:6px 14px;font-size:13px;font-weight:600;cursor:pointer;">
            Apply Flavor
          </button>
        </form>
      </section>
      <?php endif; ?>

      <section class="guild-section guild-insights-panel">
        <h3 class="guild-side-title">Roster Overview</h3>
        <p class="guild-side-copy"><?php echo $guildMembers; ?> members, average level <?php echo $avgLevel; ?>, guild average iLvl <?php echo $guildAverageItemLevel > 0 ? number_format($guildAverageItemLevel, 1) : '-'; ?>, max level <?php echo $maxLevel; ?>.</p>

        <div class="guild-divider">
          <h3 class="guild-side-title">Class Breakdown</h3>
          <div class="guild-breakdown">
            <?php foreach($orderedClassBreakdown as $classId => $classCount): ?>
              <?php
                $breakClassName = $classNames[$classId] ?? ('Class ' . $classId);
                $breakClassSlug = strtolower(str_replace(' ', '', $breakClassName));
                $breakWidth = $maxBreakdown > 0 ? round(($classCount / $maxBreakdown) * 100, 1) : 0;
              ?>
              <div class="guild-breakdown-row class-<?php echo $breakClassSlug; ?>">
                <div class="guild-breakdown-label"><?php echo htmlspecialchars($breakClassName); ?></div>
                <div class="guild-breakdown-bar"><div class="guild-breakdown-fill" style="width: <?php echo $breakWidth; ?>%; background: var(--class-color);"></div></div>
                <div><?php echo (int)$classCount; ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if (!empty($guildClassLevelCards)): ?>
            <div class="guild-level-breakdown">
              <h3 class="guild-side-title">Typical Class Level</h3>
              <p class="guild-side-copy">Median level per class inside this guild.</p>
              <div class="guild-level-columns">
                <?php foreach($orderedClassBreakdown as $classId => $classCount): ?>
                  <?php
                    $levelClassName = $classNames[$classId] ?? ('Class ' . $classId);
                    $levelClassSlug = strtolower(str_replace(' ', '', $levelClassName));
                    $medianLevel = (int)($guildClassLevelCards[$classId] ?? 0);
                    $height = $guildMedianLevelMax > 0 ? max(4, (int)round(($medianLevel / $guildMedianLevelMax) * 100)) : 0;
                  ?>
                  <div class="guild-level-column class-<?php echo $levelClassSlug; ?>">
                    <div class="guild-level-value"><?php echo $medianLevel; ?></div>
                    <div class="guild-level-track">
                      <div class="guild-level-fill" style="height: <?php echo $height; ?>%;"></div>
                    </div>
                    <div class="guild-level-label"><?php echo htmlspecialchars($levelClassName); ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </div>
</div>
</div>
</div>
