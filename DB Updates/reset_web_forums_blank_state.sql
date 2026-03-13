-- Reset forum content to a clean state, keep forum_id = 1 for news,
-- rebuild one forum for each expansion, and seed one launch announcement.
-- Run this against the website/forum database (the same DB that contains f_forums/f_topics/f_posts).
-- Assumption: config/config.xml still points news_forum_id to forum_id = 1.

SET @reset_time := UNIX_TIMESTAMP();
SET @news_forum_id := 1;
SET @topic_subject := 'New site has been dropped';
SET @topic_author := 'web dev';
SET @topic_message := 'The new site has officially dropped.

Here are all of the major improvements included in this build:

- Multi-realm armory PDO connections are now cached per realm/database and can fall back to another armory database if the preferred one is unavailable.
- The talent calculator now resolves database targets from the shared realm map instead of the older realm-name lookup flow.
- Talent pages now wire in the playerbots database when that realm has one configured.
- Character profiles can read talent metadata from either the world or armory DBC tables, which makes the page work across more database layouts.
- Character profile icon loading now checks jpg, jpeg, and png assets in /xfer/assets/images before falling back to the armory icon set.
- Skill and profession icon lookups now handle alternate dbc_spellicon column layouts more safely.
- Achievements now support both world achievement DBC tables and armory achievement DBC tables.
- Achievement pages now show icons, recent-earned highlights, grouped categories, dates, and collapsible sections for easier browsing.
- Character profile presentation was refreshed for a cleaner hero view and better achievement UX.
- The armory tooltip SQL updates are now idempotent by using ADD COLUMN IF NOT EXISTS so reruns are safer.
- German and Korean language packs now include the new SPP Proxmox, SPP Mangos, Website Issues, and Armory/Talents labels needed by the updated navigation.

Please report any website issues in the Website Issues section.';

DELETE FROM `f_markread`;
DELETE FROM `f_posts`;
DELETE FROM `f_topics`;

SET @news_cat_id := COALESCE((SELECT `cat_id` FROM `f_forums` WHERE `forum_id` = @news_forum_id LIMIT 1), 1);

DELETE FROM `f_forums`
WHERE `forum_id` <> @news_forum_id;

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
    `num_topics` = 0,
    `num_posts` = 0,
    `last_topic_id` = 0,
    `disp_position` = VALUES(`disp_position`),
    `cat_id` = VALUES(`cat_id`),
    `quick_reply` = VALUES(`quick_reply`),
    `hidden` = VALUES(`hidden`),
    `closed` = VALUES(`closed`);

UPDATE `f_forums`
SET `num_topics` = 0,
    `num_posts` = 0,
    `last_topic_id` = 0;

ALTER TABLE `f_topics` AUTO_INCREMENT = 1;
ALTER TABLE `f_posts` AUTO_INCREMENT = 1;
ALTER TABLE `f_forums` AUTO_INCREMENT = 5;

SET @db_name := DATABASE();

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = @db_name
              AND table_name = 'website_accounts'
              AND column_name = 'forum_posts'
        ),
        'UPDATE `website_accounts` SET `forum_posts` = 0',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = @db_name
              AND table_name = 'account_extend'
              AND column_name = 'forum_posts'
        ),
        'UPDATE `account_extend` SET `forum_posts` = 0',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `f_topics` (
    `topic_poster`,
    `topic_poster_id`,
    `topic_name`,
    `topic_posted`,
    `forum_id`,
    `last_post`,
    `last_post_id`,
    `last_poster`,
    `sticky`,
    `closed`,
    `num_replies`,
    `num_views`
) VALUES (
    @topic_author,
    0,
    @topic_subject,
    @reset_time,
    @news_forum_id,
    @reset_time,
    0,
    @topic_author,
    1,
    0,
    0,
    0
);

SET @topic_id := LAST_INSERT_ID();

INSERT INTO `f_posts` (
    `poster`,
    `poster_id`,
    `poster_character_id`,
    `poster_ip`,
    `message`,
    `posted`,
    `topic_id`
) VALUES (
    @topic_author,
    0,
    0,
    '127.0.0.1',
    @topic_message,
    @reset_time,
    @topic_id
);

SET @post_id := LAST_INSERT_ID();

UPDATE `f_topics`
SET `last_post` = @reset_time,
    `last_post_id` = @post_id,
    `last_poster` = @topic_author
WHERE `topic_id` = @topic_id;

UPDATE `f_forums`
SET `num_topics` = CASE WHEN `forum_id` = @news_forum_id THEN 1 ELSE 0 END,
    `num_posts` = CASE WHEN `forum_id` = @news_forum_id THEN 1 ELSE 0 END,
    `last_topic_id` = CASE WHEN `forum_id` = @news_forum_id THEN @topic_id ELSE 0 END;

INSERT INTO `f_topics` (
    `topic_poster`,
    `topic_poster_id`,
    `topic_name`,
    `topic_posted`,
    `forum_id`,
    `last_post`,
    `last_post_id`,
    `last_poster`,
    `sticky`,
    `closed`,
    `num_replies`,
    `num_views`
) VALUES (
    @topic_author,
    0,
    'Welcome to Classic discussion',
    @reset_time,
    2,
    @reset_time,
    0,
    @topic_author,
    0,
    0,
    0,
    0
);

SET @classic_topic_id := LAST_INSERT_ID();

INSERT INTO `f_posts` (
    `poster`,
    `poster_id`,
    `poster_character_id`,
    `poster_ip`,
    `message`,
    `posted`,
    `topic_id`
) VALUES (
    @topic_author,
    0,
    0,
    '127.0.0.1',
    'Use this forum for Classic questions, feedback, and discussion.',
    @reset_time,
    @classic_topic_id
);

SET @classic_post_id := LAST_INSERT_ID();

UPDATE `f_topics`
SET `last_post` = @reset_time,
    `last_post_id` = @classic_post_id,
    `last_poster` = @topic_author
WHERE `topic_id` = @classic_topic_id;

UPDATE `f_forums`
SET `num_topics` = 1,
    `num_posts` = 1,
    `last_topic_id` = @classic_topic_id
WHERE `forum_id` = 2;

INSERT INTO `f_topics` (
    `topic_poster`,
    `topic_poster_id`,
    `topic_name`,
    `topic_posted`,
    `forum_id`,
    `last_post`,
    `last_post_id`,
    `last_poster`,
    `sticky`,
    `closed`,
    `num_replies`,
    `num_views`
) VALUES (
    @topic_author,
    0,
    'Welcome to Burning Crusade discussion',
    @reset_time,
    3,
    @reset_time,
    0,
    @topic_author,
    0,
    0,
    0,
    0
);

SET @tbc_topic_id := LAST_INSERT_ID();

INSERT INTO `f_posts` (
    `poster`,
    `poster_id`,
    `poster_character_id`,
    `poster_ip`,
    `message`,
    `posted`,
    `topic_id`
) VALUES (
    @topic_author,
    0,
    0,
    '127.0.0.1',
    'Use this forum for Burning Crusade questions, feedback, and discussion.',
    @reset_time,
    @tbc_topic_id
);

SET @tbc_post_id := LAST_INSERT_ID();

UPDATE `f_topics`
SET `last_post` = @reset_time,
    `last_post_id` = @tbc_post_id,
    `last_poster` = @topic_author
WHERE `topic_id` = @tbc_topic_id;

UPDATE `f_forums`
SET `num_topics` = 1,
    `num_posts` = 1,
    `last_topic_id` = @tbc_topic_id
WHERE `forum_id` = 3;

INSERT INTO `f_topics` (
    `topic_poster`,
    `topic_poster_id`,
    `topic_name`,
    `topic_posted`,
    `forum_id`,
    `last_post`,
    `last_post_id`,
    `last_poster`,
    `sticky`,
    `closed`,
    `num_replies`,
    `num_views`
) VALUES (
    @topic_author,
    0,
    'Welcome to Wrath discussion',
    @reset_time,
    4,
    @reset_time,
    0,
    @topic_author,
    0,
    0,
    0,
    0
);

SET @wotlk_topic_id := LAST_INSERT_ID();

INSERT INTO `f_posts` (
    `poster`,
    `poster_id`,
    `poster_character_id`,
    `poster_ip`,
    `message`,
    `posted`,
    `topic_id`
) VALUES (
    @topic_author,
    0,
    0,
    '127.0.0.1',
    'Use this forum for Wrath of the Lich King questions, feedback, and discussion.',
    @reset_time,
    @wotlk_topic_id
);

SET @wotlk_post_id := LAST_INSERT_ID();

UPDATE `f_topics`
SET `last_post` = @reset_time,
    `last_post_id` = @wotlk_post_id,
    `last_poster` = @topic_author
WHERE `topic_id` = @wotlk_topic_id;

UPDATE `f_forums`
SET `num_topics` = 1,
    `num_posts` = 1,
    `last_topic_id` = @wotlk_topic_id
WHERE `forum_id` = 4;
