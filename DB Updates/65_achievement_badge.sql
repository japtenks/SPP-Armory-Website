-- 65_achievement_badge.sql
-- ---------------------------------------------------------------
-- Add achievement_badge to the website_bot_events event_type enum.
-- Run against: classicrealmd ONLY (master DB).
--   USE classicrealmd;
-- ---------------------------------------------------------------
ALTER TABLE `website_bot_events`
  MODIFY `event_type`
    ENUM('level_up','guild_created','profession_milestone','raid_clear','quest_complete',
         'guild_roster_update','achievement_badge')
    NOT NULL;
