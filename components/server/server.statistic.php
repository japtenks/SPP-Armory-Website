<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

/**
 * Server Population Statistics
 * Handles race breakdown per realm, expansion-aware.
 */

$pathway_info[] = array('title' => $lang['statistic'], 'link' => '');

// ---------------------------------------------------
// Determine current realm and expansion
// ---------------------------------------------------
$realm_param = isset($user['cur_selected_realmd'])
    ? (int)$user['cur_selected_realmd']
    : 1;

// Expansion level: 0 = Vanilla, 1 = TBC, 2 = WotLK
$expansion = isset($GLOBALS['expansion'])
    ? (int)$GLOBALS['expansion']
    : 0;

// ---------------------------------------------------
// Query race data
// ---------------------------------------------------
$rc = array();

try {
    $rc = $CHDB->selectCol(
        "SELECT race AS ARRAY_KEY, COUNT(*) AS num
         FROM `characters`
         GROUP BY race"
    );
} catch (Exception $e) {
    $rc = array();
}

// Ensure all race indexes (1–12) exist
for ($i = 1; $i <= 12; $i++) {
    if (!isset($rc[$i])) $rc[$i] = 0;
}

// ---------------------------------------------------
// Calculate totals
// ---------------------------------------------------
$num_chars = array_sum($rc);
if ($num_chars <= 0) $num_chars = 0;

$num_ally = 0;
$num_horde = 0;
$pc_ally = 0;
$pc_horde = 0;

$pc_human = $pc_orc = $pc_dwarf = $pc_ne = 0;
$pc_undead = $pc_tauren = $pc_gnome = $pc_troll = 0;
$pc_be = $pc_dranei = $pc_dk = 0;

if ($num_chars > 0) {
    // --- Base (Vanilla) races ---
    $num_ally = $rc[1] + $rc[3] + $rc[4] + $rc[7]; // Human, Dwarf, Night Elf, Gnome
    $num_horde = $rc[2] + $rc[5] + $rc[6] + $rc[8]; // Orc, Undead, Tauren, Troll

    // --- Add TBC races (if applicable) ---
    if ($expansion >= 1) {
        $num_ally  += $rc[11]; // Draenei
        $num_horde += $rc[10]; // Blood Elf
    }

    // --- Totals ---
    $pc_ally  = round(($num_ally  / $num_chars) * 100, 2);
    $pc_horde = round(($num_horde / $num_chars) * 100, 2);

    // --- Individual race percentages ---
    $pc_human   = round(($rc[1]  / $num_chars) * 100, 2);
    $pc_orc     = round(($rc[2]  / $num_chars) * 100, 2);
    $pc_dwarf   = round(($rc[3]  / $num_chars) * 100, 2);
    $pc_ne      = round(($rc[4]  / $num_chars) * 100, 2);
    $pc_undead  = round(($rc[5]  / $num_chars) * 100, 2);
    $pc_tauren  = round(($rc[6]  / $num_chars) * 100, 2);
    $pc_gnome   = round(($rc[7]  / $num_chars) * 100, 2);
    $pc_troll   = round(($rc[8]  / $num_chars) * 100, 2);

    // --- Expansion-specific races ---
    if ($expansion >= 1) {
        // The Burning Crusade or later
        $pc_be     = round(($rc[10] / $num_chars) * 100, 2);
        $pc_dranei = round(($rc[11] / $num_chars) * 100, 2);
    }

    if ($expansion >= 2 && isset($rc[12])) {
        // WotLK: Death Knights
        $pc_dk = round(($rc[12] / $num_chars) * 100, 2);
    }
} else {
    // --- No characters found ---
    $num_ally = $num_horde = $pc_ally = $pc_horde = 0;
    $pc_be = $pc_dranei = $pc_dk = 0;
}
?>
