-- 61_pm_identity_columns.sql
-- Run against: every realm's realmd DB.
--   Classic:  USE classicrealmd;
--   TBC:      USE tbcrealmd;
--   WotLK:    USE wotlkrealmd;
--
-- Adds sender_identity_id and recipient_identity_id to website_pms.
-- Both columns reference classicrealmd.website_identities.identity_id.
-- Display-name resolution is done in PHP (no cross-DB FK enforced).
--
-- Run-once migration.
-- After this SQL, run: php tools/backfill_pm_identities.php

ALTER TABLE `website_pms`
  ADD COLUMN `sender_identity_id`    INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'FK → classicrealmd.website_identities.identity_id'
    AFTER `sender_id`,
  ADD COLUMN `recipient_identity_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'FK → classicrealmd.website_identities.identity_id'
    AFTER `sender_identity_id`,
  ADD KEY `idx_pm_sender_identity`    (`sender_identity_id`),
  ADD KEY `idx_pm_recipient_identity` (`recipient_identity_id`);
