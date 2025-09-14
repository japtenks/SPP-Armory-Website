<?php
if (!defined('Armory')) { exit; }

/**
 * character-talents.php
 * - 3 talent trees side-by-side
 * - strict 4×N grid with invisible placeholders to keep columns aligned
 * - class/tab backgrounds
 * - hover tooltips from dbc_spell + dbc_spellicon
 *
 * Requires (your current schema):
 *   armory.dbc_talenttab(id, name, refmask_chrclasses, tab_number)
 *   armory.dbc_talent(id, ref_talenttab, row, col, rank1..rank5)
 *   armory.dbc_spell(id, ref_spellicon, name, description, ...)
 *   armory.dbc_spellicon(id, name)
 */

// -------------------- asset bases (adjust only if paths change) --------------------
if (!defined('TALENTS_ASSET_WEB')) {
  define('TALENTS_ASSET_WEB', '/armory/shared/global/talents');
}
if (!defined('TALENTS_ASSET_FS')) {
  define('TALENTS_ASSET_FS', realpath(__DIR__ . '/../shared/global/talents'));
}

// -------------------- helpers --------------------

/** table exists in given connection */
function tbl_exists($conn, $table) {
    return (bool) execute_query(
        $conn,
        "SELECT 1
           FROM information_schema.TABLES
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '".addslashes($table)."'
          LIMIT 1",
        2
    );
}

/** tabs (id, name, tab_number) for a class id */
function get_tabs_for_class($classId) {
    $mask = 1 << ((int)$classId - 1);
    return execute_query(
        'armory',
        "SELECT `id`, `name`, `tab_number`
           FROM `dbc_talenttab`
          WHERE `refmask_chrclasses` = {$mask}
          ORDER BY `tab_number` ASC",
        0
    ) ?: [];
}

/** prefer character_talent; else derive from character_spell */
function current_rank_for_talent($guid, array $talRow, array $rankMap, $hasCharSpell) {
    $tid = (int)$talRow['id'];
    if (isset($rankMap[$tid])) {
        return (int)$rankMap[$tid]; // already 1-based (we normalize below when building $rankMap)
    }
    if ($hasCharSpell) {
        for ($r = 5; $r >= 1; $r--) {
            $spell = (int)$talRow["rank{$r}"];
            if ($spell > 0) {
                $has = execute_query(
                    'char',
                    "SELECT 1
                       FROM `character_spell`
                      WHERE `guid` = ".(int)$guid."
                        AND `spell` = ".(int)$spell."
                        AND `disabled` = 0
                      LIMIT 1",
                    2
                );
                if ($has) return $r;
            }
        }
    }
    return 0;
}

/** first non-zero rank spell id for a talent (not used for tooltip; kept if needed later) */
function first_rank_spell(array $tal) {
    for ($i = 1; $i <= 5; $i++) {
        $id = (int)$tal["rank{$i}"];
        if ($id) return $id;
    }
    return 0;
}

/**
 * Spell info (name/description/icon) for the talent row.
 * Picks the highest non-zero rank to represent the talent.
 * Uses your schema: dbc_spell(id, ref_spellicon, name, description), dbc_spellicon(id, name)
 */
function spell_info_for_talent(array $talRow) {
	// find highest non-zero rank spell
	$spellId = 0;
	for ($r = 5; $r >= 1; $r--) {
		if (!empty($talRow["rank{$r}"])){ 
										$spellId = (int)$talRow["rank{$r}"]; 
										break; 
										}
								}		
			if (!$spellId) return ['name' => 'Unknown', 'desc' => '', 'icon' => 'inv_misc_questionmark'];

			// join dbc_spellicon by ref_spellicon
$sql = "
    SELECT
        s.`id`, s.`name`, s.`description`,
        s.`proc_chance`,
        s.`ref_spellduration`,
        s.`ref_spellradius_1`,
        s.`effect_basepoints_1`, s.`effect_basepoints_2`, s.`effect_basepoints_3`,
        s.`effect_amplitude_1`,  s.`effect_amplitude_2`,  s.`effect_amplitude_3`,
        s.`effect_chaintarget_1`, s.`effect_chaintarget_2`, s.`effect_chaintarget_3`,
        s.`effect_trigger_1`, s.`effect_trigger_2`, s.`effect_trigger_3`,
        i.`name` AS icon
    FROM `dbc_spell` s
    LEFT JOIN `dbc_spellicon` i ON i.`id` = s.`ref_spellicon`
    WHERE s.`id` = {$spellId}
    LIMIT 1
";


			$sp = execute_query('armory', $sql, 1);

    if (!$sp || !is_array($sp)) {
        // query failed or returned nothing
        return ['name' => 'Unknown', 'desc' => '', 'icon' => 'inv_misc_questionmark'];
    }

    // duration (seconds) from dbc_spellduration
    $durSecs = duration_secs_from_id((int)($sp['ref_spellduration'] ?? 0));


// build text (replace Blizzard tokens) phase 0 (parital working)
$desc = build_tooltip_desc($sp);
// Phase 1 replacement (not working) 
//$desc = resolve_tokens_phase1($sp);
// build text (Phase 1 + Phase 2) (paritial working, worse than p0)
//$desc = build_tooltip_desc_phase12($sp);



    // normalize icon base (file lookup is done elsewhere)
    $icon = strtolower(preg_replace('/[^a-z0-9_]/i', '', (string)($sp['icon'] ?? '')));
    if ($icon === '') $icon = 'inv_misc_questionmark';

    return [
        'name' => (string)($sp['name'] ?? 'Unknown'),
        'desc' => $desc,
        'icon' => $icon,
    ];
}

/** Build a web path to the icon file
    Example: armory/shared/icons/ability_ambush.jpg */
function icon_url($iconBase) {
    // absolute path so it works from any page depth
    return '/armory/shared/icons/' . $iconBase . '.jpg';
}

/** class/tab background by talent tab id (e.g. 161.jpg) */
function talent_bg_for_tab($tabId) {
    // Where the new images live
    $webBase = '/armory/shared/icon_talents';
    $fsBase  = realpath(__DIR__ . '/../shared/icon_talents');

    if (!$fsBase) {
        return ''; // folder missing; render no background
    }

    $file = (int)$tabId . '.jpg';
    $fs   = $fsBase . DIRECTORY_SEPARATOR . $file;

    if (is_file($fs)) {
        return $webBase . '/' . $file;   // e.g. /armory/shared/icon_talents/161.jpg
    }

    // Optional: class-wide default if you add one later:
    // $classDefault = $webBase . '/default.jpg';
    // if (is_file($fsBase . '/default.jpg')) return $classDefault;

    return ''; // no bg found; the tree will just have no background
}

// --- seconds → human string (seconds in, not ms) ---
function fmt_secs($sec) {
    $sec = (float)$sec;
    if ($sec < 0.0001) return '0 sec';
    if ($sec < 60) {
        // 0.5 sec, 1 sec, 9.5 sec, 12 sec ...
        $dp = ($sec < 10 && abs($sec - round($sec)) > 0.0001) ? 1 : 0;
        return rtrim(rtrim(number_format($sec, $dp), '0'), '.') . ' sec';
    }
    $m = floor($sec / 60);
    $s = $sec - $m * 60;
    if ($s < 0.0001) return $m . ' min';
    $dp = ($s < 10 && abs($s - round($s)) > 0.0001) ? 1 : 0;
    return $m . ' min ' . rtrim(rtrim(number_format($s, $dp), '0'), '.') . ' sec';
}
 
/** Pull duration seconds from dbc_spellduration (your schema) */
function duration_secs_from_id($id) {
    if (!$id) return 0;
    $row = execute_query(
        'armory',
        "SELECT `durationValue`, `ms_mod`, `ms_min`
           FROM `dbc_spellduration`
          WHERE `id` = ".(int)$id." LIMIT 1",
        1
    );
    if (!$row) return 0;

    // Prefer durationValue; if missing use the largest of ms_mod/ms_min
    $ms = (int)$row['durationValue'];
    if ($ms <= 0) {
        $ms = max((int)$row['ms_mod'], (int)$row['ms_min']);
    }
    return (int)round($ms / 1000);   // ms → sec
}

//old working
function get_spell_row($id) {
    return execute_query(
        'armory',
        "SELECT `effect_basepoints_1`, `effect_basepoints_2`, `effect_basepoints_3`,
                `ref_spellradius_1`
           FROM `dbc_spell`
          WHERE `id` = " . (int)$id . " LIMIT 1",
        1
    );
}


function get_spell_duration_id($id) {
    $row = execute_query(
        'armory',
        "SELECT `ref_spellduration`
         FROM `dbc_spell`
         WHERE `id` = " . (int)$id . " LIMIT 1",
        1
    );
    return $row ? (int)$row['ref_spellduration'] : 0;
}

function get_spell_radius_id($id) {
    $row = execute_query(
        'armory',
        "SELECT `ref_spellradius_1`
         FROM `dbc_spell`
         WHERE `id` = " . (int)$id . " LIMIT 1",
        1
    );
    return $row ? (int)$row['ref_spellradius_1'] : 0;
}

function get_radius_yds_by_id($rid) {
    $row = execute_query(
        'armory',
        "SELECT `yards_base`
         FROM `dbc_spellradius`
         WHERE `id` = " . (int)$rid . " LIMIT 1",
        1
    );
    return $row ? (float)$row['yards_base'] : 0.0;
}

function getRadiusYdsForSpellRow(array $sp) {
    $rid = (int)($sp['ref_spellradius_1'] ?? 0);
    if ($rid <= 0) return 0.0;
    return get_radius_yds_by_id($rid);
}

// --- Build a clean tooltip description for one spell row ---
function build_tooltip_desc(array $sp): string {
    $desc = (string)($sp['description'] ?? '');

    $trimNum = static function($v): string {
        $s = number_format((float)$v, 1, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');
        return ($s === '') ? '0' : $s;
    };

    // --- Cross-spell tokens: $12345s1..$12345s3 ---
    $desc = preg_replace_callback('/\$(\d+)s([1-3])/', function ($m) {
        $sid = (int)$m[1]; $idx = (int)$m[2];
        $row = get_spell_row($sid);
        if (!$row) return '0';
        $bp = (int)($row["effect_basepoints_{$idx}"] ?? 0);
        return (string)($bp + 1);
    }, $desc);

    // --- Cross-spell duration: $12345d ---
    $desc = preg_replace_callback('/\$(\d+)d\b/', function ($m) {
        $sid   = (int)$m[1];
        $durId = get_spell_duration_id($sid);
        $secs  = duration_secs_from_id($durId);
        return fmt_secs($secs);
    }, $desc);

    // --- Cross-spell radius: $12345a1 ---
    $desc = preg_replace_callback('/\$(\d+)a1\b/', function ($m) use ($trimNum) {
        $sid = (int)$m[1];
        $row = get_spell_row($sid);
        if (!$row) return '0';
        $a1  = getRadiusYdsForSpellRow($row);
        return $trimNum($a1);
    }, $desc);

    // --- Current-spell values ($s1..$s3, $d, $h) ---
    $s1 = (int)($sp['effect_basepoints_1'] ?? 0) + 1;
    $s2 = (int)($sp['effect_basepoints_2'] ?? 0) + 1;
    $s3 = (int)($sp['effect_basepoints_3'] ?? 0) + 1;
    $d  = fmt_secs(duration_secs_from_id((int)($sp['ref_spellduration'] ?? 0)));

    // --- Total-over-time tokens: $o1..$o3 = (basepoints+1) * ticks ---
    $durSecs = (int)duration_secs_from_id((int)($sp['ref_spellduration'] ?? 0));
    $durMs   = $durSecs * 1000;

    $o1 = (function() use ($sp, $durMs) {
        $bp  = (int)($sp['effect_basepoints_1'] ?? 0) + 1;
        $amp = (int)($sp['effect_amplitude_1'] ?? 0);
        $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
        return (string)($ticks > 0 ? $bp * $ticks : $bp);
    })();

    $o2 = (function() use ($sp, $durMs) {
        $bp  = (int)($sp['effect_basepoints_2'] ?? 0) + 1;
        $amp = (int)($sp['effect_amplitude_2'] ?? 0);
        $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
        return (string)($ticks > 0 ? $bp * $ticks : $bp);
    })();

    $o3 = (function() use ($sp, $durMs) {
        $bp  = (int)($sp['effect_basepoints_3'] ?? 0) + 1;
        $amp = (int)($sp['effect_amplitude_3'] ?? 0);
        $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
        return (string)($ticks > 0 ? $bp * $ticks : $bp);
    })();

    // $h = headline number for talents. Prefer proc_chance; else fall back to $s1.
    $h  = (int)($sp['proc_chance'] ?? 0); if ($h <= 0) $h = $s1;

    // --- Current-spell radius: $a1 ---
    $a1 = $trimNum(getRadiusYdsForSpellRow($sp));

    return strtr($desc, [
        '$s1' => (string)$s1, '$s2' => (string)$s2, '$s3' => (string)$s3,
        '$o1' => $o1,         '$o2' => $o2,         '$o3' => $o3,
        '$a1' => $a1,
        '$d'  => $d,
        '$h'  => (string)$h,
    ]);
}




//pretty close good to work with 
/* // --- Build a clean tooltip description for one spell row ---
function build_tooltip_desc(array $sp): string {
    $desc = (string)($sp['description'] ?? '');

    // --- Cross-spell tokens: $12345s1..$12345s3 ---
    $desc = preg_replace_callback('/\$(\d+)s([1-3])/', function ($m) {
        $sid = (int)$m[1]; $idx = (int)$m[2];
        $row = get_spell_row($sid);                       // uses your helper above
        if (!$row) return '0';
        $bp = (int)($row["effect_basepoints_{$idx}"] ?? 0);
        return (string)($bp + 1);
    }, $desc);

    // --- Cross-spell duration: $12345d ---
    $desc = preg_replace_callback('/\$(\d+)d\b/', function ($m) {
        $sid   = (int)$m[1];
        $durId = get_spell_duration_id($sid);             // uses your helper above
        $secs  = duration_secs_from_id($durId);
        return fmt_secs($secs);                            //  "6 sec", "2 min", etc.
    }, $desc);

    // --- Current-spell values ($s1..$s3, $d, $h) ---
    $s1 = (int)($sp['effect_basepoints_1'] ?? 0) + 1;
    $s2 = (int)($sp['effect_basepoints_2'] ?? 0) + 1;
    $s3 = (int)($sp['effect_basepoints_3'] ?? 0) + 1;
    $d  = fmt_secs(duration_secs_from_id((int)($sp['ref_spellduration'] ?? 0)));
	
	    // --- Total-over-time tokens: $o1..$o3 = (basepoints+1) * ticks ---
    $durSecs = (int)duration_secs_from_id((int)($sp['ref_spellduration'] ?? 0));
    $durMs   = $durSecs * 1000;

    $o1 = (function() use ($sp, $durMs) {
        $bp  = (int)($sp['effect_basepoints_1'] ?? 0) + 1;
        $amp = (int)($sp['effect_amplitude_1'] ?? 0);               // ms between ticks
        $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
        return (string)( $ticks > 0 ? $bp * $ticks : $bp );
    })();

    $o2 = (function() use ($sp, $durMs) {
        $bp  = (int)($sp['effect_basepoints_2'] ?? 0) + 1;
        $amp = (int)($sp['effect_amplitude_2'] ?? 0);
        $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
        return (string)( $ticks > 0 ? $bp * $ticks : $bp );
    })();

    $o3 = (function() use ($sp, $durMs) {
        $bp  = (int)($sp['effect_basepoints_3'] ?? 0) + 1;
        $amp = (int)($sp['effect_amplitude_3'] ?? 0);
        $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
        return (string)( $ticks > 0 ? $bp * $ticks : $bp );
    })();

    // $h = “headline” number for talents. Prefer proc_chance; else fall back to $s1.
    $h  = (int)($sp['proc_chance'] ?? 0); if ($h <= 0) $h = $s1;

    // --- Scaled basepoints: $/N;$s1..$s3  (e.g., $/1000;$s1 sec)
    // Uses the already-computed $s1/$s2/$s3 (basepoints+1) and divides by N.
    $sMap = [1 => (float)$s1, 2 => (float)$s2, 3 => (float)$s3];

    $desc = preg_replace_callback('/\$\s*\/\s*(\d+)\s*;\s*\$s([1-3])/', function ($m) use ($sMap) {
        $div = (float)$m[1];
        $idx = (int)$m[2];
        $val = $sMap[$idx] ?? 0.0;
        $out = ($div > 0.0) ? ($val / $div) : 0.0;

        // format like "1.5" but trim trailing .0
        $s = number_format($out, 1, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');
        return ($s === '') ? '0' : $s;
    }, $desc);


    return strtr($desc, [
        '$s1' => (string)$s1, '$s2' => (string)$s2, '$s3' => (string)$s3,
        '$o1' => $o1,         '$o2' => $o2,         '$o3' => $o3,
        '$d'  => $d,
        '$h'  => (string)$h,
    ]);

} */

/* function replace_spell_tokens(string $desc, array $spellRow, PDO $pdo): string{
    // ---- helpers -----------------------------------------------------------
    $getSpell = function(int $id) use ($pdo): ?array {
        $q = $pdo->prepare("
            SELECT *
            FROM dbc_spell
            WHERE id = :id
            LIMIT 1
        ");
        $q->execute([':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    };

    $fmtDuration = function(?int $refId) use ($pdo): string {
        if (!$refId) return "0 sec";
        // spellduration table usually has base, per_level, max (ms). We’ll use 'base' when present.
        $q = $pdo->prepare("
            SELECT base, per_level, max
            FROM dbc_spellduration
            WHERE id = :id
            LIMIT 1
        ");
        $q->execute([':id' => $refId]);
        $d = $q->fetch(PDO::FETCH_ASSOC);
        $ms = 0;
        if ($d) {
            $ms = (int)($d['base'] ?? 0);
            if ($ms <= 0 && isset($d['max'])) $ms = (int)$d['max'];
        }
        if ($ms <= 0) return "0 sec";
        $sec = (int)round($ms / 1000);
        if ($sec < 60) return "{$sec} sec";
        $min = intdiv($sec, 60);
        $rem = $sec % 60;
        return $rem ? "{$min} min {$rem} sec" : "{$min} min";
    };

    $fmtRadius = function(?int $refId) use ($pdo): ?string {
        if (!$refId) return null;
        // Prefer dbc_spellradius if available; otherwise a ref_ table the user mentioned.
        $q = $pdo->prepare("SELECT radius FROM dbc_spellradius WHERE id = :id LIMIT 1");
        $q->execute([':id' => $refId]);
        $r = $q->fetch(PDO::FETCH_ASSOC);
        if ($r && isset($r['radius'])) {
            // Blizzard displays yards with no trailing .0
            $val = (float)$r['radius'];
            $val = (abs($val - round($val)) < 0.0001) ? (string)round($val) : (string)$val;
            return "{$val} yd";
        }
        // fallback table name used in your notes
        $q = $pdo->prepare("SELECT radius FROM ref_spellradius_1 WHERE id = :id LIMIT 1");
        $q->execute([':id' => $refId]);
        $r = $q->fetch(PDO::FETCH_ASSOC);
        if ($r && isset($r['radius'])) {
            $val = (float)$r['radius'];
            $val = (abs($val - round($val)) < 0.0001) ? (string)round($val) : (string)$val;
            return "{$val} yd";
        }
        return null;
    };

    $amountFor = function(array $sRow, int $n): int {
        // $s1 is usually basepoints_1 + 1 for Classic tooltips
        $bp = (int)($sRow["effect_basepoints_{$n}"] ?? 0);
        return $bp + 1;
    };

    $radiusFor = function(array $sRow, int $n) use ($fmtRadius): ?string {
        // Many vanilla spells only use ref_spellradius_1; some DBCs have per-effect refs.
        $ref = $sRow["ref_spellradius_{$n}"] ?? null;
        if (!$ref || (int)$ref === 0) $ref = $sRow['ref_spellradius_1'] ?? null;
        return $fmtRadius($ref ? (int)$ref : null);
    };

    $durationFor = function(array $sRow) use ($fmtDuration): string {
        $ref = (int)($sRow['ref_spellduration'] ?? 0);
        return $fmtDuration($ref);
    };

    // ---- token resolution --------------------------------------------------
    // 1) ID-qualified tokens like $27828s2 or $27828d or $27828r1
    $desc = preg_replace_callback('/\$(\d+)([srdh])(\d)?/i', function($m) use ($getSpell, $amountFor, $radiusFor, $durationFor) {
        $id   = (int)$m[1];
        $kind = strtolower($m[2]);          // s|r|d|h
        $idx  = isset($m[3]) && $m[3] !== '' ? (int)$m[3] : 1;   // default to 1
        $row  = $getSpell($id);
        if (!$row) return $m[0]; // leave token intact if not found

        switch ($kind) {
            case 's': return (string)$amountFor($row, $idx);
            case 'r': return $radiusFor($row, $idx) ?? "0 yd";
            case 'd': return $durationFor($row);
            case 'h': // rarely used as id-qualified; map to s1 by convention
                return (string)$amountFor($row, 1);
        }
        return $m[0];
    }, $desc);

    // 2) Plain tokens for the current spell ($s1..$s3, $r1..$r3, $d, $h)
    $desc = preg_replace_callback('/\$(s|r|h)([1-3])|\$d/i', function($m) use ($spellRow, $amountFor, $radiusFor, $durationFor) {
        if (strtolower($m[0]) === '$d') {
            return $durationFor($spellRow);
        }
        $kind = strtolower($m[1]);   // s|r|h
        $idx  = (int)$m[2];          // 1..3
        if ($kind === 's') return (string)$amountFor($spellRow, $idx);
        if ($kind === 'r') return $radiusFor($spellRow, $idx) ?? "0 yd";
        if ($kind === 'h') {
            // For talents, $h is the “headline number”; map to $s1 which fixes Martyrdom (50/100).
            return (string)$amountFor($spellRow, 1);
        }
        return $m[0];
    }, $desc);

    // 3) Optional: simple replacements for a few common non-indexed fields
    $desc = str_replace('$proc', (string)((int)($spellRow['proc_chance'] ?? 0)), $desc);

    return $desc;
}
 */

function get_spell_by_id(int $id): ?array {
    global $db;
    $stmt = $db->prepare("SELECT * FROM dbc_spell WHERE id = ?");
    $stmt->execute([$id]);
    $spell = $stmt->fetch(PDO::FETCH_ASSOC);
    return $spell ?: null;
}

function get_duration_for_spell(array $spell): int {
    global $db;
    $durId = $spell['ref_spellduration'] ?? 0;
    $stmt = $db->prepare("SELECT durationValue FROM dbc_spellduration WHERE id = ?");
    $stmt->execute([$durId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['durationValue'] : 0;
}

function getRadiusForEffect($effectSlot, $spellRow) {
    global $db; // needed for the prepared statement below

    $key = "effect_radius_index_{$effectSlot}";
    if (isset($spellRow[$key]) && (int)$spellRow[$key] > 0) {
        $radiusIndex = (int)$spellRow[$key];
    } else {
        $radiusIndex = isset($spellRow['ref_spellradius_1']) ? (int)$spellRow['ref_spellradius_1'] : 0;
    }

    if ($radiusIndex <= 0) return 0;

    $stmt = $db->prepare("SELECT yards_base FROM dbc_spellradius WHERE id = ?");
    $stmt->execute([$radiusIndex]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (float)$row['yards_base'] : 0;
}

function duration_from_ref($duration_id) {
    global $dbc;  // or your DB handle
    $q = $dbc->prepare("SELECT durationValue FROM dbc_spellduration WHERE id = ?");
    $q->execute([$duration_id]);
    $result = $q->fetch(PDO::FETCH_ASSOC);
    return $result ? ($result['durationValue'] / 1000) : 0;
}

// Phase 1: $sN, $d*, $mN, $aN
function resolve_tokens_phase1(array $sp): string {
    $desc = (string)($sp['description'] ?? '');

    // Pull values from the current spell row
    $s1 = (int)($sp['effect_basepoints_1'] ?? 0) + 1;
    $s2 = (int)($sp['effect_basepoints_2'] ?? 0) + 1;
    $s3 = (int)($sp['effect_basepoints_3'] ?? 0) + 1;

    // amplitudes are ms in DBC; display as seconds ("0.5 sec", "2 sec", ...)
    $m1s = fmt_secs(((int)($sp['effect_amplitude_1'] ?? 0)) / 1000);
    $m2s = fmt_secs(((int)($sp['effect_amplitude_2'] ?? 0)) / 1000);
    $m3s = fmt_secs(((int)($sp['effect_amplitude_3'] ?? 0)) / 1000);

    // chain/targets are integers
    $a1 = (int)($sp['effect_chaintarget_1'] ?? 0);
    $a2 = (int)($sp['effect_chaintarget_2'] ?? 0);
    $a3 = (int)($sp['effect_chaintarget_3'] ?? 0);

    // duration: one value reused for $d, $d1..$d3
    $durSecs = duration_secs_from_id((int)($sp['ref_spellduration'] ?? 0));
    $dstr    = fmt_secs($durSecs);

    // Single pass for all four token families (case-insensitive)
    $desc = preg_replace_callback('/\$(s[1-3]|m[1-3]|a[1-3]|d[1-3]?)/i', function($m) use($s1,$s2,$s3,$m1s,$m2s,$m3s,$a1,$a2,$a3,$dstr){
        switch (strtolower($m[1])) {
            case 's1': return (string)$s1;
            case 's2': return (string)$s2;
            case 's3': return (string)$s3;

            case 'm1': return $m1s;
            case 'm2': return $m2s;
            case 'm3': return $m3s;

            case 'a1': return (string)$a1;
            case 'a2': return (string)$a2;
            case 'a3': return (string)$a3;

            case 'd':
            case 'd1':
            case 'd2':
            case 'd3': return $dstr;
        }
        return $m[0]; // leave unknowns untouched
    }, $desc);

    return $desc;
}

//phase 1+2
// Number tidy: 1.0 → "1", 0.50 → "0.5"
function fmt_num($x) {
    $x = (float)$x;
    if (abs($x - round($x)) < 1e-6) return (string)round($x);
    if ($x < 10) return rtrim(rtrim(number_format($x, 1, '.', ''), '0'), '.');
    return (string)round($x);
}

// Simple radius lookup (Classic uses a single radius for all effects)
function radius_yards_for_spell(array $sp): float {
    $rid = (int)($sp['ref_spellradius_1'] ?? 0);
    if ($rid <= 0) return 0.0;
    $row = execute_query('armory',
        "SELECT `yards_base` FROM `dbc_spellradius` WHERE `id` = {$rid} LIMIT 1", 1);
    return $row && isset($row['yards_base']) ? (float)$row['yards_base'] : 0.0;
}

/**
 * Resolve tokens: Phase 1 ($sN,$d*,$mN,$aN) + Phase 2 ($h,$h%, $sN%/$mN%, math, $R*)
 * Input: dbc_spell row ($sp)
 * Output: string with replacements applied
 */
function build_tooltip_desc_phase12(array $sp): string {
    $desc = (string)($sp['description'] ?? '');

    // ---- Precompute values (raw numbers) ----
    $vals = [
        // basepoints (Classic display = base+1)
        's1' => (int)($sp['effect_basepoints_1'] ?? 0) + 1,
        's2' => (int)($sp['effect_basepoints_2'] ?? 0) + 1,
        's3' => (int)($sp['effect_basepoints_3'] ?? 0) + 1,

        // amplitudes in seconds (raw number)
        'm1' => (int)($sp['effect_amplitude_1'] ?? 0) / 1000,
        'm2' => (int)($sp['effect_amplitude_2'] ?? 0) / 1000,
        'm3' => (int)($sp['effect_amplitude_3'] ?? 0) / 1000,

        // chain/targets (integers)
        'a1' => (int)($sp['effect_chaintarget_1'] ?? 0),
        'a2' => (int)($sp['effect_chaintarget_2'] ?? 0),
        'a3' => (int)($sp['effect_chaintarget_3'] ?? 0),
    ];

    // duration seconds (one value used for $d, $d1..$d3)
    $durSecs = duration_secs_from_id((int)($sp['ref_spellduration'] ?? 0));
    $vals['d']  = $durSecs; $vals['d1'] = $durSecs; $vals['d2'] = $durSecs; $vals['d3'] = $durSecs;

    // radius (Classic: same for all three)
    $rad = radius_yards_for_spell($sp);
    $vals['R1'] = $rad; $vals['R2'] = $rad; $vals['R3'] = $rad;

    // $h / $h% (use proc_chance; fallback to $s1 for talents like Martyrdom)
    $h = (int)($sp['proc_chance'] ?? 0); if ($h <= 0) $h = $vals['s1'];

    // ---- Phase 2: percent-suffix BEFORE formatting seconds ----
    // $sN% and $mN% → numeric + '%'
    $desc = preg_replace_callback('/\$(s[1-3]|m[1-3])%/i', function($m) use ($vals) {
        $key = strtolower($m[1]);
        return fmt_num($vals[$key] ?? 0) . '%';
    }, $desc);

    // $h / $h%
    $desc = preg_replace_callback('/\$(h%?)/i', function($m) use ($h) {
        return strtolower($m[1]) === 'h%' ? ($h . '%') : (string)$h;
    }, $desc);

    // Math: $/C;$TOKEN  (TOKEN ∈ s,m,a,d,h with optional index)
    $desc = preg_replace_callback('/\$\/(\d+);\$(s|m|a|d|h)([1-3]?)/i', function($m) use ($vals, $h) {
        $div = (float)$m[1];
        $prefix = strtolower($m[2]);
        $idx = $m[3] ?: '';
        if ($prefix === 'h')      $num = $h;
        elseif ($prefix === 'd')  $num = $vals['d'];
        else                      $num = $vals[$prefix.$idx] ?? 0;
        $out = ($div == 0) ? $num : ($num / $div);
        return fmt_num($out);
    }, $desc);

    // Radius: $R1..$R3  (just the number; caller text supplies "yards")
    $desc = preg_replace_callback('/\$(R[1-3])/i', function($m) use ($rad) {
        return fmt_num($rad);
    }, $desc);

    // ---- Phase 1: plain tokens (format seconds for $m* and $d*) ----
    $desc = preg_replace_callback('/\$(s[1-3]|a[1-3])/i', function($m) use($vals){
        $key = strtolower($m[1]);
        return (string)($vals[$key] ?? 0);
    }, $desc);

    // $mN → "X sec"
    $desc = preg_replace_callback('/\$(m[1-3])/i', function($m) use($vals){
        $key = strtolower($m[1]);
        return fmt_secs($vals[$key] ?? 0);
    }, $desc);

    // $d, $d1..$d3 → "X sec" / "Y min"
    $desc = preg_replace_callback('/\$(d[1-3]?)/i', function($m) use($vals){
        $key = strtolower($m[1]);
        return fmt_secs($vals[$key] ?? 0);
    }, $desc);

    return $desc;
}









//			----		helpers end	-----
// -------------------- build data --------------------

$tabs = get_tabs_for_class($stat['class']);

// rank map from character_talent if present (normalize to 1-based)
$rankMap = [];
$hasCharTalent = tbl_exists('char', 'character_talent');
if ($hasCharTalent) {
    $rows = execute_query(
        'char',
        "SELECT `talent_id`, `current_rank`
           FROM `character_talent`
          WHERE `guid` = ".(int)$stat['guid'],
        0
    );
    foreach ((array)$rows as $r) {
        $rankMap[(int)$r['talent_id']] = ((int)$r['current_rank']) + 1; // 0-based -> 1-based
    }
}
$hasCharSpell = tbl_exists('char', 'character_spell');

?>
<div class="parchment-top"><div class="parchment-content">

<style>
/* ====== layout ====== */
.talent-trees{
  display:flex;
  justify-content:center;
  gap:12px;                
  flex-wrap:nowrap;
  max-width:980px;
  margin:65px auto 0;
}
.talent-tree{
  position:relative;
  flex:0 0 276px;
  min-height:540px;
  background-position:center;
  background-size:276px 540px; 
  border-radius:10px;
}
.talent-h{
  position:absolute;
  top:-38px;
  left:50%;
  transform:translateX(-50%);
  margin:0;
  font-size:18px;
  font-weight:bold;
  color:#fff7d2;
  text-align:center;
}
.talent-flex{
  --cell:48px;
  --gap:10px;
  position:relative;
  margin:0 auto;
  width:calc(var(--cell)*4 + var(--gap)*3);
  display:flex; flex-wrap:wrap; gap:var(--gap); justify-content:center;
  top:12px;
}

/* ====== cell, icon, states ====== */
.talent-cell{
  position:relative;
  width:var(--cell); height:var(--cell);
  border-radius:6px;
  background:#2a2a2a;                     /* fallback under icon */
  background-image:var(--icon);           /* icon is a CSS var from inline style */
  background-position:center;
  background-repeat:no-repeat;
  background-size:cover;
  box-shadow:inset 0 0 0 1px #555;        /* default gray border */
  display:flex; align-items:center; justify-content:center;
  overflow:hidden;
  font:12px/1.2 "Trebuchet MS", Arial, sans-serif;
  color:#ddd;
}
.talent-cell.placeholder{ visibility:hidden; box-shadow:none; pointer-events:none; }

/* rank badge */
.talent-rank{
  position:absolute; right:2px; bottom:2px;
  padding:0 6px;
  border-radius:8px;
  background:#000a;
  font-weight:bold;
  font-size:12px;
  line-height:1;
  color:#999; /* default gray */
}

/* states */
.talent-cell.empty{                      /* 0/x */
  filter:grayscale(100%) brightness(.8); /* gray icon */
  box-shadow:inset 0 0 0 1px #555;
}
.talent-cell.empty .talent-rank{ color:#999; }

.talent-cell.learned{                    /* 1..(max-1) */
  filter:none;
  box-shadow:inset 0 0 0 2px #00ff00;    /* green border */
}
.talent-cell.learned .talent-rank{ color:#00ff00; }

.talent-cell.maxed{                      /* max/max */
  filter:none;
  box-shadow:inset 0 0 0 2px #ffd700;    /* gold border */
}
.talent-cell.maxed .talent-rank{ color:#ffd700; }

/* ====== tooltip ====== */
.talent-tt{
  position:fixed;
  z-index:9999;
  max-width:320px;
  color:#fff;
  background:#1b1b1b;
  border:1px solid #3a3a3a;
  box-shadow:0 8px 24px rgba(0,0,0,.45);
  border-radius:6px;
  font:13px/1.35 "Trebuchet MS", Arial, sans-serif;
  padding:10px 12px;
  pointer-events:none;
}
.talent-tt::before{
  content:"";
  position:absolute; top:-7px; left:50%; transform:translateX(-50%);
  border:7px solid transparent; border-bottom-color:#213a6b;
}
.talent-tt::after{
  content:"";
  position:absolute; top:-6px; left:50%; transform:translateX(-50%);
  border:6px solid transparent; border-bottom-color:#0e1b36;
}
.talent-tt h5{ margin:0 0 6px; font-size:14px; font-weight:700; }
.talent-tt p{ margin:0; white-space:normal; }
.talent-cell:hover {
  transform: scale(1.1);                 											
  z-index: 10;                           									
  box-shadow: 0 0 8px 2px rgba(255,255,200,.7),inset 0 0 0 2px #fff;       
}
.talent-cell.learned:hover {
  box-shadow: 0 0 8px 2px rgba(0,255,0,.7),
              inset 0 0 0 2px #00ff00;
}
.talent-cell.maxed:hover {
  box-shadow: 0 0 8px 2px rgba(255,215,0,.8),
              inset 0 0 0 2px #ffd700;
}



</style>
  
  <?php if (empty($tabs)): ?>
    <em>No talent tabs found for this class.</em>
  <?php else: ?>
    <div class="talent-trees">
      <?php foreach ($tabs as $t): ?>
        <?php
          $tabId   = (int)$t['id'];
          $tabName = (string)$t['name'];
          $points  = (int)talentCounting($stat['guid'], $tabId);
          $bgUrl = talent_bg_for_tab($tabId);   // $tabId already set from $t['id']


          // all talents in this tab
          $talents = execute_query(
              'armory',
              "SELECT `id`, `row`, `col`, `rank1`, `rank2`, `rank3`, `rank4`, `rank5`
                 FROM `dbc_talent`
                WHERE `ref_talenttab` = {$tabId}
                ORDER BY `row`, `col`",
              0
          ) ?: [];

          // index by row:col and detect deepest used row
          $byPos = []; $maxRow = 0;
          foreach ($talents as $tal) {
              $r = (int)$tal['row']; $c = (int)$tal['col'];
              $byPos["$r:$c"] = $tal;
              if ($r > $maxRow) $maxRow = $r;
          }
        ?>
        <div class="talent-tree" style="background-image:url('<?php echo htmlspecialchars($bgUrl); ?>');">
          <h4 class="talent-h"><?php echo htmlspecialchars($tabName); ?> (<?php echo $points; ?>)</h4>

          <div class="talent-flex">
            <?php
              $cols = 4;
              for ($r = 0; $r <= $maxRow; $r++) {
                for ($c = 0; $c < $cols; $c++) {
                  if (!isset($byPos["$r:$c"])) {
                    echo '<div class="talent-cell placeholder"></div>';
                    continue;
                  }
                  $found = $byPos["$r:$c"];

                  // max ranks present in DBC (1..5)
                  // current rank for this character
				$max = 0; for ($x = 1; $x <= 5; $x++) if (!empty($found["rank$x"])) $max = $x;
				$cur = current_rank_for_talent((int)$stat['guid'], $found, $rankMap, $hasCharSpell);

// icon + tooltip content
$sp    = spell_info_for_talent($found);
$title = htmlspecialchars($sp['name'], ENT_QUOTES);
$desc  = htmlspecialchars($sp['desc'], ENT_QUOTES);
$icon  = icon_url($sp['icon']);                 // absolute URL
$iconQ = htmlspecialchars($icon, ENT_QUOTES);

// state class
$cellClass = 'talent-cell';
if ($cur >= $max && $max > 0) {
    $cellClass .= ' maxed';
} elseif ($cur > 0) {
    $cellClass .= ' learned';
} else {
    $cellClass .= ' empty';
}

echo '<div class="'.$cellClass.'" style="--icon:url(\''.$iconQ.'\')"
          data-tt-title="'.$title.'"
          data-tt-desc="'.$desc.'">
        <span class="talent-rank">'.(int)$cur.'/'.(int)$max.'</span>
      </div>';

						  

                }
              }
            ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div></div>

<script>
(function(){
  const tt = document.createElement('div');
  tt.className = 'talent-tt';
  tt.style.display = 'none';
  document.body.appendChild(tt);

  let showTimer = null;

  function show(ev, el) {
    const title = el.getAttribute('data-tt-title') || '';
    const desc  = el.getAttribute('data-tt-desc')  || '';
    const icon  = el.getAttribute('data-tt-icon')  || '';
    tt.innerHTML = `
      <h5><span class="ico" style="background-image:url('${icon}')"></span>${title}</h5>
      <p>${desc}</p>
    `;
    tt.style.display = 'block';
    move(ev);
  }
  function hide() { clearTimeout(showTimer); tt.style.display = 'none'; }
  function move(ev){
    const pad = 16;
    let x = ev.clientX + pad, y = ev.clientY + pad;
    const r = tt.getBoundingClientRect(), vw = innerWidth, vh = innerHeight;
    if (x + r.width  > vw) x = ev.clientX - r.width  - pad;
    if (y + r.height > vh) y = ev.clientY - r.height - pad;
    tt.style.left = x + 'px';
    tt.style.top  = y + 'px';
  }

  document.addEventListener('mouseover', e => {
    const el = e.target.closest('.talent-cell[data-tt-title]');
    if (!el) return;
    clearTimeout(showTimer);
    showTimer = setTimeout(() => show(e, el), 80);
  });
  document.addEventListener('mousemove', e => {
    if (tt.style.display !== 'none') move(e);
  });
  document.addEventListener('mouseout', e => {
    const el = e.target.closest('.talent-cell[data-tt-title]');
    if (!el) return;
    if (!e.relatedTarget || !el.contains(e.relatedTarget)) hide();
  });
  window.addEventListener('scroll', hide, {passive:true});
})();
</script>