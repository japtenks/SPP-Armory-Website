<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

$newsForumId = (int)$MW->getConfig->generic_values->forum->news_forum_id;
header('Location: index.php?n=forum&sub=viewforum&fid=' . $newsForumId, true, 302);
exit;
?>
