-- 59_website_identities.sql
-- Run against: master realmd (classicrealmd, or whichever is realm 1).
--   USE classicrealmd;
--
-- This file only creates the table.
-- Run tools/backfill_identities.php to populate it across all realms.
--
-- IDEMPOTENT: safe to re-run (CREATE IF NOT EXISTS).
--
-- DROP:
--   DROP TABLE IF EXISTS website_identities;

CREATE TABLE IF NOT EXISTS `website_identities` (
  `identity_id`        INT UNSIGNED     NOT NULL AUTO_INCREMENT,

  -- 'account' | 'character' | 'bot_character'
  `identity_type`      ENUM('account','character','bot_character')
                                         NOT NULL DEFAULT 'account',

  -- FK to account.id in the relevant realm's realmd.
  `owner_account_id`   INT UNSIGNED     NULL DEFAULT NULL,

  -- 1 = Classic, 2 = TBC, 3 = WotLK
  `realm_id`           TINYINT UNSIGNED NOT NULL,

  -- characters.guid — NULL for account-type rows.
  `character_guid`     INT UNSIGNED     NULL DEFAULT NULL,

  -- Visible name on forums and PMs.
  `display_name`       VARCHAR(64)      NOT NULL DEFAULT '',

  -- Stable dedup key used as unique constraint.
  --   'account:{realm_id}:{account_id}'
  --   'char:{realm_id}:{char_guid}'
  `identity_key`       VARCHAR(80)      NOT NULL DEFAULT '',

  -- Forum scope (Phase 2). NULL = unrestricted.
  `forum_scope_type`   ENUM('all','realm','expansion','guild_recruitment','event_feed')
                                         NULL DEFAULT NULL,
  `forum_scope_value`  VARCHAR(32)      NULL DEFAULT NULL,

  -- Guild at backfill time.
  `guild_id`           INT UNSIGNED     NULL DEFAULT NULL,

  `is_bot`             TINYINT(1)       NOT NULL DEFAULT 0,
  `is_active`          TINYINT(1)       NOT NULL DEFAULT 1,

  `created_at`         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
                                         ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`identity_id`),
  UNIQUE KEY  `uq_identity_key`   (`identity_key`),
  KEY         `idx_account`       (`realm_id`, `owner_account_id`),
  KEY         `idx_char`          (`realm_id`, `character_guid`),
  KEY         `idx_type_bot`      (`identity_type`, `is_bot`, `is_active`),
  KEY         `idx_display`       (`display_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
