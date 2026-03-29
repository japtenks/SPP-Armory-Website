-- 64_guild_roster_update.sql
-- ---------------------------------------------------------------
-- Add guild_roster_update to the website_bot_events event_type enum.
-- Run against: classicrealmd ONLY (master DB).
--   USE classicrealmd;
-- ---------------------------------------------------------------
ALTER TABLE `website_bot_events`
  MODIFY `event_type`
    ENUM('level_up','guild_created','profession_milestone','raid_clear','quest_complete','guild_roster_update')
    NOT NULL;
