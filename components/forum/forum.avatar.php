<?php

function get_character_portrait_path($guid, $gender, $race, $class)
{
    $portraitDir = "templates/offlike/images/portraits/wow-70/";
    $cacheDir = "templates/offlike/cache/portraits/";
    $cacheFile = $cacheDir . "portrait_{$guid}.gif";

    if (file_exists($cacheFile)) {
        return $cacheFile;
    }

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    $matches = glob(sprintf("%s%d-%d-%d*.gif", $portraitDir, $gender, $race, $class));
    if (!empty($matches)) {
        sort($matches, SORT_NATURAL);
        copy($matches[0], $cacheFile);
        return $cacheFile;
    }

    return "/templates/offlike/images/icons/race/" . $race . '-' . $gender . ".gif";
}

function get_forum_staff_avatar_data_uri()
{
    static $uri = null;
    if ($uri !== null) {
        return $uri;
    }

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#2b1a06"/>
      <stop offset="100%" stop-color="#6f4a12"/>
    </linearGradient>
    <linearGradient id="trim" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#ffd57c"/>
      <stop offset="100%" stop-color="#b7862c"/>
    </linearGradient>
  </defs>
  <rect x="4" y="4" width="88" height="88" rx="14" fill="url(#bg)" stroke="url(#trim)" stroke-width="4"/>
  <circle cx="48" cy="36" r="15" fill="#e8d0a0"/>
  <path d="M26 73c4-13 15-21 22-21s18 8 22 21" fill="#1e2f3e"/>
  <path d="M48 20l4 8 9 1-7 6 2 9-8-5-8 5 2-9-7-6 9-1z" fill="#ffd57c"/>
  <text x="48" y="85" text-anchor="middle" font-family="Trebuchet MS, Arial, sans-serif" font-size="14" font-weight="bold" fill="#ffd57c">SPP</text>
</svg>
SVG;

    $uri = 'data:image/svg+xml;base64,' . base64_encode($svg);
    return $uri;
}

function get_forum_avatar_fallback($posterName = '')
{
    $normalized = strtolower(trim((string)$posterName));
    if ($normalized === 'web team' || $normalized === 'spp team') {
        return get_forum_staff_avatar_data_uri();
    }

    return '/templates/offlike/images/forum/icons/lock-icon.gif';
}
