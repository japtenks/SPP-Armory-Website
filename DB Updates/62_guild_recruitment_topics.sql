-- 62_guild_recruitment_topics.sql
-- Run against: every realm's realmd DB.
--   Classic:  USE classicrealmd;
--   TBC:      USE tbcrealmd;
--   WotLK:    USE wotlkrealmd;
--
-- Adds guild recruitment columns to f_topics.
-- Run-once migration.

ALTER TABLE `f_topics`
  ADD COLUMN `guild_id`              INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Guild this recruitment thread belongs to (NULL for non-recruitment topics)'
    AFTER `topic_poster_identity_id`,
  ADD COLUMN `managed_by_account_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Account ID of the guild leader who owns this thread'
    AFTER `guild_id`,
  ADD COLUMN `recruitment_status`    ENUM('active','closed') NULL DEFAULT NULL
    COMMENT 'NULL = not a recruitment thread; active/closed for guild recruitment topics'
    AFTER `managed_by_account_id`,
  ADD COLUMN `last_bumped_at`        INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Unix timestamp of last reply / bump in this recruitment thread'
    AFTER `recruitment_status`,
  ADD KEY `idx_guild_recruitment` (`guild_id`, `recruitment_status`);
