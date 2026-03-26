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
            return '/armory/images/icons/64x64/404.png';
        }

        return '/armory/images/icons/64x64/class-' . $classId . '.' . $extensions[$classId];
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
            return '/armory/images/icons/64x64/404.png';
        }

        return '/armory/images/icons/64x64/' . $icons[$raceId] . '.png';
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

$guild = $DB->selectRow("SELECT guildid, name, leaderguid, motd FROM {$realmDB}.guild WHERE guildid=?d", $guildId);
if (!$guild) {
    echo "<div style='padding:24px;color:#f5c46b;'>Guild not found.</div>";
    return;
}

$selectedWebsiteCharacterId = 0;
if (isset($user['id']) && (int)$user['id'] > 0) {
    $selectedWebsiteCharacterId = (int)$DB->selectCell(
        "SELECT character_id FROM website_accounts WHERE account_id=?d",
        (int)$user['id']
    );
}
$selectedGuildMember = null;
if ($selectedWebsiteCharacterId > 0) {
    $selectedGuildMember = $DB->selectRow(
        "SELECT gm.guid, gm.rank, COALESCE(gr.rights, 0) AS rank_rights
         FROM {$realmDB}.guild_member gm
         LEFT JOIN {$realmDB}.guild_rank gr ON gr.guildid = gm.guildid AND gr.rid = gm.rank
         WHERE gm.guildid=?d AND gm.guid=?d",
        $guildId,
        $selectedWebsiteCharacterId
    );
}
$isSelectedGuildLeader = $selectedWebsiteCharacterId > 0 && $selectedWebsiteCharacterId === (int)$guild['leaderguid'];
$selectedGuildRankRights = isset($selectedGuildMember['rank_rights']) ? (int)$selectedGuildMember['rank_rights'] : 0;
$guildSetMotdRight = 4096;
$guildViewOfficerNoteRight = 16384;
$canEditGuildNotes = $isSelectedGuildLeader;
$canViewOfficerNotes = $isSelectedGuildLeader || (($selectedGuildRankRights & $guildViewOfficerNoteRight) === $guildViewOfficerNoteRight);
$canEditGuildMotd = $isSelectedGuildLeader || (($selectedGuildRankRights & $guildSetMotdRight) === $guildSetMotdRight);
$canManageGuildRoster = $isSelectedGuildLeader;
$guildNoteFeedback = '';
$guildNoteError = '';
$guildMotdFeedback = '';
$guildMotdError = '';
$guildReturnUrl = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : ('index.php?n=server&sub=guild&realm=' . $realmId . '&guildid=' . $guildId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guild_roster_action']) && $_POST['guild_roster_action'] === 'manage_member') {
    if (!$canManageGuildRoster) {
        $guildNoteError = 'Only the selected guild leader can manage guild members from the website.';
    } else {
        $actionType = isset($_POST['guild_roster_action_type']) ? trim((string)$_POST['guild_roster_action_type']) : '';
        $targetGuid = isset($_POST['target_guid']) ? (int)$_POST['target_guid'] : 0;
        $targetMember = $DB->selectRow(
            "SELECT c.guid, c.name, gm.rank
             FROM {$realmDB}.guild_member gm
             INNER JOIN {$realmDB}.characters c ON c.guid = gm.guid
             WHERE gm.guildid=?d AND gm.guid=?d",
            $guildId,
            $targetGuid
        );

        if (!$targetMember) {
            $guildNoteError = 'That guild member could not be found.';
        } elseif ((int)$targetMember['guid'] === (int)$guild['leaderguid']) {
            $guildNoteError = 'The guild leader cannot be managed from the website.';
        } else {
            $targetName = (string)$targetMember['name'];
            $targetRank = (int)$targetMember['rank'];
            $maxGuildRankId = (int)$DB->selectCell("SELECT COALESCE(MAX(rid), 0) FROM {$realmDB}.guild_rank WHERE guildid=?d", $guildId);
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
    $submitMode = isset($_POST['guild_submit_mode']) ? (string)$_POST['guild_submit_mode'] : '';
    $shouldSaveMotd = in_array($submitMode, ['motd_only', 'all_notes'], true);
    $shouldSaveNotes = $submitMode === 'all_notes';

    if ($shouldSaveMotd) {
        if (!$canEditGuildMotd) {
            $guildMotdError = 'Your selected guild character does not have permission to update the guild MOTD.';
        } else {
            $newMotd = substr(trim((string)($_POST['guild_motd'] ?? '')), 0, 128);
            $DB->query(
                "UPDATE {$realmDB}.guild SET motd=?s WHERE guildid=?d",
                $newMotd,
                $guildId
            );
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
                $validMemberGuids = $DB->selectCol(
                    "SELECT guid FROM {$realmDB}.guild_member WHERE guildid=?d AND guid IN (?a)",
                    $guildId,
                    $noteGuids
                );

                if (count($validMemberGuids) === 0) {
                    $guildNoteError = 'No valid guild members were found for the submitted notes.';
                } else {
                    foreach ($validMemberGuids as $validGuid) {
                        $validGuid = (int)$validGuid;
                        $publicNote = substr(trim((string)($publicNotes[$validGuid] ?? '')), 0, 31);
                        $officerNote = substr(trim((string)($officerNotes[$validGuid] ?? '')), 0, 31);
                        $DB->query(
                            "UPDATE {$realmDB}.guild_member SET pnote=?s, offnote=?s WHERE guildid=?d AND guid=?d",
                            $publicNote,
                            $officerNote,
                            $guildId,
                            $validGuid
                        );
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
        $columnExists = $DB->selectCell(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='guild' AND COLUMN_NAME=?",
            $realmDB,
            $candidateColumn
        );
        if ((int)$columnExists > 0) {
            $createdColumn = $candidateColumn;
            break;
        }
    }

    if ($createdColumn !== null) {
        $createdValue = $DB->selectCell("SELECT `{$createdColumn}` FROM {$realmDB}.guild WHERE guildid=?d", $guildId);
        if (!empty($createdValue) && $createdValue !== '0000-00-00 00:00:00') {
            $createdTs = is_numeric($createdValue) ? (int)$createdValue : strtotime((string)$createdValue);
            if ($createdTs > 0) {
                $guildEstablishedLabel = date('M j, Y', $createdTs);
            }
        }
    }

    if ($guildEstablishedLabel === 'Unknown') {
        $eventTimeColumn = null;
        $eventTableExists = $DB->selectCell(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME='guild_eventlog'",
            $realmDB
        );
        if ((int)$eventTableExists > 0) {
            foreach (array('TimeStamp', 'timestamp', 'time', 'event_time') as $candidateColumn) {
                $columnExists = $DB->selectCell(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='guild_eventlog' AND COLUMN_NAME=?",
                    $realmDB,
                    $candidateColumn
                );
                if ((int)$columnExists > 0) {
                    $eventTimeColumn = $candidateColumn;
                    break;
                }
            }

            if ($eventTimeColumn !== null) {
                $firstEventValue = $DB->selectCell("SELECT MIN(`{$eventTimeColumn}`) FROM {$realmDB}.guild_eventlog WHERE guildid=?d", $guildId);
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

$leader = $DB->selectRow("SELECT guid, name, race, class, level, gender FROM {$realmDB}.characters WHERE guid=?d", (int)$guild['leaderguid']);
$members = $DB->select("
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
    WHERE gm.guildid=?d
    ORDER BY gm.rank ASC, c.level DESC, c.name ASC
", $guildId);

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
            $itemLevelRows = $DB->select("
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
  grid-template-columns: minmax(0, 1.9fr) minmax(340px, 0.95fr);
  gap: 18px;
  align-items: stretch;
  padding: 26px 24px 30px;

  border-top: 0;
  border-radius: 0 0 18px 18px;
}
.guild-side-stack {
  display: flex;
  flex-direction: column;
  min-height: 100%;
}
.guild-section {
  padding: 22px 24px 18px;
  background: rgba(4, 8, 18, 0.34);
  border: 1px solid rgba(255, 204, 72, 0.18);
  border-radius: 16px;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}
.guild-roster-panel {
  min-height: 100%;
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
  border-collapse: collapse;
  background: rgba(10, 10, 18, 0.72);
}
.guild-roster thead th {
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
  min-width: 260px;
}
.guild-roster thead th.guild-note-col,
.guild-roster tbody td.guild-note-col {
  width: 220px;
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
  width: 95%;
  min-height: 40px;
  padding: 0 12px;
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
  min-width: 180px;
}
.guild-action-form {
  margin: 0;
}
.guild-action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.guild-action-btn {
  min-height: 34px;
  padding: 0 12px;
  border-radius: 999px;
  border: 1px solid rgba(255, 204, 72, 0.26);
  background: rgba(255, 204, 72, 0.1);
  color: #ffe39a;
  font-weight: 700;
  cursor: pointer;
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
  width: 100%;
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
  color: #e5d4a0;
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
}
.class-warrior { --class-color:#C79C6E; }
.class-mage { --class-color:#69CCF0; }
.class-priest { --class-color:#FFFFFF; }
.class-hunter { --class-color:#ABD473; }
.class-rogue { --class-color:#FFF569; }
.class-warlock { --class-color:#9482C9; }
.class-paladin { --class-color:#F58CBA; }
.class-druid { --class-color:#FF7D0A; }
.class-shaman { --class-color:#0070DE; }
.class-deathknight { --class-color:#C41F3B; }
[class*="class-"] a {
  color: var(--class-color);
  text-decoration: none;
  font-weight: 700;
}
@media (max-width: 1080px) {
  .guild-hero,
  .guild-shell {
    grid-template-columns: 1fr;
  }
  .guild-roster {
    min-width: 1000px;
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
        <?php if ($canEditGuildNotes): ?>
          <button class="guild-note-save" type="submit" form="guild-note-bulk-form" name="guild_submit_mode" value="all_notes">Save All Notes</button>
        <?php endif; ?>
      </div>
      <?php if ($guildNoteFeedback !== ''): ?><div class="guild-note-banner"><?php echo htmlspecialchars($guildNoteFeedback); ?></div><?php endif; ?>
      <?php if ($guildNoteError !== ''): ?><div class="guild-note-banner is-error"><?php echo htmlspecialchars($guildNoteError); ?></div><?php endif; ?>

      <?php if ($canEditGuildNotes): ?>
        <form method="post" id="guild-note-bulk-form">
          <input type="hidden" name="guild_form_action" value="save_guild_data">
        </form>
      <?php endif; ?>
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
                    <div class="guild-note-card">
                      <label class="guild-note-label" for="pnote-<?php echo (int)$member['guid']; ?>">Public Note</label>
                      <input class="guild-note-input" id="pnote-<?php echo (int)$member['guid']; ?>" type="text" name="pnote[<?php echo (int)$member['guid']; ?>]" maxlength="31" value="<?php echo htmlspecialchars($publicNoteValue); ?>" form="guild-note-bulk-form">
                    </div>
                  <?php else: ?>
                    <div class="guild-note-card">
                      <div class="guild-note-value<?php echo $publicNoteValue === '' ? ' is-empty' : ''; ?>"><?php echo $publicNoteValue !== '' ? htmlspecialchars($publicNoteValue) : 'No public note'; ?></div>
                    </div>
                  <?php endif; ?>
                </td>
                <?php if ($canViewOfficerNotes): ?>
                  <td class="guild-notes-cell guild-note-col">
                    <?php if ($canEditGuildNotes): ?>
                      <div class="guild-note-card">
                        <label class="guild-note-label" for="offnote-<?php echo (int)$member['guid']; ?>">Officer Note</label>
                        <input class="guild-note-input" id="offnote-<?php echo (int)$member['guid']; ?>" type="text" name="offnote[<?php echo (int)$member['guid']; ?>]" maxlength="31" value="<?php echo htmlspecialchars($officerNoteValue); ?>" form="guild-note-bulk-form">
                      </div>
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
                        <input type="hidden" name="target_guid" value="<?php echo (int)$member['guid']; ?>">
                        <div class="guild-action-buttons">
                          <button class="guild-action-btn" type="submit" name="guild_roster_action_type" value="rank_up"<?php echo (int)$member['rank'] <= 1 ? ' disabled' : ''; ?>>Rank Up</button>
                          <button class="guild-action-btn" type="submit" name="guild_roster_action_type" value="rank_down"<?php echo (int)$member['rank'] >= $maxGuildRankId ? ' disabled' : ''; ?>>Rank Down</button>
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

      <?php if ($pageCount > 1): ?>
        <div class="pagination-controls"><div class="page-links"><?php echo compact_paginate($p, $pageCount, $baseUrl); ?></div></div>
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

      <section class="guild-section guild-insights-panel">
        <h3 class="guild-side-title">Roster Overview</h3>
        <p class="guild-side-copy"><?php echo $guildMembers; ?> members, average level <?php echo $avgLevel; ?>, guild average iLvl <?php echo $guildAverageItemLevel > 0 ? number_format($guildAverageItemLevel, 1) : '-'; ?>, max level <?php echo $maxLevel; ?>.</p>

        <div class="guild-divider">
          <h3 class="guild-side-title">Class Breakdown</h3>
          <div class="guild-breakdown">
            <?php foreach($classBreakdown as $classId => $classCount): ?>
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
        </div>
      </section>
    </div>
  </div>
</div>
</div>




