-- 58_sync_gm_website_admin.sql
-- Run against: realmd database (e.g. classicrealmd / tbcrealmd / wotlkrealmd)
--
-- Ensures every account row has a website_accounts record, then promotes
-- any in-game GM (gmlevel >= 3) to the matching website admin group.
--
-- g_id mapping:
--   1 = Guest / unregistered
--   2 = Registered user
--   3 = Admin     (gmlevel 3)
--   4 = Superadmin (gmlevel >= 4)
--
-- Safe to run multiple times (idempotent).

-- 1. Create website_accounts rows for any account that doesn't have one yet.
INSERT IGNORE INTO `website_accounts` (`account_id`, `display_name`, `g_id`)
SELECT `id`, `username`, 2
FROM `account`
WHERE `id` NOT IN (SELECT `account_id` FROM `website_accounts`);

-- 2. Promote gmlevel 3 accounts to website admin (g_id = 3).
UPDATE `website_accounts`
SET `g_id` = 3
WHERE `account_id` IN (
    SELECT `id` FROM `account` WHERE `gmlevel` = 3
)
AND `g_id` < 3;

-- 3. Promote gmlevel >= 4 accounts to website superadmin (g_id = 4).
UPDATE `website_accounts`
SET `g_id` = 4
WHERE `account_id` IN (
    SELECT `id` FROM `account` WHERE `gmlevel` >= 4
)
AND `g_id` < 4;
