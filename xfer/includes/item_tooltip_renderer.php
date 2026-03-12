<?php
if (!function_exists('spp_item_tooltip_item_class_name')) {
    function spp_item_tooltip_item_class_name($class, $subclass) {
        $class = (int)$class;
        $subclass = (int)$subclass;

        switch ($class) {
            case 2:
                $map = [
                    0 => 'Axe (1H)', 1 => 'Axe (2H)', 2 => 'Bow', 3 => 'Gun',
                    4 => 'Mace (1H)', 5 => 'Mace (2H)', 6 => 'Polearm', 7 => 'Sword (1H)',
                    8 => 'Sword (2H)', 10 => 'Staff', 13 => 'Fist Weapon', 15 => 'Dagger',
                    16 => 'Thrown', 18 => 'Crossbow', 19 => 'Wand', 20 => 'Fishing Pole'
                ];
                return $map[$subclass] ?? 'Weapon';
            case 4:
                $map = [
                    0 => 'Misc', 1 => 'Cloth', 2 => 'Leather', 3 => 'Mail', 4 => 'Plate',
                    6 => 'Shield', 7 => 'Libram', 8 => 'Idol', 9 => 'Totem', 10 => 'Sigil'
                ];
                return $map[$subclass] ?? 'Armor';
            case 15:
                $map = [0 => 'Junk', 2 => 'Pet', 3 => 'Holiday', 4 => 'Other', 5 => 'Mount'];
                return $map[$subclass] ?? 'Misc';
            default:
                return 'Item';
        }
    }
}

if (!function_exists('spp_item_tooltip_inventory_type_name')) {
    function spp_item_tooltip_inventory_type_name($inventoryType) {
        $map = [
            1 => 'Head', 2 => 'Neck', 3 => 'Shoulder', 5 => 'Chest', 6 => 'Waist', 7 => 'Legs',
            8 => 'Feet', 9 => 'Wrist', 10 => 'Hands', 11 => 'Finger', 12 => 'Trinket', 13 => 'One Hand',
            14 => 'Shield', 15 => 'Weapon', 16 => 'Back', 17 => 'Two-Hand', 21 => 'Main Hand',
            22 => 'Off Hand', 23 => 'Held In Off-hand'
        ];
        $inventoryType = (int)$inventoryType;
        return $map[$inventoryType] ?? ('Slot ' . $inventoryType);
    }
}

if (!function_exists('spp_item_tooltip_render_spell_effect')) {
    function spp_item_tooltip_render_spell_effect($spellId, $trigger, array $row = []) {
        $spellId = (int)$spellId;
        $trigger = (int)$trigger;
        if ($spellId <= 0) {
            return '';
        }

        $spell = armory_query("SELECT * FROM dbc_spell WHERE id={$spellId} LIMIT 1", 1);
        if (!$spell) {
            return '';
        }

        $description = spp_item_tooltip_replace_spell_tokens((string)$spell['description'], $spell);
        if ($description === '') {
            return '';
        }

        $prefix = ($trigger === 1) ? 'Equip: '
            : (($trigger === 2) ? 'Use: '
            : (($trigger === 4) ? 'Chance on hit: ' : ''));

        return '<div style="color:#1eff00">' . htmlspecialchars($prefix . $description) . '</div>';
    }
}

if (!function_exists('spp_render_item_tooltip_html')) {
    function spp_render_item_tooltip_html(array $item) {
        $entry = (int)($item['entry'] ?? 0);
        if ($entry <= 0) {
            return '';
        }

        $sql = "
            SELECT Quality, ItemLevel, InventoryType, class, subclass,
                   RequiredLevel, Armor, MaxDurability, AllowableClass,
                   stat_type1, stat_value1,
                   stat_type2, stat_value2,
                   stat_type3, stat_value3,
                   stat_type4, stat_value4,
                   stat_type5, stat_value5,
                   stat_type6, stat_value6,
                   stat_type7, stat_value7,
                   stat_type8, stat_value8,
                   stat_type9, stat_value9,
                   stat_type10, stat_value10,
                   spellid_1, spelltrigger_1,
                   spellid_2, spelltrigger_2,
                   spellid_3, spelltrigger_3,
                   spellid_4, spelltrigger_4,
                   spellid_5, spelltrigger_5,
                   holy_res, fire_res,
                   nature_res, frost_res,
                   shadow_res, arcane_res
            FROM item_template
            WHERE entry = {$entry}
            LIMIT 1
        ";
        $row = world_query($sql, 1);
        if (!$row) {
            return '';
        }

        $qualityColors = [
            0 => '#9d9d9d', 1 => '#ffffff', 2 => '#1eff00', 3 => '#0070dd', 4 => '#a335ee', 5 => '#ff8000'
        ];
        $qualityColor = $qualityColors[(int)$row['Quality']] ?? '#ffffff';

        $slotName = spp_item_tooltip_inventory_type_name((int)$row['InventoryType']);
        $className = spp_item_tooltip_item_class_name((int)$row['class'], (int)$row['subclass']);

        $html  = '<div class="tt-item">';
        $html .= '<h5 style="color:' . $qualityColor . '">' . htmlspecialchars((string)($item['name'] ?? 'Unknown Item')) . '</h5>';
        $html .= '<div style="color:#ffd100">Item Level ' . (int)$row['ItemLevel'] . '</div>';
        $html .= '<div style="display:flex;justify-content:space-between;">'
              . '<div>' . htmlspecialchars($slotName) . '</div>'
              . '<div style="text-align:right;">' . htmlspecialchars($className) . '</div>'
              . '</div>';

        if ((int)$row['Armor'] > 0) {
            $html .= '<div>' . (int)$row['Armor'] . ' Armor</div>';
        }

        for ($i = 1; $i <= 10; $i++) {
            $type = (int)($row['stat_type' . $i] ?? 0);
            $value = (int)($row['stat_value' . $i] ?? 0);
            if ($type && $value) {
                $label = stat_name($type);
                if ($label !== '') {
                    $html .= '<div>+' . $value . ' ' . htmlspecialchars($label) . '</div>';
                }
            }
        }

        $resistances = [
            'holy_res' => 'Holy Resistance',
            'fire_res' => 'Fire Resistance',
            'nature_res' => 'Nature Resistance',
            'frost_res' => 'Frost Resistance',
            'shadow_res' => 'Shadow Resistance',
            'arcane_res' => 'Arcane Resistance',
        ];
        foreach ($resistances as $column => $label) {
            $value = (int)($row[$column] ?? 0);
            if ($value > 0) {
                $html .= '<div style="color:#00ccff">+' . $value . ' ' . htmlspecialchars($label) . '</div>';
            }
        }

        for ($i = 1; $i <= 5; $i++) {
            $html .= spp_item_tooltip_render_spell_effect((int)($row['spellid_' . $i] ?? 0), (int)($row['spelltrigger_' . $i] ?? 0), $row);
        }

        if ((int)$row['MaxDurability'] > 0) {
            $html .= '<div>Durability ' . (int)$row['MaxDurability'] . ' / ' . (int)$row['MaxDurability'] . '</div>';
        }

        if ((int)$row['RequiredLevel'] > 0) {
            $html .= '<div>Requires Level ' . (int)$row['RequiredLevel'] . '</div>';
        }

        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('spp_item_tooltip_replace_spell_tokens')) {
    function spp_item_tooltip_replace_spell_tokens($description, array $spell) {
        $format = static function ($value) {
            $formatted = number_format((float)$value, 1, '.', '');
            return rtrim(rtrim($formatted, '0'), '.') ?: '0';
        };
        $formatInt = static function ($value) {
            return (string)((int)round($value));
        };
        $formatS = static function ($basePoints, $dieSides) {
            $min = $basePoints + 1;
            if ($dieSides <= 1) {
                return [$min, $min, (string)abs($min)];
            }
            $max = $basePoints + $dieSides;
            if ($max < $min) {
                $temp = $min;
                $min = $max;
                $max = $temp;
            }
            return [$min, $max, $min . ' to ' . $max];
        };

        $spellId = (int)($spell['id'] ?? 0);
        $die1 = _cache('die:' . $spellId . ':1', function () use ($spellId) { return $spellId ? get_die_sides_n($spellId, 1) : 0; });
        $die2 = _cache('die:' . $spellId . ':2', function () use ($spellId) { return $spellId ? get_die_sides_n($spellId, 2) : 0; });
        $die3 = _cache('die:' . $spellId . ':3', function () use ($spellId) { return $spellId ? get_die_sides_n($spellId, 3) : 0; });

        list($s1Min, $s1Max, $s1Text) = $formatS((int)($spell['effect_basepoints_1'] ?? 0), $die1);
        list($s2Min, $s2Max, $s2Text) = $formatS((int)($spell['effect_basepoints_2'] ?? 0), $die2);
        list($s3Min, $s3Max, $s3Text) = $formatS((int)($spell['effect_basepoints_3'] ?? 0), $die3);

        $durationId = (int)($spell['ref_spellduration'] ?? 0);
        $durationSeconds = duration_secs_from_id($durationId);
        $durationMilliseconds = $durationSeconds * 1000;

        $oN = static function (array $spellRow, $index, $durationMs) {
            $basePoints = abs((int)($spellRow['effect_basepoints_' . $index] ?? 0) + 1);
            $amplitude = (int)($spellRow['effect_amplitude_' . $index] ?? 0);
            $ticks = ($amplitude > 0) ? (int)floor($durationMs / $amplitude) : 0;
            return $ticks > 0 ? ($basePoints * $ticks) : $basePoints;
        };

        $o1 = $oN($spell, 1, $durationMilliseconds);
        $o2 = $oN($spell, 2, $durationMilliseconds);
        $o3 = $oN($spell, 3, $durationMilliseconds);

        $headline = (int)($spell['proc_chance'] ?? 0);
        if ($headline <= 0) {
            $headline = (int)$s1Min;
        }

        $radius = $format(getRadiusYdsForSpellRow($spell));
        $t1 = $format(((int)($spell['effect_amplitude_1'] ?? 0)) / 1000);
        $t2 = $format(((int)($spell['effect_amplitude_2'] ?? 0)) / 1000);
        $t3 = $format(((int)($spell['effect_amplitude_3'] ?? 0)) / 1000);
        $stacks = _stack_amount_for_spell($spellId);
        if ($stacks <= 0) {
            $stacks = max(1, abs((int)($spell['effect_basepoints_1'] ?? 0) + 1));
        }
        $durationText = fmt_secs($durationSeconds);

        $extS = static function ($sid, $index) use ($formatS) {
            $row = _cache('spell:' . $sid, function () use ($sid) { return get_spell_row($sid); });
            if (!$row) {
                return [0, 0, '0'];
            }
            return $formatS((int)($row['effect_basepoints_' . $index] ?? 0), get_die_sides_n($sid, $index));
        };
        $extO = static function ($sid, $index) {
            $row = _cache('spellO:' . $sid, function () use ($sid) { return get_spell_row($sid); });
            if (!$row) {
                return 0;
            }
            $basePoints = abs((int)($row['effect_basepoints_' . $index] ?? 0) + 1);
            $amplitude = (int)($row['effect_amplitude_' . $index] ?? 0);
            $seconds = duration_secs_from_id((int)($row['ref_spellduration'] ?? 0));
            $ticks = ($amplitude > 0) ? (int)floor(($seconds * 1000) / $amplitude) : 0;
            return $ticks > 0 ? ($basePoints * $ticks) : $basePoints;
        };
        $extD = static function ($sid) {
            $sid = (int)$sid;
            $seconds = duration_secs_from_id(get_spell_duration_id($sid));
            if ($seconds <= 0) {
                $seen = [];
                $queue = [$sid];
                for ($depth = 0; !empty($queue) && $depth < 2; $depth++) {
                    $next = [];
                    foreach ($queue as $spellIdValue) {
                        if (isset($seen[$spellIdValue])) {
                            continue;
                        }
                        $seen[$spellIdValue] = true;
                        $columns = "COALESCE(effect_trigger_spell_id_1, effect_trigger_spell_1, 0) AS t1, COALESCE(effect_trigger_spell_id_2, effect_trigger_spell_2, 0) AS t2, COALESCE(effect_trigger_spell_id_3, effect_trigger_spell_3, 0) AS t3";
                        $row = armory_query("SELECT {$columns} FROM dbc_spell WHERE id={$spellIdValue} LIMIT 1", 1);
                        for ($i = 1; $i <= 3; $i++) {
                            $triggerKey = 't' . $i;
                            $triggerSpell = isset($row[$triggerKey]) ? (int)$row[$triggerKey] : 0;
                            if ($triggerSpell <= 0) {
                                continue;
                            }
                            $triggerSeconds = duration_secs_from_id(get_spell_duration_id($triggerSpell));
                            if ($triggerSeconds > $seconds) {
                                $seconds = $triggerSeconds;
                            }
                            if (!isset($seen[$triggerSpell])) {
                                $next[] = $triggerSpell;
                            }
                        }
                    }
                    $queue = $next;
                }
            }
            if ($seconds <= 0) {
                $condition = "COALESCE(effect_trigger_spell_id_1,0)={$sid} OR COALESCE(effect_trigger_spell_1,0)={$sid} OR COALESCE(effect_trigger_spell_id_2,0)={$sid} OR COALESCE(effect_trigger_spell_2,0)={$sid} OR COALESCE(effect_trigger_spell_id_3,0)={$sid} OR COALESCE(effect_trigger_spell_3,0)={$sid}";
                $rows = armory_query("SELECT id FROM dbc_spell WHERE {$condition} LIMIT 20", 0);
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        $triggerSeconds = duration_secs_from_id(get_spell_duration_id((int)$row['id']));
                        if ($triggerSeconds > $seconds) {
                            $seconds = $triggerSeconds;
                        }
                    }
                }
            }
            return fmt_secs($seconds);
        };

        $description = preg_replace_callback('/\$(\d+)s([1-3])\b/', function ($matches) use ($extS) {
            $result = $extS((int)$matches[1], (int)$matches[2]);
            return $result[2];
        }, $description);
        $description = preg_replace_callback('/\$(\d+)s\b/', function ($matches) use ($extS) {
            $result = $extS((int)$matches[1], 1);
            return $result[2];
        }, $description);
        $description = preg_replace_callback('/\$(\d+)o([1-3])\b/', function ($matches) use ($extO) {
            return (string)$extO((int)$matches[1], (int)$matches[2]);
        }, $description);
        $description = preg_replace_callback('/\$(\d+)d\b/', function ($matches) use ($extD) {
            return $extD((int)$matches[1]);
        }, $description);
        $description = preg_replace_callback('/\$(\d+)a1\b/', function ($matches) {
            $row = _cache('spell:' . (int)$matches[1], function () use ($matches) { return get_spell_row((int)$matches[1]); });
            if (!$row) {
                return '0';
            }
            return (string)(float)getRadiusYdsForSpellRow($row);
        }, $description);
        $description = preg_replace_callback('/\$(\d+)t([1-3])\b/', function ($matches) {
            $sid = (int)$matches[1];
            $index = (int)$matches[2];
            $row = _cache('spellO:' . $sid, function () use ($sid) { return get_spell_row($sid); });
            if (!$row) {
                return '0';
            }
            $amplitude = (int)($row['effect_amplitude_' . $index] ?? 0);
            return $amplitude > 0 ? rtrim(rtrim(number_format($amplitude / 1000, 1, '.', ''), '0'), '.') : '0';
        }, $description);

        $description = preg_replace_callback('/\$\s*\/\s*(-?\d+)\s*;\s*(?:\$?(\d+))?([sS]|o)([1-3])\b/', function ($matches) use ($s1Min, $s1Max, $s2Min, $s2Max, $s3Min, $s3Max, $extS, $extO, $format, $formatInt, $o1, $o2, $o3) {
            $divisor = (int)$matches[1] ?: 1;
            $sid = isset($matches[2]) ? (int)$matches[2] : 0;
            $type = strtolower($matches[3]);
            $index = (int)$matches[4];
            if ($type === 's') {
                if ($sid === 0) {
                    $minMap = [1 => $s1Min, 2 => $s2Min, 3 => $s3Min];
                    $maxMap = [1 => $s1Max, 2 => $s2Max, 3 => $s3Max];
                } else {
                    $tmp = $extS($sid, $index);
                    $minMap = [$index => $tmp[0]];
                    $maxMap = [$index => $tmp[1]];
                }
                $min = abs((float)($minMap[$index] ?? 0)) / $divisor;
                $max = abs((float)($maxMap[$index] ?? $min)) / $divisor;
                return ($max > $min) ? $formatInt(floor($min)) . ' to ' . $formatInt(ceil($max)) : $format($min);
            }
            $value = ($sid === 0) ? ([1 => $o1, 2 => $o2, 3 => $o3][$index] ?? 0) : $extO($sid, $index);
            return $format($value / $divisor);
        }, $description);

        $description = preg_replace_callback('/\$\s*\/\s*(-?\d+)\s*;\s*S([1-3])\b/', function ($matches) use ($s1Min, $s2Min, $s3Min, $format) {
            $divisor = (int)$matches[1] ?: 1;
            $index = (int)$matches[2];
            $map = [1 => $s1Min, 2 => $s2Min, 3 => $s3Min];
            return $format(abs((float)($map[$index] ?? 0)) / $divisor);
        }, $description);

        $playerLevel = isset($GLOBALS['expansion']) ? (($GLOBALS['expansion'] == 2) ? 80 : (($GLOBALS['expansion'] == 1) ? 70 : 60)) : 60;
        $description = preg_replace_callback('/\$\{\s*\(\s*300\s*-\s*10\s*\*\s*\$max\s*\(\s*0\s*,\s*\$PL\s*-\s*60\s*\)\s*\)\s*\/\s*10\s*\}/i', function () use ($playerLevel, $format) {
            $rage = (300 - 10 * max(0, (int)$playerLevel - 60)) / 10;
            return $format($rage);
        }, $description);

        $description = preg_replace_callback('/\$\{\s*\$m([1-3])\s*\/\s*(-?\d+)\s*\}/i', function ($matches) use ($s1Min, $s2Min, $s3Min, $format) {
            $map = [1 => $s1Min, 2 => $s2Min, 3 => $s3Min];
            return $format(abs((float)($map[(int)$matches[1]] ?? 0)) / ((int)$matches[2] ?: 1));
        }, $description);
        $description = preg_replace_callback('/\$\{\s*\$(\d+)m([1-3])\s*\/\s*(-?\d+)\s*\}/i', function ($matches) use ($extS, $format) {
            $tmp = $extS((int)$matches[1], (int)$matches[2]);
            return $format(abs((float)$tmp[0]) / ((int)$matches[3] ?: 1));
        }, $description);
        $description = preg_replace_callback('/\$\{\s*(-?\d+)\s*\/\s*(-?\d+)\s*\}\b/', function ($matches) use ($format) {
            $a = (int)$matches[1];
            $b = (int)$matches[2];
            $value = (abs($a) >= abs($b)) ? $a : $b;
            return $format(abs($value) / 10.0);
        }, $description);
        $description = preg_replace_callback('/\{\$\s*(AP|RAP|SP)\s*\*\s*\$m([1-3])\s*\/\s*100\s*\}/i', function ($matches) use ($s1Min, $s2Min, $s3Min) {
            $pct = [1 => $s1Min, 2 => $s2Min, 3 => $s3Min][(int)$matches[2]] ?? 0;
            $label = ['AP' => 'Attack Power', 'RAP' => 'Ranged Attack Power', 'SP' => 'Spell Power'][strtoupper($matches[1])] ?? strtoupper($matches[1]);
            return '(' . $label . ' * ' . (int)abs($pct) . ' / 100)';
        }, $description);

        $description = strtr($description, [
            '$s1' => $s1Text, '$s2' => $s2Text, '$s3' => $s3Text,
            '$o1' => (string)$o1, '$o2' => (string)$o2, '$o3' => (string)$o3,
            '$t1' => $t1, '$t2' => $t2, '$t3' => $t3,
            '$a1' => $radius, '$d' => $durationText, '$D' => $durationText,
            '$h' => (string)$headline, '$u' => (string)$stacks,
        ]);
        $description = preg_replace_callback('/\$(m[1-3]|m)\b/i', function ($matches) use ($s1Min, $s2Min, $s3Min) {
            if (strtolower($matches[1]) === 'm') {
                return (string)$s1Min;
            }
            $map = ['m1' => $s1Min, 'm2' => $s2Min, 'm3' => $s3Min];
            $key = strtolower($matches[1]);
            return (string)($map[$key] ?? 0);
        }, $description);
        $description = preg_replace('/\$h1\b/', (string)$headline, $description);

        while (preg_match('/\$l([^:;]+):([^;]+);/', $description, $matches, PREG_OFFSET_CAPTURE)) {
            $full = $matches[0][0];
            $offset = $matches[0][1];
            $singular = $matches[1][0];
            $plural = $matches[2][0];
            $before = substr($description, 0, $offset);
            $value = 2;
            if (preg_match('/(\d+(?:\.\d+)?)(?!.*\d)/', $before, $numberMatches)) {
                $value = (float)$numberMatches[1];
            }
            $word = (abs($value - 1.0) < 1e-6) ? $singular : $plural;
            $description = substr($description, 0, $offset) . $word . substr($description, $offset + strlen($full));
        }

        $mulMap = [
            's1' => (float)$s1Min, 's2' => (float)$s2Min, 's3' => (float)$s3Min,
            'o1' => (float)$o1, 'o2' => (float)$o2, 'o3' => (float)$o3,
            'm1' => (float)$s1Min, 'm2' => (float)$s2Min, 'm3' => (float)$s3Min,
        ];
        $description = preg_replace_callback('/\$\*\s*([0-9]+(?:\.[0-9]+)?)\s*;\s*(s[1-3]|o[1-3]|m[1-3])\b/i', function ($matches) use ($mulMap, $format) {
            $key = strtolower($matches[2]);
            $factor = (float)$matches[1];
            $base = $mulMap[$key] ?? 0.0;
            return $format($factor * $base);
        }, $description);

        $description = preg_replace('/\s+%/', '%', $description);
        $description = preg_replace('/\$\(/', '(', $description);
        $description = preg_replace('/(\d+)1%/', '$1%', $description);
        $description = preg_replace('/\$(?=\d)(\d+(?:\.\d+)?)\b/', '$1', $description);
        $description = preg_replace('/(-?\d+(?:\.\d+)?)\.(?:1|2)(?=\s*sec\b)/', '$1', $description);

        return $description;
    }
}
