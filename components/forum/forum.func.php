<?php

require_once __DIR__ . '/forum.data.php';
require_once __DIR__ . '/forum.guard.php';
require_once __DIR__ . '/forum.scope.php';
require_once __DIR__ . '/forum.avatar.php';
require_once __DIR__ . '/forum.read.php';

$yesterday_ts = mktime(0, 0, 0, date("m"), date("d") - 1, date("Y"));
