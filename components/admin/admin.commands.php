<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

$commandsForumId = isset($commands_forum_id) ? (int)$commands_forum_id : 0;
header('Location: index.php?n=forum&sub=viewforum&fid=' . $commandsForumId, true, 302);
exit;
?>
