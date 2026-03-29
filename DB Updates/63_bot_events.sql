-- 63_bot_events.sql
-- ---------------------------------------------------------------
-- PART 1: website_bot_events table
-- Run against: classicrealmd ONLY (master DB).
--   USE classicrealmd;
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `website_bot_events` (
  `event_id`        INT UNSIGNED    NOT NULL AUTO_INCREMENT,

  -- What kind of event this is.
  `event_type`      ENUM('level_up','guild_created','profession_milestone','raid_clear','quest_complete')
                                    NOT NULL,

  `realm_id`        TINYINT UNSIGNED NOT NULL,
  `account_id`      INT UNSIGNED    NULL DEFAULT NULL,
  `character_guid`  INT UNSIGNED    NULL DEFAULT NULL,
  `guild_id`        INT UNSIGNED    NULL DEFAULT NULL,

  -- Arbitrary JSON payload (char name, level, profession name, etc.)
  `payload_json`    TEXT            NOT NULL DEFAULT '{}',

  -- Unique key prevents the same event being inserted twice.
  -- Format examples:
  --   level_up:realm1:char123:level60
  --   guild_created:realm1:guild55
  --   profession_milestone:realm1:char123:skill186:value300
  `dedupe_key`      VARCHAR(120)    NOT NULL DEFAULT '',

  -- Which forum to post to (resolved at scan time from config).
  `target_forum_id` INT UNSIGNED    NULL DEFAULT NULL,

  `occurred_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at`    DATETIME        NULL DEFAULT NULL,

  `status`          ENUM('pending','processing','posted','skipped','failed')
                                    NOT NULL DEFAULT 'pending',
  `error_message`   VARCHAR(255)    NULL DEFAULT NULL,

  PRIMARY KEY (`event_id`),
  UNIQUE KEY `uq_dedupe`       (`dedupe_key`),
  KEY        `idx_status`      (`status`, `occurred_at`),
  KEY        `idx_realm_type`  (`realm_id`, `event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- PART 2: content_source column on f_posts and f_topics
-- Run against: every realm's realmd DB.
--   USE classicrealmd; (then tbcrealmd, wotlkrealmd)
-- ---------------------------------------------------------------
ALTER TABLE `f_posts`
  ADD COLUMN `content_source` ENUM('player','player_assisted','system_event','bot_generated')
                              NOT NULL DEFAULT 'player'
                              AFTER `poster_identity_id`;

ALTER TABLE `f_topics`
  ADD COLUMN `content_source` ENUM('player','player_assisted','system_event','bot_generated')
                              NOT NULL DEFAULT 'player'
                              AFTER `topic_poster_identity_id`;
