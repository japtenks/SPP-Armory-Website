

<?php

/**
 * character-talents.php
 * - class/tab backgrounds witch icons
 * - hover tooltips from dbc_spell with plain text
 *
 * Requires (your current schema):
 *   armory.dbc_talenttab(id, name, refmask_chrclasses, tab_number)
 *   armory.dbc_talent(id, ref_talenttab, row, col, rank1..rank5)
 *   armory.dbc_spell(id, ref_spellicon, name, description, ...)
 *   armory.dbc_spellicon(id, name)
/* -------------------- tooltip builder -------------------- */
// Build a clean tooltip description for one spell row

/* -------------------- asset bases -------------------- */



if (!defined('Armory')) { exit; }


function build_tooltip_desc(array $sp): string {
  $desc = (string)($sp['description'] ?? '');

  // ---------- helpers ----------
  $trimNum = static function($v): string {
    $s = number_format((float)$v, 1, '.', '');
    $s = rtrim(rtrim($s, '0'), '.');
    return ($s === '') ? '0' : $s;
  };

  $rangeText = static function(int $min, int $max): string {
    return ($max > $min) ? ($min . '–' . $max) : (string)$min; // en dash
  };

// Produces min/max/text for $sN.  If $div==1000 and bp<0, treat as negative ms.
$formatS = static function (int $bp, int $dieSides, int $div = 1): array {
  // Special case: cast-time reductions stored as negative milliseconds
  if ($div === 1000 && $bp < 0) {
    $min = abs($bp) / 1000.0;
    $max = $min + ($dieSides > 0 ? $dieSides / 1000.0 : 0.0);
    // collapse if no range
    $txt = ($max > $min) ? rtrim(rtrim(number_format($min,1,'.',''), '0'),'.')
                           .'–'.
                           rtrim(rtrim(number_format($max,1,'.',''), '0'),'.')
                         : rtrim(rtrim(number_format($min,1,'.',''), '0'),'.');
    return [$min, $max, $txt];
  }

  // Normal scalar (damage/heal/etc.)
  $min = $bp + 1;
  if ($dieSides <= 1) {
    $txt = (string)$min;
    return [$min, $min, $txt];
  }
  $max = $bp + $dieSides;
  if ($max < $min) { [$min, $max] = [$max, $min]; }
  return [$min, $max, $min . '–' . $max];
};




  // ---------- cross-spell tokens ----------
  // $12345sN  
  $desc = preg_replace_callback('/\$(\d+)s([1-3])/', function ($m) use ($formatS) {
    $sid = (int)$m[1]; $idx = (int)$m[2];
    $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
    if (!$row) return '0';
    $bp  = (int)($row["effect_basepoints_{$idx}"] ?? 0);
    $die = _cache("die:$sid:$idx", function() use ($sid,$idx){ return get_die_sides_n($sid,$idx); });
    [, , $text] = $formatS($bp, $die);
    return $text;
  }, $desc);

  // $12345d  → duration
  $desc = preg_replace_callback('/\$(\d+)d\b/', function ($m) {
    $sid   = (int)$m[1];
    $durId = _cache("durid:$sid", function() use ($sid){ return get_spell_duration_id($sid); });
    $secs  = _cache("dursec:$durId", function() use ($durId){ return duration_secs_from_id($durId); });
    return fmt_secs($secs);
  }, $desc);

  // $12345a1 → radius (yards)
  $desc = preg_replace_callback('/\$(\d+)a1\b/', function ($m) {
    $sid = (int)$m[1];
    $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
    if (!$row) return '0';
    $val = _cache("radiusYds:$sid", function() use ($row){ return getRadiusYdsForSpellRow($row); });
    $s = number_format((float)$val, 1, '.', '');
    $s = rtrim(rtrim($s, '0'), '.');
    return ($s === '') ? '0' : $s;
  }, $desc);

  // $12345oN → total over-time
  $desc = preg_replace_callback('/\$(\d+)o([1-3])\b/', function ($m) {
    $sid = (int)$m[1]; $idx = (int)$m[2];
    $row = _cache("spellO:$sid", function() use ($sid){ return get_spell_o_row($sid); });
    if (!$row) return '0';
    $bp   = abs((int)($row["effect_basepoints_{$idx}"] ?? 0) + 1);
    $amp  = (int)($row["effect_amplitude_{$idx}"] ?? 0);
    $dsec = _cache("dursecBySpell:$sid", function() use ($row){
      return duration_secs_from_id((int)($row['ref_spellduration'] ?? 0));
    });
    $ticks = ($amp > 0) ? (int)floor(($dsec * 1000) / $amp) : 0;
    return (string)($ticks > 0 ? $bp * $ticks : $bp);
  }, $desc);

  // $12345tN → tick time (sec)
  $desc = preg_replace_callback('/\$(\d+)t([1-3])\b/', function ($m) {
    $sid = (int)$m[1]; $idx = (int)$m[2];
    $row = _cache("spellO:$sid", function() use ($sid){ return get_spell_o_row($sid); });
    if (!$row) return '0';
    $amp = (int)($row["effect_amplitude_{$idx}"] ?? 0);
    $sec = $amp > 0 ? ($amp / 1000.0) : 0.0;
    $s = number_format($sec, 1, '.', '');
    return rtrim(rtrim($s, '0'), '.') ?: '0';
  }, $desc);

  // $12345u → stacks of another spell id
  $desc = preg_replace_callback('/\$(\d+)u\b/', function ($m) {
    $sid = (int)$m[1];
    $n = _stack_amount_for_spell($sid);
    if ($n <= 0) {
      $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
      if ($row) $n = abs((int)($row['effect_basepoints_1'] ?? 0) + 1);
    }
    return (string)max(1, (int)$n);
  }, $desc);

  // $12345n → proc charges of another spell id
  $desc = preg_replace_callback('/\$(\d+)n\b/', function ($m) {
    $sid = (int)$m[1];
    $n = _cache("procchg:$sid", function() use ($sid){ return get_spell_proc_charges($sid); });
    if ($n <= 0) $n = _stack_amount_for_spell($sid);
    if ($n <= 0) {
      $row = _cache("spell:$sid", function() use ($sid){ return get_spell_row($sid); });
      if ($row) $n = abs((int)($row['effect_basepoints_1'] ?? 0) + 1);
    }
    return (string)max(1, (int)$n);
  }, $desc);

  // $12345xN → chaintargets from another spell
  $desc = preg_replace_callback('/\$(\d+)x([1-3])\b/', function($m){
    $sid = (int)$m[1]; $i = (int)$m[2];
    $row = execute_query('armory',
      "SELECT `effect_chaintarget_{$i}` AS x FROM `dbc_spell` WHERE `id`={$sid} LIMIT 1", 1);
    $val = $row ? (int)$row['x'] : 0;
    return (string)max(1, $val);
  }, $desc);

  // ${$*K;sN%} → (K * sNmin)%
  $desc = preg_replace_callback(
    '/\{\$\s*\*\s*([0-9]+)\s*;\s*\$s([1-3])\s*%\s*\}/i',
    function($m) use (&$s1min,&$s2min,&$s3min){
      $k   = (int)$m[1];
      $idx = (int)$m[2];
      $map = [1=>$s1min, 2=>$s2min, 3=>$s3min];
      $base = isset($map[$idx]) ? abs((int)$map[$idx]) : 0;
      return (string)($k * $base) . '%';
    },
    $desc
  );

  // ---------- current spell derived values ----------
  $currId = isset($sp['id']) ? (int)$sp['id'] : 0;

  $die1 = _cache("die:$currId:1", function() use ($currId){ return $currId?get_die_sides_n($currId,1):0; });
  $die2 = _cache("die:$currId:2", function() use ($currId){ return $currId?get_die_sides_n($currId,2):0; });
  $die3 = _cache("die:$currId:3", function() use ($currId){ return $currId?get_die_sides_n($currId,3):0; });

  $formatSLocal = $formatS;
  list($s1min,$s1max,$s1txt) = $formatS($sp['effect_basepoints_1'], $die1, 1);
  list($s2min,$s2max,$s2txt) = $formatS($sp['effect_basepoints_2'], $die2, 1000);
  list($s3min,$s3max,$s3txt) = $formatSLocal((int)($sp['effect_basepoints_3'] ?? 0), $die3);

  // ---------- divisor form: $/N; $sN  or  $/N; $<id>sN  ----------
  // Now returns a range when dividing sN (min and max both divided).
  $desc = preg_replace_callback('/\$\s*\/\s*(\d+)\s*;\s*\$?(\d+)?(s|o)([1-3])/i',
    function ($m) use ($s1min,$s1max,$s2min,$s2max,$s3min,$s3max,$formatSLocal,$rangeText) {
      $div     = max(1.0, (float)$m[1]);
      $spellId = $m[2] ? (int)$m[2] : 0;
      $type    = strtolower($m[3]);
      $idx     = (int)$m[4];

      if ($type === 's') {
        // ---- scalar range (min–max) ----
        if ($spellId === 0) {
          $mins = [1=>$s1min, 2=>$s2min, 3=>$s3min];
          $maxs = [1=>$s1max, 2=>$s2max, 3=>$s3max];
          $min = abs((int)($mins[$idx] ?? 0));
          $max = abs((int)($maxs[$idx] ?? 0));
        } else {
          $row = _cache("spell:$spellId", function() use ($spellId){ return get_spell_row($spellId); });
          if (!$row) return '0';
          $bp  = (int)($row["effect_basepoints_{$idx}"] ?? 0);
          $die = _cache("die:$spellId:$idx", function() use ($spellId,$idx){ return get_die_sides_n($spellId,$idx); });
          list($min,$max) = $formatSLocal($bp,$die);
        }
        $minOut = $min / $div;
        $maxOut = $max / $div;
        // format like the rest of the tooltips
        $fmt = function($v){
          $s = number_format($v, 1, '.', '');
          return rtrim(rtrim($s, '0'), '.') ?: '0';
        };
        return $rangeText((float)$fmt($minOut), (float)$fmt($maxOut));
      }

      // ---- over-time 'oN' remains scalar total; divide normally ----
      $row = ($spellId === 0) ? null : _cache("spellO:$spellId", function() use ($spellId){ return get_spell_o_row($spellId); });
      if (!$row && $spellId !== 0) return '0';

      $bp   = ($spellId === 0) ? null : abs((int)($row["effect_basepoints_{$idx}"] ?? 0) + 1);
      $amp  = ($spellId === 0) ? null : (int)($row["effect_amplitude_{$idx}"] ?? 0);
      $dur  = ($spellId === 0) ? 0 : duration_secs_from_id((int)($row['ref_spellduration'] ?? 0));
      $ticks= ($amp && $amp > 0) ? (int)floor(($dur * 1000)/$amp) : 0;
      $val  = ($spellId === 0) ? 0 : ($ticks > 0 ? $bp * $ticks : $bp);

      $out  = ($div > 0) ? ($val / $div) : $val;
      $s    = number_format($out, 1, '.', '');
      return rtrim(rtrim($s, '0'), '.') ?: '0';
    },
    $desc
  );

  // ---------- duration aggregation for $d and totals ----------
  $getDurSecBySpellId = function($sid){
    if ($sid <= 0) return 0;
    $durId = _cache("durid:$sid", function() use ($sid){ return get_spell_duration_id($sid); });
    return _cache("dursec:$durId", function() use ($durId){ return duration_secs_from_id($durId); });
  };

  $currId  = isset($sp['id']) ? (int)$sp['id'] : 0;
  $durSecs = $getDurSecBySpellId($currId);

  if (strpos($desc, '$d') !== false) {
    $seen  = []; $queue = [$currId]; $depth = 0;
    while (!empty($queue) && $depth < 2) {
      $next = [];
      foreach ($queue as $sid) {
        if ($sid <= 0 || isset($seen[$sid])) continue;
        $seen[$sid] = true;

        $ds = $getDurSecBySpellId($sid);
        if ($ds > $durSecs) $durSecs = $ds;

        $base = _trigger_col_base();
        $col1 = $base.'1'; $col2 = $base.'2'; $col3 = $base.'3';
        $row = execute_query('armory',
          "SELECT `$col1` AS t1, `$col2` AS t2, `$col3` AS t3
             FROM `dbc_spell` WHERE `id`=".(int)$sid." LIMIT 1", 1);
        if ($row) {
          for ($i = 1; $i <= 3; $i++) {
            $tid = isset($row["t{$i}"]) ? (int)$row["t{$i}"] : 0;
            if ($tid > 0 && !isset($seen[$tid])) $next[] = $tid;
          }
        }
      }
      $queue = $next; $depth++;
    }

    if ($durSecs <= 2) {
      $base = _trigger_col_base();
      $col1 = $base.'1'; $col2 = $base.'2'; $col3 = $base.'3';
      $parents = execute_query(
        'armory',
        "SELECT `id` FROM `dbc_spell`
           WHERE `$col1`=".(int)$currId."
              OR `$col2`=".(int)$currId."
              OR `$col3`=".(int)$currId."
           LIMIT 20",
        0
      );
      if (is_array($parents)) {
        foreach ($parents as $pr) {
          $pid = (int)$pr['id'];
          $pds = $getDurSecBySpellId($pid);
          if ($pds > $durSecs) $durSecs = $pds;
        }
      }
    }
  }

  $durMs = $durSecs * 1000;
  $d     = fmt_secs($durSecs);

  // over-time totals for current spell (o1..o3)
  $o1 = (function() use ($sp, $durMs) {
    $bp  = abs((int)($sp['effect_basepoints_1'] ?? 0) + 1);
    $amp = (int)($sp['effect_amplitude_1'] ?? 0);
    $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
    return (string)($ticks > 0 ? $bp * $ticks : $bp);
  })();
  $o2 = (function() use ($sp, $durMs) {
    $bp  = abs((int)($sp['effect_basepoints_2'] ?? 0) + 1);
    $amp = (int)($sp['effect_amplitude_2'] ?? 0);
    $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
    return (string)($ticks > 0 ? $bp * $ticks : $bp);
  })();
  $o3 = (function() use ($sp, $durMs) {
    $bp  = abs((int)($sp['effect_basepoints_3'] ?? 0) + 1);
    $amp = (int)($sp['effect_amplitude_3'] ?? 0);
    $ticks = ($amp > 0) ? (int)floor($durMs / $amp) : 0;
    return (string)($ticks > 0 ? $bp * $ticks : $bp);
  })();

  // headline value fallback
  $h  = (int)($sp['proc_chance'] ?? 0);
  if ($h <= 0) $h = $s1min;

  // radius & tick times for current spell
  $a1 = $trimNum(getRadiusYdsForSpellRow($sp));
  $t1 = $trimNum(((int)($sp['effect_amplitude_1'] ?? 0)) / 1000.0);
  $t2 = $trimNum(((int)($sp['effect_amplitude_2'] ?? 0)) / 1000.0);
  $t3 = $trimNum(((int)($sp['effect_amplitude_3'] ?? 0)) / 1000.0);

  // ${AP*$mN/100} → “(Attack Power * N / 100)”
  $desc = preg_replace_callback(
    '/\{\$\s*(AP|RAP|SP)\s*\*\s*\$m([1-3])\s*\/\s*100\s*\}/i',
    function ($m) use ($s1min, $s2min, $s3min) {
      $idx  = (int)$m[2];
      $map  = [1 => $s1min, 2 => $s2min, 3 => $s3min];
      $pct  = (int)abs($map[$idx] ?? 0);
      $labels = ['AP'=>'Attack Power','RAP'=>'Ranged Attack Power','SP'=>'Spell Power'];
      $label  = $labels[strtoupper($m[1])] ?? strtoupper($m[1]);
      return '(' . $label . ' * ' . $pct . ' / 100)';
    },
    $desc
  );

  // $m1/$m2/$m3 → min only (no ranges)
  $desc = preg_replace_callback('/\$(m[1-3])\b/', function($m) use ($s1min,$s2min,$s3min){
    switch ($m[1]) {
      case 'm1': return (string)$s1min;
      case 'm2': return (string)$s2min;
      case 'm3': return (string)$s3min;
    }
    return $m[0];
  }, $desc);



  // $n (proc charges) – fallback to cached lookup
  $procN = (int)($sp['proc_charges'] ?? 0);
  if ($procN <= 0 && isset($sp['id'])) $procN = (int)get_spell_proc_charges((int)$sp['id']);
  if ($procN > 0) $desc = preg_replace('/\$n\b/i', (string)$procN, $desc);

  // $xN for current spell
  $desc = preg_replace_callback('/\$x([1-3])\b/', function($m) use ($sp){
    $i   = (int)$m[1];
    $val = (int)($sp["effect_chaintarget_{$i}"] ?? 0);
    return (string)max(1, $val);
  }, $desc);

  // $u (max stacks for current spell)
  $u = 1;
  if (!empty($sp['id'])) {
    $u = _stack_amount_for_spell((int)$sp['id']);
    if ($u <= 0) $u = abs((int)($sp['effect_basepoints_1'] ?? 0) + 1);
    if ($u < 1)  $u = 1;
  }

  // Grammar: $l<singular>:<plural>;
  while (preg_match('/\$l([^:;]+):([^;]+);/', $desc, $m, PREG_OFFSET_CAPTURE)) {
    $full     = $m[0][0];
    $offset   = $m[0][1];
    $singular = $m[1][0];
    $plural   = $m[2][0];

    $before = substr($desc, 0, $offset);
    $val = 2;
    if (preg_match('/(\d+(?:\.\d+)?)(?!.*\d)/', $before, $nm)) $val = (float)$nm[1];
    $word = (abs($val - 1.0) < 1e-6) ? $singular : $plural;

    $desc = substr($desc, 0, $offset) . $word . substr($desc, $offset + strlen($full));
  }

  // $*factor; token  (supports s1..s3 & o1..o3 & m1..m3). For sN we multiply the **min** by design.
  $__mulMap = array(
    's1' => (float)$s1min, 's2' => (float)$s2min, 's3' => (float)$s3min,
    'o1' => (float)$o1,    'o2' => (float)$o2,    'o3' => (float)$o3,
    'm1' => (float)$s1min, 'm2' => (float)$s2min, 'm3' => (float)$s3min
  );

  $desc = preg_replace_callback('/\$\*\s*([0-9]+(?:\.[0-9]+)?)\s*;\s*(s[1-3]|o[1-3]|m[1-3])/i',
    function($m) use ($__mulMap) {
      $factor = (float)$m[1];
      $key    = strtolower($m[2]);
      $base   = isset($__mulMap[$key]) ? (float)$__mulMap[$key] : 0.0;
      $val    = $factor * $base;
      $s = number_format($val, 1, '.', '');
      $s = rtrim(rtrim($s, '0'), '.');
      return ($s === '') ? '0' : $s;
    }, $desc
  );

// Divisor form: $/N; $sN / $mN / $<id>sN  → divide both min & max; oN stays scalar.
  $desc = preg_replace_callback('/\$\s*\/\s*(\d+)\s*;\s*\$?(\d+)?(s|o)([1-3])/i',
    function ($m) use ($s1min,$s1max,$s2min,$s2max,$s3min,$s3max,$formatSLocal,$rangeText) {
      $div     = max(1.0, (float)$m[1]);
      $spellId = $m[2] ? (int)$m[2] : 0;
      $type    = strtolower($m[3]);
      $idx     = (int)$m[4];

      if ($type === 's') {
        // compute min/max, then divide both
        if ($spellId === 0) {
          $mins = [1=>$s1min, 2=>$s2min, 3=>$s3min];
          $maxs = [1=>$s1max, 2=>$s2max, 3=>$s3max];
          $min = abs((int)($mins[$idx] ?? 0));
          $max = abs((int)($maxs[$idx] ?? 0));
        } else {
          $row = _cache("spell:$spellId", function() use ($spellId){ return get_spell_row($spellId); });
          if (!$row) return '0';
          $bp  = (int)($row["effect_basepoints_{$idx}"] ?? 0);
          $die = _cache("die:$spellId:$idx", function() use ($spellId,$idx){ return get_die_sides_n($spellId,$idx); });
          list($min,$max) = $formatSLocal($bp,$die);
        }

        $fmt = function($v){
          $s = number_format($v, 1, '.', '');
          return rtrim(rtrim($s, '0'), '.') ?: '0';
        };

        $minOut = (float)$fmt($min / $div);
        $maxOut = (float)$fmt($max / $div);
        return ($maxOut > $minOut) ? ($minOut . '–' . $maxOut) : (string)$minOut;
      }

    // ---- over-time 'oN' remains scalar total; divide normally ----
    $row = ($spellId === 0) ? null : _cache("spellO:$spellId", function() use ($spellId){ return get_spell_o_row($spellId); });
    if (!$row && $spellId !== 0) return '0';

    $bp   = ($spellId === 0) ? null : abs((int)($row["effect_basepoints_{$idx}"] ?? 0) + 1);
    $amp  = ($spellId === 0) ? null : (int)($row["effect_amplitude_{$idx}"] ?? 0);
    $dur  = ($spellId === 0) ? 0 : duration_secs_from_id((int)($row['ref_spellduration'] ?? 0));
    $ticks= ($amp && $amp > 0) ? (int)floor(($dur * 1000)/$amp) : 0;
    $val  = ($spellId === 0) ? 0 : ($ticks > 0 ? $bp * $ticks : $bp);

    $out  = ($div > 0) ? ($val / $div) : $val;
    $s    = number_format($out, 1, '.', '');
    return rtrim(rtrim($s, '0'), '.') ?: '0';
  },
  $desc
);


  // alias: $D == $d
  $desc = str_replace('$D', $d, $desc);

  // Final map for current spell tokens
  $desc = strtr($desc, [
    '$s1' => $s1txt, '$s2' => $s2txt, '$s3' => $s3txt,   // now min–max where applicable
    '$o1' => $o1,    '$o2' => $o2,    '$o3' => $o3,
    '$t1' => $t1,    '$t2' => $t2,    '$t3' => $t3,
    '$a1' => $a1,
    '$d'  => $d,
    '$h'  => (string)max(0,(int)($sp['proc_chance'] ?? 0)),
    '$u'  => (string)$u,
  ]);

  // cleanup
  $desc = preg_replace('/(\d+)1%/', '$1%', $desc);
  $desc = preg_replace('/\$\(/', '(', $desc);
  $desc = preg_replace('/\$\w*sec:secs;/', ' sec', $desc);
  $desc = preg_replace('/\s+%/', '%', $desc);

  return $desc;
}
?>