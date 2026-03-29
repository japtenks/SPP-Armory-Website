-- 60_forum_identity_columns.sql
-- Run against: every realm's realmd DB.
--   Classic:  USE classicrealmd;
--   TBC:      USE tbcrealmd;
--   WotLK:    USE wotlkrealmd;
--
-- Adds identity-aware columns to f_posts, f_topics, and f_forums.
-- Run-once migration — columns do NOT use IF NOT EXISTS (MySQL 5.7).
-- Safe to check manually: SHOW COLUMNS FROM f_posts LIKE 'poster_identity_id';
--
-- After running this SQL, execute:
--   php tools/backfill_post_identities.php
-- to populate identity IDs on existing posts and topics.

-- ---------------------------------------------------------------
-- f_posts: link each post to a website_identities row
-- ---------------------------------------------------------------
ALTER TABLE `f_posts`
  ADD COLUMN `poster_identity_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'FK → classicrealmd.website_identities.identity_id'
    AFTER `poster_character_id`,
  ADD KEY `idx_poster_identity` (`poster_identity_id`);

-- ---------------------------------------------------------------
-- f_topics: link the opening post author to an identity row
-- ---------------------------------------------------------------
ALTER TABLE `f_topics`
  ADD COLUMN `topic_poster_identity_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'FK → classicrealmd.website_identities.identity_id'
    AFTER `topic_poster_id`,
  ADD KEY `idx_topic_poster_identity` (`topic_poster_identity_id`);

-- ---------------------------------------------------------------
-- f_forums: optional scope restriction per forum
-- ---------------------------------------------------------------
--   scope_type = 'all'         → anyone can post (default)
--   scope_type = 'realm'       → scope_value = realm ID  (e.g. '1')
--   scope_type = 'expansion'   → scope_value = 'classic' | 'tbc' | 'wotlk'
--   scope_type = 'guild_recruitment' → reserved for Phase 4
--   scope_type = 'event_feed'  → reserved for Phase 5 (bot-only writes)
-- ---------------------------------------------------------------
ALTER TABLE `f_forums`
  ADD COLUMN `scope_type`  ENUM('all','realm','expansion','guild_recruitment','event_feed')
                           NOT NULL DEFAULT 'all'
                           COMMENT 'Who may post: all / realm / expansion / guild_recruitment / event_feed'
                           AFTER `forum_id`,
  ADD COLUMN `scope_value` VARCHAR(32) NULL DEFAULT NULL
                           COMMENT 'Qualifier for scope_type (realm ID or expansion slug)'
                           AFTER `scope_type`;
