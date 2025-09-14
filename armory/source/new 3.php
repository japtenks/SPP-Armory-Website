/* ------------------ tooltip helpers: duration + token resolver ------------------ */

/** Convert a duration id (dbc_spellduration.id) to seconds. */
function _duration_secs_from_id($durId) {
    if (!$durId) return 0;
    $row = execute_query(
	'armory',
    "SELECT `durationValue` AS d FROM `dbc_spellduration` WHERE `id` = ".(int)$durId." LIMIT 1",
        1
    );
    return $row ? max(0, (int)$row['d']) : 0;
}

/** Human friendly seconds → "X sec/min/hr". */
function _fmt_duration($secs) {
    if ($secs <= 0) return "0 sec";
    if ($secs < 60) return $secs." sec";
    $min = intdiv($secs, 60);
    $sec = $secs % 60;
    if ($min < 60) return $min . " min" . ($sec ? " ".$sec." sec" : "");
    $hr  = intdiv($min, 60);
    $min = $min % 60;
    return $hr." hr".($min ? " ".$min." min" : "");
}

/**
 * Replace common WoW tooltip tokens in $sp['description'] using
 * fields from dbc_spell (basepoints, amplitude, duration).
 *
 * Handles: $s1..$s3, $t1..$t3 (ticks), $o1..$o3 (over-time total), $d (duration).
 * Also normalizes prefixed tokens like $7001d → $d, $7001o1 → $o1, $7001t1 → $t1.
 */
function _resolve_spell_tokens(array $sp, int $durSecs) {
    $desc = (string)($sp['description'] ?? '');

    // Normalize the “prefixed” tokens the client uses sometimes (e.g. $7001d, $7001o1, $7001t1).
    $desc = preg_replace('/\$\d{3,5}d\b/', '$d',  $desc);      // duration
    $desc = preg_replace('/\$\d{3,5}o([123])\b/', '\$o$1', $desc); // over-time total
    $desc = preg_replace('/\$\d{3,5}t([123])\b/', '\$t$1', $desc); // tick count
    $desc = preg_replace('/\$\d{3,5}s([123])\b/', '\$s$1', $desc); // sN value (rare)

    // Collect base points & amplitude for effects 1..3
    $bp = [
        1 => (int)($sp['effect_basepoints_1'] ?? 0),
        2 => (int)($sp['effect_basepoints_2'] ?? 0),
        3 => (int)($sp['effect_basepoints_3'] ?? 0),
    ];
    $amp = [
        1 => (int)($sp['effect_amplitude_1'] ?? 0),
        2 => (int)($sp['effect_amplitude_2'] ?? 0),
        3 => (int)($sp['effect_amplitude_3'] ?? 0),
    ];

    // Build replacement map for $sN
    foreach ([1,2,3] as $n) {
        $desc = preg_replace('/\$(?:s|S)'.$n.'\b/', (string)$bp[$n], $desc);
    }

    // $d → total duration (pretty)
    $desc = preg_replace('/\$(?:d|D)\b/', _fmt_duration($durSecs), $desc);

    // $tN → tick count = duration / amplitude (rounded down if needed)
    foreach ([1,2,3] as $n) {
        $ticks = ($durSecs > 0 && $amp[$n] > 0) ? max(1, (int)round($durSecs / $amp[$n])) : 0;
        $desc = preg_replace('/\$(?:t|T)'.$n.'\b/', (string)$ticks, $desc);
        // $oN → total amount = $sN * $tN
        $o = ($ticks > 0) ? $bp[$n] * $ticks : $bp[$n];
        $desc = preg_replace('/\$(?:o|O)'.$n.'\b/', (string)$o, $desc);
    }

    // Any still-unrecognized $tokens → remove the $ and leave the trailing text,
    // then collapse multiple spaces (keeps the sentence readable).
    $desc = preg_replace('/\$[A-Za-z0-9]+/', '', $desc);
    $desc = preg_replace('/\s{2,}/', ' ', $desc);

    return trim($desc);
}

/* ------------------ spell info fetch (use the resolver above) ------------------ */

function spell_info_for_talent(array $talRow) {
    // pick highest defined rank 5..1
    $spellId = 0;
    for ($r = 5; $r >= 1; $r--) {
        if (!empty($talRow["rank{$r}"])) { $spellId = (int)$talRow["rank{$r}"]; break; }
    }
    if (!$spellId) return ['name'=>'Unknown', 'desc'=>'', 'icon'=>'inv_misc_questionmark'];

    $sql = "
        SELECT 
            s.`id`, s.`name`, s.`description`,
            s.`ref_spellduration`,
            s.`effect_basepoints_1`, s.`effect_basepoints_2`, s.`effect_basepoints_3`,
            s.`effect_amplitude_1`,  s.`effect_amplitude_2`,  s.`effect_amplitude_3`,
            i.`name` AS icon
        FROM `dbc_spell` s
        LEFT JOIN `dbc_spellicon` i ON i.`id` = s.`ref_spellicon`
        WHERE s.`id` = {$spellId}
        LIMIT 1
    ";
    $sp = execute_query('armory', $sql, 1);
    if (!$sp) return ['name'=>'Unknown', 'desc'=>'', 'icon'=>'inv_misc_questionmark'];

    $durSecs = _duration_secs_from_id((int)$sp['ref_spellduration']);
    $desc    = _resolve_spell_tokens($sp, $durSecs);

    $icon = strtolower(preg_replace('/[^a-z0-9_]/i', '', (string)($sp['icon'] ?? '')));
    if ($icon === '') $icon = 'inv_misc_questionmark';

    return [
        'name' => (string)$sp['name'],
        'desc' => $desc,
        'icon' => $icon,
    ];
}
