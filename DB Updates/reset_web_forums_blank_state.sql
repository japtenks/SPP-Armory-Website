-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               5.7.26 - MySQL Community Server (GPL)
-- Server OS:                    Win32
-- HeidiSQL Version:             10.2.0.5599
-- --------------------------------------------------------
--
-- Forum seed script (non-destructive)
-- Run this against the website/forum database that contains:
--   f_forums, f_topics, f_posts
-- This script:
--   - keeps all existing topics/posts
--   - keeps forum_id = 1 as News
--   - adds Classic/TBC/Wrath forums if they are missing
--   - adds one new News announcement as the newest topic
--   - adds one welcome topic to each expansion forum only if that forum is empty

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

SET @reset_time := UNIX_TIMESTAMP();
SET @news_forum_id := 1;
SET @topic_author := 'web Team';
SET @topic_author_id := 0;
SET @topic_author_ip := '::1';
SET @topic_subject := 'New site has been released';
SET @topic_message := 'Major site redesign<br />\r\n<br />\r\nHere are all of the major improvements included in this build:<br />\r\n<br />\r\n 
- [Game Guide] Searchable GM and Bot commands page<br />\r\n 
- [Workshop] Realm Status, Player Map and Statics reworked.<br />\r\n
- [Workshop] New Auction House, Downloads and Armor Set pages.<br />\r\n 
- [Forums] Working*<br />\r\n 
- [Armory] Characters list all the bots/players. Filter for player and bots.<br />\r\n
- [Armory] Character view has been updated, along with new tab views*.<br />\r\n
- [Armory] Guilds list view created.<br />\r\n
- [Armory] Guild View modernized.<br />\r\n
- [Armory] Honor View modernized.<br />\r\n
- [Armory] Item Seach modernized.<br />\r\n<br />\r\n
Known issues:<br />\r\n
- Must be logged in to the "top" char in your account list.<br />\r\n
- Talent Tab in Char view is rendering wonky.<br />\r\n<br />\r\n
Please report any additional website issues in the Website Issues section.';

SET @classic_subject := 'Welcome to Classic discussion';
SET @classic_message := 'Use this forum for Classic questions, feedback, and discussion.';
SET @tbc_subject := 'Welcome to Burning Crusade discussion';
SET @tbc_message := 'Use this forum for Burning Crusade questions, feedback, and discussion.';
SET @wotlk_subject := 'Welcome to Wrath discussion';
SET @wotlk_message := 'Use this forum for Wrath of the Lich King questions, feedback, and discussion.';

-- UPDATE website accounts
REPLACE INTO `website_accounts` (`account_id`, `display_name`)
SELECT `id`, `username`
FROM `account`;

UPDATE `website_accounts`
SET `g_id` = '3'
WHERE `account_id` IN (SELECT `id` FROM `account` WHERE `gmlevel` = '3');

UPDATE `website_accounts`
SET `g_id` = '4'
WHERE `account_id` IN (SELECT `id` FROM `account` WHERE `gmlevel` = '4');

SET @news_cat_id := COALESCE(
    (SELECT `cat_id` FROM `f_forums` WHERE `forum_id` = @news_forum_id LIMIT 1),
    1
);

UPDATE `f_forums`
SET `forum_name` = 'News',
    `forum_desc` = 'Official website announcements and release notes.',
    `disp_position` = 1,
    `cat_id` = @news_cat_id,
    `quick_reply` = 0,
    `hidden` = 0,
    `closed` = 0
WHERE `forum_id` = @news_forum_id;

INSERT INTO `f_forums` (
    `forum_id`,
    `forum_name`,
    `forum_desc`,
    `num_topics`,
    `num_posts`,
    `last_topic_id`,
    `disp_position`,
    `cat_id`,
    `quick_reply`,
    `hidden`,
    `closed`
) VALUES
    (2, 'Classic', 'Discussion and updates for the Classic realm.', 0, 0, 0, 2, @news_cat_id, 1, 0, 0),
    (3, 'The Burning Crusade', 'Discussion and updates for the Burning Crusade realm.', 0, 0, 0, 3, @news_cat_id, 1, 0, 0),
    (4, 'Wrath of the Lich King', 'Discussion and updates for the Wrath realm.', 0, 0, 0, 4, @news_cat_id, 1, 0, 0)
ON DUPLICATE KEY UPDATE
    `forum_name` = VALUES(`forum_name`),
    `forum_desc` = VALUES(`forum_desc`),
    `disp_position` = VALUES(`disp_position`),
    `cat_id` = VALUES(`cat_id`),
    `quick_reply` = VALUES(`quick_reply`),
    `hidden` = VALUES(`hidden`),
    `closed` = VALUES(`closed`);

-- Add the new News topic as the newest announcement
INSERT INTO `f_topics` (
    `topic_poster`,
    `topic_poster_id`,
    `topic_name`,
    `topic_posted`,
    `last_post`,
    `last_post_id`,
    `last_poster`,
    `num_views`,
    `num_replies`,
    `closed`,
    `sticky`,
    `redirect_url`,
    `forum_id`
) VALUES (
    @topic_author,
    @topic_author_id,
    @topic_subject,
    @reset_time,
    @reset_time,
    0,
    @topic_author,
    1,
    1,
    0,
    1,
    NULL,
    @news_forum_id
);

SET @news_topic_id := LAST_INSERT_ID();

INSERT INTO `f_posts` (
    `poster`,
    `poster_id`,
    `poster_ip`,
    `poster_character_id`,
    `message`,
    `posted`,
    `edited`,
    `edited_by`,
    `topic_id`
) VALUES (
    @topic_author,
    @topic_author_id,
    @topic_author_ip,
    0,
    @topic_message,
    @reset_time,
    NULL,
    NULL,
    @news_topic_id
);

SET @news_post_id := LAST_INSERT_ID();

UPDATE `f_topics`
SET `last_post` = @reset_time,
    `last_post_id` = @news_post_id,
    `last_poster` = @topic_author
WHERE `topic_id` = @news_topic_id;

UPDATE `f_forums`
SET `num_topics` = (SELECT COUNT(*) FROM `f_topics` WHERE `forum_id` = @news_forum_id),
    `num_posts` = (SELECT COUNT(*) FROM `f_posts` p INNER JOIN `f_topics` t ON p.`topic_id` = t.`topic_id` WHERE t.`forum_id` = @news_forum_id),
    `last_topic_id` = @news_topic_id
WHERE `forum_id` = @news_forum_id;

-- Seed Classic welcome topic only if the forum has no topics yet
SET @classic_topic_count := (SELECT COUNT(*) FROM `f_topics` WHERE `forum_id` = 2);
INSERT INTO `f_topics` (
    `topic_poster`,
    `topic_poster_id`,
    `topic_name`,
    `topic_posted`,
    `last_post`,
    `last_post_id`,
    `last_poster`,
    `num_views`,
    `num_replies`,
    `closed`,
    `sticky`,
    `redirect_url`,
    `forum_id`
)
SELECT
    @topic_author,
    @topic_author_id,
    @classic_subject,
    @reset_time,
    @reset_time,
    0,
    @topic_author,
    1,
    1,
    0,
    0,
    NULL,
    2
FROM DUAL
WHERE @classic_topic_count = 0;

SET @classic_topic_id := LAST_INSERT_ID();

INSERT INTO `f_posts` (
    `poster`,
    `poster_id`,
    `poster_ip`,
    `poster_character_id`,
    `message`,
    `posted`,
    `edited`,
    `edited_by`,
    `topic_id`
)
SELECT
    @topic_author,
    @topic_author_id,
    @topic_author_ip,
    0,
    @classic_message,
    @reset_time,
    NULL,
    NULL,
    @classic_topic_id
FROM DUAL
WHERE @classic_topic_id > 0;

SET @classic_post_id := LAST_INSERT_ID();

UPDATE `f_topics`
SET `last_post` = @reset_time,
    `last_post_id` = @classic_post_id,
    `last_poster` = @topic_author
WHERE `topic_id` = @classic_topic_id
  AND @classic_topic_id > 0;

UPDATE `f_forums`
SET `num_topics` = (SELECT COUNT(*) FROM `f_topics` WHERE `forum_id` = 2),
    `num_posts` = (SELECT COUNT(*) FROM `f_posts` p INNER JOIN `f_topics` t ON p.`topic_id` = t.`topic_id` WHERE t.`forum_id` = 2),
    `last_topic_id` = COALESCE((SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = 2 ORDER BY `last_post` DESC, `topic_id` DESC LIMIT 1), 0)
WHERE `forum_id` = 2;

-- Seed TBC welcome topic only if the forum has no topics yet
SET @tbc_topic_count := (SELECT COUNT(*) FROM `f_topics` WHERE `forum_id` = 3);
INSERT INTO `f_topics` (
    `topic_poster`,
    `topic_poster_id`,
    `topic_name`,
    `topic_posted`,
    `last_post`,
    `last_post_id`,
    `last_poster`,
    `num_views`,
    `num_replies`,
    `closed`,
    `sticky`,
    `redirect_url`,
    `forum_id`
)
SELECT
    @topic_author,
    @topic_author_id,
    @tbc_subject,
    @reset_time,
    @reset_time,
    0,
    @topic_author,
    1,
    1,
    0,
    0,
    NULL,
    3
FROM DUAL
WHERE @tbc_topic_count = 0;

SET @tbc_topic_id := LAST_INSERT_ID();

INSERT INTO `f_posts` (
    `poster`,
    `poster_id`,
    `poster_ip`,
    `poster_character_id`,
    `message`,
    `posted`,
    `edited`,
    `edited_by`,
    `topic_id`
)
SELECT
    @topic_author,
    @topic_author_id,
    @topic_author_ip,
    0,
    @tbc_message,
    @reset_time,
    NULL,
    NULL,
    @tbc_topic_id
FROM DUAL
WHERE @tbc_topic_id > 0;

SET @tbc_post_id := LAST_INSERT_ID();

UPDATE `f_topics`
SET `last_post` = @reset_time,
    `last_post_id` = @tbc_post_id,
    `last_poster` = @topic_author
WHERE `topic_id` = @tbc_topic_id
  AND @tbc_topic_id > 0;

UPDATE `f_forums`
SET `num_topics` = (SELECT COUNT(*) FROM `f_topics` WHERE `forum_id` = 3),
    `num_posts` = (SELECT COUNT(*) FROM `f_posts` p INNER JOIN `f_topics` t ON p.`topic_id` = t.`topic_id` WHERE t.`forum_id` = 3),
    `last_topic_id` = COALESCE((SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = 3 ORDER BY `last_post` DESC, `topic_id` DESC LIMIT 1), 0)
WHERE `forum_id` = 3;

-- Seed Wrath welcome topic only if the forum has no topics yet
SET @wotlk_topic_count := (SELECT COUNT(*) FROM `f_topics` WHERE `forum_id` = 4);
INSERT INTO `f_topics` (
    `topic_poster`,
    `topic_poster_id`,
    `topic_name`,
    `topic_posted`,
    `last_post`,
    `last_post_id`,
    `last_poster`,
    `num_views`,
    `num_replies`,
    `closed`,
    `sticky`,
    `redirect_url`,
    `forum_id`
)
SELECT
    @topic_author,
    @topic_author_id,
    @wotlk_subject,
    @reset_time,
    @reset_time,
    0,
    @topic_author,
    1,
    1,
    0,
    0,
    NULL,
    4
FROM DUAL
WHERE @wotlk_topic_count = 0;

SET @wotlk_topic_id := LAST_INSERT_ID();

INSERT INTO `f_posts` (
    `poster`,
    `poster_id`,
    `poster_ip`,
    `poster_character_id`,
    `message`,
    `posted`,
    `edited`,
    `edited_by`,
    `topic_id`
)
SELECT
    @topic_author,
    @topic_author_id,
    @topic_author_ip,
    0,
    @wotlk_message,
    @reset_time,
    NULL,
    NULL,
    @wotlk_topic_id
FROM DUAL
WHERE @wotlk_topic_id > 0;

SET @wotlk_post_id := LAST_INSERT_ID();

UPDATE `f_topics`
SET `last_post` = @reset_time,
    `last_post_id` = @wotlk_post_id,
    `last_poster` = @topic_author
WHERE `topic_id` = @wotlk_topic_id
  AND @wotlk_topic_id > 0;

UPDATE `f_forums`
SET `num_topics` = (SELECT COUNT(*) FROM `f_topics` WHERE `forum_id` = 4),
    `num_posts` = (SELECT COUNT(*) FROM `f_posts` p INNER JOIN `f_topics` t ON p.`topic_id` = t.`topic_id` WHERE t.`forum_id` = 4),
    `last_topic_id` = COALESCE((SELECT `topic_id` FROM `f_topics` WHERE `forum_id` = 4 ORDER BY `last_post` DESC, `topic_id` DESC LIMIT 1), 0)
WHERE `forum_id` = 4;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
